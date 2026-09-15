<?php

/*
|--------------------------------------------------------------------------
| Customer portal: the ownership boundary
|--------------------------------------------------------------------------
|
| The portal has no permissions of its own. Every endpoint under /v1/my is open
| to every signed-in borrower, and the only thing separating one borrower's
| loans, receipts, identity documents and bank accounts from another's is the
| customer_id each controller filters on. That filter is the whole security
| model, so it is asserted here route by route rather than sampled.
|
| Two complete borrower files are built for every test. A single-customer
| fixture cannot tell a filter that works from a filter that matches nothing,
| and a "not listed" assertion against an empty database passes for the wrong
| reason -- so each test proves both halves: the other customer's row is absent
| AND the caller's own row is present.
|
*/

/**
 * The `id` of every row in a paginated portal listing.
 *
 * @return array<int, int>
 */
function portalListedIds($response): array
{
    return collect($response->json('data.data'))->pluck('id')->map(fn ($id) => (int) $id)->all();
}

/*
|--------------------------------------------------------------------------
| Loans
|--------------------------------------------------------------------------
*/

it('does not list another customer loan', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/loans'));

    $response->assertStatus(200);
    expect(portalListedIds($response))->toContain($world['alice']['loan']->id);
    expect(portalListedIds($response))->not->toContain($world['bob']['loan']->id);
});

it('does not open another customer loan', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->getJson($this->api('/my/loans/') . $world['bob']['loan']->id)->assertStatus(404);
    $this->getJson($this->api('/my/loans/') . $world['alice']['loan']->id)->assertStatus(200);
});

it('does not expose another customer repayment schedule', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->getJson($this->api('/my/loans/') . $world['bob']['loan']->id . '/installments')->assertStatus(404);
    $this->getJson($this->api('/my/loans/') . $world['alice']['loan']->id . '/installments')->assertStatus(200);
});

it('does not expose another customer revision history', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->getJson($this->api('/my/loans/') . $world['bob']['loan']->id . '/revisions')->assertStatus(404);
    $this->getJson($this->api('/my/loans/') . $world['alice']['loan']->id . '/revisions')->assertStatus(200);
});

it('does not expose another customer statement', function () {
    // The statement is the worst single leak in the portal if it goes wrong:
    // it bundles the borrower's name, identity number, address, phone, email,
    // every receipt and the whole schedule into one response.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->getJson($this->api('/my/loans/') . $world['bob']['loan']->id . '/statement')->assertStatus(404);

    $own = $this->getJson($this->api('/my/loans/') . $world['alice']['loan']->id . '/statement');

    $own->assertStatus(200);
    expect($own->json('data.customer.id_number'))->toBe($world['alice']['customer']->id_number);
    expect(json_encode($own->json()))->not->toContain($world['bob']['customer']->id_number);
});

/*
|--------------------------------------------------------------------------
| Loan applications
|--------------------------------------------------------------------------
*/

it('does not list another customer loan applications', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/loan-applications'));

    $response->assertStatus(200);

    $ids = portalListedIds($response);

    expect($ids)->toContain($world['alice']['pending']->id);
    expect($ids)->not->toContain($world['bob']['pending']->id);
    expect($ids)->not->toContain($world['bob']['loan']->id);
});

it('does not open another customer loan application', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->getJson($this->api('/my/loan-applications/') . $world['bob']['pending']->id)->assertStatus(404);
    $this->getJson($this->api('/my/loan-applications/') . $world['alice']['pending']->id)->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Payments
|--------------------------------------------------------------------------
*/

it('does not list another customer payments', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/payments'));

    $response->assertStatus(200);
    expect(portalListedIds($response))->toContain($world['alice']['payment']->id);
    expect(portalListedIds($response))->not->toContain($world['bob']['payment']->id);
});

it('does not open another customer payment receipt', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $this->getJson($this->api('/my/payments/') . $world['bob']['payment']->id)->assertStatus(404);
    $this->getJson($this->api('/my/payments/') . $world['alice']['payment']->id)->assertStatus(200);
});

it('does not let the loan filter reach another customer payments', function () {
    // loan_application_id is caller-supplied and goes straight into a where
    // clause. If the ownership condition were applied only when the filter is
    // absent -- or replaced by it -- then naming somebody else's loan id would
    // turn the listing into a window onto their receipts. Asking for a loan
    // that is not yours must narrow the result to nothing, never widen it.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/payments?loan_application_id=') . $world['bob']['loan']->id);

    $response->assertStatus(200);
    expect($response->json('data.data'))->toBe([]);
    expect(json_encode($response->json()))->not->toContain($world['bob']['payment']->receipt_no);
});

it('applies the loan filter to the customer own payments', function () {
    // The other half of the previous test: a filter that answers nothing for
    // everything would pass it while being useless.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/payments?loan_application_id=') . $world['alice']['loan']->id);

    $response->assertStatus(200);
    expect(portalListedIds($response))->toBe([$world['alice']['payment']->id]);
});

