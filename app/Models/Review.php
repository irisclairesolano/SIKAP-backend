<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';

    public $timestamps = false;

    protected $fillable = [
        'application_id',
        'reviewer_id',
        'reviewee_id',
        'reviewer_role',
        'cat1',
        'cat2',
        'cat3',
        'cat4',
        'overall_rating',
        'comment'
    ];

    protected $casts = [
        'overall_rating' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($review) {
            $sum = (int)$review->cat1 + (int)$review->cat2 + (int)$review->cat3 + (int)$review->cat4;
            $review->overall_rating = $sum / 4;
        });
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
