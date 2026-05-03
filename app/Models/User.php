<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'pgsql';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'barangay',
        'municipality',
        'document_url',
        'selfie_url',
        'verification_status',
        'verification_badge',
        'is_suspended'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'verification_badge' => 'boolean',
        'is_suspended' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function workerProfile()
    {
        return $this->hasOne(WorkerProfile::class);
    }

    public function employerProfile()
    {
        return $this->hasOne(EmployerProfile::class);
    }

    public function jobPosts()
    {
        return $this->hasMany(JobPost::class, 'employer_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'worker_id');
    }

    public function referral()
    {
        return $this->hasOne(Referral::class, 'new_user_id');
    }

    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function emailOtps()
    {
        return $this->hasMany(EmailOtp::class);
    }
}
