<?php

namespace Crater\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStorePaymentsRequest extends FormRequest
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
            'ids' => [
                'required',
            ],
            'ids.*' => [
                'required',
                Rule::exists('invoices', 'id'),
            ],
            'payment_date' => [
                'nullable',
                'date',
            ],
            'payment_method_id' => [
                'nullable',
                Rule::exists('payment_methods', 'id'),
            ],
        ];
    }
}
