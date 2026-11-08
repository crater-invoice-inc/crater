<?php

use Crater\Http\Controllers\V1\Admin\Estimate\EstimatesController;
use Crater\Http\Controllers\V1\Admin\Estimate\SendEstimateController;
use Crater\Http\Requests\DeleteEstimatesRequest;
use Crater\Http\Requests\EstimatesRequest;
use Crater\Http\Requests\SendEstimatesRequest;
use Crater\Mail\SendEstimateMail;
use Crater\Models\Estimate;
use Crater\Models\EstimateItem;
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

test('get estimates', function () {
    $response = getJson('api/v1/estimates?page=1');

    $response->assertOk();
});

test('create estimate', function () {
    $estimate = Estimate::factory()->raw([
        'estimate_number' => 'EST-000006',
        'items' => [
            EstimateItem::factory()->raw(),
        ],
        'taxes' => [
            Tax::factory()->raw(),
        ],
    ]);

    postJson('api/v1/estimates', $estimate)
        ->assertStatus(201);

    $this->assertDatabaseHas('estimates', [
        'template_name' => $estimate['template_name'],
        'estimate_number' => $estimate['estimate_number'],
        'discount_type' => $estimate['discount_type'],
        'discount_val' => $estimate['discount_val'],
        'sub_total' => $estimate['sub_total'],
        'discount' => $estimate['discount'],
        'customer_id' => $estimate['customer_id'],
        'total' => $estimate['total'],
        'notes' => $estimate['notes'],
        'tax' => $estimate['tax'],
    ]);
});

test('store validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        EstimatesController::class,
        'store',
        EstimatesRequest::class
    );
});

test('update estimate', function () {
    $estimate = Estimate::factory()
        ->hasItems(1)
        ->hasTaxes(1)
        ->create([
            'estimate_date' => '1988-07-18',
            'expiry_date' => '1988-08-18',
        ]);

    $estimate2 = Estimate::factory()->raw([
        'items' => [
            EstimateItem::factory()->raw([
                'estimate_id' => $estimate->id
            ]),
        ],
        'taxes' => [
            Tax::factory()->raw([
                'tax_type_id' => $estimate->taxes[0]->tax_type_id,
            ]),
        ],
    ]);

    $response = putJson('api/v1/estimates/'.$estimate->id, $estimate2);

    $this->assertDatabaseHas('estimates', [
        'template_name' => $estimate2['template_name'],
        'estimate_number' => $estimate2['estimate_number'],
        'discount_type' => $estimate2['discount_type'],
        'discount_val' => $estimate2['discount_val'],
        'sub_total' => $estimate2['sub_total'],
        'discount' => $estimate2['discount'],
        'customer_id' => $estimate2['customer_id'],
        'total' => $estimate2['total'],
        'notes' => $estimate2['notes'],
        'tax' => $estimate2['tax'],
    ]);

    $this->assertDatabaseHas('estimate_items', [
        'estimate_id' => $estimate2['items'][0]['estimate_id'],
    ]);

    $response->assertStatus(200);
});

test('update validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        EstimatesController::class,
        'update',
        EstimatesRequest::class
    );
});

test('search estimates', function () {
    $filters = [
        'page' => 1,
        'limit' => 15,
        'search' => 'doe',
        'from_date' => '2020-07-18',
        'to_date' => '2020-07-20',
        'estimate_number' => '000003',
    ];

    $queryString = http_build_query($filters, '', '&');

    $response = getJson('api/v1/estimates?'.$queryString);

    $response->assertStatus(200);
});

test('send estimate using a form request', function () {
    $this->assertActionUsesFormRequest(
        SendEstimateController::class,
        '__invoke',
        SendEstimatesRequest::class
    );
});

test('send estimate to customer', function () {
    Mail::fake();

    $estimate = Estimate::factory()->create([
        'estimate_date' => '1988-07-18',
        'expiry_date' => '1988-08-18',
    ]);

    $data = [
        'subject' => 'test',
        'body' => 'test',
        'from' => 'john@example.com',
        'to' => 'doe@example.com',
    ];

    postJson("api/v1/estimates/{$estimate->id}/send", $data)
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    Mail::assertSent(SendEstimateMail::class);
});

