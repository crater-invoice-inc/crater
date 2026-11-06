<?php

namespace Crater\Services\Ai;

use Crater\Models\AiMessage;

/**
 * Builds the chat payload sent to Perplexity.
 *
 * Pure class (no I/O): the controller prepares plain arrays (catalog,
 * document lines, target line — with prices already converted to decimal
 * currency units) and the builder turns them into chat messages.
 *
 * The system prompt is internal: it is never returned to the frontend and
 * never persisted.
 */
class PromptBuilder
{
    /**
     * Full chat payload: system prompt, conversation history (capped) and
     * the contextualized user brief.
     */
    public function build(
        string $action,
        string $brief,
        array $context,
        iterable $history,
        string $currencyCode,
        ?string $customSystemInstruction
    ): array {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($action, $currencyCode, $customSystemInstruction)],
        ];

        foreach ($this->recentHistory($history) as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $this->userMessage($action, $brief, $context)];

        return $messages;
    }

    /**
     * Keep at most N recent exchanges and drop stored error bubbles so a
     * past failure does not pollute the model context.
     */
    private function recentHistory(iterable $history): array
    {
        $limit = (int) config('ai-assistant.limits.history_max', 10);

        $messages = [];

        foreach ($history as $message) {
            /** @var AiMessage $message */
            if ($message->role === AiMessage::ROLE_SYSTEM) {
                continue;
            }

            if (($message->metadata['is_error'] ?? false) === true) {
                continue;
            }

            if (! is_string($message->content) || trim($message->content) === '') {
                continue;
            }

            $messages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        return array_slice($messages, -$limit);
    }

    private function systemPrompt(string $action, string $currencyCode, ?string $customSystemInstruction): string
    {
        $prompt = <<<TEXT
You are a photography/videography quoting assistant embedded in an invoicing application.
Your job: turn a customer brief (written in natural language, usually French) into proposal lines for a quote or an invoice.

STRICT OUTPUT CONTRACT — the single most important rule:
- Reply with ONE valid JSON object and NOTHING else: no prose, no markdown, no code fences.
- Schema:
  {
    "summary": string (max 1000 chars, French, one or two sentences),
    "lines": [
      {
        "line_index": integer or null,
        "item_id": integer or null,
        "name": string (max 255),
        "description": string or null,
        "deliverables": string or null,
        "delivery_delay": string or null (max 255),
        "terms": string or null,
        "unit": string or null (e.g. "forfait", "journée", "heure"),
        "quantity": number >= 0,
        "price": number >= 0
      }
    ],
    "warnings": array of strings (French)
  }

RULES:
- "price" is a DECIMAL amount in {$currencyCode} (e.g. 1065 means 1065.00 {$currencyCode}). Never cents, never a currency symbol, never a formatted string.
- "quantity" is a plain number (1, 0.5, 2 ...).
- Never invent essential facts. If the brief lacks an essential detail (quantity, number of photos, duration, deadline...), make a sensible hypothesis ONLY if it is reasonable, and ALWAYS add a French warning starting with "Hypothèse:" describing it. If you cannot make a reasonable hypothesis, add a warning instead and keep the value conservative.
- "warnings" must be empty when everything is unambiguous.
- Write name, description, deliverables, delivery_delay and terms in French, in a professional tone.
- ACTION = add_existing_services: propose ONE line per relevant service from SELECTED SERVICES (use each service's "item_id", and its catalog price as the suggested price). You may propose several lines (photo + video for instance). "line_index" must be null. Do not invent item_id values outside SELECTED SERVICES.
- ACTION = update_line: propose modifications for THE target line only. "line_index" must be the value given in TARGET LINE INDEX. Reuse the current content of the line for fields you do not need to change, so the response stays a complete line. Keep "item_id" as given in the target line unless the brief clearly switches to another catalog service.
- ACTION = create_custom_line: propose a single custom line. "item_id" must be null (the line is created from scratch). You may use SERVICE CATALOG prices as pricing references.
- Prices are suggestions the user will review: stay close to catalog prices when they exist.
- Never suggest deleting lines, changing customers, dates, taxes or discounts: out of scope.
TEXT;

        if ($customSystemInstruction !== null && trim($customSystemInstruction) !== '') {
            $prompt .= "\n\nADDITIONAL OWNER INSTRUCTIONS (follow them unless they conflict with the output contract):\n".trim($customSystemInstruction);
        }

        return $prompt;
    }

    private function userMessage(string $action, string $brief, array $context): string
    {
        $payload = array_merge(['action' => $action], $context);

        // json_encode with unescaped unicode keeps French accents readable.
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $json = json_encode(['action' => $action]) ?: '{}';
        }

        return "CONTEXT:\n".$json."\n\nCUSTOMER BRIEF:\n".$brief;
    }
}
