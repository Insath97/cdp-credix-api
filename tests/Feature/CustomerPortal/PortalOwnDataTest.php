<?php

use App\Enums\LoanRevisionStatus;
use App\Models\Customer;
use App\Models\LoanRevision;

/*
|--------------------------------------------------------------------------
| Customer portal: the borrower's own file
|--------------------------------------------------------------------------
|
| The confinement tests next door prove nothing leaks out. This file proves
| something comes back, and that it is arithmetically right: a portal that
| returned an empty list to everybody would satisfy every ownership assertion
| ever written.
|
| The fixture is deliberately lopsided -- three months of 10,000 with exactly
| one of them paid -- so approved (30,000), total payable (30,000), paid
| (10,000), outstanding (20,000) and remaining (2) are five distinct figures.
| An endpoint that reported the wrong one cannot coincidentally match.
|
*/

/*
|--------------------------------------------------------------------------
| Loans
|--------------------------------------------------------------------------
*/

it('reports the customer own loan figures', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/loans'));

    $response->assertStatus(200);

    $row = collect($response->json('data.data'))->firstWhere('id', $world['alice']['loan']->id);

    expect($row)->not->toBeNull();
    expect((float) $row['approved_amount'])->toBe(30000.0);
    expect((float) $row['total_payable_amount'])->toBe(30000.0);
    expect((float) $row['paid_amount'])->toBe(10000.0);
    expect((float) $row['outstanding_balance'])->toBe(20000.0);
    expect((float) $row['monthly_installment'])->toBe(10000.0);
    expect((int) $row['remaining_installments'])->toBe(2);
    expect($row['status'])->toBe('active');
});

it('leaves an undisbursed application out of the loans list', function () {
    // /my/loans is the money-is-out view and /my/loan-applications is the
    // pipeline view. A submitted application appearing under "My Loans" would
    // tell the borrower they have a loan they have not been given.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $ids = collect($this->getJson($this->api('/my/loans'))->json('data.data'))->pluck('id')->all();

    expect($ids)->not->toContain($world['alice']['pending']->id);
});

it('shows one of the customer own loans in full', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/loans/') . $world['alice']['loan']->id);

    $response->assertStatus(200);
    expect((float) $response->json('data.requested_amount'))->toBe(30000.0);
    expect((float) $response->json('data.outstanding_balance'))->toBe(20000.0);
    expect($response->json('data.loan_product.id'))->toBe($world['alice']['loan']->loan_product_id);
});

it('returns the customer own repayment schedule', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/loans/') . $world['alice']['loan']->id . '/installments');

    $response->assertStatus(200);

    $rows = $response->json('data.installments');

    expect($rows)->toHaveCount(3);
    expect($rows[0]['status'])->toBe('paid');
    expect((float) $rows[0]['balance'])->toBe(0.0);
    expect((float) $rows[1]['balance'])->toBe(10000.0);

    // The next-due marker is what the portal's "pay now" button reads, so it
    // has to skip the month already settled rather than simply take the first.
    expect($response->json('data.next_installment.installment_no'))->toBe(2);
    expect($rows[1]['is_next_due'])->toBeTrue();
});

it('returns the customer own revision history', function () {
    $world = portalWorld();

    $loan = $world['alice']['loan'];

    LoanRevision::create([
        'loan_application_id'          => $loan->id,
        'revision_no'                  => 1,
        'revision_type'                => 'term_extension',
        'status'                       => LoanRevisionStatus::Approved,
        'previous_outstanding_amount'  => 20000,
        'previous_term'                => 3,
        'previous_installment_amount'  => 10000,
        'revised_outstanding_amount'   => 20000,
        'revised_term'                 => 4,
        'revised_installment_amount'   => 5000,
        'reason'                       => 'Lost overtime hours',
        'effective_date'               => now()->toDateString(),
    ]);

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/loans/') . $loan->id . '/revisions');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.revision_no'))->toBe(1);
    expect((float) $response->json('data.0.revised_installment_amount'))->toBe(5000.0);
});

it('returns the customer own statement', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/loans/') . $world['alice']['loan']->id . '/statement');

    $response->assertStatus(200);
    expect($response->json('data.customer.full_name'))->toBe('Alice Portal');
    expect((float) $response->json('data.outstanding_balance'))->toBe(20000.0);
    expect((float) $response->json('data.loan.total_payable_amount'))->toBe(30000.0);
    expect($response->json('data.payment_history'))->toHaveCount(1);
    expect($response->json('data.installment_schedule'))->toHaveCount(3);
});

