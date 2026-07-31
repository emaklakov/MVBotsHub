<?php

namespace App\Models\Admin\User;

use App\Models\Concerns\HasTwoFactorEmailCode;
use App\Models\Concerns\HasUserSettings;
use App\Models\Concerns\LogsUserActivity;
use App\Notifications\Auth\ResetPassword;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Модель для работы с пользователями:
 * наследуется от Authenticatable и использует трейты LogsUserActivity и HasTwoFactorEmailCode для логирования действий пользователей и работы с двухфакторной аутентификацией.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, CanResetPasswordTrait, HasTwoFactorEmailCode;
    use LogsUserActivity, HasUserSettings;

    protected string $guard_name = 'moonshine';

    /**
     * Поля, которые никогда не должны попадать в лог изменений
     */
    protected static array $logExcludedFields = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(UserLog::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPassword($token));
    }
}
