<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use RecordsActivity;

    public const STATUS_CONFIRMED = 'confirme';

    public const STATUS_DONE = 'honore';

    public const STATUS_CANCELLED = 'annule';

    public const NEEDS = ['ia' => 'IA & Automatisation', 'web' => 'Web & Mobile', 'infra' => 'Infra & Sécurité'];

    public const DEADLINES = ['urgent' => 'Urgent (< 2 semaines)', 'month' => 'Dans le mois', 'later' => 'Projet à venir / Analyse'];

    // Fuseau des créneaux proposés sur le site (heure de Douala).
    public const TIMEZONE = 'Africa/Douala';

    protected $fillable = [
        'name',
        'email',
        'company',
        'need',
        'deadline',
        'slot',
        'status',
        'locale',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'slot' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Booking $booking) {
            $booking->slot_key = $booking->status === self::STATUS_CANCELLED ? null : $booking->slot->utc()->format('Y-m-d H:i');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_CANCELLED);
    }

    public function scopeUpcoming($query)
    {
        return $query->active()->where('slot', '>=', now());
    }

    /** Créneau au format des créneaux du front : « 2026-10-22T09:00:00+01:00 ». */
    public function slotIso(): string
    {
        return $this->slot->copy()->setTimezone(self::TIMEZONE)->format('Y-m-d\TH:i:sP');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'Confirmé',
            self::STATUS_DONE => 'Honoré',
            self::STATUS_CANCELLED => 'Annulé',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'bg-primary text-white',
            self::STATUS_DONE => 'bg-success text-white',
            self::STATUS_CANCELLED => 'bg-secondary text-white',
            default => 'bg-light text-dark',
        };
    }
}
