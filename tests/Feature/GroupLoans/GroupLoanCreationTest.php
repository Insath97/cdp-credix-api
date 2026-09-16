<?php

use App\Enums\GroupLoanStatus;
use App\Enums\LoanApplicationStatus;
use App\Models\Application;
use App\Models\Customer;
use App\Models\GroupLoan;
use App\Models\LoanApplication;
use App\Models\LoanApplicationCustomer;
use App\Models\LoanProduct;
use App\Models\LoanTerm;
use App\Models\LoanType;
use App\Models\Setting;

/*
|--------------------------------------------------------------------------
| Group loans: submitting one, and what is refused
|--------------------------------------------------------------------------
|
| A group borrows as ONE loan. A successful submission therefore writes three
| things at once, and all three have to agree: the group_loans header, the
| single loan_applications row that carries the money, and one
| loan_application_customers row per member. Nothing in the request is trusted
| for the amount -- the requested total is recomputed from the item lines, and
| the service charge comes from System Settings.
|
| This file also holds the fixture builders the rest of tests/Feature/GroupLoans
| uses. Pest loads every file in the suite into one global scope, so they are
| reachable from the other files in this directory as long as the directory is
| run as a whole (`php artisan test tests/Feature/GroupLoans`), which is how
| this module is meant to be run.
|
*/

/**
 * A loan product a group loan may actually be submitted against.
 *
 * Not any active product will do. CreateGroupLoanRequest only accepts a
 * product whose loan type carries the code DEVELOPMENT_FUND, so a product
 * built the way the individual-loan tests build theirs is rejected on
 * loan_product_id and the test then measures the wrong refusal. The loan type
 * and term are found-or-created because both carry a unique code and several
 * products in one test would otherwise collide.
 */
function groupLoanProduct(array $overrides = []): LoanProduct
{
    $term = LoanTerm::firstOrCreate(
        ['code' => 'GRP-TERM'],
        ['title' => 'Monthly', 'is_active' => true],
    );

    $type = LoanType::firstOrCreate(
        ['code' => 'DEVELOPMENT_FUND'],
        ['title' => 'Development Fund', 'loan_term_id' => $term->id, 'is_active' => true],
    );

    return LoanProduct::create(array_merge([
        'name'                 => 'Development Fund ' . fake()->unique()->numerify('###'),
        'code'                 => 'GRP-' . fake()->unique()->numerify('####'),
        'loan_type_id'         => $type->id,
        'loan_term_id'         => $term->id,
        // Deliberately left null: a group loan carries no interest rate at
        // any point, and repayment is derived from the service charge alone.
        'interest_rate'        => null,
        'interest_type'        => null,
        'min_amount'           => 1000,
        'max_amount'           => 10000000,
        'min_term_months'      => 1,
        'max_term_months'      => 60,
        'processing_fee_type'  => 'fixed',
        'processing_fee_value' => 0,
        'is_active'            => true,
        'is_group_loan'        => true,
    ], $overrides));
}

/**
 * Existing customers to put in a group. Nothing in the group loan endpoints
 * creates a customer, so every member must already be on file.
 *
 * @return \Illuminate\Support\Collection<int, Customer>
 */
function groupLoanCustomers(int $count = 3): \Illuminate\Support\Collection
{
    return collect(range(1, $count))->map(fn ($n) => Customer::create([
        'full_name'          => "Group Member {$n}",
        'name_with_initials' => "G. Member {$n}",
        'id_type'            => 'nic',
        'id_number'          => 'GL' . fake()->unique()->numerify('##########'),
        'address_line_1'     => '1 Test Lane',
        'country'            => 'Sri Lanka',
        'date_of_birth'      => '1990-01-01',
        'phone_primary'      => '0744125923',
        'have_whatsapp'      => false,
        'preferred_language' => 'en',
    ]));
}

/**
 * A complete, valid create payload.
 *
 * The two item lines come to 180,000 (10 x 12,000 plus 3 x 20,000), which is
 * what the whole of this module's arithmetic is worked against: at the seeded
 * 10% service charge over 12 months for 3 members, every figure divides
 * exactly, so a failing assertion is a real disagreement rather than a cent of
 * rounding.
 */
function groupLoanPayload(array $overrides = [], int $members = 3): array
{
    $customerIds = groupLoanCustomers($members)->pluck('id');

    return array_merge([
        'loan_product_id'   => groupLoanProduct()->id,
        'group_name'        => 'Kandy Growers',
        'number_of_members' => $members,
        'competency'        => 'Entrepreneurship',
        'term_months'       => 12,
        'items'             => [
            ['item_name' => 'Sewing machine', 'quantity' => 10, 'unit_price' => 12000],
            ['item_name' => 'Fabric bales', 'quantity' => 3, 'unit_price' => 20000],
        ],
        'members'           => $customerIds->map(fn ($id) => ['customer_id' => $id])->all(),
    ], $overrides);
}

