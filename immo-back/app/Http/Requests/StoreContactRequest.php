<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'website_hp' => ['nullable', 'max:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'Votre adresse e-mail est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'subject.required' => 'Le sujet de votre message est obligatoire.',
            'message.required' => 'Le message ne peut pas être vide.',
            'message.min' => 'Votre message doit contenir au moins 10 caractères.',
            'website_hp.max' => 'Tentative de soumission bloquée.',
        ];
    }
}
