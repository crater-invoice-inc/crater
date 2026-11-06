<?php

namespace Crater\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingsRequest extends FormRequest
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
            // An empty/omitted key keeps the stored one.
            'api_key' => [
                'nullable',
                'string',
                'max:255',
            ],
            'model' => [
                'required',
                'string',
                'max:100',
            ],
            'base_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            'system_instruction' => [
                'nullable',
                'string',
                'max:4000',
            ],
        ];
    }

    public function messages()
    {
        return [
            'model.required' => __('Le modèle Perplexity est obligatoire.'),
            'base_url.url' => __('L’URL de base doit être une URL valide.'),
        ];
    }
}