/**
 * A group loan already sitting at Available, built directly rather than
 * through the endpoint.
 *
 * It mirrors exactly what GroupLoanController::store() writes, because the
 * lifecycle, listing and deletion tests are about what happens AFTER
 * submission: driving the create endpoint in each of them would mean any one
 * of those tests could fail for a reason that belongs to this file.
 *
 * @return array{group: GroupLoan, application: LoanApplication, customers: \Illuminate\Support\Collection}
 */
function groupLoanFixture(int $members = 3, int $termMonths = 12, ?int $branchId = null): array
{
    $customers = groupLoanCustomers($members);
    $requestedAmount = 180000.00;

    $groupLoan = GroupLoan::create([
        'loan_product_id'           => groupLoanProduct()->id,
        'branch_id'                 => $branchId,
        'group_name'                => 'Kandy Growers',
        'number_of_members'         => $members,
        'competency'                => 'Entrepreneurship',
        'requested_amount'          => $requestedAmount,
        'service_charge_percentage' => (float) Setting::get('group_loan_service_charge_percentage', 0),
        'term_months'               => $termMonths,
        'applied_at'                => now(),
        'status'                    => GroupLoanStatus::Available,
    ]);

    $groupLoan->items()->create([
        'item_name'  => 'Sewing machine',
        'quantity'   => 10,
        'unit_price' => 12000,
        'line_total' => 120000,
    ]);
    $groupLoan->items()->create([
        'item_name'  => 'Fabric bales',
        'quantity'   => 3,
        'unit_price' => 20000,
        'line_total' => 60000,
    ]);

    $application = Application::create([
        'application_type'        => 'group_loan',
        'requested_amount'        => $requestedAmount,
        'repayment_period_months' => $termMonths,
    ]);

    $loanApplication = $groupLoan->loanApplication()->create([
        'application_id'   => $application->id,
        'customer_id'      => $customers->first()->id,
        'loan_product_id'  => $groupLoan->loan_product_id,
        'branch_id'        => $branchId,
        'requested_amount' => $requestedAmount,
        'interest_rate'    => null,
        'interest_type'    => null,
        'term_months'      => $termMonths,
        'applied_at'       => now(),
        'status'           => LoanApplicationStatus::Submitted,
    ]);

    foreach ($customers as $customer) {
        $loanApplication->loanApplicationCustomers()->create(['customer_id' => $customer->id]);
    }

    return [
        'group'       => $groupLoan->fresh(),
        'application' => $loanApplication->fresh(),
        'customers'   => $customers,
    ];
}

/*
|--------------------------------------------------------------------------
| A valid submission
|--------------------------------------------------------------------------
*/

it('creates a group loan with its items, its single loan application and one row per member', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload();

    $response = $this->postJson($this->api('/group-loans'), $payload);

    $response->assertStatus(201);

    $groupLoan = GroupLoan::latest('id')->firstOrFail();

    expect($groupLoan->status->value)->toBe('available');
    expect($groupLoan->number_of_members)->toBe(3);
    expect($groupLoan->term_months)->toBe(12);

    // The amount is recomputed from the item lines rather than taken from the
    // request: 10 x 12,000 + 3 x 20,000 = 180,000.
    expect((float) $groupLoan->requested_amount)->toBe(180000.0);
    expect($groupLoan->items()->count())->toBe(2);

    // One loan application for the whole group, not one per member.
    expect(LoanApplication::where('group_loan_id', $groupLoan->id)->count())->toBe(1);

    $application = $groupLoan->loanApplication;

    expect($application->status->value)->toBe('submitted');
    expect((float) $application->requested_amount)->toBe(180000.0);
    expect($application->term_months)->toBe(12);

    // A group loan has no interest rate at all -- the service charge stands in
    // its place, so leaving a rate on the row would double-count.
    expect($application->interest_rate)->toBeNull();

    // Every member is attached to that one application.
    $memberIds = LoanApplicationCustomer::where('loan_application_id', $application->id)
        ->pluck('customer_id')
        ->sort()
        ->values()
        ->all();

    expect($memberIds)->toBe(collect($payload['members'])->pluck('customer_id')->sort()->values()->all());
});

