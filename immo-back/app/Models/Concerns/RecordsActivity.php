<?php

namespace App\Models\Concerns;

use App\Services\Activity;

/**
 * Journalise création, modification et suppression du modèle quand elles viennent de l'équipe (App\Services\Activity).
 * Une modification n'enregistre que les noms des champs changés ; celles qui ne touchent que des champs techniques
 * ($activityIgnored) ne sont pas journalisées.
 */
trait RecordsActivity
{
    protected static function bootRecordsActivity(): void
    {
        static::created(fn ($model) => Activity::recordModel('created', $model));
        static::deleted(fn ($model) => Activity::recordModel('deleted', $model));
        static::updated(function ($model) {
            $ignored = ['created_at', 'updated_at', 'remember_token', ...($model->activityIgnored ?? [])];
            $fields = array_values(array_diff(array_keys($model->getChanges()), $ignored));
            if ($fields !== []) {
                Activity::recordModel('updated', $model, ['fields' => $fields]);
            }
        });
    }
}
