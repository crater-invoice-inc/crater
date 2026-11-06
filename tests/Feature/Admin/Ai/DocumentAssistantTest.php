<?php

use Crater\Exceptions\AiAssistantException;
use Crater\Models\AiConversation;
use Crater\Models\AiMessage;
use Crater\Models\Estimate;
use Crater\Models\Invoice;
use Crater\Models\Item;
use Crater\Models\Setting;
use Crater\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
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

    Setting::setSetting('perplexity_api_key', 'pplx-test-key-0000');
    Setting::setSetting('perplexity_model', 'sonar');

    $this->services = Item::factory()->count(2)->sequence(
        ['name' => 'Production vidéo', 'price' => 106500, 'deliverables' => '- 10 clips finaux'],
        ['name' => 'Production photo', 'price' => 41500, 'deliverables' => '- 10 photos retouchées'],
    )->create();

    $this->estimateA = Estimate::factory()->create();
    $this->estimateB = Estimate::factory()->create();
    $this->invoice = Invoice::factory()->create();
});

function fakePerplexityProposal(array $proposal): void
{
    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => json_encode($proposal)]],
            ],
        ]),
    ]);
}

function proposalPayload($services): array
{
    return [
        'summary' => 'Proposition de deux lignes : production vidéo et production photo.',
        'lines' => [
            [
                'line_index' => null,
                'item_id' => $services[0]->id,
                'name' => 'Production vidéo – Glaces du Fjord',
                'description' => 'Tournage et montage.',
                'deliverables' => "- 10 clips finaux\n- Livraison par lien",
                'delivery_delay' => 'Livraison sous 10 jours ouvrables.',
                'terms' => '- 1 ronde de révisions incluse.',
                'unit' => 'forfait',
                'quantity' => 1,
                'price' => 1065,
            ],
            [
                'line_index' => null,
                'item_id' => $services[1]->id,
                'name' => 'Production photo – Glaces du Fjord',
                'description' => 'Photos aériennes et au sol.',
                'deliverables' => '- 10 photos retouchées',
                'delivery_delay' => 'Livraison sous 7 jours ouvrables.',
                'terms' => '- 1 ronde de révisions incluse.',
                'unit' => 'forfait',
                'quantity' => 1,
                'price' => 415,
            ],
        ],
        'warnings' => [
            'Hypothèse: le brief ne précise pas le nombre de photos.',
        ],
    ];
}

test('owner gets a structured proposal for add_existing_services', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    $response = postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => $this->services->pluck('id')->all(),
        'brief' => 'Le client veut un clip vidéo et des photos aériennes pour son produit de glaces.',
        'document_lines' => [
            ['name' => 'Frais de déplacement', 'quantity' => 1, 'price' => 15000, 'unit_name' => 'forfait'],
        ],
    ])
        ->assertOk()
        ->assertJsonStructure(['conversation_id', 'action', 'summary', 'lines', 'warnings']);

    $data = $response->json();

    expect($data['action'])->toBe('add_existing_services')
        ->and($data['lines'])->toHaveCount(2)
        // item_id is remapped to the real service of the company.
        ->and(in_array($data['lines'][0]['item_id'], $this->services->pluck('id')->all()))->toBeTrue()
        // Prices come back as integer cents.
        ->and($data['lines'][0]['price'])->toBe(106500)
        ->and($data['lines'][1]['price'])->toBe(41500)
        ->and($data['lines'][0]['line_index'])->toBeNull()
        ->and($data['warnings'][0])->toStartWith('Hypothèse:');

    // The API key must never leak into the response.
    expect($response->getContent())->not->toContain('pplx-test-key-0000');

    // Conversation and both messages are persisted.
    $conversation = AiConversation::find($data['conversation_id']);

    expect($conversation->document_id)->toBe($this->estimateA->id)
        ->and($conversation->messages)->toHaveCount(2);

    $assistantMessage = $conversation->messages->last();

    expect($assistantMessage->role)->toBe('assistant')
        ->and($assistantMessage->metadata['proposal']['lines'][0]['price'])->toBe(106500);
});