it('snapshots the service charge percentage from system settings', function () {
    // There is no per-application override, so the only place the charge can
    // come from is the setting -- and it is frozen onto the header at
    // submission so a later change to the setting cannot rewrite a live loan.
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $this->postJson($this->api('/group-loans'), groupLoanPayload())->assertStatus(201);

    expect((float) GroupLoan::latest('id')->firstOrFail()->service_charge_percentage)
        ->toBe((float) Setting::get('group_loan_service_charge_percentage'));
});

it('names the first member as the primary applicant on the application row', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload();

    $this->postJson($this->api('/group-loans'), $payload)->assertStatus(201);

    $application = GroupLoan::latest('id')->firstOrFail()->loanApplication;

    expect($application->customer_id)->toBe($payload['members'][0]['customer_id']);
});

/*
|--------------------------------------------------------------------------
| What a submission must not be allowed to say
|--------------------------------------------------------------------------
*/

it('names every required field when nothing is sent', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $response = $this->postJson($this->api('/group-loans'), []);

    $this->assertValidationFailed($response, [
        'loan_product_id',
        'number_of_members',
        'competency',
        'term_months',
        'items',
        'members',
    ]);
});

it('refuses a member list that disagrees with the declared headcount', function () {
    // The declared count is what the branch wrote on the paper form and the
    // member rows are what was keyed in; a mismatch means one of the two is
    // wrong, and approving the wrong one splits the money across the wrong
    // number of people.
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload(['number_of_members' => 5], members: 3);

    $response = $this->postJson($this->api('/group-loans'), $payload);

    $this->assertValidationFailed($response, ['members']);
    expect(GroupLoan::count())->toBe(0);
});

it('refuses a member list longer than the declared headcount', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload(['number_of_members' => 2], members: 4);

    $this->assertValidationFailed($this->postJson($this->api('/group-loans'), $payload), ['members']);
});

it('refuses the same customer twice in one group', function () {
    // Two rows for one person would give them two shares of the money and two
    // schedules, and the headcount would be a lie.
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $customer = groupLoanCustomers(1)->first();

    $payload = groupLoanPayload([
        'number_of_members' => 2,
        'members'           => [
            ['customer_id' => $customer->id],
            ['customer_id' => $customer->id],
        ],
    ]);

    $this->assertValidationFailed($this->postJson($this->api('/group-loans'), $payload), ['members.1.customer_id']);
});

it('accepts a single member, because the individual Development Fund tier uses this endpoint too', function () {
    // Documenting a deliberate decision, and the risk that comes with it.
    //
    // number_of_members is min:1 on purpose: a Development Fund loan is
    // item-based whether one person or a group takes it, and the wizard submits
    // the individual tier through this same endpoint. A floor of two would
    // reject every one of those.
    //
    // The consequence is worth knowing. A one-member group loan carries no
    // guarantor requirement, because the workflow skips that check for anything
    // with a group_loan_id, and no interest, because a group loan is priced on
    // a service charge instead. So this route is a cheaper and less secured way
    // to lend to one person than the individual loan endpoint is. Only the
    // product's loan type keeps Standard Borrowing out of it.
    //
    // GroupLoanWorkflowService::MIN_MEMBERS is 2, but it is enforced only when
    // a member is REMOVED, never at creation.
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload(['number_of_members' => 1], members: 1);

    $this->postJson($this->api('/group-loans'), $payload)->assertStatus(201);
});

it('refuses an inactive loan product', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload(['loan_product_id' => groupLoanProduct(['is_active' => false])->id]);

    $this->assertValidationFailed($this->postJson($this->api('/group-loans'), $payload), ['loan_product_id']);
});

it('refuses a deleted loan product', function () {
    // A product that has been withdrawn must stop being lendable the moment it
    // is deleted, exactly as an inactive one does. Soft deletion is how this
    // schema withdraws a product, so the row is still physically present --
    // which is precisely why an existence check has to exclude it rather than
    // assume it is gone.
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $product = groupLoanProduct();
    $product->delete();

    $payload = groupLoanPayload(['loan_product_id' => $product->id]);

    $this->assertValidationFailed($this->postJson($this->api('/group-loans'), $payload), ['loan_product_id']);
});

it('refuses a competency that is not one of the configured options', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload(['competency' => 'Interpretive dance']);

    $this->assertValidationFailed($this->postJson($this->api('/group-loans'), $payload), ['competency']);
});

/*
|--------------------------------------------------------------------------
| Items
|--------------------------------------------------------------------------
|
| The items are not decoration: they ARE the requested amount, so a group loan
| with no items would be a loan for nothing, and a line with no quantity would
| contribute an amount nobody can account for.
|
*/

