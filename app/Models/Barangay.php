<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    
    protected $table = 'barangays';
    public $timestamps = true;

    protected $fillable = ['name', 'municipality_id'];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }
}
