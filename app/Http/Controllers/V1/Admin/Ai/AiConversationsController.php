<?php

namespace Crater\Http\Controllers\V1\Admin\Ai;

use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\Ai\BindConversationRequest;
use Crater\Http\Resources\AiConversationResource;
use Crater\Models\AiConversation;
use Crater\Models\AiMessage;
use Crater\Models\Estimate;
use Crater\Models\Invoice;
use Crater\Services\Ai\AiAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Conversation history, scoped to a single document.
 *
 * History is never shared: a conversation is keyed by company +
 * document_type + document_id (saved documents) or by draft_key
 * (not-yet-saved documents).
 */
class AiConversationsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        AiAccess::assertAuthorized($request);

        $data = $request->validate([
            'document_type' => 'required|in:estimate,invoice',
            'document_id' => 'nullable|integer|min:1',
            'draft_key' => 'nullable|string|max:64',
        ]);

        $companyId = (int) $request->header('company');

        if (! empty($data['document_id'])) {
            $this->assertDocumentExists($data['document_type'], (int) $data['document_id'], $companyId);

            $conversation = AiConversation::findForDocument(
                $data['document_type'],
                (int) $data['document_id'],
                $companyId
            );
        } elseif (! empty($data['draft_key'])) {
            $conversation = AiConversation::findForDraft(
                $data['document_type'],
                $data['draft_key'],
                $companyId
            );
        } else {
            abort(422, 'Fournissez document_id ou draft_key.');
        }

        if (! $conversation) {
            // No conversation yet: the panel opens on an empty state.
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => new AiConversationResource($conversation->load('messages')),
        ]);
    }

    /**
     * Attach a draft conversation (draft_key) to its freshly saved
     * document. If the document already owns a conversation, the draft
     * messages are merged into it so no history is lost.
     */
    public function bind(BindConversationRequest $request): JsonResponse
    {
        AiAccess::assertAuthorized($request);

        $companyId = (int) $request->header('company');
        $documentType = $request->input('document_type');
        $documentId = (int) $request->input('document_id');

        $this->assertDocumentExists($documentType, $documentId, $companyId);

        // The draft conversation is looked up with the document type as
        // part of the key: a draft key of the wrong type is simply not
        // found, so a bind can never move an estimate conversation onto
        // an invoice (or the other way around).
        $draft = AiConversation::findForDraft(
            $documentType,
            (string) $request->input('draft_key'),
            $companyId
        );

        if (! $draft) {
            abort(404, 'Conversation de brouillon introuvable.');
        }

        $existing = AiConversation::findForDocument($documentType, $documentId, $companyId);

        if ($existing && $existing->id === $draft->id) {
            return response()->json([
                'data' => new AiConversationResource($existing->load('messages')),
            ]);
        }

        if ($existing) {
            // Merge: move the draft messages into the existing conversation,
            // then drop the draft shell.
            AiMessage::where('ai_conversation_id', $draft->id)
                ->update(['ai_conversation_id' => $existing->id]);

            $draft->delete();

            return response()->json([
                'data' => new AiConversationResource($existing->fresh()->load('messages')),
            ]);
        }

        $draft->update([
            'document_id' => $documentId,
            'draft_key' => null,
        ]);

        return response()->json([
            'data' => new AiConversationResource($draft->fresh()->load('messages')),
        ]);
    }

    private function assertDocumentExists(string $documentType, int $documentId, int $companyId): void
    {
        $query = $documentType === 'estimate' ? Estimate::whereCompany() : Invoice::whereCompany();

        if (! $query->where('id', $documentId)->exists()) {
            abort(404, 'Document introuvable.');
        }
    }
}