test('estimate mark as accepted', function () {
    $estimate = Estimate::factory()->create([
        'estimate_date' => '1988-07-18',
        'expiry_date' => '1988-08-18',
    ]);

    $data = [
        'status' => Estimate::STATUS_ACCEPTED,
    ];

    $response = postJson("api/v1/estimates/{$estimate->id}/status", $data);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $estimate2 = Estimate::find($estimate->id);
    $this->assertEquals($estimate2->status, Estimate::STATUS_ACCEPTED);
});

test('estimate mark as rejected', function () {
    $estimate = Estimate::factory()->create([
        'estimate_date' => '1988-07-18',
        'expiry_date' => '1988-08-18',
    ]);

    $data = [
        'status' => Estimate::STATUS_REJECTED,
    ];

    $response = postJson("api/v1/estimates/{$estimate->id}/status", $data);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $estimate2 = Estimate::find($estimate->id);
    $this->assertEquals($estimate2->status, Estimate::STATUS_REJECTED);
});

test('create invoice from estimate', function () {
    $estimate = Estimate::factory()->create([
        'estimate_date' => '1988-07-18',
        'expiry_date' => '1988-08-18',
    ]);

    $response = postJson("api/v1/estimates/{$estimate->id}/convert-to-invoice")
        ->assertStatus(200);
});

test('delete multiple estimates using a form request', function () {
    $this->assertActionUsesFormRequest(
        EstimatesController::class,
        'delete',
        DeleteEstimatesRequest::class
    );
});

test('delete multiple estimates', function () {
    $estimates = Estimate::factory()
        ->count(3)
        ->create([
            'estimate_date' => '1988-07-18',
            'expiry_date' => '1988-08-18',
        ]);

    $ids = $estimates->pluck('id');

    $data = [
        'ids' => $ids,
    ];

    $response = postJson('api/v1/estimates/delete', $data);

    $response
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    foreach ($estimates as $estimate) {
        $this->assertDeleted($estimate);
    }
});

test('get estimate templates', function () {
    getJson('api/v1/estimates/templates')->assertStatus(200);
});

