# Placing a loan — the backend path

What the API actually does between the officer pressing **Finish** in the loan wizard and a
loan that can take money. Companion to `ARCHITECTURE.md`, which covers the system as a
whole; this one follows a single loan from creation to its first installment.

The frontend side of the same journey is `ARCHITECTURE.md` in the `cdp-crediX-fe` repo.

---

## 1. The wizard is not one call

`Finish` is **four to eight HTTP calls in sequence, with no transaction spanning them.**
There is no `POST /loans` that takes the whole wizard.

**Individual / joint loan:**

```
POST /api/v1/loan-applications            → loan_applications row (+ applications row)
  for each guarantor:
    POST /api/v1/guarantors               → guarantors row
    POST /api/v1/loan-application-guarantors   → the pivot link
    POST /api/v1/documents  (multipart)   → one per staged file
```

**Group loan (development fund):**

```
POST /api/v1/group-loans                  → group_loans + applications + ONE
                                            loan_applications row + N pivot members
```

> **Consequence worth knowing.** The loan application is written first. If a guarantor call
> fails afterwards, the application exists with no guarantor — the client does not roll it
> back. Nothing is lost, because the workflow refuses to move an individual loan to
> `Verified` with zero guarantors (`LoanApplicationWorkflowService::transition()`), so the
> gap surfaces at the next gate rather than silently.

---

## 2. `POST /loan-applications`

`LoanApplicationController::store()`, permission `Loan Application Create`,
validated by `CreateLoanApplicationRequest`.

### 2.1 Two rows, not one

`applications` is a thin generic envelope shared with a future lease module; the loan
itself is `loan_applications`. Step one of `store()` creates the envelope when the caller
did not supply `application_id`:

```php
Application::create([
    'application_type' => 'loan',
    'branch'           => Branch::find($branch_id)?->name,   // the NAME, not the id
    'requested_amount' => …,
    'repayment_period_months' => $term_months,
]);
```

`applications.application_no` is **never passed in**. `Application::boot()::creating` mints
it through `ReferenceNumberService::forApplication($branch)`:

```
APP-{BRANCHCODE}-{yyyymmdd}{00000001}
```

`{BRANCHCODE}` is `branches.code` with non-alphanumerics stripped (`BR-COL` → `BRCOL`),
falling back to `COL`. The counter is taken under `lockForUpdate()` and ordered by
`LENGTH(column) DESC` so 10 sorts after 9, not after 1.

> Numbers issued before this format (`APP-{BRANCH}-{yymm}{0001}`) are deliberately left
> alone — they are printed on paper and quoted in SMS already sent. No prefix this
> generator builds can collide with them.

### 2.2 What the controller fills in

| Field | Source |
|---|---|
| `status` | forced to `LoanApplicationStatus::Submitted` — a client cannot start a loan anywhere else |
| `applied_by` | `Auth::id()` if not supplied |
| `applied_at` | `now()` if not supplied |
| `monthly_installment` | **deliberately left null** |

> **Why no installment is calculated here.** `requested_amount` is not what gets disbursed.
> Every financial figure is computed at `approve()` from `approved_amount`, once that number
> is actually known. Working out a schedule from the requested amount would produce a figure
> the borrower might be shown and that then changes.

### 2.3 Joint borrowers

`joint_customer_ids[]` are validated as `distinct`, existing, and **not equal to
`customer_id`**. The controller then writes `loan_application_customers` rows for the
primary **and** each joint customer — so the pivot always carries the complete set, never
just the co-borrowers.

### 2.4 The recommending employee

Five fields ride along: `recommended_by_employee_id` plus a snapshot of
`recommender_name` / `_employee_code` / `_nic` / `_phone`. All nullable — the link because
a recommender may not be on the employee register yet, the four details only because no
form sends them yet. The intent is that no loan goes out unattributed; tighten the four to
`required` the moment a UI captures them.

### 2.5 Notification

Every user holding `config('notifications.staff_role')` with a phone on their employee
record gets an SMS: *"New loan application pending for review."* Sent outside any
transaction and failures do not fail the request.

---

## 3. Guarantors

### 3.1 `POST /guarantors`

`CreateGuarantorRequest` requires `customer_id`, `full_name`, `type`
(`guarantor_1|guarantor_2`), `id_type`, `id_number`, and **`employment_status`**, which is
a fork:

| `employment_status` | Also required |
|---|---|
| `Employed` | `occupation`, `employer_name`, `salary` |
| `Self-Employed` | `business_name`, `business_registration_number`, `business_phone` |

