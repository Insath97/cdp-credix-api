# CDP Credix API — Architecture

A microfinance loan-management API: customer onboarding, loan origination, disbursement,
repayment tracking, arrears and recovery. Laravel 12, MySQL, JSON API consumed by a
separate frontend (Vite/React) and a customer self-service portal.

```
Customer ──> Application ──> LoanApplication ──> Installments ──> Payments
                                   │
                                   ├──> overdue ──> RecoveryCase ──> Agent
                                   └──> LoanRevision (restructure)
```

---

## 1. Stack

| | |
|---|---|
| PHP | 8.2+ |
| Framework | Laravel 12 |
| Auth | `php-open-source-saver/jwt-auth` — the **`api` guard is JWT and is the default** |
| Permissions | `spatie/laravel-permission` — 189 permissions in 44 groups, 3 roles |
| Social login | `laravel/socialite` + Google provider |
| SMS | Dialog gateway via `SmsService` (HTTP) |
| DB | MySQL, 61 tables, 53 migrations |

`laravel/sanctum` is installed but the `api` guard is JWT. For local API testing, mint a
token with `auth('api')->login(User::first())` rather than logging in through the endpoint.

**Scale:** 46 models · 55 controllers · 76 form requests · 7 services · 4 enums ·
5 console commands · 298 `/api/v1` routes across 52 resource groups.

---

## 2. Conventions

Every module follows the same shape:

```
routes/v1.php  ──>  Controller  ──>  FormRequest (validation)
                        │
                        ├──> Service   (workflow / money / notifications)
                        └──> Model     (Eloquent, enum-cast status columns)
```

**Response envelope** — every endpoint returns the same shape:

```json
{ "status": "success|error", "message": "...", "data": { ... } }
```

**Controllers** implement `HasMiddleware` and declare per-action permissions:

```php
new Middleware('permission:Loan Application Approve', only: ['approve'])
```

**Validation** lives in `app/Http/Requests/*`. Cross-entity guards that depend on a
record's *state* live in the controller instead, because Laravel skips closure rules for
fields absent from the payload (`ClosureValidationRule` is not an `ImplicitRule`).

**Traits:** `ActivityLogTrait` (audit trail → `activity_logs`), `FileUploadTrait`,
`FriendlyValidationErrors`, `MasksSensitiveDataTrait`, `ResolvesAuthenticatedCustomerTrait`.

> ⚠️ Most request classes import `FriendlyValidationErrors` **and** declare their own
> `failedValidation()`, which shadows the trait. The top-level `message` is therefore
> generic; the real rule message is always in `errors[].messages[]`. Read from there.

---

## 3. The domain core: one `LoanApplication`, three shapes

`applications` is a thin generic envelope (shared with a future lease module) carrying
`application_no` (`APP-…`) and a coarse status. `loan_applications` is the real loan.

**Every loan is exactly one `loan_applications` row.** What differs is who is attached:

| | Individual | Joint | Group |
|---|---|---|---|
| `loan_applications` rows | 1 | 1 | **1** |
| `loan_application_customers` | none | N co-borrowers | N members |
| Header table | — | — | `group_loans` |
| Interest | `interest_rate` | `interest_rate` | **none — `NULL`** |
| Charge | interest | interest | **service charge from Settings** |
| Guarantor | required | required | optional |
| Installment rows | 1 per period | 1 per period | **1 per member per period** |
| Liability | single borrower | joint | per member |

`LoanApplication::isGroupLoan()` (`group_loan_id !== null`) is the discriminator.
`isJointLoan()` means "has pivot customers" — **true for Group Loans too**, and it gates
the email channel, which both are meant to receive.

### Group Loan specifics

```
group_loans (header: items total, service_charge_percentage, term, status)
   └─ 1 loan_applications row   (group_loan_id set, interest_rate NULL)
        ├─ N loan_application_customers      the members
        ├─ N×term loan_installments          one run per member
        └─ payments                          each tagged with the paying member
```

- `requested_amount` is **computed from `group_loan_items`**, never client-supplied.
- Members must be existing `customers`; nothing creates a customer inline.
- `GroupLoan::memberBreakdown()` reports each member's own due / paid / balance /
  penalty / status / overdue, read from their own installment rows.
