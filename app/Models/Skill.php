<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $fillable = [
        'name',
        'category'
    ];

    public function workers()
    {
        return $this->belongsToMany(WorkerProfile::class, 'worker_skill');
    }
}