Enforced with `required_if`, so a half-filled block is a 422. A guarantor belongs to a
**customer**, not to a loan — the same person can back a later loan for the same borrower.

### 3.2 `POST /loan-application-guarantors`

The pivot, and the only place three real rules live:

1. **Unique per application** — the same guarantor cannot be pledged twice to one loan.
2. **Must belong to the borrower** — `guarantors.customer_id` has to equal the
   application's `customer_id`, else *"This guarantor does not belong to the customer on
   this loan application."*
3. **Not already pledged** — `Guarantor::used_for_loan` blocks a guarantor who is standing
   behind another live application.

### 3.3 The guarantor rule at the gate

`LoanApplicationWorkflowService::transition()` refuses `Verified` when
`group_loan_id === null` and the application has no guarantor links:

> *At least one guarantor is required before this loan application can be verified.*

Group loans are exempt — their guarantors are optional.

---

## 4. Documents

`POST /documents`, multipart. `CreateDocumentRequest` requires `document_name` and either a
`file` or a `file_path`; `document_type` must be a key of `Document::TYPES`.

Three optional foreign keys, and they are **independent**:

| Column | Means |
|---|---|
| `customer_id` | whose paper this is |
| `loan_application_id` | which application it was collected for |
| `guarantor_id` | set when the paper belongs to a guarantor rather than the borrower |

A guarantor's pay slip carries **all three** — the guarantor owns it, the borrower's file
holds it, and the application it was collected for is recorded. That is what lets the
document screens group by person and filter by application at the same time.

`uploaded_by` / `uploaded_at` are stamped server-side; the client cannot claim them.

> **A customer edit must not touch these.** `CustomerController::update()` replaces the
> customer's child collections, and an unscoped `$customer->documents()->delete()` used to
> destroy loan and guarantor documents that the customer form never sends. It is now scoped
> to `whereNull('loan_application_id')->whereNull('guarantor_id')`, and the guarantor
> replacement only runs when the payload actually contains a `guarantors` key. Before that
> fix, renaming a customer deleted every guarantor they had — cascading to the loan links
> and the guarantors' documents.

---

## 5. `POST /group-loans` — one call, everything

`GroupLoanController::store()`, wrapped in `DB::transaction()` (the only creation path here
that is atomic).

```
group_loans                    header: group_name, competency, term, status=available
  └─ group_loan_items          item_name, quantity, unit_price, line_total
  └─ applications              application_type = 'group_loan'
  └─ loan_applications         ONE row, group_loan_id set
       └─ loan_application_customers   one per member (primary included)
```

**One loan application, not one per member.** The members live in the pivot. Their separate
liabilities appear later as separate *installment* rows, not separate applications.

Two figures are **never taken from the client**:

- `requested_amount` = `sum(quantity × unit_price)` over the submitted items.
- `service_charge_percentage` = `Setting::get('group_loan_service_charge_percentage')`.
  A group loan has **no interest rate at all** — `interest_rate` and `interest_type` are
  written `NULL` on purpose.

Members must be existing customers; nothing creates a customer inline on this path.

> **Individual-loan endpoints refuse a group loan.** All ten mutation actions on
> `LoanApplicationController` return 422 for a row with `group_loan_id`, pointing at
> `/group-loans`. Without that guard, `approve()` would run the interest formula against a
> `NULL` rate and quietly produce a zero charge.

---

## 6. After creation: the gates

Creation lands the loan at `submitted`. Everything after it goes through
`LoanApplicationWorkflowService::transition()` — the **only** way a status changes.

```
submitted → reviewed → verified → approved ─┬─ on_hold ─┬─ accepted → disbursed → active ⇄ overdue
                                            ├─ accepted ─┘                              │
                                            └─ declined → cancelled                   closed
```

| Endpoint | Permission | Records |
|---|---|---|
| `PATCH {id}/review` | `Loan Application Review` | `reviewed_by/at`, `reviewed_remarks`, **ticked document ids** |
| `PATCH {id}/verify` | `Loan Application Verify` | `verified_by/at`, `verified_remarks`, **ticked document ids** |
| `PATCH {id}/approve` | `Loan Application Approve` | `approved_by/at`, `approved_amount`, all the money |
| `PATCH {id}/hold-offer` | `Loan Application Offer Response` | → `on_hold` |
| `PATCH {id}/accept-offer` | `Loan Application Offer Response` | → `accepted` |
| `PATCH {id}/decline-offer` | `Loan Application Offer Response` | → `declined`, then `cancelled` |
| `PATCH {id}/disburse` | `Loan Application Disburse` | → `disbursed`, **generates the schedule** |

