<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    use RecordsActivity;

    public const STATUS_NEW = 'nouvelle';

    public const STATUS_IN_PROGRESS = 'en_cours';

    public const STATUS_SENT = 'devis_envoye';

    public const STATUS_ACCEPTED = 'acceptee';

    public const STATUS_REFUSED = 'refusee';

    public const STATUS_CLOSED = 'cloturee';

    /** Pôles du formulaire de chiffrage du front. */
    public const DOMAINS = ['ia' => 'IA & Automatisation', 'web' => 'Web & Mobile', 'infra' => 'Infra & Sécurité'];

    protected $fillable = [
        'prospect_id',
        'reference',
        'budget_range',
        'timeline',
        'domain',
        'locale',
        'description',
        'attachment_path',
        'attachment_original_name',
        'status',
        'internal_notes',
        'privacy_consent',
    ];

    protected function casts(): array
    {
        return [
            'privacy_consent' => 'boolean',
        ];
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'quote_request_service');
    }

    public function getDomainLabelAttribute(): ?string
    {
        return $this->domain ? (self::DOMAINS[$this->domain] ?? $this->domain) : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'Nouvelle',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_SENT => 'Devis envoyé',
            self::STATUS_ACCEPTED => 'Acceptée',
            self::STATUS_REFUSED => 'Refusée',
            self::STATUS_CLOSED => 'Clôturée',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'bg-primary text-white',
            self::STATUS_IN_PROGRESS => 'bg-warning text-dark',
            self::STATUS_SENT => 'bg-info text-white',
            self::STATUS_ACCEPTED => 'bg-success text-white',
            self::STATUS_REFUSED => 'bg-danger text-white',
            self::STATUS_CLOSED => 'bg-secondary text-white',
            default => 'bg-light text-dark',
        };
    }
}
