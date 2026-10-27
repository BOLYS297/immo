<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Avis clients du mur /recommandations du front, en français et en anglais. */
class TestimonialController extends Controller
{
    public function index(): View
    {
        $testimonials = Testimonial::orderBy('sort_order')->orderBy('id')->paginate(15);

        return view('admin.testimonials.index', compact('testimonials'));
    }

    public function create(): View
    {
        return view('admin.testimonials.create', ['testimonial' => new Testimonial(['rating' => 5, 'sort_order' => 0, 'is_published' => false])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Testimonial::create($this->validated($request));

        return redirect()->route('admin.testimonials.index')->with('success', 'Témoignage ajouté.');
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($this->validated($request));

        return redirect()->route('admin.testimonials.index')->with('success', 'Témoignage mis à jour.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return redirect()->route('admin.testimonials.index')->with('success', 'Témoignage supprimé.');
    }

    private function validated(Request $request): array
    {
        $default = config('cms.default_locale');
        $locales = array_keys(config('cms.locales'));
        $rules = [
            'client_name' => ['required', 'string', 'max:80'],
            'role' => ['nullable', 'string', 'max:80'],
            'company' => ['nullable', 'string', 'max:80'],
            'pole' => ['nullable', 'string', 'max:40'],
            'photo_id' => ['nullable', 'integer', 'exists:media,id'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
        ];
        foreach ($locales as $locale) {
            $rules["content.{$locale}"] = [$locale === $default ? 'required' : 'nullable', 'string', 'max:800'];
            $rules["metric.{$locale}"] = ['nullable', 'string', 'max:120'];
        }

        $validated = $request->validate($rules, [], ["content.{$default}" => 'citation (FR)']);

        // Toutes les langues, '' pour un champ vide : vider un champ efface bien l'ancienne valeur.
        $translations = fn (string $key) => collect($locales)->mapWithKeys(fn ($l) => [$l => trim((string) ($validated[$key][$l] ?? ''))])->all();
        $text = fn (string $key) => filled($validated[$key] ?? null) ? trim($validated[$key]) : null;

        return [
            'client_name' => trim($validated['client_name']),
            'role' => $text('role'),
            'company' => $text('company'),
            'pole' => $text('pole'),
            'photo_id' => $validated['photo_id'] ?? null,
            'content' => $translations('content'),
            'metric' => $translations('metric'),
            'rating' => $validated['rating'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
