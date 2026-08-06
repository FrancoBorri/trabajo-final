<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'professional_id',
        'title',
        'description',
        'price',
        'duration',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
