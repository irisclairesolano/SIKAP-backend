<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPost extends Model
{
    use HasFactory, SoftDeletes;

    

    protected $fillable = [
        'employer_id',
        'reference_number',
        'title',
        'description',
        'category',
        'barangay',
        'municipality',
        'duration_type',
        'compensation',
        'slots',
        'accepted_count',
        'status',
        'rating_window_expires_at'
    ];

    protected $casts = [
        'rating_window_expires_at' => 'datetime',
        'deleted_at' => 'datetime',
        'compensation' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $date = date('Ymd');
            $count = static::withTrashed()->whereDate('created_at', today())->count() + 1;
            $post->reference_number = 'SIKAP-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