test('perplexity receives the configured model and a json only instruction', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => $this->services->pluck('id')->all(),
        'brief' => 'Clip vidéo + photos.',
    ])->assertOk();

    Http::assertSent(function (Request $request) {
        if (! str_contains($request->url(), 'chat/completions')) {
            return false;
        }

        $messages = $request->data()['messages'];

        return $request->hasHeader('Authorization', 'Bearer pplx-test-key-0000')
            && $request->data()['model'] === 'sonar'
            && $messages[0]['role'] === 'system'
            && str_contains($messages[0]['content'], 'ONE valid JSON object')
            && str_contains(end($messages)['content'], 'Clip vidéo');
    });
});

test('conversation history is isolated per document', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Un clip vidéo svp.',
    ])->assertOk();

    // Same estimate: history is there.
    $history = getJson('api/v1/ai/conversations?document_type=estimate&document_id='.$this->estimateA->id)
        ->assertOk()
        ->json('data');

    expect($history['messages'])->toHaveCount(2);

    // Another estimate: no shared history.
    expect(getJson('api/v1/ai/conversations?document_type=estimate&document_id='.$this->estimateB->id)
        ->assertOk()
        ->json('data'))->toBeNull();

    // Invoices have their own conversations too.
    expect(getJson('api/v1/ai/conversations?document_type=invoice&document_id='.$this->invoice->id)
        ->assertOk()
        ->json('data'))->toBeNull();
});

test('a saved document can only own one conversation', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    foreach (['premier brief', 'second brief'] as $brief) {
        postJson('api/v1/ai/document-assistant', [
            'document_type' => 'estimate',
            'document_id' => $this->estimateA->id,
            'action' => 'add_existing_services',
            'service_ids' => [$this->services[0]->id],
            'brief' => $brief,
        ])->assertOk();
    }

    expect(AiConversation::where('document_type', 'estimate')
        ->where('document_id', $this->estimateA->id)
        ->count())->toBe(1)
        ->and(AiMessage::count())->toBe(4);
});

test('update_line proposal is attached to the requested line index', function () {
    fakePerplexityProposal(array_merge(proposalPayload($this->services), [
        'lines' => [[
            'line_index' => 7, // The model got it wrong on purpose.
            'item_id' => null,
            'name' => 'Shooting photo – demi-journée',
            'description' => null,
            'deliverables' => null,
            'delivery_delay' => null,
            'terms' => null,
            'unit' => 'demi-journée',
            'quantity' => 0.5,
            'price' => 415,
        ]],
        'warnings' => [],
    ]));

    $response = postJson('api/v1/ai/document-assistant', [
        'document_type' => 'invoice',
        'document_id' => $this->invoice->id,
        'action' => 'update_line',
        'line_index' => 1,
        'line' => [
            'name' => 'Shooting photo',
            'quantity' => 1,
            'price' => 83000,
            'unit_name' => 'journée',
        ],
        'brief' => 'Le client ne veut qu’une demi-journée finalement.',
    ])->assertOk();

    $lines = $response->json('lines');

    expect($lines[0]['line_index'])->toBe(1)
        ->and($lines[0]['price'])->toBe(41500)
        // The mismatch is reported to the user instead of being silent.
        ->and($response->json('warnings'))->not->toBeEmpty();
});

test('create_custom_line works without any service', function () {
    fakePerplexityProposal([
        'summary' => 'Une ligne personnalisée.',
        'lines' => [[
            'line_index' => null,
            'item_id' => null,
            'name' => 'Forfait édition spéciale',
            'description' => null,
            'deliverables' => null,
            'delivery_delay' => null,
            'terms' => null,
            'unit' => 'forfait',
            'quantity' => 1,
            'price' => 250,
        ]],
        'warnings' => [],
    ]);

    $response = postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateB->id,
        'action' => 'create_custom_line',
        'brief' => 'Le client veut un forfait sur mesure d’édition.',
    ])->assertOk();

    expect($response->json('lines.0.item_id'))->toBeNull()
        ->and($response->json('lines.0.price'))->toBe(25000);
});

