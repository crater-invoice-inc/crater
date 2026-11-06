<?php

namespace Crater\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemsRequest extends FormRequest
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

    public function prepareForValidation()
    {
        // A service is inactive unless the request explicitly activates it.
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'required',
            ],
            'price' => [
                'required',
            ],
            'unit_id' => [
                'nullable',
            ],
            'description' => [
                'nullable',
            ],
            'deliverables' => [
                'nullable',
                'string',
            ],
            'delivery_delay' => [
                'nullable',
                'string',
                'max:255',
            ],
            'terms' => [
                'nullable',
                'string',
            ],
            'default_quantity' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
