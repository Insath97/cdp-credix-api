<?php

use App\Enums\LoanApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\CustomerBankDetail;
use App\Models\Document;
use App\Models\FixedAssests;
use App\Models\Guarantor;
use App\Models\Liability;
use App\Models\LoanApplication;
use App\Models\LoanApplicationCustomer;
use App\Models\LoanInstallment;
use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use App\Models\MovingAssests;
use App\Models\Notification;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Customer portal: who gets in
|--------------------------------------------------------------------------
|
| Everything under /v1/my answers a borrower asking about their own file. The
| door therefore has to be shut three different ways, and this file states all
| three: no token at all, a token belonging to the back office, and a customer
| token with no borrower record behind it.
|
| The shared fixtures for the whole CustomerPortal suite also live here. Pest
| loads every test file into one global scope, so a helper declared in any of
| them is callable from all of them; they are gathered in one place so there is
| a single definition of what "a customer with a full file" means, and every
| name is prefixed `portal` because a collision with a helper in another
| directory is a fatal redeclare rather than a test failure.
|
*/

/**
 * Every route in the /my group, as [method, path].
 *
 * The ids are deliberately ones that cannot exist. These access tests are
 * about the gate, and a request that never reaches a controller cannot care
 * whether the row is there -- 401 and 403 are decided by middleware, so a
 * missing row would only matter if the gate had already let the caller past.
 */
function portalEndpoints(): array
{
    return [
        ['get', '/my/dashboard'],
        ['get', '/my/profile'],
        ['patch', '/my/profile'],
        ['get', '/my/loan-applications'],
        ['get', '/my/loan-applications/999999'],
        ['get', '/my/loans'],
        ['get', '/my/loans/999999'],
        ['get', '/my/loans/999999/installments'],
        ['get', '/my/loans/999999/revisions'],
        ['get', '/my/loans/999999/statement'],
        ['get', '/my/payments'],
        ['get', '/my/payments/999999'],
        ['get', '/my/notifications'],
        ['get', '/my/documents'],
        ['get', '/my/documents/999999/download'],
        ['get', '/my/guarantors'],
        ['get', '/my/fixed-assets'],
        ['get', '/my/moving-assets'],
        ['get', '/my/liabilities'],
        ['get', '/my/bank-details'],
    ];
}

/**
 * The portal listings that hand a page size straight to a paginator.
 */
function portalPaginatedEndpoints(): array
{
    return [
        '/my/loan-applications',
        '/my/loans',
        '/my/payments',
        '/my/documents',
        '/my/notifications',
    ];
}

/**
 * Fire every endpoint and return the status each answered with, keyed by
 * "method path".
 *
 * Collected into a map and asserted in one go rather than asserted one at a
 * time, so a failure names the endpoints that answered wrongly instead of
 * stopping at the first.
 *
 * The request itself is handed in as a closure. ApiTestCase::api() is
 * protected, and a top-level function is outside the test case's scope, so the
 * caller keeps the part that needs `$this` and this helper keeps the loop.
 *
 * @return array<string, int>
 */
function portalStatuses(callable $call, array $endpoints): array
{
    $statuses = [];

    foreach ($endpoints as [$method, $path]) {
        $statuses["{$method} {$path}"] = $call($method, $path);
    }

    return $statuses;
}

/**
 * @return array<string, int>
 */
function portalExpect(array $statuses, int $status): array
{
    return array_fill_keys(array_keys($statuses), $status);
}

/*
|--------------------------------------------------------------------------
| Fixtures
|--------------------------------------------------------------------------
*/

function portalCustomer(string $label, array $attributes = []): Customer
{
    return Customer::create(array_merge([
        'customer_id'        => 'CID-' . strtoupper($label) . '-' . fake()->unique()->numerify('####'),
        'full_name'          => $label . ' Portal',
        'name_with_initials' => substr($label, 0, 1) . '. Portal',
        'id_type'            => 'nic',
        'id_number'          => 'PRT' . fake()->unique()->numerify('##########'),
        'address_line_1'     => '1 ' . $label . ' Lane',
        'city'               => 'Colombo',
        'country'            => 'Sri Lanka',
        'date_of_birth'      => '1990-01-01',
        'phone_primary'      => '0770000000',
        'email'              => strtolower($label) . fake()->unique()->numerify('####') . '@example.test',
        'have_whatsapp'      => false,
        'preferred_language' => 'en',
        'is_active'          => true,
    ], $attributes));
}

