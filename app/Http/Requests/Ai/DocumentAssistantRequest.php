<?php

namespace Crater\Http\Requests\Ai;

use Crater\Models\AiConversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentAssistantRequest extends FormRequest
{
    public const ACTION_ADD_EXISTING_SERVICES = 'add_existing_services';

    public const ACTION_UPDATE_LINE = 'update_line';

    public const ACTION_CREATE_CUSTOM_LINE = 'create_custom_line';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $limits = config('ai-assistant.limits');

        return [
            'document_type' => [
                'required',
                Rule::in(AiConversation::documentTypes()),
            ],
            // Exactly one of document_id / draft_key (checked in withValidator).
            'document_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'draft_key' => [
                'nullable',
                'string',
                'max:64',
            ],
            'action' => [
                'required',
                Rule::in([
                    self::ACTION_ADD_EXISTING_SERVICES,
                    self::ACTION_UPDATE_LINE,
                    self::ACTION_CREATE_CUSTOM_LINE,
                ]),
            ],
            'brief' => [
                'required',
                'string',
                'max:'.$limits['brief_max'],
            ],
            'service_ids' => [
                'required_if:action,'.self::ACTION_ADD_EXISTING_SERVICES,
                'array',
                'max:'.$limits['service_ids_max'],
            ],
            'service_ids.*' => [
                'integer',
            ],
            'line_index' => [
                'required_if:action,'.self::ACTION_UPDATE_LINE,
                'integer',
                'min:0',
                'max:'.($limits['document_lines_max'] - 1),
            ],
            // Current line content, sent by the client (draft documents are
            // not persisted yet, so the backend cannot load the line).
            'line' => [
                'required_if:action,'.self::ACTION_UPDATE_LINE,
                'array',
            ],
            'line.name' => [
                'nullable',
                'string',
            ],
            // Optional snapshot of the current document lines, used as model
            // context. Prices here are cents (Crater's internal storage).
            'document_lines' => [
                'nullable',
                'array',
                'max:'.$limits['document_lines_max'],
            ],
            'document_lines.*' => [
                'array',
            ],
        ];
    }

    public function messages()
    {
        return [
            'document_type.in' => __('Le type de document doit être « estimate » ou « invoice ».'),
            'action.in' => __('Action inconnue pour l’assistant IA.'),
            'brief.required' => __('Le brief client est obligatoire.'),
            'brief.max' => __('Le brief ne doit pas dépasser :max caractères.'),
            'service_ids.required_if' => __('Sélectionnez au moins un service.'),
            'line.required_if' => __('Le contenu de la ligne à modifier est obligatoire.'),
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasDocument = $this->filled('document_id');
            $hasDraft = $this->filled('draft_key');

            if ($hasDocument === $hasDraft) {
                $validator->errors()->add(
                    'document_id',
                    __('Fournissez soit document_id (document enregistré), soit draft_key (nouveau document).')
                );
            }
        });
    }
}
