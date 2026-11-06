<?php

namespace Crater\Http\Controllers\V1\Admin\Ai;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\Ai\UpdateAiSettingsRequest;
use Crater\Services\Ai\AiAccess;
use Crater\Services\Ai\AiSettingsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI assistant settings (instance scope in v1).
 *
 * The stored API key is write-only: show() returns a masked hint at most,
 * never the key itself.
 */
class AiSettingsController extends Controller
{
    private AiSettingsRepository $settings;

    public function __construct(AiSettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    public function show(Request $request): JsonResponse
    {
        AiAccess::assertAuthorized($request);

        return response()->json($this->settings->publicPayload());
    }

    public function update(UpdateAiSettingsRequest $request): JsonResponse
    {
        AiAccess::assertAuthorized($request);

        $this->settings->update($request->validated());

        return response()->json(
            array_merge(['success' => true], $this->settings->publicPayload())
        );
    }
}
