<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    
    protected $table = 'municipalities';
    public $timestamps = true;

    protected $fillable = ['name'];

    public function barangays()
    {
        return $this->hasMany(Barangay::class);
    }
}
