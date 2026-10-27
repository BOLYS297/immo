<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
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
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:150'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['required', 'integer', 'exists:services,id'],
            'budget_range' => ['nullable', 'string', 'max:100'],
            'timeline' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,docx,png,jpg,jpeg,zip', 'max:5120'],
            'privacy_consent' => ['required', 'accepted'],
            'website_hp' => ['nullable', 'max:0'],
        ];
    }

    /**
     * Custom error messages in French
     */
    public function messages(): array
    {
        return [
            'last_name.required' => 'Le nom est obligatoire.',
            'email.required' => 'Une adresse e-mail valide est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'phone.required' => 'Le numéro de téléphone est obligatoire pour vous recontacter.',
            'services.required' => 'Veuillez sélectionner au moins un service pour votre projet.',
            'services.min' => 'Veuillez sélectionner au moins un service pour votre projet.',
            'description.required' => 'Veuillez décrire votre projet ou besoin.',
            'description.min' => 'La description de votre besoin doit contenir au moins 20 caractères.',
            'attachment.mimes' => 'Le fichier joint doit être au format PDF, DOCX, PNG, JPG ou ZIP.',
            'attachment.max' => 'La taille du fichier joint ne doit pas dépasser 5 Mo.',
            'privacy_consent.accepted' => 'Vous devez accepter le traitement de vos données pour que nous puissions traiter votre demande.',
            'website_hp.max' => 'Tentative de soumission automatisée bloquée.',
        ];
    }
}
