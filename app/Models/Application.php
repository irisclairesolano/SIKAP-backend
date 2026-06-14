<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    

    protected $fillable = [
        'job_post_id',
        'worker_id',
        'cover_note',
        'status',
        'references_revealed',
        'contact_revealed',
        'slot_locked_at',
        'employer_confirmed_at',
        'applied_at',
        'responded_at',
        'final_agreed_price'
    ];

    protected $casts = [
        'references_revealed' => 'boolean',
        'contact_revealed' => 'boolean',
        'slot_locked_at' => 'datetime',
        'employer_confirmed_at' => 'datetime',
        'applied_at' => 'datetime',
        'responded_at' => 'datetime',
        'final_agreed_price' => 'decimal:2'
    ];

    public function job()
    {
        return $this->belongsTo(JobPost::class, 'job_post_id');
    }

    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
