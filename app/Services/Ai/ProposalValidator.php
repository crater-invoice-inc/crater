<?php

namespace Crater\Services\Ai;

use Crater\Exceptions\AiAssistantException;

/**
 * Validates and normalizes the JSON proposal returned by the model.
 *
 * The model output is untrusted input: everything is checked against a
 * strict schema, unknown fields are dropped, values are cast/truncated to
 * their storage limits and prices are converted from decimal currency
 * units to integer cents (Crater's internal representation).
 *
 * On any structural failure an AiAssistantException (502) is thrown with a
 * French user-facing message that never includes the raw model output.
 */
class ProposalValidator
{
    private const LINE_FIELDS = [
        'line_index',
        'item_id',
        'name',
        'description',
        'deliverables',
        'delivery_delay',
        'terms',
        'unit',
        'quantity',
        'price',
    ];

    /**
     * @param  array{allowed_item_ids?: int[], requested_line_index?: ?int}  $context
     * @return array{summary: string, lines: array, warnings: string[]}
     */
    public function validate(string $rawContent, string $action, array $context = []): array
    {
        $data = $this->decode($rawContent);

        $unknownTopLevel = array_diff(array_keys($data), ['summary', 'lines', 'warnings']);

        if ($unknownTopLevel !== []) {
            throw $this->invalid();
        }

        $summary = $this->normalizeSummary($data['summary'] ?? null);
        $warnings = $this->normalizeWarnings($data['warnings'] ?? []);
        $lines = $this->normalizeLines($data['lines'] ?? null, $action, $context, $warnings);

        return [
            'summary' => $summary,
            'lines' => $lines,
            'warnings' => array_slice($warnings, 0, $this->limit('warnings_max', 10)),
        ];
    }

    private function decode(string $rawContent): array
    {
        $content = trim($rawContent);

        // Tolerate models that wrap the JSON in markdown code fences.
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```[a-zA-Z]*\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', trim((string) $content));
        }

        $decoded = json_decode((string) $content, true);

        if (! is_array($decoded)) {
            throw $this->invalid();
        }

        return $decoded;
    }

    private function normalizeSummary($summary): string
    {
        if (! is_string($summary) || trim($summary) === '') {
            throw $this->invalid();
        }

        return mb_substr(trim($summary), 0, $this->limit('summary_max', 1000));
    }

    private function normalizeWarnings($warnings): array
    {
        $normalized = [];

        if ($warnings === null) {
            return [];
        }

        if (! is_array($warnings)) {
            throw $this->invalid();
        }

        foreach ($warnings as $warning) {
            if (! is_string($warning) || trim($warning) === '') {
                continue;
            }

            $normalized[] = mb_substr(trim($warning), 0, 500);
        }

        return $normalized;
    }

    private function normalizeLines($lines, string $action, array $context, array &$warnings): array
    {
        if (! is_array($lines) || $lines === []) {
            throw $this->invalid();
        }

        $max = $this->limit('proposal_lines_max', 20);

        if (count($lines) > $max) {
            throw $this->invalid();
        }

        $allowedItemIds = array_map('intval', $context['allowed_item_ids'] ?? []);
        $requestedLineIndex = $context['requested_line_index'] ?? null;

        $normalized = [];

        foreach ($lines as $line) {
            if (! is_array($line)) {
                throw $this->invalid();
            }

            $normalized[] = $this->normalizeLine(
                $line,
                $action,
                $allowedItemIds,
                $requestedLineIndex,
                $warnings
            );
        }

        return $normalized;
    }

    private function normalizeLine(
        array $line,
        string $action,
        array $allowedItemIds,
        ?int $requestedLineIndex,
        array &$warnings
    ): array {
        $name = $line['name'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            throw $this->invalid();
        }

        $quantity = $line['quantity'] ?? null;

        // A quoted line always carries a strictly positive quantity.
        if (! is_numeric($quantity) || (float) $quantity <= 0) {
            throw $this->invalid();
        }

        $price = $line['price'] ?? null;

        if (! is_numeric($price) || (float) $price < 0) {
            throw $this->invalid();
        }

        $itemId = null;

        if (isset($line['item_id']) && $line['item_id'] !== null) {
            if (! is_numeric($line['item_id'])) {
                throw $this->invalid();
            }

            $candidate = (int) $line['item_id'];

            if (in_array($candidate, $allowedItemIds, true)) {
                $itemId = $candidate;
            } else {
                $itemId = null;
                $warnings[] = 'Hypothèse: le service #'.$candidate.' proposé par l’IA n’appartient pas au catalogue fourni; la ligne sera libre.';
            }
        }

        $lineIndex = null;

        if ($action === 'update_line') {
            $lineIndex = $requestedLineIndex;

            if (isset($line['line_index'])
                && is_numeric($line['line_index'])
                && (int) $line['line_index'] !== $requestedLineIndex
            ) {
                $warnings[] = 'Hypothèse: l’IA visait la ligne '.$line['line_index'].'; la proposition a été rattachée à la ligne '.$requestedLineIndex.'.';
            }
        }

        return [
            'line_index' => $lineIndex,
            'item_id' => $itemId,
            'name' => mb_substr(trim($name), 0, $this->limit('name_max', 255)),
            'description' => $this->optionalText($line['description'] ?? null, $this->limit('long_text_max', 65000)),
            'deliverables' => $this->optionalText($line['deliverables'] ?? null, $this->limit('long_text_max', 65000)),
            'delivery_delay' => $this->optionalText($line['delivery_delay'] ?? null, $this->limit('short_text_max', 255)),
            'terms' => $this->optionalText($line['terms'] ?? null, $this->limit('long_text_max', 65000)),
            'unit' => $this->optionalText($line['unit'] ?? null, $this->limit('unit_max', 64)),
            // The model works in decimal currency units, Crater stores cents.
            'price' => (int) round(((float) $price) * 100),
            'quantity' => round((float) $quantity, 2),
        ];
    }

    private function optionalText($value, int $max): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return mb_substr(trim($value), 0, $max);
    }

    private function limit(string $key, int $default): int
    {
        return (int) config('ai-assistant.limits.'.$key, $default);
    }

    private function invalid(): AiAssistantException
    {
        return new AiAssistantException(
            'La réponse de l’IA est invalide. Veuillez relancer la proposition.',
            502
        );
    }
}
