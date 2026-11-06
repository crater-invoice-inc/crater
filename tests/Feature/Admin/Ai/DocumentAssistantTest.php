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
    )->create(['is_active' => true]);

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

test('estimate and invoice histories are isolated even with the same document id', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    // Force an estimate and an invoice to share the same numeric id: the
    // document type must stay the discriminator, never the id alone.
    $estimate = Estimate::factory()->create();
    $invoice = Invoice::factory()->create();

    $sharedId = max(Estimate::max('id'), Invoice::max('id')) + 1000;
    Estimate::where('id', $estimate->id)->update(['id' => $sharedId]);
    Invoice::where('id', $invoice->id)->update(['id' => $sharedId]);

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $sharedId,
        'action' => 'create_custom_line',
        'brief' => 'Brief sur le devis.',
    ])->assertOk();

    // The invoice sharing the id sees no estimate history.
    expect(getJson('api/v1/ai/conversations?document_type=invoice&document_id='.$sharedId)
        ->assertOk()
        ->json('data'))->toBeNull();

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'invoice',
        'document_id' => $sharedId,
        'action' => 'create_custom_line',
        'brief' => 'Brief sur la facture.',
    ])->assertOk();

    $estimateHistory = getJson('api/v1/ai/conversations?document_type=estimate&document_id='.$sharedId)
        ->assertOk()
        ->json('data');

    $invoiceHistory = getJson('api/v1/ai/conversations?document_type=invoice&document_id='.$sharedId)
        ->assertOk()
        ->json('data');

    // Each type only ever loads its own conversation.
    expect($estimateHistory['messages'])->toHaveCount(2)
        ->and($estimateHistory['messages'][0]['content'])->toBe('Brief sur le devis.')
        ->and($invoiceHistory['messages'])->toHaveCount(2)
        ->and($invoiceHistory['messages'][0]['content'])->toBe('Brief sur la facture.');
});

test('a draft key never resolves a conversation of another document type', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    // Draft conversation started on an estimate.
    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'draft_key' => 'draft-shared-1',
        'action' => 'create_custom_line',
        'brief' => 'Brief sur devis brouillon.',
    ])->assertOk();

    // The same draft key seen from an invoice: no history.
    expect(getJson('api/v1/ai/conversations?document_type=invoice&draft_key=draft-shared-1')
        ->assertOk()
        ->json('data'))->toBeNull();

    // An assistant call on an invoice with that key creates its own
    // conversation instead of reusing the estimate one.
    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'invoice',
        'draft_key' => 'draft-shared-1',
        'action' => 'create_custom_line',
        'brief' => 'Brief sur facture brouillon.',
    ])->assertOk();

    $conversations = AiConversation::where('draft_key', 'draft-shared-1')->get();

    expect($conversations)->toHaveCount(2)
        ->and($conversations->pluck('document_type')->sort()->values()->all())->toBe(['estimate', 'invoice']);

    // Each conversation holds only its own messages.
    $estimateDraft = AiConversation::findForDraft('estimate', 'draft-shared-1', $this->companyId);
    $invoiceDraft = AiConversation::findForDraft('invoice', 'draft-shared-1', $this->companyId);

    expect($estimateDraft->messages)->toHaveCount(2)
        ->and($estimateDraft->messages[0]->content)->toBe('Brief sur devis brouillon.')
        ->and($invoiceDraft->messages)->toHaveCount(2)
        ->and($invoiceDraft->messages[0]->content)->toBe('Brief sur facture brouillon.');
});

test('binding never attaches a draft conversation to another document type', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    // Draft conversation started on an estimate.
    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'draft_key' => 'draft-xtype-1',
        'action' => 'create_custom_line',
        'brief' => 'Brief sur devis brouillon.',
    ])->assertOk();

    // Trying to bind it onto an invoice: the draft does not exist for
    // that document type, nothing is bound.
    postJson('api/v1/ai/conversations/bind', [
        'draft_key' => 'draft-xtype-1',
        'document_type' => 'invoice',
        'document_id' => $this->invoice->id,
    ])->assertStatus(404);

    $conversation = AiConversation::findForDraft('estimate', 'draft-xtype-1', $this->companyId);

    expect($conversation)->not->toBeNull()
        ->and($conversation->document_id)->toBeNull()
        // The invoice still owns no conversation.
        ->and(AiConversation::findForDocument('invoice', $this->invoice->id, $this->companyId))->toBeNull();

    // Binding with the matching type works and keeps the document type.
    postJson('api/v1/ai/conversations/bind', [
        'draft_key' => 'draft-xtype-1',
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
    ])->assertOk();

    $bound = AiConversation::findForDocument('estimate', $this->estimateA->id, $this->companyId);

    expect($bound->document_type)->toBe('estimate')
        ->and($bound->document_id)->toBe($this->estimateA->id)
        ->and($bound->draft_key)->toBeNull()
        ->and($bound->messages)->toHaveCount(2);
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

