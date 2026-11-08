<?php

use Crater\Http\Controllers\V1\Admin\Invoice\InvoicesController;
use Crater\Http\Requests\InvoicesRequest;
use Crater\Mail\SendInvoiceMail;
use Crater\Models\Invoice;
use Crater\Models\InvoiceItem;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('testGetInvoices', function () {
    $response = getJson('api/v1/invoices?page=1&type=OVERDUE&limit=20');

    $response->assertOk();
});

test('create invoice', function () {
    $invoice = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
        ]);

    $response = postJson('api/v1/invoices', $invoice);

    $response->assertOk();

    $this->assertDatabaseHas('invoices', [
        'template_name' => $invoice['template_name'],
        'invoice_number' => $invoice['invoice_number'],
        'sub_total' => $invoice['sub_total'],
        'discount' => $invoice['discount'],
        'customer_id' => $invoice['customer_id'],
        'total' => $invoice['total'],
        'tax' => $invoice['tax'],
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'item_id' => $invoice['items'][0]['item_id'],
        'name' => $invoice['items'][0]['name']
    ]);
});

test('create invoice as sent', function () {
    $invoice = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
        ]);

    $response = postJson('api/v1/invoices', $invoice);

    $response->assertOk();

    $this->assertDatabaseHas('invoices', [
        'invoice_number' => $invoice['invoice_number'],
        'sub_total' => $invoice['sub_total'],
        'total' => $invoice['total'],
        'tax' => $invoice['tax'],
        'discount' => $invoice['discount'],
        'customer_id' => $invoice['customer_id'],
        'template_name' => $invoice['template_name'],
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'item_id' => $invoice['items'][0]['item_id'],
        'name' => $invoice['items'][0]['name']
    ]);
});

test('store validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        InvoicesController::class,
        'store',
        InvoicesRequest::class
    );
});

test('update invoice', function () {
    $invoice = Invoice::factory()->create([
        'invoice_date' => '1988-07-18',
        'due_date' => '1988-08-18',
    ]);

    $invoice2 = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
        ]);

    putJson('api/v1/invoices/'.$invoice->id, $invoice2)->assertOk();

    $this->assertDatabaseHas('invoices', [
        'invoice_number' => $invoice2['invoice_number'],
        'sub_total' => $invoice2['sub_total'],
        'total' => $invoice2['total'],
        'tax' => $invoice2['tax'],
        'discount' => $invoice2['discount'],
        'customer_id' => $invoice2['customer_id'],
        'template_name' => $invoice2['template_name'],
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'item_id' => $invoice2['items'][0]['item_id'],
        'name' => $invoice2['items'][0]['name']
    ]);
});

test('update validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        InvoicesController::class,
        'update',
        InvoicesRequest::class
    );
});

test('send invoice to customer', function () {
    Mail::fake();

    $invoices = Invoice::factory()->create([
        'invoice_date' => '1988-07-18',
        'due_date' => '1988-08-18',
    ]);

    $data = [
        'from' => 'john@example.com',
        'to' => 'doe@example.com',
        'subject' => 'email subject',
        'body' => 'email body',
    ];

    $response = postJson('api/v1/invoices/'.$invoices->id.'/send', $data);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $invoice2 = Invoice::find($invoices->id);

    $this->assertEquals($invoice2->status, Invoice::STATUS_SENT);
    Mail::assertSent(SendInvoiceMail::class);
});

test('invoice mark as paid', function () {
    $invoice = Invoice::factory()->create([
        'invoice_date' => '1988-07-18',
        'due_date' => '1988-08-18',
    ]);

    $data = [
        'status' => Invoice::STATUS_COMPLETED,
    ];

    $response = postJson('api/v1/invoices/'.$invoice->id.'/status', $data);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertEquals(Invoice::find($invoice->id)->paid_status, Invoice::STATUS_PAID);
});

test('invoice mark as sent', function () {
    $invoice = Invoice::factory()->create([
        'invoice_date' => '1988-07-18',
        'due_date' => '1988-08-18',
    ]);

    $data = [
        'status' => Invoice::STATUS_SENT,
    ];

    $response = postJson('api/v1/invoices/'.$invoice->id.'/status', $data);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $this->assertEquals(Invoice::find($invoice->id)->status, Invoice::STATUS_SENT);
});

test('search invoices', function () {
    $filters = [
        'page' => 1,
        'limit' => 15,
        'search' => 'doe',
        'status' => Invoice::STATUS_DRAFT,
        'from_date' => '2019-01-20',
        'to_date' => '2019-01-27',
        'invoice_number' => '000012',
    ];

    $queryString = http_build_query($filters, '', '&');

    $response = getJson('api/v1/invoices?'.$queryString);

    $response->assertOk();
});

test('delete multiple invoices', function () {
    $invoices = Invoice::factory()->count(3)->create([
        'invoice_date' => '1988-07-18',
        'due_date' => '1988-08-18',
    ]);

    $ids = $invoices->pluck('id');

    $data = [
        'ids' => $ids,
    ];

    postJson('api/v1/invoices/delete', $data)
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    foreach ($invoices as $invoice) {
        $this->assertDeleted($invoice);
    }
});

