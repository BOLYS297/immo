<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/** Une action de l'équipe dans l'administration. Écrite par App\Services\Activity ; lue dans « Journal ». */
class ActivityLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    /** Conservation : 12 mois (purge quotidienne : php artisan model:prune, planifiée dans routes/console.php). */
    public const RETENTION_MONTHS = 12;

    protected $fillable = ['user_id', 'user_name', 'action', 'subject_type', 'subject_id', 'subject_label', 'details', 'ip'];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function prunable()
    {
        return static::where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS));
    }
}
