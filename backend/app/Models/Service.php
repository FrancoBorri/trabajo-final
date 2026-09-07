<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    // Datos que se pueden asignar
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