test('system prompt carries the studio context, pricing rule and few-shot examples', function () {
    fakePerplexityProposal(proposalPayload($this->services));

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => $this->services->pluck('id')->all(),
        'brief' => 'Un clip et des photos svp.',
    ])->assertOk();

    Http::assertSent(function (Request $request) {
        if (! str_contains($request->url(), 'chat/completions')) {
            return false;
        }

        $system = collect($request->data()['messages'])->firstWhere('role', 'system')['content'];

        return str_contains($system, 'Saguenay')
            && str_contains($system, 'ONE valid JSON object')
            && str_contains($system, 'NEVER invent a price')
            && str_contains($system, 'SEPARATE')
            && str_contains($system, 'Example 1')
            && str_contains($system, 'Example 2')
            && str_contains($system, 'Example 3')
            && str_contains($system, 'Example 4')
            && str_contains($system, 'Hypothèse:');
    });
});

test('incomplete brief surfaces model warnings without an invented price', function () {
    // The model follows the contract: no reference price exists, so it
    // returns price 0 and explains it in warnings instead of inventing.
    fakePerplexityProposal([
        'summary' => 'Ajout d’une ligne personnalisée pour la location de drone.',
        'lines' => [[
            'line_index' => null,
            'item_id' => null,
            'name' => 'Prise de vues aériennes par drone – Chantier',
            'description' => 'Opération d’un drone avec pilote certifié pendant une journée.',
            'deliverables' => '- Séquences aériennes brutes',
            'delivery_delay' => 'Livraison sous 7 jours ouvrables.',
            'terms' => '- Vol soumis aux conditions météo.',
            'unit' => 'journée',
            'quantity' => 1,
            'price' => 0,
        ]],
        'warnings' => [
            'Aucun prix de référence disponible; le prix doit être saisi manuellement.',
        ],
    ]);

    $response = postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'create_custom_line',
        'brief' => 'Il nous faudrait de la location de drone sur le chantier.',
    ])->assertOk();

    expect($response->json('lines.0.price'))->toBe(0)
        ->and($response->json('lines.0.quantity'))->toBe(1)
        ->and($response->json('warnings'))->toHaveCount(1)
        ->and($response->json('warnings.0'))->toContain('saisi manuellement');
});

test('unknown service ids are rejected before calling the model', function () {
    Http::fake([
        '*/chat/completions' => Http::response(['choices' => []]),
    ]);

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [999999],
        'brief' => 'Un service inexistant.',
    ])->assertStatus(422);

    // The model was never called for an invalid catalog request.
    Http::assertNothingSent();
});

test('inactive service ids are rejected before calling the model', function () {
    Http::fake([
        '*/chat/completions' => Http::response(['choices' => []]),
    ]);

    $inactiveService = Item::factory()->create([
        'name' => 'Service désactivé',
        'is_active' => false,
    ]);

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'add_existing_services',
        'service_ids' => [$inactiveService->id],
        'brief' => 'Un service désactivé.',
    ])->assertStatus(422);

    Http::assertNothingSent();
});

test('inactive services never reach the model catalog context', function () {
    Item::factory()->create([
        'name' => 'Service désactivé secret',
        'is_active' => false,
    ]);

    fakePerplexityProposal(proposalPayload($this->services));

    postJson('api/v1/ai/document-assistant', [
        'document_type' => 'estimate',
        'document_id' => $this->estimateA->id,
        'action' => 'create_custom_line',
        'brief' => 'Un forfait sur mesure.',
    ])->assertOk();

    Http::assertSent(function (Request $request) {
        if (! str_contains($request->url(), 'chat/completions')) {
            return false;
        }

        // No message (system, history or context) may carry an inactive service.
        foreach ($request->data()['messages'] as $message) {
            if (str_contains($message['content'], 'Service désactivé secret')) {
                return false;
            }
        }

        return true;
    });
});
