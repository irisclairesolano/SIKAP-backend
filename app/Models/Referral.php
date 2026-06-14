<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    

    public $timestamps = false;

    protected $fillable = [
        'new_user_id',
        'referrer_contact',
        'referrer_name'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'new_user_id');
    }
}
