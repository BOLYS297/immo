<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TranslatedInput;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Questions fréquentes, en français et en anglais, regroupées par catégorie. */
class FaqController extends Controller
{
    use TranslatedInput;

    public function index(): View
    {
        $faqs = Faq::orderByDesc('is_published')->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        return view('admin.faqs.create', ['faq' => new Faq(['is_published' => true, 'sort_order' => (Faq::max('sort_order') ?? -1) + 1]), 'categories' => $this->categories()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Faq::create($this->validated($request));

        return redirect()->route('admin.faqs.index')->with('success', 'Question ajoutée.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', ['faq' => $faq, 'categories' => $this->categories()]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        return redirect()->route('admin.faqs.index')->with('success', 'Question mise à jour.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('success', 'Question supprimée.');
    }

    /** Catégories françaises déjà utilisées : suggestions du champ (le bloc « FAQ » filtre sur ce nom). */
    private function categories(): array
    {
        return Faq::get()->map(fn (Faq $f) => $f->getTranslation('category', config('cms.default_locale'), false))->filter()->unique()->sort()->values()->all();
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'sort_order' => ['required', 'integer', 'between:0,999'],
            'is_published' => ['boolean'],
        ] + $this->translatedRules([
            'question' => ['string', 'max:255'],
            'answer' => ['string', 'max:5000'],
            'category' => ['string', 'max:80'],
        ], ['question', 'answer']), [], $this->translatedAttributes(['question' => 'question', 'answer' => 'réponse', 'category' => 'catégorie']));

        return [
            'sort_order' => $validated['sort_order'],
            'is_published' => $request->boolean('is_published'),
            'question' => $this->translations($validated, 'question'),
            'answer' => $this->translations($validated, 'answer'),
            'category' => $this->translations($validated, 'category'),
        ];
    }
}
