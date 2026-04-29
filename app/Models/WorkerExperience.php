<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerExperience extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    protected $fillable = [
        'worker_profile_id',
        'job_title',
        'employer_name',
        'duration',
        'description'
    ];

    public function workerProfile()
    {
        return $this->belongsTo(WorkerProfile::class);
    }
}