function portalProduct(): LoanProduct
{
    $term = LoanTerm::create(['code' => 'PRT-' . fake()->unique()->numerify('#####'), 'title' => 'Monthly', 'is_active' => true]);
    $type = LoanType::create(['code' => 'PRT-' . fake()->unique()->numerify('#####'), 'title' => 'Standard', 'loan_term_id' => $term->id, 'is_active' => true]);

    return LoanProduct::create([
        'name'                 => 'Portal Product ' . fake()->unique()->numerify('####'),
        'code'                 => 'PRP-' . fake()->unique()->numerify('#####'),
        'loan_type_id'         => $type->id,
        'loan_term_id'         => $term->id,
        'interest_rate'        => 10,
        'interest_type'        => 'flat',
        'min_amount'           => 1000,
        'max_amount'           => 1000000,
        'min_term_months'      => 1,
        'max_term_months'      => 60,
        'processing_fee_type'  => 'fixed',
        'processing_fee_value' => 0,
        'penalty_type'         => 'fixed',
        'penalty_value'        => 0,
        'grace_period_days'    => 30,
        'is_active'            => true,
    ]);
}

/**
 * A live, disbursed loan of `$months` installments of `$monthly`, with the
 * first month already settled by a matching payment.
 *
 * One month is paid on purpose. It makes every figure the portal reports
 * distinct from the principal -- outstanding, paid, remaining and total
 * payable are four different numbers -- so an endpoint that returned the wrong
 * one cannot accidentally match.
 */
function portalLoan(Customer $customer, float $monthly, int $months, string $receiptTag = 'RCP'): LoanApplication
{
    $principal = $monthly * $months;

    $application = Application::create([
        'application_type' => 'loan',
        'requested_amount' => $principal,
    ]);

    $loan = LoanApplication::create([
        'application_id'      => $application->id,
        'loan_product_id'     => portalProduct()->id,
        'customer_id'         => $customer->id,
        'requested_amount'    => $principal,
        'approved_amount'     => $principal,
        'interest_rate'       => 10,
        'interest_type'       => 'flat',
        'term_months'         => $months,
        'monthly_installment' => $monthly,
        'outstanding_balance' => $principal - $monthly,
        'status'              => LoanApplicationStatus::Active,
        'applied_at'          => now()->subMonths($months),
        'disbursed_at'        => now()->subMonths($months),
    ]);

    foreach (range(1, $months) as $n) {
        $settled = $n === 1;

        LoanInstallment::create([
            'loan_application_id' => $loan->id,
            'customer_id'         => $customer->id,
            'installment_no'      => $n,
            'due_date'            => now()->addMonths($n)->toDateString(),
            'amount_due'          => $monthly,
            'amount_paid'         => $settled ? $monthly : 0,
            'penalty_amount'      => 0,
            'balance'             => $settled ? 0 : $monthly,
            'status'              => $settled ? 'paid' : 'upcoming',
        ]);
    }

    Payment::create([
        'loan_application_id' => $loan->id,
        'loan_installment_id' => LoanInstallment::where('loan_application_id', $loan->id)
            ->where('installment_no', 1)->value('id'),
        'customer_id'         => $customer->id,
        'amount'              => $monthly,
        'payment_method'      => 'cash',
        'paid_at'             => now()->subMonth(),
        'receipt_no'          => $receiptTag . '-' . fake()->unique()->numerify('######'),
    ]);

    return $loan->fresh();
}

/**
 * An application still in the pipeline, which /my/loans must not list and
 * /my/loan-applications must.
 */
function portalPendingApplication(Customer $customer, float $amount): LoanApplication
{
    $application = Application::create([
        'application_type' => 'loan',
        'requested_amount' => $amount,
    ]);

    return LoanApplication::create([
        'application_id'   => $application->id,
        'loan_product_id'  => portalProduct()->id,
        'customer_id'      => $customer->id,
        'requested_amount' => $amount,
        'interest_rate'    => 10,
        'term_months'      => 6,
        'status'           => LoanApplicationStatus::Submitted,
        'applied_at'       => now()->subDays(3),
    ]);
}

/**
 * A document with real bytes behind it, so the download route can be asked for
 * the file rather than only for a 404.
 *
 * Written through the 'local' disk because that disk's root is exactly the
 * first place FileUploadTrait::resolveStoredFile() looks; a faked disk would
 * put the bytes somewhere the resolver does not know about and the download
 * would 404 for the wrong reason.
 */
function portalDocument(Customer $customer, string $name): Document
{
    $path = 'uploads/documents/portal-' . fake()->unique()->numerify('########') . '.txt';

    Storage::disk('local')->put($path, "bytes for {$name}");

    return Document::create([
        'customer_id'   => $customer->id,
        'document_name' => $name,
        'document_type' => 'nic_copy',
        'file_path'     => $path,
        'status'        => 'active',
        'is_active'     => true,
        'uploaded_at'   => now(),
    ]);
}

