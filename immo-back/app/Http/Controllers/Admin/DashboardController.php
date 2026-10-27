<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_services' => Service::published()->count(),
            'total_projects' => Project::published()->count(),
            'total_quotes' => QuoteRequest::count(),
            'new_quotes' => QuoteRequest::where('status', QuoteRequest::STATUS_NEW)->count(),
            'in_progress_quotes' => QuoteRequest::where('status', QuoteRequest::STATUS_IN_PROGRESS)->count(),
            'total_posts' => Post::published()->count(),
            'new_messages' => ContactMessage::where('status', ContactMessage::STATUS_NEW)->count(),
        ];

        // Prospects : uniquement pour les rôles qui les traitent (la vue masque aussi ces blocs).
        $canLeads = Gate::allows('leads.manage');
        $latestQuotes = $canLeads ? QuoteRequest::with(['prospect', 'services'])->latest()->take(6)->get() : collect();
        $latestMessages = $canLeads ? ContactMessage::latest()->take(5)->get() : collect();

        // À relire et publier : pages dont le brouillon diffère de la version en ligne, articles en brouillon.
        $pendingPages = Gate::allows('pages.publish')
            ? Page::with('publishedRevision')->orderByDesc('updated_at')->get()->filter(fn (Page $page) => $page->hasUnpublishedChanges())->values()
            : collect();
        $pendingPosts = Gate::allows('posts.publish') ? Post::with('author')->where('is_published', false)->latest('updated_at')->take(8)->get() : collect();

        return view('admin.dashboard', compact('stats', 'latestQuotes', 'latestMessages', 'pendingPages', 'pendingPosts'));
    }
}