- `GroupLoan::splitEvenly($amount, $parts)` is the shared rounding convention:
  even split, **last part absorbs the remainder**.

---

## 4. Lifecycle state machines

`LoanApplicationStatus` (`app/Enums/`) owns the only legal transitions:

```
submitted ──> reviewed ──> verified ──> approved ──> disbursed ──> active ⇄ overdue
     │            │            │                                      │       │
     └────────────┴──────┬─────┘                                      └───┬───┘
                  cancelled / rejected                                 closed
```

`isTerminal()` = `cancelled | rejected | closed` — no transitions out.

**Three separate hands before money moves.** Review, verify and approve are three
distinct stages, each stamping its own actor (`reviewed_by`/`reviewed_at`,
`verified_by`/`verified_at`, `approved_by`/`approved_at`) — and **no one person may cover
two of them**. The check lives in `LoanApplicationWorkflowService::assertSegregationOfDuties()`,
so Individual, Joint and Group loans inherit it from the same place (the group cascade
transitions through that method too).

| endpoint | permission | stamps |
|---|---|---|
| `PATCH /loan-applications/{id}/review` | `Loan Application Review` | `reviewed_by` |
| `PATCH /loan-applications/{id}/verify` | `Loan Application Verify` | `verified_by` |
| `PATCH /loan-applications/{id}/approve` | `Loan Application Approve` | `approved_by` |

Group loans have the same three endpoints under `/group-loans/{id}/…`. The header stays
`available` through review and verify — only the audit fields and the cascaded member
stage move.

Set `loan_approval_segregation_enabled` to false where one officer legitimately handles
the whole file (a very small branch), accepting that it removes the maker-checker control.
**You need at least three users holding the three permissions, or approval is impossible.**

`GroupLoanStatus` is deliberately **coarser** (it drives four frontend tabs):

```
available ──> locked ──> disbursed ──> closed      (+ rejected, cancelled)
```

Verification does **not** move the group header (submitted and verified are both
`available`). The granular per-member timeline lives on `loan_applications.status`.

### `status` vs `is_active`

Two separate columns. `status` is the workflow; `is_active` is a visibility flag used by
list filters and the frontend badge. **A terminal status forces `is_active = false`** —
enforced inside both `transition()` methods, so cancel/reject/close and the group cascade
all get it from one place. `activate()`/`toggleStatus()` refuse to reactivate a terminal
record, and `is_active` is not accepted by the generic update endpoints.

### Workflow services

| service | responsibility |
|---|---|
| `LoanApplicationWorkflowService::transition()` | the **only** way a loan's status changes — guards the enum map, enforces the guarantor rule (skipped when `group_loan_id` is set), writes `loan_application_status_history`, mirrors `applications.status`, and fires schedule generation on `Disbursed` |
| `GroupLoanWorkflowService` | header lifecycle + cascade to the group's single application; owns the service-charge math and `MIN_MEMBERS = 2` |
| `LoanRevisionService` | restructuring (reduce installment / extend term / principal only) |

---

## 5. Money

### Approval

```php
// Individual / Joint
interest  = approved × interest_rate / 100          // flat, whole term
total     = approved + interest
monthly   = total / term_months

// Group — no interest at any point
charge    = approved × group_loans.service_charge_percentage / 100
total     = approved + charge
monthly   = total / term_months
```

**Invariant: `approved_amount ≤ requested_amount`.** Enforced in both approve endpoints;
for a Group Loan the ceiling is the item total. `processing_fee` must be `≥ 0`.

`net_disbursement_amount = max(0, approved − processing_fee)` — the cash actually handed
over. It is a **record field only** (used in the disbursement SMS); the fee never reduces
what the borrower owes, since interest, installments and `outstanding_balance` all derive
from the full `approved_amount`.

### Installments

Generated **only** on the `Disbursed` transition, by `InstallmentScheduleService::generate()`.
It reads just `term_months`, `monthly_installment` and `disbursed_at`, is idempotent, and
is the sole initialiser of `outstanding_balance`.

`scheduleOwnersFor()` decides who owns a schedule — a Group Loan's members, or the single
customer otherwise. At one owner the loop collapses to exactly the original
single-schedule behaviour.

