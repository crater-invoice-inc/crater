<?php

use Crater\Models\Setting;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->user = User::find(1);
    $this->companyId = $this->user->companies()->first()->id;

    $this->withHeaders(['company' => $this->companyId]);
    Sanctum::actingAs($this->user, ['*']);
});

test('defaults are exposed without any key stored', function () {
    getJson('api/v1/ai/settings')
        ->assertOk()
        ->assertJson([
            'model' => 'sonar',
            'api_key_configured' => false,
            'api_key_hint' => null,
        ]);
});

test('owner can save settings and key is never returned', function () {
    $response = postJson('api/v1/ai/settings', [
        'api_key' => 'pplx-secret-key-1234',
        'model' => 'sonar-pro',
        'base_url' => 'https://api.perplexity.ai',
        'system_instruction' => 'Toujours répondre en français.',
    ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'model' => 'sonar-pro',
            'api_key_configured' => true,
            'api_key_hint' => '••••1234',
        ]);

    // The full key must never appear in any API payload.
    expect($response->getContent())->not->toContain('pplx-secret-key-1234');

    // It IS stored server side (read back through the model, not the API).
    expect(Setting::getSetting('perplexity_api_key'))->toBe('pplx-secret-key-1234');
});

test('updating without a new api key keeps the stored one', function () {
    postJson('api/v1/ai/settings', [
        'api_key' => 'pplx-first-key-abcd',
        'model' => 'sonar',
    ])->assertOk();

    postJson('api/v1/ai/settings', [
        'api_key' => '',
        'model' => 'sonar-pro',
    ])->assertOk();

    expect(Setting::getSetting('perplexity_api_key'))->toBe('pplx-first-key-abcd')
        ->and(Setting::getSetting('perplexity_model'))->toBe('sonar-pro');
});

test('non owner admin role is forbidden', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $admin->companies()->attach($this->companyId);
    Sanctum::actingAs($admin, ['*']);

    getJson('api/v1/ai/settings')->assertStatus(403);

    postJson('api/v1/ai/settings', [
        'api_key' => 'pplx-should-not-save',
        'model' => 'sonar',
    ])->assertStatus(403);

    expect(Setting::getSetting('perplexity_api_key'))->toBeNull();
});

test('super admin role is allowed', function () {
    $superAdmin = User::factory()->create(['role' => 'super admin']);
    $superAdmin->companies()->attach($this->companyId);
    Sanctum::actingAs($superAdmin, ['*']);

    getJson('api/v1/ai/settings')->assertOk();
});

test('guest cannot access settings', function () {
    $this->app['auth']->forgetGuards();

    getJson('api/v1/ai/settings')->assertStatus(401);
});

test('invalid base url is rejected', function () {
    postJson('api/v1/ai/settings', [
        'model' => 'sonar',
        'base_url' => 'not-an-url',
    ])->assertStatus(422);
});
