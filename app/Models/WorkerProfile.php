<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerProfile extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $fillable = [
        'user_id',
        'bio',
        'availability_status',
        'reputation_score'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'worker_skill');
    }

    public function experiences()
    {
        return $this->hasMany(WorkerExperience::class);
    }

    public function references()
    {
        return $this->hasMany(WorkerCharacterReference::class);
    }
}