- Unique index: **`(loan_application_id, customer_id, installment_no)`**.
  Every row carries a `customer_id` (individual → its own, joint → primary, group → the
  member), which keeps that constraint as strong as it was — MySQL treats `NULL`s as distinct.
- `LoanInstallment::SETTLED_STATUSES = ['paid','waived','revised']` is the single source
  for "settled", used by `scopePayable()`, `isSettled()`, `isPastDue()` and payment carry-forward.
- Statuses: `upcoming · due · partially_paid · overdue · paid · waived · revised`.
- Manual creation (`POST /loan-installments`) is refused unless the loan is
  `disbursed|active|overdue`.

> Rounding note: `outstanding_balance` is set to `monthly × term`, which can sit a few
> cents under the header's `total_repayment_amount` (18,333.33 × 24 = 439,999.92 vs
> 440,000). Pre-existing and identical for all loan types.

### Payments

`PaymentController::store()`, in one transaction:

1. create the `payments` row (`RCPT-%06d`), inferring `customer_id` from the targeted installment
2. apply to that installment, capped at what it owed
3. **carry any excess forward** — `carryForwardExcess()`, scoped by `customer_id` so a
   Group Loan member's overpayment can never pay down another member's months
4. decrement `outstanding_balance`
5. close the loan when **no installment still owes anything** and status is `active` **or**
   `overdue`, then close the group header via `closeIfFullyRepaid()`

A settled installment can never take another payment (`CreatePaymentRequest`).
`GET /loan-installments/list?payable=true` narrows a picker to payable months; the
endpoint returns the full schedule by default.

Reversal (`DELETE /payments/{id}`) unwinds the installment, the recorded
`carry_forward_breakdown`, and the loan balance.

---

## 6. Arrears and recovery

Five nightly commands (`routes/console.php`), all driven by System Settings:

| time | command | effect |
|---|---|---|
| daily | `installments:send-due-reminders` | SMS 3 days before a due date |
| 00:30 | `loans:mark-overdue` | past grace → installment `overdue` + one flat penalty; syncs loan `Active ⇄ Overdue`; settles cases no longer in arrears |
| 01:00 | `installments:send-overdue-sms` | nudges on days 7/14/21 past due |
| 01:30 | `recovery:escalate-internal` | ≥ 30 days overdue → internal `RecoveryCase` |
| 02:00 | `recovery:escalate-external` | ≥ 45 days → external case, parent marked `escalated` |

**Everything here is per party, not per loan.** `LoanApplication::arrearsGroups($filter)`
returns one entry per party in arrears — **one per member** for a Group Loan, so if four of
five members pay on time only the fifth is marked overdue, penalised, and notified. The
four who paid receive nothing. Individual/Joint return a single entry covering the loan.

- Penalty: flat `loan_products.penalty_value`, charged **once per installment row**
  (guarded on `penalty_amount == 0`), added to both the row and `outstanding_balance`.
- Grace: `loan_products.grace_period_days`, falling back to `installment_due_period_days`.
  An installment goes overdue only when `due_date + grace < today`, so with the stock
  30-day grace **and** 30-day threshold you need **31+** days past due to open a case.
- `RecoveryCase.customer_id` names the member being pursued; `hasLiveCaseFor()` scopes the
  "already has a live case" guard per `(loan, customer)`, so one member's case never blocks
  another's.
- Case statuses: `open · in_progress · resolved · escalated · closed`.
  `LIVE_STATUSES = ['open','in_progress']`. **`escalated`** means superseded by the
  external-stage child (`parent_case_id`) — the debt is neither cured (`resolved`) nor
  abandoned (`closed`), and `closed_at` stays null.
- Agents are **assigned by an admin**, never auto-picked:
  `PATCH /recovery-cases/{id}/assign-agent` validates against the case's stage — internal
  needs an active `recovery_agents` user, external an active `external_recovery_agents` row.

The nudge reminders deliberately key off `isPastDue()` (unpaid and past due), **not** the
formal `overdue` status, which is only stamped after the grace period — later than the
whole day-7/14/21 window.

---

## 6b. Repayment credit score

