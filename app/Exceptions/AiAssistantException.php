<?php

namespace Crater\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Domain exception for the AI document assistant.
 *
 * The message is always safe to display to the end user (French) and must
 * never contain the Perplexity API key, the full system prompt or the raw
 * model response.
 */
class AiAssistantException extends Exception
{
    protected int $status;

    public function __construct(string $message, int $status = 502)
    {
        parent::__construct($message);

        $this->status = $status;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => $this->getMessage(),
        ], $this->status);
    }
}
