<?php

/*
|--------------------------------------------------------------------------
| Customer portal: the joint co-applicant
|--------------------------------------------------------------------------
|
| A joint loan has one primary borrower on loan_applications.customer_id and
| the rest of the borrowers in loan_application_customers. Both are liable for
| the same money, so both must see the same loan.
|
| LoanApplication::scopeForCustomer() encodes exactly that: the primary column
| OR a row in the join table. CustomerLoanController asks through the scope.
| CustomerDashboardController and CustomerPaymentController do not -- they
| filter on customer_id alone. If that difference is real, a co-applicant can
| open the loan from the loans screen while their dashboard shows nothing owed
| and their payment history is empty, which is the worst of both: they are
| liable for a debt the portal tells them they do not have.
|
| These tests state what a co-applicant should see. A failure here is the
| controller, not the test.
|
*/

/**
 * A joint loan: `$primary` on the application row, `$joint` alongside them in
 * loan_application_customers.
 *
 * @return array<string, mixed>
 */
function portalJointLoanFor($primary, $joint): array
{
    $loan = portalLoan($primary, 20000, 4, 'JOINT');

    portalJoinAsCoApplicant($loan, $primary);
    portalJoinAsCoApplicant($loan, $joint);

    return [
        'loan'    => $loan,
        'payment' => \App\Models\Payment::where('loan_application_id', $loan->id)->firstOrFail(),
    ];
}

/*
|--------------------------------------------------------------------------
| What already works
|--------------------------------------------------------------------------
*/

it('lets a joint co-applicant open the loan', function () {
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    $fixture = portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    $this->getJson($this->api('/my/loans/') . $fixture['loan']->id)->assertStatus(200);
});

it('lists the joint loan for the co-applicant', function () {
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    $fixture = portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    $response = $this->getJson($this->api('/my/loans'));

    $response->assertStatus(200);
    expect(collect($response->json('data.data'))->pluck('id')->all())->toContain($fixture['loan']->id);
});

it('gives the joint co-applicant the schedule and the statement', function () {
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    $fixture = portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    $this->getJson($this->api('/my/loans/') . $fixture['loan']->id . '/installments')->assertStatus(200);
    $this->getJson($this->api('/my/loans/') . $fixture['loan']->id . '/statement')->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| The same loan, seen through the other two controllers
|--------------------------------------------------------------------------
*/

it('counts the joint loan in the co-applicant dashboard totals', function () {
    // 80,000 borrowed with one 20,000 month settled leaves 60,000 owing, and
    // the co-applicant owes it jointly. A dashboard reading zero is telling a
    // liable borrower they have no debt.
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    $response = $this->getJson($this->api('/my/dashboard'));

    $response->assertStatus(200);
    expect($response->json('data.active_loans_count'))->toBe(1);
    expect((float) $response->json('data.total_outstanding_amount'))->toBe(60000.0);
    expect($response->json('data.loan_summary.total_loans'))->toBe(1);
});

it('shows the joint loan next installment to the co-applicant', function () {
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    $fixture = portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    $response = $this->getJson($this->api('/my/dashboard'));

    $response->assertStatus(200);
    expect($response->json('data.next_installment'))->not->toBeNull();
    expect($response->json('data.next_installment.loan_application_id'))->toBe($fixture['loan']->id);
    expect((float) $response->json('data.next_installment.amount'))->toBe(20000.0);
});

it('lists the joint loan payments for the co-applicant', function () {
    // The co-applicant can open the loan and read its statement, which already
    // prints this payment. Their own payment history omitting it is not a
    // privacy boundary, just an inconsistency between two controllers.
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    $fixture = portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    $response = $this->getJson($this->api('/my/payments'));

    $response->assertStatus(200);
    expect(collect($response->json('data.data'))->pluck('id')->all())->toContain($fixture['payment']->id);
});

it('opens a joint loan receipt for the co-applicant', function () {
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    $fixture = portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    $this->getJson($this->api('/my/payments/') . $fixture['payment']->id)->assertStatus(200);
});

it('applies the loan filter to a joint loan for the co-applicant', function () {
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    $fixture = portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    $response = $this->getJson($this->api('/my/payments?loan_application_id=') . $fixture['loan']->id);

    $response->assertStatus(200);
    expect(collect($response->json('data.data'))->pluck('id')->all())->toContain($fixture['payment']->id);
});

/*
|--------------------------------------------------------------------------
| Being a co-applicant is not a way in
|--------------------------------------------------------------------------
*/

it('does not give a joint co-applicant the primary borrower personal file', function () {
    // Sharing a loan shares the loan. It does not share the other borrower's
    // guarantors, assets, documents or bank accounts, which are theirs alone.
    $primary = portalCustomer('Carol');
    $joint   = portalCustomer('Dave');

    portalJointLoanFor($primary, $joint);
    portalFinancials($primary, 'Carol');
    $primaryDocument = portalDocument($primary, 'Carol NIC Copy');

    $this->actingAsApi($this->customerUser(['customer_id' => $joint->id]));

    expect($this->getJson($this->api('/my/guarantors'))->json('data'))->toBe([]);
    expect($this->getJson($this->api('/my/fixed-assets'))->json('data'))->toBe([]);
    expect($this->getJson($this->api('/my/moving-assets'))->json('data'))->toBe([]);
    expect($this->getJson($this->api('/my/liabilities'))->json('data'))->toBe([]);
    expect($this->getJson($this->api('/my/bank-details'))->json('data'))->toBe([]);

    $this->getJson($this->api('/my/documents/') . $primaryDocument->id . '/download')->assertStatus(404);

    $profile = $this->getJson($this->api('/my/profile'));
    $profile->assertStatus(200);
    expect($profile->json('data.id_number'))->toBe($joint->id_number);
});

it('does not let an unrelated customer reach a joint loan', function () {
    // The join table is what widens access, so it is also what must not widen
    // it for anybody else: a customer who is on no row of it stays out.
    $primary  = portalCustomer('Carol');
    $joint    = portalCustomer('Dave');
    $stranger = portalCustomer('Erin');

    $fixture = portalJointLoanFor($primary, $joint);

    $this->actingAsApi($this->customerUser(['customer_id' => $stranger->id]));

    $this->getJson($this->api('/my/loans/') . $fixture['loan']->id)->assertStatus(404);
    $this->getJson($this->api('/my/loans/') . $fixture['loan']->id . '/statement')->assertStatus(404);
    $this->getJson($this->api('/my/payments/') . $fixture['payment']->id)->assertStatus(404);
    expect($this->getJson($this->api('/my/loans'))->json('data.data'))->toBe([]);
});
