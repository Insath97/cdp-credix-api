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
