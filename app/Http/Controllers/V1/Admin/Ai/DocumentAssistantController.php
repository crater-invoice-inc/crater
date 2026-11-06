<?php

namespace Crater\Http\Controllers\V1\Admin\Ai;

use Crater\Exceptions\AiAssistantException;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\Ai\DocumentAssistantRequest;
use Crater\Models\AiConversation;
use Crater\Models\AiMessage;
use Crater\Models\CompanySetting;
use Crater\Models\Currency;
use Crater\Models\Estimate;
use Crater\Models\Invoice;
use Crater\Models\Item;
use Crater\Services\Ai\AiAccess;
use Crater\Services\Ai\AiSettingsRepository;
use Crater\Services\Ai\PerplexityClient;
use Crater\Services\Ai\PromptBuilder;
use Crater\Services\Ai\ProposalValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * POST /api/v1/ai/document-assistant
 *
 * Turns a customer brief into a structured line proposal.
 *
 * Guarantees:
 *  - the AI never writes to estimates/invoices/items: the endpoint only
 *    persists the conversation and returns a proposal the user must
 *    review, adjust and apply from the regular save flow;
 *  - the Perplexity API key, the full system prompt and the raw model
 *    response are never exposed in the API answer;
 *  - prices cross the boundary as decimal currency units (model side) and
 *    are converted to integer cents once validated (Crater side).
 */
class DocumentAssistantController extends Controller
{
    private AiSettingsRepository $settings;

    private PerplexityClient $client;

    private PromptBuilder $promptBuilder;

    private ProposalValidator $proposalValidator;

    public function __construct(
        AiSettingsRepository $settings,
        PerplexityClient $client,
        PromptBuilder $promptBuilder,
        ProposalValidator $proposalValidator
    ) {
        $this->settings = $settings;
        $this->client = $client;
        $this->promptBuilder = $promptBuilder;
        $this->proposalValidator = $proposalValidator;
    }

    public function __invoke(DocumentAssistantRequest $request): JsonResponse
    {
        AiAccess::assertAuthorized($request);

        $companyId = (int) $request->header('company');
        $user = $request->user();
        $documentType = $request->input('document_type');
        $documentId = $request->filled('document_id') ? (int) $request->input('document_id') : null;
        $draftKey = $request->filled('draft_key') ? (string) $request->input('draft_key') : null;
        $action = $request->input('action');
        $brief = (string) $request->input('brief');

        if ($documentId !== null) {
            $this->assertDocumentExists($documentType, $documentId, $companyId);
        }

        $conversation = AiConversation::resolveForRequest(
            $documentType,
            $documentId,
            $draftKey,
            $companyId,
            (int) $user->id
        );

        // History is captured BEFORE the new user message is stored.
        $history = $conversation->messages()->get();

        $conversation->messages()->create([
            'role' => AiMessage::ROLE_USER,
            'content' => $brief,
            'metadata' => [
                'action' => $action,
                'service_ids' => $request->input('service_ids'),
                'line_index' => $request->filled('line_index') ? (int) $request->input('line_index') : null,
            ],
        ]);

        $currencyCode = $this->currencyCode($companyId);
        $services = $this->resolveServices($request, $companyId, $action);
        $context = [
            'currency' => $currencyCode,
            'service_catalog' => $services->values()->all(),
            'current_document_lines' => $this->documentLines($request),
        ];

        if ($action === DocumentAssistantRequest::ACTION_UPDATE_LINE) {
            $context['target_line_index'] = (int) $request->input('line_index');
            $context['target_line'] = $this->targetLine($request);
        }

        try {
            $messages = $this->promptBuilder->build(
                $action,
                $brief,
                $context,
                $history,
                $currencyCode,
                $this->settings->systemInstruction()
            );

            $rawContent = $this->client->chat($messages);

            $proposal = $this->proposalValidator->validate($rawContent, $action, [
                'allowed_item_ids' => $services->pluck('id')->all(),
                'requested_line_index' => $action === DocumentAssistantRequest::ACTION_UPDATE_LINE
                    ? (int) $request->input('line_index')
                    : null,
            ]);
        } catch (AiAssistantException $exception) {
            // Keep a visible trace of the failure in the panel history.
            $conversation->messages()->create([
                'role' => AiMessage::ROLE_ASSISTANT,
                'content' => $exception->getMessage(),
                'metadata' => [
                    'action' => $action,
                    'is_error' => true,
                ],
            ]);

            throw $exception;
        }

        $conversation->messages()->create([
            'role' => AiMessage::ROLE_ASSISTANT,
            'content' => $proposal['summary'],
            'metadata' => [
                'action' => $action,
                'proposal' => $proposal,
            ],
        ]);

        return response()->json([
            'conversation_id' => $conversation->id,
            'action' => $action,
            'summary' => $proposal['summary'],
            'lines' => $proposal['lines'],
            'warnings' => $proposal['warnings'],
        ]);
    }

