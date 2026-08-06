<?php

namespace App\Models\Users\Traits;

use App\Application\Users\Services\ActivityLogger;

/**
 * Трейт для логирования действий пользователей:
 * логирует создание, обновление и удаление моделей пользователей в системе.
 */
trait LogsUserActivity
{
    /**
     * Поля, которые никогда не должны попадать в лог изменений
     */
    protected static array $logExcludedFields = [
        'password',
        'remember_token',
    ];

    protected static function bootLogsUserActivity(): void
    {
        static::created(function ($model) {
            ActivityLogger::log(
                'created',
                $model,
                self::activityLabel() . ' создан(а)',
            );
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();

            unset($changes['updated_at']);

            foreach (static::$logExcludedFields as $field) {
                unset($changes[$field]);
            }

            // если после исключения служебных полей ничего не осталось —
            // значит менялся только пароль/токен, реального изменения данных нет
            if (empty($changes)) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);

            ActivityLogger::log(
                'updated',
                $model,
                self::activityLabel() . ' обновлён(а)',
                [
                    'before' => $original,
                    'after' => $changes,
                ],
            );
        });

        static::deleted(function ($model) {
            ActivityLogger::log(
                'deleted',
                $model,
                self::activityLabel() . ' удалён(а)',
            );
        });
    }

    protected static function activityLabel(): string
    {
        return class_basename(static::class);
    }
}
