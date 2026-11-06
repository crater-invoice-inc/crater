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
        'default_quantity' => 2.5,
    ]);

    $response = postJson('api/v1/items', $item)
        ->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'deliverables' => 'Full shooting day and edited rushes',
        'delivery_delay' => '2 weeks',
        'terms' => '50% deposit upfront',
        'default_quantity' => 2.5,
    ]);

    $data = $response->getData()->data;
    expect($data->deliverables)->toBe('Full shooting day and edited rushes')
        ->and($data->delivery_delay)->toBe('2 weeks')
        ->and($data->terms)->toBe('50% deposit upfront')
        ->and($data->default_quantity)->toEqual(2.5);
});

test('update item with service fields', function () {
    $item = Item::factory()->create();

    $payload = Item::factory()->raw([
        'deliverables' => 'Updated deliverables',
        'delivery_delay' => '3 days',
        'terms' => 'Updated terms',
        'default_quantity' => 4,
    ]);

    putJson("api/v1/items/{$item->id}", $payload)
        ->assertOk();

    $this->assertDatabaseHas('items', [
        'id' => $item->id,
        'deliverables' => 'Updated deliverables',
        'delivery_delay' => '3 days',
        'terms' => 'Updated terms',
        'default_quantity' => 4,
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

test('an existing service is inactive by default', function () {
    // No is_active attribute set: the row behaves like the pre-migration
    // services, which must land on the default (inactive) value.
    $item = Item::factory()->create();

    expect($item->refresh()->is_active)->toBeFalse();

    $response = getJson("api/v1/items/{$item->id}")
        ->assertOk();

    expect($response->getData()->data->is_active)->toBeFalse();
});

test('a new service is inactive by default', function () {
    $item = Item::factory()->raw();

    unset($item['is_active']);

    $response = postJson('api/v1/items', $item)
        ->assertOk();

    expect($response->getData()->data->is_active)->toBeFalse();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'is_active' => false,
    ]);
});

test('the items list keeps inactive services visible without the filter', function () {
    $active = Item::factory()->create(['is_active' => true, 'name' => 'Active service']);
    $inactive = Item::factory()->create(['is_active' => false, 'name' => 'Inactive service']);

    $names = collect(getJson('api/v1/items?limit=all')
        ->assertOk()
        ->getData()->data)->pluck('name');

    expect($names)->toContain('Active service')
        ->and($names)->toContain('Inactive service')
        ->and($active->is_active)->toBeTrue()
        ->and($inactive->is_active)->toBeFalse();
});

test('active services appear in the active filter', function () {
    Item::factory()->create(['is_active' => true, 'name' => 'Active service']);
    Item::factory()->create(['is_active' => false, 'name' => 'Inactive service']);

    $response = getJson('api/v1/items?active=1&limit=all')
        ->assertOk();

    $names = collect($response->getData()->data)->pluck('name');

    expect($names)->toContain('Active service')
        ->and($names)->not->toContain('Inactive service');
});

test('inactive services appear in the inactive filter', function () {
    Item::factory()->create(['is_active' => true, 'name' => 'Active service']);
    Item::factory()->create(['is_active' => false, 'name' => 'Inactive service']);

    $names = collect(getJson('api/v1/items?active=0&limit=all')
        ->assertOk()
        ->getData()->data)->pluck('name');

    expect($names)->toContain('Inactive service')
        ->and($names)->not->toContain('Active service');
});

test('is_active can be set on creation and updated', function () {
    $created = Item::factory()->raw(['is_active' => true]);

    $response = postJson('api/v1/items', $created)
        ->assertOk();

    expect($response->getData()->data->is_active)->toBeTrue();

    $this->assertDatabaseHas('items', [
        'name' => $created['name'],
        'is_active' => true,
    ]);

    putJson('api/v1/items/'.$response->getData()->data->id, array_merge($created, [
        'is_active' => false,
    ]))->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $created['name'],
        'is_active' => false,
    ]);
});