/**
 * The declared-means side of a customer's file: guarantor, the two asset
 * kinds, a liability and a bank account.
 *
 * `$tag` is stamped into a field on each row so a test can search a response
 * body for one customer's marker and prove the other customer's never appears.
 *
 * @return array{guarantor: Guarantor, fixed: FixedAssests, moving: MovingAssests, liability: Liability, bank: CustomerBankDetail}
 */
function portalFinancials(Customer $customer, string $tag): array
{
    $guarantor = Guarantor::create([
        'customer_id'   => $customer->id,
        'full_name'     => $tag . ' Guarantor',
        'type'          => 'guarantor_1',
        'id_type'       => 'nic',
        'id_number'     => 'GRT' . fake()->unique()->numerify('#########'),
        'phone_primary' => '0761111111',
    ]);

    $fixed = FixedAssests::create([
        'customer_id'       => $customer->id,
        'owner_name'        => $tag . ' Landowner',
        'property_location' => $tag . ' Estate',
        'extent'            => '10 perches',
        'market_value'      => 1500000,
        'is_mortaged'       => false,
        'is_active'         => true,
    ]);

    $moving = MovingAssests::create([
        'customer_id'                => $customer->id,
        'assest_category'            => 'vehicle',
        'owner_name'                 => $tag . ' Driver',
        'make_model'                 => $tag . ' Motors',
        'registation_no'             => strtoupper($tag) . '-1234',
        'market_value'               => 800000,
        'mortgage_lease_hire_status' => 'none',
        'is_active'                  => true,
    ]);

    $liability = Liability::create([
        'customer_id'         => $customer->id,
        'liability_type'      => 'bank_loan',
        'institution_name'    => $tag . ' Bank PLC',
        'original_amount'     => 200000,
        'outstanding_balance' => 120000,
        'monthly_installment' => 5000,
        'is_active'           => true,
    ]);

    $bank = CustomerBankDetail::create([
        'customer_id'    => $customer->id,
        'bank_name'      => $tag . ' Bank PLC',
        'branch_name'    => $tag . ' Branch',
        'account_number' => fake()->unique()->numerify('##########'),
        'payment_method' => 'bank_transfer',
        'is_active'      => true,
    ]);

    return compact('guarantor', 'fixed', 'moving', 'liability', 'bank');
}

function portalNotification(Customer $customer, string $subject): Notification
{
    return Notification::create([
        'customer_id' => $customer->id,
        'type'        => 'payment_reminder',
        'channel'     => 'sms',
        'recipient'   => $customer->phone_primary,
        'subject'     => $subject,
        'message'     => $subject . ' body',
        'status'      => 'sent',
        'sent_at'     => now(),
    ]);
}

/**
 * One complete borrower file.
 *
 * @return array<string, mixed>
 */
function portalFile(string $label, float $monthly, int $months): array
{
    $customer = portalCustomer($label);

    $loan    = portalLoan($customer, $monthly, $months, strtoupper($label));
    $pending = portalPendingApplication($customer, $monthly * 2);

    return array_merge([
        'customer'     => $customer,
        'loan'         => $loan,
        'pending'      => $pending,
        'monthly'      => $monthly,
        'months'       => $months,
        'payment'      => Payment::where('loan_application_id', $loan->id)->firstOrFail(),
        'installments' => LoanInstallment::where('loan_application_id', $loan->id)->orderBy('installment_no')->get(),
        'document'     => portalDocument($customer, $label . ' NIC Copy'),
        'notification' => portalNotification($customer, $label . ' reminder'),
        'tag'          => $label,
    ], portalFinancials($customer, $label));
}

/**
 * Two unrelated borrowers, each with a complete file.
 *
 * Two, and not one plus a bare row, because the question this suite exists to
 * answer is not "does the endpoint filter" but "does the filter hold" -- and a
 * filter that silently matches nothing passes the first version of that test.
 *
 * @return array{alice: array<string, mixed>, bob: array<string, mixed>}
 */
function portalWorld(): array
{
    return [
        'alice' => portalFile('Alice', 10000, 3),
        'bob'   => portalFile('Bob', 25000, 2),
    ];
}

/**
 * Link a co-applicant onto an existing loan.
 */
function portalJoinAsCoApplicant(LoanApplication $loan, Customer $customer): LoanApplicationCustomer
{
    return LoanApplicationCustomer::create([
        'loan_application_id' => $loan->id,
        'customer_id'         => $customer->id,
    ]);
}

/*
|--------------------------------------------------------------------------
| No token
|--------------------------------------------------------------------------
*/

it('refuses every portal endpoint to an unauthenticated caller', function () {
    $statuses = portalStatuses(
        fn ($method, $path) => $this->{$method . 'Json'}($this->api($path))->status(),
        portalEndpoints(),
    );

    expect($statuses)->toBe(portalExpect($statuses, 401));
});