it('refuses a group loan with no items', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/group-loans'), groupLoanPayload(['items' => []])),
        ['items'],
    );
});

it('refuses an item with no name', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload(['items' => [['quantity' => 2, 'unit_price' => 5000]]]);

    $this->assertValidationFailed($this->postJson($this->api('/group-loans'), $payload), ['items.0.item_name']);
});

it('refuses an item with no quantity', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload(['items' => [['item_name' => 'Sewing machine', 'quantity' => 0, 'unit_price' => 5000]]]);

    $this->assertValidationFailed($this->postJson($this->api('/group-loans'), $payload), ['items.0.quantity']);
});

it('refuses a negative quantity', function () {
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload(['items' => [['item_name' => 'Sewing machine', 'quantity' => -3, 'unit_price' => 5000]]]);

    $this->assertValidationFailed($this->postJson($this->api('/group-loans'), $payload), ['items.0.quantity']);
});

/*
|--------------------------------------------------------------------------
| Editing the item list before approval
|--------------------------------------------------------------------------
|
| The items ARE the amount, so every change to them has to move the requested
| total on the header AND on the application underneath, or approval works from
| one figure while the screen shows another.
|
*/

it('recomputes the requested amount when an item is added', function () {
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Item Create']));

    $this->postJson($this->api('/group-loan-items'), [
        'group_loan_id' => $groupLoan->id,
        'item_name'     => 'Cutting table',
        'quantity'      => 2,
        'unit_price'    => 15000,
    ])->assertStatus(201);

    $groupLoan->refresh();

    // 180,000 plus 2 x 15,000.
    expect((float) $groupLoan->requested_amount)->toBe(210000.0);
    expect((float) $groupLoan->loanApplication->requested_amount)->toBe(210000.0);
});

it('recomputes the requested amount when an item is removed', function () {
    $groupLoan = groupLoanFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Item Delete']));

    $this->deleteJson($this->api('/group-loan-items/') . $groupLoan->items()->first()->id)
        ->assertStatus(200);

    $groupLoan->refresh();

    // The 120,000 line is gone, leaving the 60,000 one.
    expect((float) $groupLoan->requested_amount)->toBe(60000.0);
    expect((float) $groupLoan->loanApplication->requested_amount)->toBe(60000.0);
});

it('refuses to remove the last item from a group loan', function () {
    // A group loan for nothing is not a group loan; dropping the final item
    // has to go through cancellation instead.
    $groupLoan = groupLoanFixture()['group'];
    $groupLoan->items()->first()->delete();

    $this->actingAsApi($this->userWithPermissions(['Group Loan Item Delete']));

    $this->deleteJson($this->api('/group-loan-items/') . $groupLoan->items()->first()->id)
        ->assertStatus(422);

    expect($groupLoan->items()->count())->toBe(1);
});

it('refuses to add an item to a group loan that has already been approved', function () {
    $groupLoan = groupLoanApprovedFixture()['group'];

    $this->actingAsApi($this->userWithPermissions(['Group Loan Item Create']));

    $response = $this->postJson($this->api('/group-loan-items'), [
        'group_loan_id' => $groupLoan->id,
        'item_name'     => 'Late addition',
        'quantity'      => 1,
        'unit_price'    => 50000,
    ]);

    $this->assertValidationFailed($response, ['group_loan_id']);
    expect((float) $groupLoan->fresh()->requested_amount)->toBe(180000.0);
});

it('computes each line total and the requested amount from the items alone', function () {
    // A client that sends its own requested_amount must not be believed: the
    // amount is the sum of what was actually asked for.
    $this->actingAsApi($this->userWithPermissions(['Group Loan Create']));

    $payload = groupLoanPayload([
        'requested_amount' => 999999,
        'items'            => [
            ['item_name' => 'Seed stock', 'quantity' => 4, 'unit_price' => 2500],
            ['item_name' => 'Irrigation pipe', 'quantity' => 2.5, 'unit_price' => 4000],
        ],
    ]);

    $this->postJson($this->api('/group-loans'), $payload)->assertStatus(201);

    $groupLoan = GroupLoan::latest('id')->firstOrFail();

    // 4 x 2,500 = 10,000 and 2.5 x 4,000 = 10,000, so 20,000 in total.
    expect((float) $groupLoan->requested_amount)->toBe(20000.0);
    expect($groupLoan->items->pluck('line_total')->map(fn ($v) => (float) $v)->all())->toBe([10000.0, 10000.0]);
});