A borrower earns points for every installment settled on time and loses points for every
one settled late or left unpaid past its grace period. The loan's score is the total
divided by the number of installments **judged**, so a 6-month loan and a 36-month loan are
directly comparable. Every number in that sentence is a System Setting (§9), not a constant.

**Everything is derived, and is recomputed rather than incremented.** `credit_score_events`,
`customer_credit_scores` and `customers.credit_score` are all rebuilt from
`loan_installments` on each run — `CreditScoreService::recomputePair()` deletes a
`(loan, customer)` pair's events and reinserts them. That is what makes a reversed payment,
a waived penalty and a loan revision self-correct; an incremental +10/-10 ledger would drift
out of step with all three and there would be no way to tell that it had. **Nothing outside
`CreditScoreService` may write to those three places.**

| table | grain | holds |
|---|---|---|
| `credit_score_events` | one row per judged installment (unique on `loan_installment_id`) | `event_type` (`on_time` / `late_paid` / `late_unpaid`), signed `points`, `days_late`, `occurred_on` |
| `customer_credit_scores` | one row per `(loan, customer)` | `total_points`, `installments_counted`, `on_time_count`, `late_count`, `average_points`, `final_score`, `finalized_at` |
| `customers.credit_score` | one row per customer | the weighted roll-up an officer reads at application time |

**Per party, like recovery (§6).** A Group Loan scores each member separately off their own
installment rows — one member paying late never marks the other four. Individual and Joint
loans score the loan's own `customer_id`, matching `InstallmentScheduleService::scheduleOwnersFor()`.

**What is not judged.** `revised` rows (superseded by a restructure — the replacement rows
carry the real schedule) and `waived` rows (a decision about the debt, not evidence about
the borrower) are skipped, as is any unpaid installment still inside its window. Skipped
rows are excluded from the divisor too, so a month that is not yet late cannot drag the
average down.

**Null is not zero.** A customer with no judged installment has `credit_score = null` and
`Customer::hasCreditHistory() === false`. A first-time borrower and a serial defaulter must
never render alike; `CreditScoreService::band()` returns null rather than `'poor'` for them,
and `GET /credit-scores` hides them unless `?include_unscored=1`.

**Scale.** `average_points` runs from `-credit_score_late_penalty_points` to
`+credit_score_on_time_points`. `normalize()` maps that linearly onto
`0..credit_score_normalize_max` for display, so punctual-every-month is the maximum and
late-every-month is 0. Set `credit_score_normalize_max` to 0 to store the raw average
instead. `final_score` is provisional while the loan repays and is stamped `finalized_at`
once it reaches `Closed`.

**Two grace periods, do not confuse them.** `credit_score_grace_days` decides when a payment
stops counting as punctual. The recovery grace period (§6) decides when a loan turns overdue
and is charged a penalty. They are independent, and a lender may well want scoring to be the
stricter of the two.

**Where it recomputes:**

