<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Documents
|--------------------------------------------------------------------------
|
| This module holds the most sensitive material in the system: identity cards,
| bank statements, pay slips and employer letters, for borrowers and for their
| guarantors.
|
| Two things therefore matter more here than anywhere else. Where the bytes
| land, because a file under the web root is public whatever the API says about
| it. And who may ask for them, because a stored path that the client chose is
| a path the client can point anywhere.
|
*/

function documentCustomer(?Branch $branch = null): Customer
{
    return Customer::create([
        'full_name' => 'Doc Owner', 'name_with_initials' => 'D. Owner',
        'id_type' => 'nic', 'id_number' => 'DOC' . fake()->unique()->numerify('##########'),
        'address_line_1' => '1 Test Lane', 'country' => 'Sri Lanka',
        'date_of_birth' => '1990-01-01', 'phone_primary' => '0744125923',
        'have_whatsapp' => false, 'preferred_language' => 'en',
        'branch_id' => $branch?->id,
    ]);
}

/*
|--------------------------------------------------------------------------
| Gating
|--------------------------------------------------------------------------
*/

it('requires authentication on every document endpoint', function () {
    $this->getJson($this->api('/documents'))->assertStatus(401);
    $this->postJson($this->api('/documents'), [])->assertStatus(401);
    $this->getJson($this->api('/documents/applications'))->assertStatus(401);
});

it('refuses the document list to a signed-in user without the permission', function () {
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/documents'))->assertStatus(403);
});

it('refuses the applications feed to a signed-in user without the permission', function () {
    // documents/applications is a paginated dump of every loan application:
    // customer name, phone, email, product, branch and status. It appears in
    // none of the controller's permission lists, so it was readable by any
    // authenticated principal.
    $this->actingAsApi($this->userWithPermissions([]));

    $this->getJson($this->api('/documents/applications'))->assertStatus(403);
});