/*
|--------------------------------------------------------------------------
| Documents
|--------------------------------------------------------------------------
*/

it('does not list another customer documents', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/documents'));

    $response->assertStatus(200);
    expect(portalListedIds($response))->toContain($world['alice']['document']->id);
    expect(portalListedIds($response))->not->toContain($world['bob']['document']->id);
});

it('does not stream another customer document', function () {
    // The listing and the download resolve the row separately, so confinement
    // on one says nothing about the other -- and this route hands back bytes,
    // not a filtered projection of them.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $foreign = $this->getJson($this->api('/my/documents/') . $world['bob']['document']->id . '/download');
    $own     = $this->getJson($this->api('/my/documents/') . $world['alice']['document']->id . '/download');

    $foreign->assertStatus(404);
    expect($foreign->json('message'))->toBe('Document not found');

    // The control: the same route, asked for the borrower's own document,
    // actually serves it. Without this the 404 above would pass just as well
    // on a route that refuses everyone.
    //
    // Asserted on the status rather than the body, because a successful
    // download streams the file itself and has no JSON to read.
    $own->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| The declared-means file
|--------------------------------------------------------------------------
|
| Guarantors, assets, liabilities and bank accounts are not loan data: they are
| third parties' names and phone numbers and the borrower's own account
| numbers. Each is a flat list with no pagination, so the only filter is the
| relationship the controller reads them through.
|
*/

it('does not list another customer guarantors', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/guarantors'));

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$world['alice']['guarantor']->id]);
    expect(json_encode($response->json()))->not->toContain('Bob Guarantor');
});

it('does not list another customer fixed assets', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/fixed-assets'));

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$world['alice']['fixed']->id]);
    expect(json_encode($response->json()))->not->toContain('Bob Estate');
});

it('does not list another customer moving assets', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/moving-assets'));

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$world['alice']['moving']->id]);
    expect(json_encode($response->json()))->not->toContain('Bob Motors');
});

it('does not list another customer liabilities', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/liabilities'));

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$world['alice']['liability']->id]);
    expect(json_encode($response->json()))->not->toContain('Bob Bank PLC');
});

it('does not list another customer bank details', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/bank-details'));

    $response->assertStatus(200);
    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$world['alice']['bank']->id]);
    expect(json_encode($response->json()))->not->toContain('Bob Branch');
});

/*
|--------------------------------------------------------------------------
| Notifications and profile
|--------------------------------------------------------------------------
*/

it('does not list another customer notifications', function () {
    // Notifications quote balances, due dates and approval decisions in their
    // message body, so a leak here is a leak of the underlying loan.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/notifications'));

    $response->assertStatus(200);
    expect(portalListedIds($response))->toBe([$world['alice']['notification']->id]);
    expect(json_encode($response->json()))->not->toContain('Bob reminder');
});

it('returns only the signed-in customer profile', function () {
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $response = $this->getJson($this->api('/my/profile'));

    $response->assertStatus(200);
    expect($response->json('data.id_number'))->toBe($world['alice']['customer']->id_number);
    expect(json_encode($response->json()))->not->toContain($world['bob']['customer']->id_number);
});

/*
|--------------------------------------------------------------------------
| One sweep across the whole portal
|--------------------------------------------------------------------------
*/

it('never mentions the other customer anywhere in the portal', function () {
    // A belt-and-braces pass over every listing at once. The per-endpoint
    // tests above assert on ids, which catches a broken filter; this one
    // searches the raw bodies for the other borrower's identifying strings,
    // which also catches a leak through an eager-loaded relationship or a
    // shaped field that quietly carries somebody else's data along with the
    // right id.
    $world = portalWorld();

    $this->actingAsApi($this->customerUser(['customer_id' => $world['alice']['customer']->id]));

    $markers = [
        $world['bob']['customer']->id_number,
        $world['bob']['customer']->customer_code,
        $world['bob']['payment']->receipt_no,
        $world['bob']['bank']->account_number,
        'Bob Portal',
        'Bob Guarantor',
        'Bob reminder',
    ];

    $bodies = [];

    foreach ([
        '/my/dashboard', '/my/profile', '/my/loans', '/my/loan-applications',
        '/my/payments', '/my/documents', '/my/notifications', '/my/guarantors',
        '/my/fixed-assets', '/my/moving-assets', '/my/liabilities', '/my/bank-details',
    ] as $path) {
        $bodies[$path] = json_encode($this->getJson($this->api($path))->json());
    }

    $leaks = [];

    foreach ($bodies as $path => $body) {
        foreach ($markers as $marker) {
            if ($marker !== null && $marker !== '' && str_contains($body, (string) $marker)) {
                $leaks[] = "{$path} leaked {$marker}";
            }
        }
    }

    expect($leaks)->toBe([]);
});