/*
|--------------------------------------------------------------------------
| A back-office token
|--------------------------------------------------------------------------
|
| Staff and customers authenticate against the same JWT guard, so a valid
| back-office token is a valid token here too. Only the user_type claim stands
| between an admin and a borrower's portal, which is precisely why it is worth
| asserting on every route rather than on a sample.
|
*/

it('refuses every portal endpoint to an admin user', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $statuses = portalStatuses(
        fn ($method, $path) => $this->{$method . 'Json'}($this->api($path))->status(),
        portalEndpoints(),
    );

    expect($statuses)->toBe(portalExpect($statuses, 403));
});

it('refuses every portal endpoint to a branch officer', function () {
    $chain = $this->organisationChain();

    $this->actingAsApi($this->officerAtBranch($chain['branch'], ['Loan Application Index', 'Payment Index']));

    $statuses = portalStatuses(
        fn ($method, $path) => $this->{$method . 'Json'}($this->api($path))->status(),
        portalEndpoints(),
    );

    expect($statuses)->toBe(portalExpect($statuses, 403));
});

it('refuses every portal endpoint to a super admin', function () {
    // The portal is not a privilege level, it is a different audience. Holding
    // every permission in the system is still not being the borrower.
    $this->actingAsApi($this->superAdmin());

    $statuses = portalStatuses(
        fn ($method, $path) => $this->{$method . 'Json'}($this->api($path))->status(),
        portalEndpoints(),
    );

    expect($statuses)->toBe(portalExpect($statuses, 403));
});

/*
|--------------------------------------------------------------------------
| A customer token with nothing behind it
|--------------------------------------------------------------------------
*/

it('refuses a customer account that has no customer record behind it', function () {
    // customerUser() builds a user whose type is 'customer' but whose
    // customer_id is null. Every controller resolves the borrower through that
    // column, so this is the shape of an account half-way through being
    // provisioned -- and it must be turned away deliberately rather than by
    // whatever exception the first null dereference happens to raise.
    $this->actingAsApi($this->customerUser());

    $statuses = portalStatuses(
        fn ($method, $path) => $this->{$method . 'Json'}($this->api($path))->status(),
        portalEndpoints(),
    );

    expect($statuses)->toBe(portalExpect($statuses, 404));
});

it('does not turn the missing-customer refusal into a server error', function () {
    // Stated separately from the status assertion above because the failure
    // mode it guards is specific: every controller body sits inside a blanket
    // `catch (\Throwable)` that answers 500 and, with APP_DEBUG on, prints the
    // exception message. An abort(404) raised inside that try block is a
    // Throwable like any other, so a deliberate refusal can come back as a 500
    // carrying internal detail.
    $this->actingAsApi($this->customerUser());

    $response = $this->getJson($this->api('/my/dashboard'));

    $response->assertStatus(404);
    expect($response->json('message'))->toBe('Customer profile not found.');
    expect(json_encode($response->json()))->not->toContain('Failed to retrieve');
});

it('keeps a genuine not-found answer at 404 for a linked customer', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->getJson($this->api('/my/loans/999999'))->assertStatus(404);
    $this->getJson($this->api('/my/loan-applications/999999'))->assertStatus(404);
    $this->getJson($this->api('/my/payments/999999'))->assertStatus(404);
    $this->getJson($this->api('/my/documents/999999/download'))->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Page size
|--------------------------------------------------------------------------
*/

it('survives a page size of zero on every portal listing', function () {
    // per_page is read straight off the query string and handed to paginate()
    // unclamped, which is what Controller::perPage() exists to prevent. Zero is
    // the value a cleared filter sends, and it only survives here by accident:
    // Eloquent's own `$perPage ?: $model->getPerPage()` treats it as "not
    // asked" and substitutes the default. The accident is worth pinning down,
    // because it is the reason the neighbouring negative-size case looks like
    // an unrelated bug rather than the same one.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $statuses = [];

    foreach (portalPaginatedEndpoints() as $path) {
        $statuses[$path] = $this->getJson($this->api($path . '?per_page=0'))->status();
    }

    expect($statuses)->toBe(array_fill_keys(array_keys($statuses), 200));
});

it('survives a negative page size on every portal listing', function () {
    // A negative size is truthy, so nothing substitutes a default for it. It
    // reaches the query builder, where a negative LIMIT is silently dropped
    // while the OFFSET is kept -- producing `order by ... offset 0` with no
    // limit clause, which is a syntax error on both SQLite and MySQL. The
    // blanket catch turns it into a 500, so any caller can make the portal
    // answer with a server error and, with APP_DEBUG on, the raw SQL.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $statuses = [];

    foreach (portalPaginatedEndpoints() as $path) {
        $statuses[$path] = $this->getJson($this->api($path . '?per_page=-5'))->status();
    }

    expect($statuses)->toBe(array_fill_keys(array_keys($statuses), 200));
});
