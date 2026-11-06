<?php

use Crater\Exceptions\AiAssistantException;
use Crater\Services\Ai\ProposalValidator;

beforeEach(function () {
    $this->validator = new ProposalValidator();
});

function validProposalJson(): string
{
    return json_encode([
        'summary' => 'Proposition de deux lignes : production vidéo et production photo.',
        'lines' => [
            [
                'line_index' => null,
                'item_id' => 12,
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
                'item_id' => 11,
                'name' => 'Production photo – Glaces du Fjord',
                'description' => null,
                'deliverables' => '- 10 photos retouchées',
                'delivery_delay' => null,
                'terms' => null,
                'unit' => null,
                'quantity' => 0.5,
                'price' => 415.5,
            ],
        ],
        'warnings' => [
            'Hypothèse: le brief ne précise pas le nombre de photos.',
        ],
    ]);
}

test('validates and normalizes a valid proposal', function () {
    $proposal = $this->validator->validate(validProposalJson(), 'add_existing_services', [
        'allowed_item_ids' => [11, 12],
    ]);

    expect($proposal['summary'])->toBe('Proposition de deux lignes : production vidéo et production photo.')
        ->and($proposal['lines'])->toHaveCount(2)
        ->and($proposal['lines'][0]['item_id'])->toBe(12)
        // Decimal currency units are converted to integer cents.
        ->and($proposal['lines'][0]['price'])->toBe(106500)
        ->and($proposal['lines'][1]['price'])->toBe(41550)
        ->and($proposal['lines'][1]['quantity'])->toBe(0.5)
        ->and($proposal['lines'][0]['line_index'])->toBeNull()
        ->and($proposal['warnings'])->toHaveCount(1);
});

test('accepts markdown fenced json', function () {
    $fenced = "```json\n".validProposalJson()."\n```";

    $proposal = $this->validator->validate($fenced, 'add_existing_services', [
        'allowed_item_ids' => [11, 12],
    ]);

    expect($proposal['lines'])->toHaveCount(2);
});

test('rejects invalid json with a french 502 error', function () {
    $this->validator->validate('Voici ma proposition en prose.', 'add_existing_services');
})->throws(AiAssistantException::class, 'La réponse de l’IA est invalide.', 502);

test('rejects unknown top level keys', function () {
    $payload = json_decode(validProposalJson(), true);
    $payload['ops'] = [['op' => 'delete_line']];

    $this->validator->validate(json_encode($payload), 'add_existing_services');
})->throws(AiAssistantException::class);

test('rejects an empty lines array', function () {
    $payload = json_decode(validProposalJson(), true);
    $payload['lines'] = [];

    $this->validator->validate(json_encode($payload), 'add_existing_services');
})->throws(AiAssistantException::class);

test('rejects a negative price', function () {
    $payload = json_decode(validProposalJson(), true);
    $payload['lines'][0]['price'] = -10;

    $this->validator->validate(json_encode($payload), 'add_existing_services');
})->throws(AiAssistantException::class);

test('nulls an item_id outside the allowed catalog and warns', function () {
    $payload = json_decode(validProposalJson(), true);
    $payload['lines'][0]['item_id'] = 999;

    $proposal = $this->validator->validate(json_encode($payload), 'add_existing_services', [
        'allowed_item_ids' => [11, 12],
    ]);

    expect($proposal['lines'][0]['item_id'])->toBeNull()
        ->and($proposal['warnings'])->toHaveCount(2)
        ->and($proposal['warnings'][1])->toContain('999');
});

test('forces the requested line index on update_line', function () {
    $payload = json_decode(validProposalJson(), true);
    $payload['lines'] = [$payload['lines'][0]];
    $payload['lines'][0]['line_index'] = 7;

    $proposal = $this->validator->validate(json_encode($payload), 'update_line', [
        'allowed_item_ids' => [11, 12],
        'requested_line_index' => 2,
    ]);

    expect($proposal['lines'][0]['line_index'])->toBe(2)
        ->and($proposal['warnings'])->toHaveCount(2)
        ->and($proposal['warnings'][1])->toContain('ligne 2');
});

test('forces line_index to null outside update_line', function () {
    $payload = json_decode(validProposalJson(), true);
    $payload['lines'] = [$payload['lines'][0]];
    $payload['lines'][0]['line_index'] = 3;

    $proposal = $this->validator->validate(json_encode($payload), 'create_custom_line', [
        'allowed_item_ids' => [],
    ]);

    expect($proposal['lines'][0]['line_index'])->toBeNull();
});

test('rejects more lines than the configured cap', function () {
    $payload = json_decode(validProposalJson(), true);
    $payload['lines'] = array_fill(0, 21, $payload['lines'][0]);

    $this->validator->validate(json_encode($payload), 'add_existing_services');
})->throws(AiAssistantException::class);

test('truncates an oversized summary', function () {
    $payload = json_decode(validProposalJson(), true);
    $payload['summary'] = str_repeat('a', 5000);

    $proposal = $this->validator->validate(json_encode($payload), 'add_existing_services', [
        'allowed_item_ids' => [11, 12],
    ]);

    expect(mb_strlen($proposal['summary']))->toBe(1000);
});
