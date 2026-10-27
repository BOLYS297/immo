<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Journal d'activité de l'administration. Seules les actions d'un membre de l'équipe sont enregistrées
 * (pas les formulaires du site, ni le portail client, ni les commandes et seeders), sauf les tentatives de connexion.
 * Les modèles suivis (trait RecordsActivity) s'enregistrent seuls ; les actions composées (publication,
 * restauration, réordonnancement…) appellent record() et coupent l'enregistrement détaillé avec quietly().
 */
class Activity
{
    private static int $muted = 0;

    /** $subject : modèle concerné (ou [type, id, libellé]) ; $details : données non sensibles (noms de champs, ancien → nouveau rôle…). */
    public static function record(string $action, Model|array|null $subject = null, array $details = [], ?User $user = null): void
    {
        $user ??= Auth::user();
        if (! str_starts_with($action, 'auth.') && ! $user?->isStaff()) {
            return;
        }

        [$type, $id, $label] = match (true) {
            $subject instanceof Model => [class_basename($subject), $subject->getKey(), static::label($subject)],
            is_array($subject) => $subject + [null, null, null],
            default => [null, null, null],
        };

        ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => $id,
            'subject_label' => $label !== null ? mb_substr($label, 0, 255) : null,
            'details' => $details ?: null,
            'ip' => request()?->ip(),
        ]);
    }

    /** Événement de modèle (trait RecordsActivity), ignoré pendant une action composée déjà journalisée. */
    public static function recordModel(string $action, Model $model, array $details = []): void
    {
        if (static::$muted === 0) {
            static::record($action, $model, $details);
        }
    }

    /** Exécute $callback sans journaliser les événements de modèle qu'il déclenche. */
    public static function quietly(callable $callback): mixed
    {
        static::$muted++;
        try {
            return $callback();
        } finally {
            static::$muted--;
        }
    }

    /** Libellé lisible d'un modèle : titre, nom, question, référence… en français. */
    public static function label(Model $model): ?string
    {
        if (method_exists($model, 'activityLabel')) {
            return $model->activityLabel();
        }
        foreach (['title', 'name', 'question', 'label', 'reference', 'original_name', 'slug', 'email'] as $key) {
            $value = method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($key)
                ? $model->getTranslation($key, config('cms.default_locale'), true)
                : $model->getAttribute($key);
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return '#'.$model->getKey();
    }
}
