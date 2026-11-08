<?php

use Crater\Http\Controllers\V1\Admin\Settings\CompanyController;
use Crater\Http\Requests\CompanyRequest;
use Crater\Http\Requests\ProfileRequest;
use Crater\Models\Invoice;
use Crater\Models\InvoiceItem;
use Crater\Models\Tax;
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
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('get profile', function () {
    getJson('api/v1/me')
        ->assertOk();
});


test('update profile using a form request', function () {
    $this->assertActionUsesFormRequest(
        CompanyController::class,
        'updateProfile',
        ProfileRequest::class
    );
});

test('update profile', function () {
    $user = [
        'name' => 'John Doe',
        'password' => 'admin@123',
        'email' => 'admin@crater.in',
    ];

    $response = putJson('api/v1/me', $user);

    $response->assertOk();

    $this->assertDatabaseHas('users', [
        'name' => $user['name'],
        'email' => $user['email'],
    ]);
});

test('update company using a form request', function () {
    $this->assertActionUsesFormRequest(
        CompanyController::class,
        'updateCompany',
        CompanyRequest::class
    );
});

test('update company', function () {
    $company = [
        'name' => 'XYZ',
        'country_id' => 2,
        'state' => 'city',
        'city' => 'state',
        'address_street_1' => 'test1',
        'address_street_2' => 'test2',
        'phone' => '1234567890',
        'zip' => '112233',
        'address' => [
            'country_id' => 2
        ]
    ];

    putJson('api/v1/company', $company)
        ->assertOk();

    $this->assertDatabaseHas('companies', [
        'name' => $company['name'],
    ]);

    $this->assertDatabaseHas('addresses', [
        'country_id' => $company['country_id'],
    ]);
});

test('update settings', function () {
    $settings = [
        'currency' => 1,
        'time_zone' => 'Asia/Kolkata',
        'language' => 'en',
        'fiscal_year' => '1-12',
        'carbon_date_format' => 'Y/m/d',
        'moment_date_format' => 'YYYY/MM/DD',
        'notification_email' => 'noreply@crater.in',
        'notify_invoice_viewed' => 'YES',
        'notify_estimate_viewed' => 'YES',
        'tax_per_item' => 'YES',
        'discount_per_item' => 'YES',
    ];

    $response = postJson('/api/v1/company/settings', ['settings' => $settings]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    foreach ($settings as $key => $value) {
        $this->assertDatabaseHas('company_settings', [
            'option' => $key,
            'value' => $value,
        ]);
    }
});

test('update settings without currency setting', function () {
    $settings = [
        'notification_email' => 'noreply@crater.in',
    ];

    $response = postJson('/api/v1/company/settings', ['settings' => $settings]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    foreach ($settings as $key => $value) {
        $this->assertDatabaseHas('company_settings', [
            'option' => $key,
            'value' => $value,
        ]);
    }
});

test('update currency settings after company has currency and transactions is not allowed', function () {
    $settings = [
        'currency' => 1,
    ];

    $response = postJson('/api/v1/company/settings', ['settings' => $settings]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    Invoice::factory()
        ->raw([
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
        ]);

    $settings = [
        'currency' => 2,
    ];

    $response = postJson('/api/v1/company/settings', ['settings' => $settings]);

    $response->assertOK()
        ->assertJson([
            'success' => false,
            'message' => 'Cannot update company currency after transactions are created.'
        ]);


    $this->assertDatabaseHas('company_settings', [
        'option' => 'currency',
        'value' => 1,
    ]);
});

test('get notification email settings', function () {
    $data['settings'] = [
        'currency',
        'time_zone',
        'language',
        'fiscal_year',
        'carbon_date_format',
        'moment_date_format',
        'notification_email',
        'notify_invoice_viewed',
        'notify_estimate_viewed',
        'tax_per_item',
        'discount_per_item',
    ];

    $response = getJson('/api/v1/company/settings?'.http_build_query($data));

    $response->assertOk();
});

test('company settings accept null values and store empty strings', function () {
    $data = [
        'settings' => [
            'invoice_default_terms_and_conditions' => null,
            'invoice_default_payment_information' => null,
        ],
    ];

    postJson('api/v1/company/settings', $data)
        ->assertOk();

    $this->assertDatabaseHas('company_settings', [
        'company_id' => User::find(1)->companies()->first()->id,
        'option' => 'invoice_default_terms_and_conditions',
        'value' => '',
    ]);

    $this->assertDatabaseHas('company_settings', [
        'company_id' => User::find(1)->companies()->first()->id,
        'option' => 'invoice_default_payment_information',
        'value' => '',
    ]);
});
