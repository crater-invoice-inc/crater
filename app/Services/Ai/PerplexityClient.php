<?php

namespace Crater\Services\Ai;

use Crater\Exceptions\AiAssistantException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin server-side wrapper around the Perplexity chat completions API.
 *
 * Security rules enforced here:
 *  - the API key is only read from settings and sent as a bearer token;
 *  - nothing that could contain the key (request headers, full client
 *    exceptions) is ever logged;
 *  - every failure mode maps to a user-facing French message through
 *    AiAssistantException.
 */
class PerplexityClient
{
    private AiSettingsRepository $settings;

    public function __construct(AiSettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Send a chat completion request and return the raw content of the
     * first choice. The content is NOT trusted: it must go through
     * ProposalValidator before being stored or returned.
     */
    public function chat(array $messages): string
    {
        $apiKey = $this->settings->apiKey();

        if ($apiKey === null) {
            throw new AiAssistantException(
                'La clé API Perplexity n’est pas configurée. Renseignez-la dans les paramètres de l’assistant IA.',
                400
            );
        }

        try {
            // NB: connectTimeout() is only available from Laravel 9; on
            // Laravel 8 a single timeout() covers the whole request.
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout(config('ai-assistant.request.timeout', 45))
                ->post($this->endpoint(), [
                    'model' => $this->settings->model(),
                    'messages' => $messages,
                ]);
        } catch (ConnectionException $exception) {
            // The exception message never contains the request headers,
            // so it is safe to log as-is.
            Log::warning('AI assistant: Perplexity connection failed.', [
                'reason' => $exception->getMessage(),
            ]);

            throw new AiAssistantException(
                'Le service Perplexity n’a pas répondu à temps. Veuillez réessayer.',
                504
            );
        } catch (Throwable $exception) {
            Log::warning('AI assistant: unexpected Perplexity client failure.', [
                'exception' => get_class($exception),
            ]);

            throw new AiAssistantException(
                'Impossible de contacter le service Perplexity. Veuillez réessayer.',
                502
            );
        }

        $this->assertSuccessful($response->status());

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new AiAssistantException(
                'Le modèle n’a renvoyé aucune réponse exploitable. Veuillez réessayer.',
                502
            );
        }

        return $content;
    }

    private function endpoint(): string
    {
        return $this->settings->baseUrl().'/chat/completions';
    }

    private function assertSuccessful(int $status): void
    {
        if ($status === 401 || $status === 403) {
            throw new AiAssistantException(
                'La clé API Perplexity a été refusée. Vérifiez qu’elle est valide.',
                502
            );
        }

        if ($status === 429) {
            throw new AiAssistantException(
                'La limite d’appels Perplexity est atteinte. Veuillez réessayer dans quelques instants.',
                429
            );
        }

        if ($status >= 500) {
            throw new AiAssistantException(
                'Le service Perplexity rencontre un problème temporaire. Veuillez réessayer.',
                502
            );
        }

        if ($status >= 400) {
            throw new AiAssistantException(
                'La requête vers Perplexity a été refusée (erreur '.$status.'). Vérifiez le modèle configuré.',
                502
            );
        }
    }
}