    /**
     * Services used as model context. Inactive services are never exposed:
     * a selected-but-inactive id is treated as unknown.
     *
     * add_existing_services: the selected services only (every id must
     * belong to the company). Other actions: the whole catalog, capped.
     */
    private function resolveServices(DocumentAssistantRequest $request, int $companyId, string $action): Collection
    {
        $query = Item::where('items.company_id', $companyId)
            ->where('items.is_active', true)
            ->with('unit');

        if ($action === DocumentAssistantRequest::ACTION_ADD_EXISTING_SERVICES) {
            $serviceIds = array_map('intval', (array) $request->input('service_ids'));

            $items = $query->whereIn('items.id', $serviceIds)->get();

            if ($items->count() !== count(array_unique($serviceIds))) {
                abort(422, 'Un des services sélectionnés est introuvable ou désactivé.');
            }

            return $items->map(function (Item $item) {
                return $this->serviceContext($item);
            });
        }

        return $query
            ->orderBy('items.name')
            ->limit((int) config('ai-assistant.limits.catalog_max', 50))
            ->get()
            ->map(function (Item $item) {
                return $this->serviceContext($item);
            });
    }

    private function serviceContext(Item $item): array
    {
        return [
            'id' => (int) $item->id,
            'name' => (string) $item->name,
            'description' => $this->clip($item->description, 400),
            'deliverables' => $this->clip($item->deliverables, 400),
            'delivery_delay' => $this->clip($item->delivery_delay, 200),
            'terms' => $this->clip($item->terms, 400),
            'unit' => $item->unit->name ?? null,
            // Crater stores cents; the model works in decimal units.
            'price' => round(((int) $item->price) / 100, 2),
            'default_quantity' => $item->default_quantity !== null ? (float) $item->default_quantity : null,
        ];
    }

    /**
     * Snapshot of the current document lines (context only). The client
     * sends prices in cents, exactly like the document form stores them.
     */
    private function documentLines(DocumentAssistantRequest $request): array
    {
        $lines = (array) $request->input('document_lines', []);

        $lines = array_slice($lines, 0, (int) config('ai-assistant.limits.document_lines_max', 30));

        $normalized = [];

        foreach (array_values($lines) as $index => $line) {
            $normalized[] = [
                'line_index' => $index,
                'name' => $this->clip($line['name'] ?? null, 200),
                'quantity' => isset($line['quantity']) && is_numeric($line['quantity']) ? round((float) $line['quantity'], 2) : null,
                'price' => isset($line['price']) && is_numeric($line['price']) ? round(((float) $line['price']) / 100, 2) : null,
                'unit' => $this->clip($line['unit_name'] ?? ($line['unit'] ?? null), 64),
            ];
        }

        return $normalized;
    }

    private function targetLine(DocumentAssistantRequest $request): array
    {
        $line = (array) $request->input('line');

        return [
            'item_id' => isset($line['item_id']) && is_numeric($line['item_id']) ? (int) $line['item_id'] : null,
            'name' => $this->clip($line['name'] ?? null, 200),
            'description' => $this->clip($line['description'] ?? null, 400),
            'deliverables' => $this->clip($line['deliverables'] ?? null, 400),
            'delivery_delay' => $this->clip($line['delivery_delay'] ?? null, 200),
            'terms' => $this->clip($line['terms'] ?? null, 400),
            'unit' => $this->clip($line['unit_name'] ?? ($line['unit'] ?? null), 64),
            'quantity' => isset($line['quantity']) && is_numeric($line['quantity']) ? round((float) $line['quantity'], 2) : 1,
            'price' => isset($line['price']) && is_numeric($line['price']) ? round(((float) $line['price']) / 100, 2) : 0,
        ];
    }

    private function currencyCode(int $companyId): string
    {
        $currencyId = CompanySetting::getSetting('currency', $companyId);

        $currency = $currencyId ? Currency::find($currencyId) : null;

        return $currency->code ?? 'CAD';
    }

    private function assertDocumentExists(string $documentType, int $documentId, int $companyId): void
    {
        $query = $documentType === 'estimate' ? Estimate::whereCompany() : Invoice::whereCompany();

        if (! $query->where('id', $documentId)->exists()) {
            abort(404, 'Document introuvable.');
        }
    }

    private function clip($value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }
}
