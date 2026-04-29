<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerCharacterReference extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $fillable = [
        'worker_profile_id',
        'name',
        'phone',
        'relationship'
    ];

    public function workerProfile()
    {
        return $this->belongsTo(WorkerProfile::class);
    }
}
