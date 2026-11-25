<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'full_name' => 'required|string|min:3|max:255', // Nome completo com limite de caracteres
            'ccv' => 'required|digits:3', // Exatamente 3 dígitos para o CCV
            'card_number' => 'required|string|size:20', // Exatamente 16 dígitos
            'card_expiration' => 'required|date_format:m/y|after_or_equal:today', // Formato MM/YY e data futura ou atual
        ];
    }

    /**
     * Mensagens de validação personalizadas.
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'O nome completo é obrigatório.',
            'full_name.min' => 'O nome completo deve ter pelo menos 3 caracteres.',
            'full_name.max' => 'O nome completo não pode exceder 255 caracteres.',

            'ccv.required' => 'O CCV é obrigatório.',
            'ccv.digits' => 'O CCV deve conter exatamente 3 dígitos.',

            'card_number.required' => 'O número do cartão é obrigatório.',
            'card_number.size' => 'O número do cartão deve conter exatamente 16 dígitos.',
            'card_number.regex' => 'O número do cartão deve conter apenas números.',

            'card_expiration.required' => 'A data de expiração do cartão é obrigatória.',
            'card_expiration.date_format' => 'A data de expiração deve estar no formato MM/YY.',
            'card_expiration.after_or_equal' => 'A data de expiração deve ser atual ou no futuro.',
        ];
    }
}