test('clone invoice', function () {
    $invoices = Invoice::factory()->create([
        'invoice_date' => '1988-07-18',
        'due_date' => '1988-08-18',
    ]);

    postJson("api/v1/invoices/{$invoices->id}/clone")
        ->assertStatus(201);
});

test('create invoice with negative tax', function () {
    $invoice = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw([
                'percent' => -9.99
            ])],
            'items' => [InvoiceItem::factory()->raw()],
        ]);

    $response = postJson('api/v1/invoices', $invoice);

    $response->assertOk();

    $this->assertDatabaseHas('invoices', [
        'invoice_number' => $invoice['invoice_number'],
        'sub_total' => $invoice['sub_total'],
        'total' => $invoice['total'],
        'tax' => $invoice['tax'],
        'discount' => $invoice['discount'],
        'customer_id' => $invoice['customer_id'],
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'name' => $invoice['items'][0]['name'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'tax_type_id' => $invoice['taxes'][0]['tax_type_id']
    ]);
});

test('create invoice with tax per item', function () {
    $invoice = Invoice::factory()
        ->raw([
                'tax_per_item' => 'YES',
                'items' => [
                    InvoiceItem::factory()->raw([
                        'taxes' => [Tax::factory()->raw()],
                    ]),
                    InvoiceItem::factory()->raw([
                        'taxes' => [Tax::factory()->raw()],
                    ]),
                ],
            ]);

    $response = postJson('api/v1/invoices', $invoice);

    $response->assertOk();

    $this->assertDatabaseHas('invoices', [
        'invoice_number' => $invoice['invoice_number'],
        'sub_total' => $invoice['sub_total'],
        'total' => $invoice['total'],
        'tax' => $invoice['tax'],
        'discount' => $invoice['discount'],
        'customer_id' => $invoice['customer_id'],
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'name' => $invoice['items'][0]['name'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'tax_type_id' => $invoice['items'][0]['taxes'][0]['tax_type_id']
    ]);
});

test('create invoice with EUR currency', function () {
    $invoice = Invoice::factory()
        ->raw([
            'discount_type' => 'fixed',
            'discount_val' => 20,
            'sub_total' => 100,
            'total' => 84,
            'tax' => 4,
            'due_amount' => 84,
            'exchange_rate' => 86.403538,
            'base_discount_val' => 1728.07,
            'base_sub_total' => 8640.35,
            'base_total' => 7257.90,
            'base_tax' => 345.61,
            'base_due_amount' => 7257.90,
            'taxes' => [Tax::factory()->raw([
                'amount' => 4,
                'percent' => 5,
                'base_amount' => 345.61,
            ])],
            'items' => [InvoiceItem::factory()->raw([
                'discount_type' => 'fixed',
                'price' => 100,
                'quantity' => 1,
                'discount' => 0,
                'discount_val' => 0,
                'tax' => 0,
                'total' => 100,
                'base_price' => 8640.35,
                'exchange_rate' => 86.403538,
                'base_discount_val' => 0,
                'base_tax' => 0,
                'base_total' => 8640.35,
            ])],
        ]);

    $response = postJson('api/v1/invoices', $invoice)->assertOk();

    $this->assertDatabaseHas('invoices', [
        'template_name' => $invoice['template_name'],
        'invoice_number' => $invoice['invoice_number'],
        'sub_total' => $invoice['sub_total'],
        'discount' => $invoice['discount'],
        'customer_id' => $invoice['customer_id'],
        'total' => $invoice['total'],
        'tax' => $invoice['tax'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'tax_type_id' => $invoice['taxes'][0]['tax_type_id'],
        'amount' => $invoice['tax']
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'item_id' => $invoice['items'][0]['item_id'],
        'name' => $invoice['items'][0]['name']
    ]);
});

test('update invoice with EUR currency', function () {
    $invoice = Invoice::factory()
        ->hasItems(1)
        ->hasTaxes(1)
        ->create([
        'invoice_date' => '1988-07-18',
        'due_date' => '1988-08-18',
    ]);

    $invoice2 = Invoice::factory()
        ->raw([
            'id' => $invoice['id'],
            'discount_type' => 'fixed',
            'discount_val' => 20,
            'sub_total' => 100,
            'total' => 84,
            'tax' => 4,
            'due_amount' => 84,
            'exchange_rate' => 86.403538,
            'base_discount_val' => 1728.07,
            'base_sub_total' => 8640.35,
            'base_total' => 7257.897192,
            'base_tax' => 345.614152,
            'base_due_amount' => 7257.897192,
            'taxes' => [Tax::factory()->raw([
                'tax_type_id' => $invoice->taxes[0]->tax_type_id,
                'amount' => 4,
                'percent' => 5,
                'base_amount' => 345.614152,
            ])],
            'items' => [InvoiceItem::factory()->raw([
                'invoice_id' => $invoice->id,
                'discount_type' => 'fixed',
                'price' => 100,
                'quantity' => 1,
                'discount' => 0,
                'discount_val' => 0,
                'tax' => 0,
                'total' => 100,
                'base_price' => 8640.3538,
                'exchange_rate' => 86.403538,
                'base_discount_val' => 0,
                'base_tax' => 0,
                'base_total' => 8640.3538,
            ])],
        ]);

    putJson('api/v1/invoices/'.$invoice->id, $invoice2)->assertOk();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice['id'],
        'invoice_number' => $invoice2['invoice_number'],
        'sub_total' => $invoice2['sub_total'],
        'total' => $invoice2['total'],
        'tax' => $invoice2['tax'],
        'discount' => $invoice2['discount'],
        'customer_id' => $invoice2['customer_id'],
        'template_name' => $invoice2['template_name'],
        'exchange_rate' => $invoice2['exchange_rate'],
        'base_total' => $invoice2['base_total'],
    ]);

    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoice2['items'][0]['invoice_id'],
        'item_id' => $invoice2['items'][0]['item_id'],
        'name' => $invoice2['items'][0]['name'],
        'exchange_rate' => $invoice2['items'][0]['exchange_rate'],
        'base_price' => $invoice2['items'][0]['base_price'],
        'base_total' => $invoice2['items'][0]['base_total'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'amount' => $invoice2['taxes'][0]['amount'],
        'name' => $invoice2['taxes'][0]['name'],
        'base_amount' => $invoice2['taxes'][0]['base_amount'],
    ]);
});

