<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'min:3', 'max:255'],
            'cardNumber' => ['required', 'regex:/^[0-9\s]{16,20}$/'],
            'ccv' => ['required', 'digits_between:3,4'],
            'cardExpiration' => ['required', 'date_format:m/Y', 'after_or_equal:today'],
        ];
    }

    /**
     * Mensagens de validação personalizadas.
     */
    public function messages(): array
    {
        return [
            'cardNumber.regex' => 'Invalid card number.',
            'ccv.digits_between' => 'The CVV must have 3 or 4 digits.',
            'cardExpiration.date_format' => 'Use o formato MM/AAAA.',
            'cardExpiration.after_or_equal' => 'The card has expired.',
        ];
    }
}
