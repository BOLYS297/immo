<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use RecordsActivity;

    public const STATUS_NEW = 'nouveau';

    public const STATUS_READ = 'lu';

    public const STATUS_PROCESSED = 'traite';

    public const STATUS_ARCHIVED = 'archive';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
    ];

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'Nouveau',
            self::STATUS_READ => 'Lu',
            self::STATUS_PROCESSED => 'Traité',
            self::STATUS_ARCHIVED => 'Archivé',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'bg-danger text-white',
            self::STATUS_READ => 'bg-info text-white',
            self::STATUS_PROCESSED => 'bg-success text-white',
            self::STATUS_ARCHIVED => 'bg-secondary text-white',
            default => 'bg-light text-dark',
        };
    }
}