test('create invoice with payment information and notes', function () {
    $invoice = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
            'notes' => 'Footer terms text',
            'payment_information' => 'Interac transfer: billing@example.com',
        ]);

    $response = postJson('api/v1/invoices', $invoice);

    $response->assertOk();

    $this->assertDatabaseHas('invoices', [
        'invoice_number' => $invoice['invoice_number'],
        'notes' => 'Footer terms text',
        'payment_information' => 'Interac transfer: billing@example.com',
    ]);

    $response->assertJsonPath('data.notes', 'Footer terms text');
    $response->assertJsonPath('data.payment_information', 'Interac transfer: billing@example.com');
});

test('create invoice without payment information', function () {
    $invoice = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
        ]);
    unset($invoice['payment_information']);

    $response = postJson('api/v1/invoices', $invoice);

    $response->assertOk();

    $this->assertDatabaseHas('invoices', [
        'invoice_number' => $invoice['invoice_number'],
        'payment_information' => null,
    ]);
});

test('payment information must be a string when present', function () {
    $invoice = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
            'payment_information' => ['not', 'a', 'string'],
        ]);

    postJson('api/v1/invoices', $invoice)
        ->assertStatus(422)
        ->assertJsonValidationErrors('payment_information');
});

test('update invoice payment information', function () {
    $invoice = Invoice::factory()->create([
        'payment_information' => null,
    ]);

    $invoice2 = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
            'payment_information' => 'Updated payment info',
        ]);

    putJson('api/v1/invoices/'.$invoice->id, $invoice2)->assertOk();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'payment_information' => 'Updated payment info',
    ]);

    $invoice3 = Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
            'payment_information' => null,
        ]);

    putJson('api/v1/invoices/'.$invoice->id, $invoice3)->assertOk();

    $this->assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'payment_information' => null,
    ]);
});

test('clone invoice copies payment information', function () {
    $invoice = Invoice::factory()->create([
        'payment_information' => 'Cloned payment info',
    ]);

    $response = postJson("api/v1/invoices/{$invoice->id}/clone")
        ->assertStatus(201);

    $this->assertDatabaseHas('invoices', [
        'payment_information' => 'Cloned payment info',
    ]);
});

test('invoice4 pdf renders for invoice without payment information', function () {
    $invoice = Invoice::factory()->create([
        'template_name' => 'invoice4',
        'payment_information' => null,
    ]);

    $response = get("/invoices/pdf/{$invoice->unique_hash}");

    $response->assertOk();
});

test('invoice4 pdf shows payment information only when filled', function () {
    $invoice = Invoice::factory()->create([
        'template_name' => 'invoice4',
        'payment_information' => 'Interac transfer: billing@example.com',
        'notes' => 'Some terms',
    ]);

    $emptyInvoice = Invoice::factory()->create([
        'template_name' => 'invoice4',
        'payment_information' => null,
        'notes' => 'Some terms',
    ]);

    $filled = get("/invoices/pdf/{$invoice->unique_hash}?preview=1");
    $filled->assertOk();
    $html = $filled->getContent();
    $this->assertStringContainsString('Interac transfer: billing@example.com', $html);
    $this->assertStringNotContainsString('pdf_payment_information', $html);
    $this->assertStringNotContainsString('pdf_invoice_terms_and_conditions', $html);

    $empty = get("/invoices/pdf/{$emptyInvoice->unique_hash}?preview=1");
    $empty->assertOk();
    $html = $empty->getContent();
    $this->assertStringNotContainsString('Interac transfer: billing@example.com', $html);
    $this->assertStringNotContainsString('pdf_payment_information', $html);
    $this->assertStringNotContainsString('pdf_invoice_terms_and_conditions', $html);
});
