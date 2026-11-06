<?php

namespace Crater\Http\Requests\Ai;

use Crater\Models\AiConversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BindConversationRequest extends FormRequest
{
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
        return [
            'draft_key' => [
                'required',
                'string',
                'max:64',
            ],
            'document_type' => [
                'required',
                Rule::in(AiConversation::documentTypes()),
            ],
            'document_id' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages()
    {
        return [
            'draft_key.required' => __('La clé de brouillon est obligatoire.'),
            'document_id.required' => __('L’identifiant du document est obligatoire.'),
        ];
    }
}
