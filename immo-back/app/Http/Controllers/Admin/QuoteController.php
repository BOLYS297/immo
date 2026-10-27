<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');
        $search = $request->query('q');

        $query = QuoteRequest::with(['prospect', 'services'])->latest();

        if ($statusFilter && in_array($statusFilter, [
            QuoteRequest::STATUS_NEW,
            QuoteRequest::STATUS_IN_PROGRESS,
            QuoteRequest::STATUS_SENT,
            QuoteRequest::STATUS_ACCEPTED,
            QuoteRequest::STATUS_REFUSED,
            QuoteRequest::STATUS_CLOSED,
        ])) {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'LIKE', "%{$search}%")
                    ->orWhereHas('prospect', function ($pq) use ($search) {
                        $pq->where('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%")
                            ->orWhere('company', 'LIKE', "%{$search}%");
                    });
            });
        }

        $quotes = $query->paginate(15)->withQueryString();

        $statusCounts = [
            'all' => QuoteRequest::count(),
            QuoteRequest::STATUS_NEW => QuoteRequest::where('status', QuoteRequest::STATUS_NEW)->count(),
            QuoteRequest::STATUS_IN_PROGRESS => QuoteRequest::where('status', QuoteRequest::STATUS_IN_PROGRESS)->count(),
            QuoteRequest::STATUS_SENT => QuoteRequest::where('status', QuoteRequest::STATUS_SENT)->count(),
            QuoteRequest::STATUS_ACCEPTED => QuoteRequest::where('status', QuoteRequest::STATUS_ACCEPTED)->count(),
            QuoteRequest::STATUS_REFUSED => QuoteRequest::where('status', QuoteRequest::STATUS_REFUSED)->count(),
            QuoteRequest::STATUS_CLOSED => QuoteRequest::where('status', QuoteRequest::STATUS_CLOSED)->count(),
        ];

        return view('admin.quotes.index', compact('quotes', 'statusFilter', 'search', 'statusCounts'));
    }

    public function show(QuoteRequest $quote): View
    {
        $quote->load(['prospect', 'services.category']);

        return view('admin.quotes.show', compact('quote'));
    }

    public function updateStatus(Request $request, QuoteRequest $quote): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:nouvelle,en_cours,devis_envoye,acceptee,refusee,cloturee'],
        ]);

        $quote->update(['status' => $validated['status']]);

        return back()->with('success', 'Le statut du devis '.$quote->reference.' a été mis à jour avec succès.');
    }

    public function updateNotes(Request $request, QuoteRequest $quote): RedirectResponse
    {
        $validated = $request->validate([
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $quote->update(['internal_notes' => $validated['internal_notes']]);

        return back()->with('success', 'Les notes internes ont été enregistrées.');
    }

    public function downloadAttachment(QuoteRequest $quote): StreamedResponse|RedirectResponse
    {
        if (! $quote->attachment_path || ! Storage::disk('local')->exists($quote->attachment_path)) {
            return back()->with('error', 'Le fichier joint demandé est introuvable.');
        }

        $downloadName = $quote->attachment_original_name ?? basename($quote->attachment_path);

        return Storage::disk('local')->download($quote->attachment_path, $downloadName);
    }

    public function destroy(QuoteRequest $quote): RedirectResponse
    {
        $ref = $quote->reference;
        if ($quote->attachment_path && Storage::disk('local')->exists($quote->attachment_path)) {
            Storage::disk('local')->delete($quote->attachment_path);
        }

        $quote->delete();

        return redirect()->route('admin.quotes.index')->with('success', 'La demande de devis '.$ref.' a été supprimée.');
    }
}