test('draft conversation is bound to the document on first save', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'draft_key' => 'draft-abc-123',
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Brief sur un devis pas encore enregistré.',
    ])->assertOk();

    expect(AiConversation::where('draft_key', 'draft-abc-123')->exists())->toBeTrue();

    postJson('api/v1/ai/conversations/bind', [
        'draft_key' => 'draft-abc-123',
        'document_type' => 'estimate',
        'document_id' => $this->estimateB->id,
    ])->assertOk();

    $conversation = AiConversation::findForDocument('estimate', $this->estimateB->id, $this->companyId);

    expect($conversation)->not->toBeNull()
        ->and($conversation->draft_key)->toBeNull()
        ->and($conversation->messages)->toHaveCount(2)
        // The draft shell is gone.
        ->and(AiConversation::where('draft_key', 'draft-abc-123')->exists())->toBeFalse();
});

test('binding a draft merges into an existing document conversation', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    // Existing conversation on the document.
    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateB->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Premier échange déjà enregistré.',
    ])->assertOk();

    // Draft conversation created before the document was saved.
    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'draft_key' => 'draft-merge-1',
        'action' => 'create_custom_line',
        'brief' => 'Échange en brouillon.',
    ])->assertOk();

    postJson('api/v1/ai/conversations/bind', [
        'draft_key' => 'draft-merge-1',
        'document_type' => 'estimate',
        'document_id' => $this->estimateB->id,
    ])->assertOk();

    $conversation = AiConversation::findForDocument('estimate', $this->estimateB->id, $this->companyId);

    expect($conversation->messages)->toHaveCount(4)
        ->and(AiConversation::count())->toBe(1);
});

test('invalid model answer returns a 502 with a french error and is traced', function () {
    Http::fake([
        '*/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => 'je refuse le JSON']],
            ],
        ]),
    ]);

    $response = postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Un clip vidéo.',
    ])->assertStatus(502);

    expect($response->json('error'))->toBe('La réponse de l’IA est invalide. Veuillez relancer la proposition.');

    $conversation = AiConversation::first();

    // User message + assistant error bubble, but no proposal stored.
    expect($conversation->messages)->toHaveCount(2)
        ->and($conversation->messages->last()->metadata['is_error'])->toBeTrue();
});

test('missing api key returns a clear french error', function () {
    Setting::setSetting('perplexity_api_key', '');

    fakePerplexityProposal(proposalPayload($this->services));

    $response = postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Un clip vidéo.',
    ])->assertStatus(400);

    expect($response->json('error'))->toContain('clé API Perplexity n’est pas configurée');
});

test('perplexity auth failure maps to a french error', function () {
    Http::fake([
        '*/chat/completions' => Http::response(['error' => ['message' => 'bad key']], 401),
    ]);

    $response = postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Un clip vidéo.',
    ])->assertStatus(502);

    expect($response->json('error'))->toBe('La clé API Perplexity a été refusée. Vérifiez qu’elle est valide.');
});

test('non owner cannot use the assistant', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    $admin = User::factory()->create(['role' => 'admin']);
    $admin->companies()->attach($this->companyId);
    Sanctum::actingAs($admin, ['*']);

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Un clip vidéo.',
    ])->assertStatus(403);

    expect(AiConversation::count())->toBe(0);
});

test('document of another company is not found', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    $otherCompany = \Crater\Models\Company::factory()->create();
    $otherUser = User::factory()->create(['role' => 'admin']);
    $otherUser->companies()->attach($otherCompany->id);

    $foreignEstimate = Estimate::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $foreignEstimate->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Tentative d’accès à un document d’une autre société.',
    ])->assertStatus(404);
});

test('oversized brief is rejected', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => str_repeat('a', 8001),
    ])->assertStatus(422);
});

test('assistant calls are rate limited per user', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    $payload = [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [$this->services[0]->id],
        'brief' => 'Un clip vidéo.',
    ];

    foreach (range(1, 10) as $i) {
        postJson('api/v1/ai/document-assistant', $payload)->assertOk();
    }

    $response = postJson('api/v1/ai/document-assistant', $payload);

    expect($response->status())->toBe(429)
        ->and($response->json('error'))->toBe('Trop de demandes à l’assistant IA. Veuillez patienter une minute.');
});