### 6.1 Three separate hands

Review, verify and approve each stamp their own actor, and **no one person may cover two
of them** — `assertSegregationOfDuties()`. Super Admin is exempt; turn the rule off entirely
with `loan_approval_segregation_enabled` where one officer legitimately handles the whole
file. **You need at least three users holding the three permissions, or approval is
impossible.**

### 6.2 The document checklist

`review` and `verify` accept `reviewed_document_ids[]` / `verified_document_ids[]` and
stamp `documents.reviewed_by/at` / `verified_by/at` on exactly those rows, clearing the
mark on the application's other documents. Sent as an **empty array**, it clears
everything — that is the officer saying they ticked nothing.

Marking happens **outside** the status transition and is wrapped in try/catch: a failure to
stamp a checklist must never roll back a workflow step that already happened.

`remarks` is `required|min:3` on both.

### 6.3 Approval is an offer

`approved_amount` is validated `min:0.01` and `max:requested_amount` — you may approve less
than was asked for, never more. Approving 300,000 against a request for 500,000 is an
**offer**, so the loan cannot go straight to the cash desk:

- `disburse` works **only** from `accepted`.
- `decline-offer` needs a reason from `LoanApplication::OFFER_DECLINE_REASONS`
  (`amount_too_low`, `interest_rate_too_high`, `term_not_suitable`, `no_longer_needed`,
  `borrowed_elsewhere`, `other`) — free text cannot be grouped by a report — plus remarks,
  and it performs **two** transitions in one action: `declined`, then `cancelled`. Both land
  in `loan_application_status_history`, so the reason survives the file being closed.
- The answer is stored in `offer_responded_by/at`, `offer_decline_reason`, `offer_remarks`.

> Group loans have **no offer endpoints yet**. `groupLoansApi` covers review / verify /
> approve / disburse only.

### 6.4 The approval reference

On the **first** transition to `approved`, inside the transaction:

```
CDP-{BRANCHCODE}-{000000001}
```

Guarded on `approval_reference_no` being null, not on the transition alone — a loan that is
reverted and approved again keeps the reference the customer was already given.

### 6.5 Disbursement generates the schedule

`transition()` calls `InstallmentScheduleService::generate()` on `Disbursed`.
`scheduleOwnersFor()` decides whose name goes on the rows:

- **Group loan** → one full run of installments **per member**, from the pivot.
- **Individual / joint** → one run against `loan_applications.customer_id`.

That per-party grain is what later lets recovery and credit scoring judge one member
without marking the other four.

---

## 7. What the loan touches once it is live

| | Where |
|---|---|
| Repayment | `payments` → `loan_installments`, each payment tagged with the paying member |
| Credit score | recomputed after every payment, reversal and installment edit — `ARCHITECTURE.md` §6b |
| Arrears | the nightly `MarkOverdueLoanApplications`, then recovery cases — §6 |
| Restructuring | `LoanRevisionService` stamps superseded rows `revised` |

---

## 8. Traps specific to this path

**Nothing spans the wizard's calls.** Only `POST /group-loans` is atomic. The individual
path can leave an application without guarantors or without documents; the workflow gate is
the safety net, not a transaction.

**`applications.branch` is a name, `loan_applications.branch_id` is an id.** They are
different columns on different tables and are both written from the same request.

**Status is set server-side.** `CreateLoanApplicationRequest` accepts a `status`, but
`store()` overwrites it with `Submitted`. Do not rely on passing one.

**The guarantor uniqueness rule is per application, but `used_for_loan` is global.** A
guarantor freed by a closed loan becomes available again; one standing behind a live
application cannot be pledged elsewhere, and the message says so.

**Migrations are merged into the original create migration**, with a throwaway patch
migration run and then deleted along with its `migrations` row. A migration file can look
correct while the live column is missing — check `Schema::hasColumn()` before assuming the
code is wrong.

**Date-only columns need `date:Y-m-d`, not `date`.** `APP_TIMEZONE` is `Asia/Colombo`, so a
plain `'date'` cast serialises `2026-07-21` as `"2026-07-20T18:30:00Z"`, and the frontend
renders dates by slicing the first 10 characters — showing **the day before**.
