<?php

use Crater\Models\Estimate;
use Crater\Models\EstimateItem;
use Crater\Models\Item;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

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
    Sanctum::actingAs($user, ['*']);
});

test('create item with service fields', function () {
    $item = Item::factory()->raw([
        'deliverables' => 'Full shooting day and edited rushes',
        'delivery_delay' => '2 weeks',
        'terms' => '50% deposit upfront',
    ]);

    $response = postJson('api/v1/items', $item)
        ->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'deliverables' => 'Full shooting day and edited rushes',
        'delivery_delay' => '2 weeks',
        'terms' => '50% deposit upfront',
    ]);

    $data = $response->getData()->data;
    expect($data->deliverables)->toBe('Full shooting day and edited rushes')
        ->and($data->delivery_delay)->toBe('2 weeks')
        ->and($data->terms)->toBe('50% deposit upfront');
});

test('update item with service fields', function () {
    $item = Item::factory()->create();

    $payload = Item::factory()->raw([
        'deliverables' => 'Updated deliverables',
        'delivery_delay' => '3 days',
        'terms' => 'Updated terms',
    ]);

    putJson("api/v1/items/{$item->id}", $payload)
        ->assertOk();

    $this->assertDatabaseHas('items', [
        'id' => $item->id,
        'deliverables' => 'Updated deliverables',
        'delivery_delay' => '3 days',
        'terms' => 'Updated terms',
    ]);
});

test('create estimate with service fields on line items', function () {
    $estimate = Estimate::factory()->raw([
        'estimate_number' => 'EST-SF-001',
        'items' => [
            EstimateItem::factory()->raw([
                'deliverables' => 'Line deliverables',
                'delivery_delay' => '10 days',
                'terms' => 'Line terms',
            ]),
        ],
    ]);

    postJson('api/v1/estimates', $estimate)
        ->assertStatus(201);

    $this->assertDatabaseHas('estimate_items', [
        'deliverables' => 'Line deliverables',
        'delivery_delay' => '10 days',
        'terms' => 'Line terms',
    ]);

    $stored = Estimate::where('estimate_number', 'EST-SF-001')->first();

    $response = getJson("api/v1/estimates/{$stored->id}")
        ->assertOk();

    $line = $response->getData()->data->items[0];
    expect($line->deliverables)->toBe('Line deliverables')
        ->and($line->delivery_delay)->toBe('10 days')
        ->and($line->terms)->toBe('Line terms');
});

test('update estimate keeps service fields on line items', function () {
    $estimate = Estimate::factory()
        ->hasItems(1)
        ->create();

    $payload = Estimate::factory()->raw([
        'estimate_number' => 'EST-SF-002',
        'items' => [
            EstimateItem::factory()->raw([
                'deliverables' => 'Updated line deliverables',
                'delivery_delay' => '1 month',
                'terms' => 'Updated line terms',
            ]),
        ],
    ]);

    putJson("api/v1/estimates/{$estimate->id}", $payload)
        ->assertOk();

    $this->assertDatabaseHas('estimate_items', [
        'estimate_id' => $estimate->id,
        'deliverables' => 'Updated line deliverables',
        'delivery_delay' => '1 month',
        'terms' => 'Updated line terms',
    ]);
});

test('clone estimate preserves service fields on line items', function () {
    $estimate = Estimate::factory()
        ->hasItems(1, [
            'deliverables' => 'Cloned deliverables',
            'delivery_delay' => '48 hours',
            'terms' => 'Cloned terms',
        ])
        ->create();

    $response = postJson("api/v1/estimates/{$estimate->id}/clone")
        ->assertStatus(201);

    $cloneId = $response->getData()->data->id;

    $this->assertDatabaseHas('estimate_items', [
        'estimate_id' => $cloneId,
        'deliverables' => 'Cloned deliverables',
        'delivery_delay' => '48 hours',
        'terms' => 'Cloned terms',
    ]);
});

test('convert estimate to invoice preserves service fields on line items', function () {
    $estimate = Estimate::factory()
        ->hasItems(1, [
            'deliverables' => 'Converted deliverables',
            'delivery_delay' => '5 business days',
            'terms' => 'Converted terms',
        ])
        ->create();

    $response = postJson("api/v1/estimates/{$estimate->id}/convert-to-invoice")
        ->assertOk();

    $invoiceId = $response->getData()->data->id;

    $this->assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoiceId,
        'deliverables' => 'Converted deliverables',
        'delivery_delay' => '5 business days',
        'terms' => 'Converted terms',
    ]);
});
