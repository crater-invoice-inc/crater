<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI document assistant configuration
    |--------------------------------------------------------------------------
    |
    | Instance-wide configuration for the Perplexity-backed document
    | assistant. The API key itself is stored in the `settings` table
    | (instance scope for v1) and is never exposed through the API.
    |
    */

    'default_model' => env('AI_ASSISTANT_DEFAULT_MODEL', 'sonar'),

    'default_base_url' => env('AI_ASSISTANT_BASE_URL', 'https://api.perplexity.ai'),

    // Setting keys used in the `settings` table (instance scope in v1).
    // Keeping them centralized here makes a future per-company key
    // (CompanySetting) a drop-in change in AiSettingsRepository.
    'setting_keys' => [
        'api_key' => 'perplexity_api_key',
        'model' => 'perplexity_model',
        'base_url' => 'perplexity_base_url',
        'system_instruction' => 'perplexity_system_instruction',
    ],

    // Hard limits applied when building the model context and validating
    // its answers.
    'limits' => [
        'catalog_max' => 50,          // services sent to the model
        'history_max' => 10,          // previous messages sent to the model
        'document_lines_max' => 30,   // document lines snapshot
        'brief_max' => 8000,          // user brief, in characters
        'proposal_lines_max' => 20,   // lines in one proposal
        'warnings_max' => 10,
        'summary_max' => 1000,
        'name_max' => 255,
        'unit_max' => 64,
        'short_text_max' => 255,      // delivery_delay
        'long_text_max' => 65000,     // description / deliverables / terms
        'service_ids_max' => 50,
    ],

    'request' => [
        // Whole-request timeout (Laravel 8 has no separate connect timeout).
        'timeout' => 45,
    ],

    'rate_limit' => [
        'per_minute' => 10,
    ],
];