/*
|--------------------------------------------------------------------------
| Applications and payments
|--------------------------------------------------------------------------
*/

it('masks the internal review stages on the applications list', function () {
    // 'submitted', 'reviewed' and 'verified' are the lender's own maker-checker
    // steps. The borrower is told the file is being processed, not which desk
    // it is sitting on.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/loan-applications'));

    $response->assertStatus(200);

    $row = collect($response->json('data.data'))->firstWhere('id', $world['alice']['pending']->id);

    expect($row['status'])->toBe('processing');
    expect((float) $row['requested_amount'])->toBe(20000.0);
});

it('returns the customer own payment history', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/payments'));

    $response->assertStatus(200);

    $rows = $response->json('data.data');

    expect($rows)->toHaveCount(1);
    expect((float) $rows[0]['amount'])->toBe(10000.0);
    expect($rows[0]['receipt_no'])->toBe($world['alice']['payment']->receipt_no);
    expect($rows[0]['installment_no'])->toBe(1);
});

it('returns the customer own receipt', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/payments/') . $world['alice']['payment']->id);

    $response->assertStatus(200);
    expect((float) $response->json('data.amount'))->toBe(10000.0);
    expect($response->json('data.payment_method'))->toBe('cash');
});

/*
|--------------------------------------------------------------------------
| Documents, financial profile and notifications
|--------------------------------------------------------------------------
*/

it('returns the customer own documents and streams them', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/documents'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(1);
    expect($response->json('data.data.0.document_name'))->toBe('Alice NIC Copy');

    // The download route resolves the stored path through
    // FileUploadTrait::resolveStoredFile(), which CustomerDocumentController
    // names in a comment but never actually uses as a trait -- so the call is
    // to a method that does not exist on the class.
    $this->get($this->api('/my/documents/') . $world['alice']['document']->id . '/download')
        ->assertStatus(200);
});

it('returns the customer own declared means', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    expect($this->getJson($this->api('/my/guarantors'))->json('data.0.full_name'))->toBe('Alice Guarantor');
    expect((float) $this->getJson($this->api('/my/fixed-assets'))->json('data.0.market_value'))->toBe(1500000.0);
    expect((float) $this->getJson($this->api('/my/moving-assets'))->json('data.0.market_value'))->toBe(800000.0);
    expect((float) $this->getJson($this->api('/my/liabilities'))->json('data.0.outstanding_balance'))->toBe(120000.0);
    expect($this->getJson($this->api('/my/bank-details'))->json('data.0.bank_name'))->toBe('Alice Bank PLC');
});

it('masks the account number on the customer own bank details', function () {
    // The borrower already knows their account number; the portal showing it in
    // full only creates somewhere else for it to be read from.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/bank-details'));

    $response->assertStatus(200);
    expect($response->json('data.0.account_number_masked'))->not->toBe($world['alice']['bank']->account_number);
    expect(json_encode($response->json()))->not->toContain($world['alice']['bank']->account_number);
});

it('returns the customer own notifications', function () {
    $world = portalWorld();

    portalNotification($world['alice']['customer'], 'Alice second reminder');

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/notifications'));

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(2);
    expect(collect($response->json('data.data'))->pluck('subject')->all())
        ->toContain('Alice reminder', 'Alice second reminder');
});

it('filters notifications by type', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    expect($this->getJson($this->api('/my/notifications?type=payment_reminder'))->json('data.data'))->toHaveCount(1);
    expect($this->getJson($this->api('/my/notifications?type=loan_approved'))->json('data.data'))->toHaveCount(0);
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

it('reports the customer own dashboard totals', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/dashboard'));

    $response->assertStatus(200);

    expect($response->json('data.customer_name'))->toBe('Alice Portal');
    expect($response->json('data.active_loans_count'))->toBe(1);
    expect((float) $response->json('data.total_outstanding_amount'))->toBe(20000.0);

    // The next installment is the second month: the first is already settled.
    expect((float) $response->json('data.next_installment.amount'))->toBe(10000.0);
    expect($response->json('data.next_installment.loan_application_id'))->toBe($world['alice']['loan']->id);

    // One live loan and one application still in the pipeline. The pipeline one
    // is counted under the masked 'processing' bucket, not under 'submitted'.
    expect($response->json('data.loan_summary.total_loans'))->toBe(2);
    expect($response->json('data.loan_summary.active'))->toBe(1);
    expect($response->json('data.loan_summary.processing'))->toBe(1);

    expect((float) $response->json('data.overdue_amount'))->toBe(0.0);
});

