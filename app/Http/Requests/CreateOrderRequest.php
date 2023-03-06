<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'products.*.product_id' => [
                'required',
                'int',
                'min:1',
                'exists:products,id',
            ],
            'products.*.quantity' => [
                'required',
                'int',
                'min:1',
            ],
        ];
    }
}