it('refuses the applications feed to a customer', function () {
    // A customer's portal token is a token for this API too. Nothing about the
    // back office should answer it.
    $this->actingAsApi($this->customerUser());

    $this->getJson($this->api('/documents/applications'))->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Where the bytes land
|--------------------------------------------------------------------------
*/

it('does not write uploaded documents under the web root', function () {
    // public/ is served directly by the web server, so anything written there
    // is downloadable by anyone who can guess or read the path -- no token, no
    // session, no permission. Identity documents cannot live there.
    $this->actingAsApi($this->userWithPermissions(['Document Create']));

    $customer = documentCustomer();

    $this->postJson($this->api('/documents'), [
        'customer_id'   => $customer->id,
        'document_name' => 'NIC Copy',
        'document_type' => 'nic_copy',
        'file'          => UploadedFile::fake()->create('nic.pdf', 40, 'application/pdf'),
    ])->assertStatus(201);

    $stored = Document::where('document_name', 'NIC Copy')->value('file_path');

    expect($stored)->not->toBeNull();
    expect(file_exists(public_path($stored)))->toBeFalse();
});

it('keeps the uploaded file readable through the application', function () {
    // Moving it out of the web root is only half the fix: the file still has to
    // be reachable by someone entitled to it.
    $this->actingAsApi($this->userWithPermissions(['Document Create']));

    $customer = documentCustomer();

    $this->postJson($this->api('/documents'), [
        'customer_id'   => $customer->id,
        'document_name' => 'Bank Statement',
        'document_type' => 'bank_statement',
        'file'          => UploadedFile::fake()->create('statement.pdf', 40, 'application/pdf'),
    ])->assertStatus(201);

    $stored = Document::where('document_name', 'Bank Statement')->value('file_path');

    expect(Storage::disk('local')->exists($stored))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| The stored path
|--------------------------------------------------------------------------
*/

it('refuses to take the stored path from the client', function () {
    // file_path is what every read, stream and delete resolves against. Letting
    // a caller choose it means choosing which file the server opens: pointed at
    // ../.env against a victim's customer_id, their own portal will stream the
    // application's secrets back to them.
    $this->actingAsApi($this->userWithPermissions(['Document Create']));

    $customer = documentCustomer();

    $response = $this->postJson($this->api('/documents'), [
        'customer_id'   => $customer->id,
        'document_name' => 'Traversal Attempt',
        'document_type' => 'nic_copy',
        'file_path'     => '../.env',
    ]);

    expect($response->status())->not->toBe(201);
    $this->assertDatabaseMissing('documents', ['file_path' => '../.env']);
});

it('requires an actual upload rather than a path', function () {
    $this->actingAsApi($this->userWithPermissions(['Document Create']));

    $customer = documentCustomer();

    $this->assertValidationFailed(
        $this->postJson($this->api('/documents'), [
            'customer_id'   => $customer->id,
            'document_name' => 'No File',
            'document_type' => 'nic_copy',
        ]),
        ['file']
    );
});

/*
|--------------------------------------------------------------------------
| Upload rules
|--------------------------------------------------------------------------
*/

it('rejects a file type that is not a document or an image', function () {
    $this->actingAsApi($this->userWithPermissions(['Document Create']));

    $customer = documentCustomer();

    $this->assertValidationFailed(
        $this->postJson($this->api('/documents'), [
            'customer_id'   => $customer->id,
            'document_name' => 'Payload',
            'document_type' => 'other',
            'file'          => UploadedFile::fake()->create('payload.php', 10, 'text/x-php'),
        ]),
        ['file']
    );
});

it('rejects a file over the size limit', function () {
    $this->actingAsApi($this->userWithPermissions(['Document Create']));

    $customer = documentCustomer();

    $this->assertValidationFailed(
        $this->postJson($this->api('/documents'), [
            'customer_id'   => $customer->id,
            'document_name' => 'Huge',
            'document_type' => 'other',
            // The rule caps at 10240 KB.
            'file'          => UploadedFile::fake()->create('huge.pdf', 10241, 'application/pdf'),
        ]),
        ['file']
    );
});

it('rejects a document type outside the known list', function () {
    $this->actingAsApi($this->userWithPermissions(['Document Create']));

    $customer = documentCustomer();

    $this->assertValidationFailed(
        $this->postJson($this->api('/documents'), [
            'customer_id'   => $customer->id,
            'document_name' => 'Odd',
            'document_type' => 'blackmail_material',
            'file'          => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ]),
        ['document_type']
    );
});

it('requires a document name', function () {
    $this->actingAsApi($this->userWithPermissions(['Document Create']));

    $this->assertValidationFailed(
        $this->postJson($this->api('/documents'), [
            'file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ]),
        ['document_name']
    );
});

/*
|--------------------------------------------------------------------------
| Reading one back
|--------------------------------------------------------------------------
*/

it('lists and shows documents for a holder of Document Index', function () {
    $this->actingAsApi($this->userWithPermissions(['Document Index', 'Document Create']));

    $customer = documentCustomer();

    $this->postJson($this->api('/documents'), [
        'customer_id'   => $customer->id,
        'document_name' => 'NIC Copy',
        'document_type' => 'nic_copy',
        'file'          => UploadedFile::fake()->create('nic.pdf', 20, 'application/pdf'),
    ])->assertStatus(201);

    $this->getJson($this->api('/documents'))->assertStatus(200);

    $id = Document::where('document_name', 'NIC Copy')->value('id');
    $this->getJson($this->api('/documents/') . $id)->assertStatus(200);
});

it('returns 404 for a document that does not exist', function () {
    $this->actingAsApi($this->userWithPermissions(['Document Index']));

    $this->getJson($this->api('/documents/999999'))->assertStatus(404);
});

it('does not expose a guarantor identity number in the document listing', function () {
    // Customer::SUMMARY_COLUMNS deliberately withholds id_number. The guarantor
    // select alongside it should not quietly put the same field back.
    $this->actingAsApi($this->userWithPermissions(['Document Index']));

    $response = $this->getJson($this->api('/documents'));

    $response->assertStatus(200);
    expect(json_encode($response->json()))->not->toContain('"id_number"');
});

/*
|--------------------------------------------------------------------------
| Branch confinement
|--------------------------------------------------------------------------
*/

it('does not let an officer open another branch document', function () {
    // index() confines documents to the officer's branch. show(), update() and
    // destroy() looked their row up by id alone, so the confinement stopped at
    // the listing and every document was one guessed id away.
    $chain = $this->organisationChain();

    $other = Branch::create([
        'name' => 'Kandy', 'code' => 'KDY-D1', 'address_line1' => '2 Hill St', 'city' => 'Kandy',
        'zone_id' => $chain['zonal']->id, 'region_id' => $chain['region']->id,
        'province_id' => $chain['province']->id, 'phone_primary' => '0812222222',
        'opening_date' => '2021-01-01', 'branch_type' => 'city', 'is_active' => true,
    ]);

    $foreignCustomer = documentCustomer($other);

    $document = Document::create([
        'customer_id'   => $foreignCustomer->id,
        'document_name' => 'Foreign NIC',
        'document_type' => 'nic_copy',
        'file_path'     => 'uploads/documents/foreign.pdf',
        'uploaded_at'   => now(),
    ]);

    $this->actingAsApi($this->officerAtBranch($chain['branch'], ['Document Index', 'Document Delete']));

    $this->getJson($this->api('/documents/') . $document->id)->assertStatus(404);
    $this->deleteJson($this->api('/documents/') . $document->id)->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Deleting
|--------------------------------------------------------------------------
*/

it('soft deletes a document and keeps its file', function () {
    // The row soft-deletes, so it can be restored. Destroying the bytes at the
    // same time would leave a restored row pointing at nothing.
    $this->actingAsApi($this->userWithPermissions(['Document Create', 'Document Delete']));

    $customer = documentCustomer();

    $this->postJson($this->api('/documents'), [
        'customer_id'   => $customer->id,
        'document_name' => 'Deletable',
        'document_type' => 'other',
        'file'          => UploadedFile::fake()->create('d.pdf', 20, 'application/pdf'),
    ])->assertStatus(201);

    $document = Document::where('document_name', 'Deletable')->first();
    $path = $document->file_path;

    $this->deleteJson($this->api('/documents/') . $document->id)->assertStatus(200);

    $this->assertSoftDeleted('documents', ['id' => $document->id]);
    expect(Storage::disk('local')->exists($path))->toBeTrue();
});