it('does not count another customer loan in the dashboard totals', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/dashboard'));

    $response->assertStatus(200);

    // Bob's loan is 50,000 with 25,000 outstanding. Either figure appearing
    // here would mean the dashboard is summing the whole book.
    expect((float) $response->json('data.total_outstanding_amount'))->toBe(20000.0);
    expect($response->json('data.loan_summary.total_loans'))->toBe(2);
    expect($response->json('data.customer_code'))->toBe($world['alice']['customer']->customer_code);
});

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/

it('returns the signed-in customer profile', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/profile'));

    $response->assertStatus(200);
    expect($response->json('data.full_name'))->toBe('Alice Portal');
    expect($response->json('data.customer_code'))->toBe($world['alice']['customer']->customer_code);
    expect($response->json('data.bank_details'))->toHaveCount(1);
});

it('updates the contact details the profile form allows', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->patchJson($this->api('/my/profile'), [
        'phone_primary'   => '0759999999',
        'phone_secondary' => '0758888888',
        'email'           => 'alice.moved@example.test',
        'address_line_1'  => '42 New Road',
        'address_line_2'  => 'Apartment 7',
        'landmark'        => 'Opposite the temple',
        'city'            => 'Kandy',
        'state'           => 'Central',
        'postal_code'     => '20000',
        'have_whatsapp'   => true,
        'whatsapp_number' => '0759999999',
    ])->assertStatus(200);

    $customer = $world['alice']['customer']->fresh();

    expect($customer->phone_primary)->toBe('0759999999');
    expect($customer->phone_secondary)->toBe('0758888888');
    expect($customer->email)->toBe('alice.moved@example.test');
    expect($customer->address_line_1)->toBe('42 New Road');
    expect($customer->city)->toBe('Kandy');
    expect($customer->postal_code)->toBe('20000');
    expect($customer->have_whatsapp)->toBeTrue();
});

it('rejects a malformed contact detail rather than storing it', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->assertValidationFailed(
        $this->patchJson($this->api('/my/profile'), ['email' => 'not-an-address']),
        ['email']
    );

    expect($world['alice']['customer']->fresh()->email)->toBe($world['alice']['customer']->email);
});

it('does not let the customer edit anything that is not a contact detail', function () {
    // Every one of these is fillable on the Customer model, so mass assignment
    // is not what stops them -- only the absence of a rule in
    // UpdateCustomerProfileRequest is, and update() writes whatever
    // validated() hands back. Each would be a real privilege escalation: moving
    // yourself to another branch changes who can see your file, credit_score
    // decides what you can borrow, is_active decides whether the account works
    // at all, and customer_code and id_number are the identity the whole
    // system keys off.
    $world = portalWorld();

    $customer = $world['alice']['customer'];
    $branch   = $this->organisationChain()['branch'];

    $before = [
        'branch_id'     => $customer->branch_id,
        'credit_score'  => $customer->credit_score,
        'is_active'     => $customer->is_active,
        'customer_code' => $customer->customer_code,
        'id_number'     => $customer->id_number,
    ];

    $this->actingAsApi($this->customerUser(['customer_id' => $customer->id]));

    $this->patchJson($this->api('/my/profile'), [
        'branch_id'     => $branch->id,
        'credit_score'  => 999,
        'is_active'     => false,
        'customer_code' => 'CUS-HACKED',
        'id_number'     => 'FORGED123456',
        // A legitimate field alongside them, so the request is one the endpoint
        // accepts rather than one it throws out wholesale.
        'phone_primary' => '0751111111',
    ])->assertStatus(200);

    $after = Customer::findOrFail($customer->id);

    expect($after->branch_id)->toBe($before['branch_id']);
    expect($after->credit_score)->toBe($before['credit_score']);
    expect($after->is_active)->toBe($before['is_active']);
    expect($after->customer_code)->toBe($before['customer_code']);
    expect($after->id_number)->toBe($before['id_number']);

    // And the contact field in the same request did land, which is what proves
    // the update ran at all rather than being rejected as a whole.
    expect($after->phone_primary)->toBe('0751111111');
});

it('does not let one customer profile update touch another customer', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->patchJson($this->api('/my/profile'), [
        'id'             => $world['bob']['customer']->id,
        'customer_id'    => $world['bob']['customer']->customer_id,
        'phone_primary'  => '0757777777',
    ])->assertStatus(200);

    expect($world['bob']['customer']->fresh()->phone_primary)->toBe('0770000000');
    expect($world['alice']['customer']->fresh()->phone_primary)->toBe('0757777777');
});
