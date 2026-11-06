<?php

namespace Crater\Services\Ai;

use Crater\Models\Setting;

/**
 * Resolves AI assistant settings.
 *
 * v1 stores everything in the instance-wide `settings` table. Swapping this
 * class internals to CompanySetting (per-company key) later requires no
 * change in its consumers.
 *
 * The API key is write-only by design: it is read server-side to call
 * Perplexity, never returned in full by any API response and never logged.
 */
class AiSettingsRepository
{
    public function apiKey(): ?string
    {
        $key = Setting::getSetting($this->key('api_key'));

        return is_string($key) && $key !== '' ? $key : null;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null;
    }

    /**
     * Opaque hint about the stored key (last 4 characters only), used by the
     * settings UI to show that a key is configured.
     */
    public function apiKeyHint(): ?string
    {
        $key = $this->apiKey();

        if ($key === null) {
            return null;
        }

        return str_repeat('•', 4).substr($key, -4);
    }

    public function model(): string
    {
        $model = Setting::getSetting($this->key('model'));

        return is_string($model) && $model !== ''
            ? $model
            : config('ai-assistant.default_model', 'sonar');
    }

    public function baseUrl(): string
    {
        $baseUrl = Setting::getSetting($this->key('base_url'));

        if (is_string($baseUrl) && $baseUrl !== '') {
            return rtrim($baseUrl, '/');
        }

        return rtrim(config('ai-assistant.default_base_url', 'https://api.perplexity.ai'), '/');
    }

    public function systemInstruction(): ?string
    {
        $instruction = Setting::getSetting($this->key('system_instruction'));

        return is_string($instruction) && trim($instruction) !== ''
            ? $instruction
            : null;
    }

    /**
     * Persist settings. An empty/absent api_key keeps the current one so the
     * UI can update the model without re-entering the secret.
     */
    public function update(array $data): void
    {
        $settings = [];

        if (array_key_exists('model', $data) && $data['model'] !== null) {
            $settings[$this->key('model')] = (string) $data['model'];
        }

        if (array_key_exists('base_url', $data)) {
            $settings[$this->key('base_url')] = $data['base_url'] !== null ? rtrim((string) $data['base_url'], '/') : '';
        }

        if (array_key_exists('system_instruction', $data)) {
            $settings[$this->key('system_instruction')] = $data['system_instruction'] !== null ? (string) $data['system_instruction'] : '';
        }

        if (array_key_exists('api_key', $data)
            && is_string($data['api_key'])
            && trim($data['api_key']) !== ''
        ) {
            $settings[$this->key('api_key')] = trim($data['api_key']);
        }

        if ($settings !== []) {
            Setting::setSettings($settings);
        }
    }

    /**
     * Public (safe) representation of the settings: never includes the key.
     */
    public function publicPayload(): array
    {
        return [
            'model' => $this->model(),
            'base_url' => $this->baseUrl(),
            'system_instruction' => $this->systemInstruction(),
            'api_key_configured' => $this->isConfigured(),
            'api_key_hint' => $this->apiKeyHint(),
        ];
    }

    private function key(string $name): string
    {
        return config("ai-assistant.setting_keys.{$name}");
    }
}