test('create estimate with tax per item', function () {
    $estimate = Estimate::factory()->raw([
        'estimate_number' => 'EST-000006',
        'tax_per_item' => 'YES',
        'items' => [
            EstimateItem::factory()->raw([
                'taxes' => [Tax::factory()->raw()],
            ]),
            EstimateItem::factory()->raw([
                'taxes' => [Tax::factory()->raw()],
            ]),
        ],
    ]);

    postJson('api/v1/estimates', $estimate)
        ->assertStatus(201);

    $this->assertDatabaseHas('estimates', [
        'template_name' => $estimate['template_name'],
        'estimate_number' => $estimate['estimate_number'],
        'discount_type' => $estimate['discount_type'],
        'discount_val' => $estimate['discount_val'],
        'sub_total' => $estimate['sub_total'],
        'discount' => $estimate['discount'],
        'customer_id' => $estimate['customer_id'],
        'total' => $estimate['total'],
        'notes' => $estimate['notes'],
        'tax' => $estimate['tax'],
    ]);

    $this->assertDatabaseHas('estimate_items', [
        'name' => $estimate['items'][0]['name'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'tax_type_id' => $estimate['items'][0]['taxes'][0]['tax_type_id']
    ]);
});

test('create estimate with EUR currency', function () {
    $estimate = Estimate::factory()
        ->raw([
            'discount_type' => 'fixed',
            'discount_val' => 20,
            'sub_total' => 200,
            'total' => 189,
            'tax' => 9,
            'exchange_rate' => 86.403538,
            'base_discount_val' => 1728.07,
            'base_sub_total' => 17280.71,
            'base_total' => 16330.27,
            'base_tax' => 777.63,
            'taxes' => [Tax::factory()->raw([
                'amount' => 9,
                'percent' => 5,
                'exchange_rate' => 86.403538,
                'base_amount' => 777.63,
            ])],
            'items' => [EstimateItem::factory()->raw([
                'discount_type' => 'fixed',
                'quantity' => 1,
                'discount' => 0,
                'discount_val' => 0,
                'price' => 200,
                'tax' => 0,
                'total' => 200,
                'exchange_rate' => 86.403538,
                'base_discount_val' => 0,
                'base_price' => 17280.71,
                'base_tax' => 777.63,
                'base_total' => 17280.71,
            ])],
        ]);

    $response = postJson('api/v1/estimates', $estimate)->assertStatus(201);

    $this->assertDatabaseHas('estimates', [
        'template_name' => $estimate['template_name'],
        'estimate_number' => $estimate['estimate_number'],
        'discount_type' => $estimate['discount_type'],
        'discount_val' => $estimate['discount_val'],
        'sub_total' => $estimate['sub_total'],
        'discount' => $estimate['discount'],
        'customer_id' => $estimate['customer_id'],
        'total' => $estimate['total'],
        'notes' => $estimate['notes'],
        'tax' => $estimate['tax'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'tax_type_id' => $estimate['taxes'][0]['tax_type_id'],
        'amount' => $estimate['tax']
    ]);

    $this->assertDatabaseHas('estimate_items', [
        'item_id' => $estimate['items'][0]['item_id'],
        'name' => $estimate['items'][0]['name']
    ]);
});

test('update estimate with EUR currency', function () {
    $estimate = Estimate::factory()
        ->hasItems(1)
        ->hasTaxes(1)
        ->create([
            'estimate_date' => '1988-07-18',
            'expiry_date' => '1988-08-18',
        ]);

    $estimate2 = Estimate::factory()
        ->raw([
            'id' => $estimate->id,
            'discount_type' => 'fixed',
            'discount_val' => 20,
            'sub_total' => 200,
            'total' => 189,
            'tax' => 9,
            'exchange_rate' => 86.403538,
            'base_discount_val' => 1728.07076,
            'base_sub_total' => 17280.7076,
            'base_total' => 16330.268682,
            'base_tax' => 777.631842,
            'taxes' => [Tax::factory()->raw([
                'tax_type_id' => $estimate->taxes[0]->tax_type_id,
                'amount' => 9,
                'percent' => 5,
                'exchange_rate' => 86.403538,
                'base_amount' => 777.631842,
            ])],
            'items' => [EstimateItem::factory()->raw([
                'estimate_id' => $estimate->id,
                'discount_type' => 'fixed',
                'quantity' => 1,
                'discount' => 0,
                'discount_val' => 0,
                'price' => 200,
                'tax' => 0,
                'total' => 200,
                'exchange_rate' => 86.403538,
                'base_discount_val' => 0,
                'base_price' => 17280.7076,
                'base_tax' => 777.631842,
                'base_total' => 17280.7076,
            ])],
        ]);

    $response = putJson('api/v1/estimates/'.$estimate->id, $estimate2);

    $this->assertDatabaseHas('estimates', [
        'id' => $estimate['id'],
        'template_name' => $estimate2['template_name'],
        'estimate_number' => $estimate2['estimate_number'],
        'discount_type' => $estimate2['discount_type'],
        'discount_val' => $estimate2['discount_val'],
        'sub_total' => $estimate2['sub_total'],
        'discount' => $estimate2['discount'],
        'customer_id' => $estimate2['customer_id'],
        'total' => $estimate2['total'],
        'tax' => $estimate2['tax'],
        'exchange_rate' => $estimate2['exchange_rate'],
        'base_discount_val' => $estimate2['base_discount_val'],
        'base_sub_total' => $estimate2['base_sub_total'],
        'base_total' => $estimate2['base_total'],
        'base_tax' => $estimate2['base_tax'],
    ]);

    $this->assertDatabaseHas('estimate_items', [
        'estimate_id' => $estimate2['items'][0]['estimate_id'],
        'exchange_rate' => $estimate2['items'][0]['exchange_rate'],
        'base_price' => $estimate2['items'][0]['base_price'],
        'base_discount_val' => $estimate2['items'][0]['base_discount_val'],
        'base_tax' => $estimate2['items'][0]['base_tax'],
        'base_total' => $estimate2['items'][0]['base_total'],
    ]);

    $response->assertStatus(200);
});

test('estimate pdf shows estimate level taxes', function () {
    $estimate = Estimate::factory()->create([
        'template_name' => 'estimate4',
        'tax_per_item' => 'NO',
    ]);

    $tax = $estimate->taxes()->create(
        Tax::factory()->raw(['amount' => 50, 'percent' => 5])
    );

    $response = get("/estimates/pdf/{$estimate->unique_hash}?preview=1");

    $response->assertOk();

    $html = $response->getContent();
    $this->assertStringContainsString($tax->name, $html);
    $this->assertStringContainsString('(5%)', $html);
});
