<?php

namespace App\Domain\Users\Traits;

use App\Application\Users\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;

/**
 * Трейт для логирования действий пользователей:
 * логирует создание, обновление и удаление моделей пользователей в системе.
 */
trait LogsUserActivity
{
    protected static function bootLogsUserActivity(): void
    {
        static::created(function ($model) {
            if (!static::shouldLog()) {
                return;
            }

            ActivityLogger::log(
                'created',
                $model,
                self::logLabel() . ' создан(а)',
            );
        });

        static::updated(function ($model) {
            if (!static::shouldLog()) {
                return;
            }

            $changes = $model->getChanges();

            unset($changes['updated_at']);

            $original = array_intersect_key($model->getOriginal(), $changes);

            foreach (static::getLogExcludedFields() as $field) {
                if (isset($original[$field])) {
                    $original[$field] = '* * * * * * *';
                }

                if (isset($changes[$field])) {
                    $changes[$field] = '* * * * * * *';
                }
            }

            ActivityLogger::log(
                'updated',
                $model,
                self::logLabel() . ' обновлён(а)',
                [
                    'before' => $original,
                    'after' => $changes,
                ],
            );
        });

        static::deleted(function ($model) {
            if (!static::shouldLog()) {
                return;
            }

            ActivityLogger::log(
                'deleted',
                $model,
                self::logLabel() . ' удалён(а)',
            );
        });
    }

    /**
     * Логировать только если действие выполняет авторизованный пользователь.
     * Переопределите в модели, если нужно другое поведение.
     */
    protected static function shouldLog(): bool
    {
        return Auth::check();
    }

    /**
     * Поля, которые всегда исключаются из лога изменений.
     * Модель может дополнить список через свойство $logExcludedFields.
     */
    protected static function getLogExcludedFields(): array
    {
        $modelSpecific = property_exists(static::class, 'logExcludedFields')
            ? static::$logExcludedFields
            : [];

        return array_merge(['updated_at'], $modelSpecific);
    }

    protected static function logLabel(): string
    {
        return class_basename(static::class);
    }
}