| trigger | file |
|---|---|
| payment recorded | `PaymentController::store()`, post-commit |
| payment reversed | `PaymentController::destroy()`, post-commit |
| installment edited or penalty waived | `LoanInstallmentController::update()`, post-commit |
| nightly, 02:30 | `credit-score:recompute` (live loans only — last in the chain, so `loans:mark-overdue` has already stamped today's rows) |
| backfill / one-off | `credit-score:recompute --all` (also `--customer=` / `--loan=`) |

Every inline hook is **post-commit and never fatal** — it is wrapped in `try/catch` and only
logs a warning. A credit score is always rebuildable and the nightly job picks up anything
missed, so it must never be the reason a cashier's receipt fails.

A loan with **no schedule** (never disbursed) gets no score row at all — `recomputePair()`
deletes one if it finds one, and `--all` additionally prunes rows whose loan has lost its
schedule. Without that guard every merely-submitted application padded the borrower's
history with a "no history" loan that said nothing about how they repay.

**`credit_score_band` is appended by the `Customer` model**, so the loan application review,
the customer profile and the customer list all render the band without a second request and
without reimplementing the thresholds. That accessor calls `CreditScoreService::band()` once
per serialised customer, which is why the service is **bound as a singleton** and memoises
its settings snapshot (`config()` / `forgetConfig()`) — `CACHE_STORE` is `database` here, and
a fresh instance per call turned a 15-row customer list into 75 cache queries.
`SettingController::update()` calls `forgetConfig()` so a save cannot leave the snapshot stale.

**A settings change is not retroactive.** Stored scores were produced under the rules in
force at their last recompute. After changing any `credit_score_*` setting, run
`credit-score:recompute --all`, or the nightly job will only catch up the live loans.

**API.** `GET /credit-scores` (ranked list, weakest first by default) ·
`GET /credit-scores/{customerId}` (score, band, per-loan breakdown, ledger, and the rules the
score was produced under) · `POST /credit-scores/{customerId}/recompute`. Permissions
`Credit Score Index` / `Credit Score Recompute`.

---

## 6c. The two loan application references

A loan application carries **two** human-readable numbers, both minted by
`CreditScoreService`'s neighbour `app/Services/ReferenceNumberService.php`:

| | column | format | example | minted |
|---|---|---|---|---|
| Application reference | `applications.application_no` | `APP-{BRANCH}-{yyyymmdd}{00000001}` | `APP-COL-2026090900000001` | `Application::boot()` on create |
| Approval reference | `loan_applications.approval_reference_no` | `CDP-{BRANCH}-{000000001}` | `CDP-COL-000000001` | `LoanApplicationWorkflowService::transition()` on the first `Approved` |

Before approval the application reference **is** the loan's reference —
`LoanApplication::reference()` returns `application_no`, and that is what every
SMS quotes. The approval reference is null until approval and is **never
reissued**: the mint is guarded on the column being null, not on the transition,
so a loan reverted and approved again keeps the number the customer was given.

The two shapes are deliberately different. The application reference carries the
day it was taken and restarts its counter daily per branch; the approval
reference is one unbroken series per branch. Different prefixes *and* the date
mean the two can never collide, which matters because both appear side by side
on the review screen.

**Branch code** comes from `branches.code`, uppercased with every
non-alphanumeric character removed, so `BR-COL` reads `BRCOL`. The separators
have to go: a reference is split on its hyphens to be read, and a branch code
carrying one of its own turns a three-part number into a four-part one. Digits
survive (`NG2` stays `NG2`) — only punctuation and spaces are dropped.

**Legacy numbers are left alone.** Applications created before this format read
`APP-{BRANCH}-{yymm}{0001}`. They are quoted in SMS already sent and referenced
from payments, so they are not rewritten, and no prefix the new generator builds
can match them — the new counter starts clean. `loans:backfill-approval-references`
(`--dry-run` writes and rolls back, so its preview is truthful) issued approval
references to loans approved before the column existed, in approval order.

`nextSequence()` takes `lockForUpdate` and orders by `LENGTH(col) DESC` first.
Every *other* generator in this codebase (§11) does neither: they read the max
with no lock, and sort strings, so they mint duplicates under concurrency and
stop advancing once the counter gains a digit.

---

## 6d. The recommending employee

A CDP employee recommends the borrower, and this is recorded at **two
independent levels**:

| level | table | meaning | required? |
|---|---|---|---|
| **Customer-wise** | `customers` | the standing introducer on the customer's file, captured at registration | optional |
| **Loan-wise** | `loan_applications` | who put *this particular loan* forward | **required** on create |

Both are kept because they diverge: a customer introduced by one officer can
have a later loan recommended by another, and when a loan goes bad it is the
loan-level answer the business needs. Neither is derived from the other — the
frontend may prefill the loan's recommender from the customer's, but the two
rows are stored and edited separately.

The same five columns on each table: `recommended_by_employee_id` (FK →
`employees`, nullOnDelete) plus a **snapshot** — `recommender_name`,
`recommender_employee_code`, `recommender_nic`, `recommender_phone`.

**Both, on purpose.** The link is what lets you list every loan an employee
introduced. The snapshot is what they asserted on the day, and it has to survive
them changing their phone number, being renamed, or leaving (the FK nulls out,
the record does not). It is also what lets a recommender who is not yet in the
employee register be recorded at all. **Read the snapshot for display**; use the
relation only to walk back to the employee's current file.

**Validation.** The four details are `required` on
`CreateLoanApplicationRequest` and `nullable` on
`Create`/`UpdateCustomerRequest` — an existing customer may have walked in with
no introducer, but no loan goes out unattributed. `recommended_by_employee_id`
is nullable everywhere: refusing a loan because the recommender has not been
entered into the employee register yet would put a data-entry gap ahead of the
business. On `UpdateLoanApplicationRequest` all five are nullable too — a
partial edit must not have to resend them.

**Two of the three write paths carry it.** `LoanApplicationController::store()`
(mass-assigned, so `$fillable` + the rule is enough) and
`GroupLoanController::store()` (explicit array, nullable there — the group form
is its own flow). `CustomerController::store()` deliberately does **not**:
its loan branch fires only when the payload carries `loan_product_id`, and the
customer registration form has no loan fields at all (`TAB_LIST` in
`CustomerForm.tsx` ends at "User Account"; the `tab: 10, 'Loan Application'`
entries in its `FIELD_TAB_MAP` are only a fallback bucket for routing server
validation errors, not a rendered tab). That branch is unreachable from the UI,
so wiring a recommender into it would have been code no one could run.

`GET /employees/list` returns `id_number` and `phone` for the picker, with
`phone` falling back to `phone_primary` (most rows carry only the latter).
`Employee::scopeSearch()` matches NIC and phone as well as name/code/email — an
officer usually has the recommender's card or number, not the exact spelling of
their name.

**There is no frontend for either level yet** — the columns, validation and
persistence exist and are covered by tests, but nothing in the UI sends them.
That is why the loan-level fields are `nullable` rather than `required`: making
them required today would 422 every submission from `LoanApplicationForm` and
`LoanWizard`. The intent is that no loan goes out unattributed, so **tighten the
four loan-level rules to `required` as soon as the UI captures them** — the
comment in `CreateLoanApplicationRequest` says the same. Nullable is not
unchecked: a supplied `recommended_by_employee_id` must still exist, and the
`max:255` limits still apply.

`Customer::create()`/`update()` and `LoanApplicationController::store()` are all
mass-assignment, so `$fillable` plus the request rule is enough on those paths;
`GroupLoanController::store()` builds its array explicitly and passes the five
keys through by hand.

---

## 7. Notifications

`NotificationService` writes a `notifications` row **before** dispatching, then sends SMS
(`SmsService` → Dialog) and/or email (`GenericNotificationMail`). Rows persist regardless
of delivery outcome, so they are the audit trail.

Recipients come from `LoanApplication::notifiableCustomers()` — every attached customer,
or the primary when there is no pivot. SMS goes to all notifiable customers; **email only
when the loan has pivot customers**, so Individual Loan behaviour is unchanged.

Per-installment notifications resolve the recipient from the **row's own customer** for
Group Loans, which is what prevents an N×N fan-out.

> `.env` holds **live** Dialog and SMTP credentials. Always `Http::fake()` and
> `Mail::fake()` before running anything that can notify.

---

## 8. Auth, permissions, portal

- `POST /api/v1/login` → JWT; `me`, `logout`, `forgot-password`, `reset-forgot-password`,
  OTP verification (`login_otp_verifications`), `password_change_requests`.
- Staff authorisation is entirely permission-based (`PermissionsSeeder`, 44 groups).
  Seeding **adds only** — it never prunes removed permissions.
- **Customer portal** under `/api/v1/my/*` (20 read-mostly routes): dashboard, loans,
  installments, statement, payments, revisions, documents, assets, profile.
  It resolves the caller via `ResolvesAuthenticatedCustomerTrait` and scopes every query
  with `LoanApplication::scopeForCustomer()` — `customer_id` match **or** pivot match — so a
  Joint co-borrower or Group member sees their loan even when they are not the primary.

---

## 9. System Settings

`settings` (key/value/type/group), read through `Setting::get()` which caches forever and
busts on `set()`. Types: `integer|boolean|json|string`.

| group | keys |
|---|---|
| `loan_recovery` | `installment_due_period_days` 30 · `overdue_sms_frequency_days` 7 · `overdue_sms_duration_weeks` 3 · `internal_recovery_threshold_days` 30 · `external_recovery_threshold_days` 45 · `sms_notifications_enabled` · `recovery_escalation_enabled` |
| `group_loan` | `group_loan_service_charge_percentage` 10 · `group_loan_competency` (json list) |
| `loan_revision` | `loan_revision_enabled` · `loan_revision_allowed_types` |
| `customer` | `customer_bank_list` (json) |
| `credit_score` | `credit_score_enabled` · `credit_score_on_time_points` 10 · `credit_score_late_penalty_points` 10 · `credit_score_grace_days` 0 · `credit_score_count_unpaid_overdue` · `credit_score_normalize_max` 100 (see §6b) |

Settings are deliberately minimal — fixed business rules (the 2-member floor, rounding
convention, cascade behaviour) are hardcoded, not exposed.

---

## 10. Product hierarchy

```
LoanTerm (Traditional / Islamic)
   └─ LoanType (Standard Borrowing / Development Fund)
        └─ LoanProduct   is_group_loan flag, rate, term bounds,
                         processing fee rule, penalty_value, grace_period_days
```

Group-vs-individual is expressed **solely** by `loan_products.is_group_loan`; `loan_types`
carries no such flag. A group product has `interest_rate = NULL`, and the create request
requires a rate only for non-group products.

---

## 11. Conventions and traps

**Migrations.** New columns are merged into the table's *original* create migration rather
than added as `add_x_to_y` files. The live DB is then brought in step with a throwaway
migration that is deleted (with its `migrations` row) once run. Consequence: a migration
file can look correct while the live column is missing — check `Schema::hasColumn()` before
assuming the code is wrong.

**Index swaps.** MySQL uses the leftmost-column index to satisfy a foreign key and refuses
to drop it. Create the replacement under a new name **first**, then drop the old one.

**Date-only columns need `date:Y-m-d`, not `date`.** `APP_TIMEZONE` is `Asia/Colombo`, so a
plain `'date'` cast serialises `2026-07-21` as `"2026-07-20T18:30:00Z"` — midnight Colombo
expressed in UTC. Every date in the frontend is rendered by taking the first 10 characters,
so a plain cast displays **the day before**. `loan_installments.due_date` and
`credit_score_events.occurred_on` are cast `date:Y-m-d` for exactly this reason; the PHP-side
Carbon behaviour is unaffected. **Other date columns in this schema have not been audited for
it** — check before trusting a date on screen.

**Enum cast changes.** When a `$casts` enum class changes, grep every `!== SomeEnum::`
comparison on that attribute — a mismatched enum comparison is not a type error in PHP, it
silently always fails.

**Scheduler commands are global.** They iterate *every* qualifying loan, so running one
under a faked clock mutates real rows. Take a `mysqldump` first and wrap verification in an
outer transaction.

**Party identity is not the installment's `customer_id`.** A recovery case's
`customer_id` is the *member* for a Group Loan and **null** for Individual and Joint loans
— but every installment carries a real `customer_id` regardless. Comparing the two
directly never matches on an individual loan. Always derive the party from
`LoanApplication::arrearsGroups()`, which is the identity a case is opened against.
Getting this wrong once caused every individual loan's live case to be auto-resolved on
the next nightly run while the loan was still in arrears.

**Individual-loan endpoints refuse group loans.** All 10 mutation actions on
`LoanApplicationController` return 422 for a `group_loan_id` row, pointing at `/group-loans`.
Without that, `approve()` would run the interest formula against a `NULL` rate and silently
produce a zero charge.

---

## 12. Testing

There is no meaningful PHPUnit suite (`tests/` holds only `AuthTest` and scaffolds).
Features are verified by throwaway scripts that drive the real controllers, services and
commands:

```php
$base = 'C:/laragon/www/cdp-credix-api';
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
```

Rules that make this work:

- Wrap the run in `DB::beginTransaction()` / `DB::rollBack()` in a `finally` — nested
  `DB::transaction()` calls inside the app become savepoints and do not commit.
- `Http::fake()` + `Mail::fake()` **always**.
- Time-travel with `Carbon::setTestNow()`; `Artisan::call()` runs in-process so the faked
  clock is shared. Reset it in the `finally`.
- Resolving a FormRequest by hand needs
  `->setContainer(app())->setRedirector(app('redirect'))` then `validateResolved()`, plus a
  bound `Route` when the rules read `$this->route('id')`.
- Assert **invariants** (group total == sum of member rows), not frozen figures — live data
  changes underneath.
