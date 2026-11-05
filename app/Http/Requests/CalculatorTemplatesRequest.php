<?php

namespace Crater\Http\Requests;

use Crater\Models\Calculator;
use Illuminate\Foundation\Http\FormRequest;

class CalculatorTemplatesRequest extends FormRequest
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
            'category' => [
                'required',
                'in:'.implode(',', Calculator::CATEGORIES),
            ],
            'unit_price' => [
                'nullable',
                'numeric',
            ],
        ];
    }
}
