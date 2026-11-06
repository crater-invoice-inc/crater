<?php

namespace Crater\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiConversation extends Model
{
    use HasFactory;

    public const DOCUMENT_TYPE_ESTIMATE = 'estimate';

    public const DOCUMENT_TYPE_INVOICE = 'invoice';

    protected $table = 'ai_conversations';

    protected $guarded = ['id'];

    protected $casts = [
        'document_id' => 'integer',
        'draft_key' => 'string',
    ];

    public static function documentTypes(): array
    {
        return [
            self::DOCUMENT_TYPE_ESTIMATE,
            self::DOCUMENT_TYPE_INVOICE,
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(AiMessage::class, 'ai_conversation_id')->orderBy('created_at')->orderBy('id');
    }

    public function scopeWhereCompany($query, $company_id)
    {
        $query->where('ai_conversations.company_id', $company_id);
    }

    /**
     * Find the conversation attached to a saved document (one per document).
     */
    public static function findForDocument(string $documentType, int $documentId, int $companyId): ?self
    {
        return self::where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * Find the conversation attached to a not-yet-saved document, keyed by
     * a client generated draft key.
     */
    public static function findForDraft(string $draftKey, int $companyId): ?self
    {
        return self::where('draft_key', $draftKey)
            ->where('company_id', $companyId)
            ->first();
    }

    /**
     * Get (or create) the conversation for a request. Saved documents are
     * keyed by document id; drafts by draft key.
     */
    public static function resolveForRequest(
        string $documentType,
        ?int $documentId,
        ?string $draftKey,
        int $companyId,
        int $userId
    ): self {
        if ($documentId !== null) {
            $conversation = self::findForDocument($documentType, $documentId, $companyId);
        } else {
            $conversation = self::findForDraft($draftKey, $companyId);
        }

        if ($conversation) {
            return $conversation;
        }

        return self::create([
            'document_type' => $documentType,
            'document_id' => $documentId,
            'draft_key' => $draftKey,
            'company_id' => $companyId,
            'user_id' => $userId,
        ]);
    }
}
