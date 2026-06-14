<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployerProfile extends Model
{
    use HasFactory;

    

    protected $fillable = [
        'user_id',
        'description',
        'contact_info',
        'reputation_score'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
