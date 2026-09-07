<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Availability extends Model
{
    protected $table = 'availability';

    protected $fillable = [
        'professional_id',
        'day_week',
        'time_start',
        'time_end',
    ];

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
