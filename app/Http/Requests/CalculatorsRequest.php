<?php

namespace Crater\Http\Requests;

use Crater\Models\Calculator;
use Illuminate\Foundation\Http\FormRequest;

class CalculatorsRequest extends FormRequest
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
            'name' => [
                'required',
            ],
            'notes' => [
                'nullable',
            ],
            'calculator_items' => [
                'nullable',
                'array',
            ],
            'calculator_items.*.category' => [
                'required',
                'in:'.implode(',', Calculator::CATEGORIES),
            ],
            'calculator_items.*.type' => [
                'nullable',
                'in:role,task,cost',
            ],
            'calculator_items.*.item_name' => [
                'required',
            ],
            'calculator_items.*.description' => [
                'nullable',
            ],
            'calculator_items.*.unit_price' => [
                'required',
                'numeric',
            ],
            'calculator_items.*.quantity' => [
                'nullable',
                'numeric',
            ],
        ];
    }
}
