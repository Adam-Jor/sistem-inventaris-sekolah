<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function loans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Cek apakah alamat email user dapat menerima email eksternal
     * (mengabaikan domain dummy RFC 2606 seperti .test saat menggunakan SMTP asli).
     */
    public function hasDeliverableEmail(): bool
    {
        if (! $this->email || ! filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (app()->runningUnitTests() || in_array(config('mail.default'), ['log', 'array'], true)) {
            return true;
        }

        $lowerEmail = strtolower($this->email);

        foreach (['.test', '.example', '.invalid', '.localhost'] as $reservedTld) {
            if (str_ends_with($lowerEmail, $reservedTld)) {
                return false;
            }
        }

        return true;
    }
}
