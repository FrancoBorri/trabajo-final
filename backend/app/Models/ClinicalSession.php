<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalSession extends Model
{
    protected $fillable = [
        'clinical_history_id',
        'appointment_id',
        'session_number',
        'topic',
        'evolution',
        'observations',
    ];


    public function clinicalHistory()
    {
        return $this->belongsTo(ClinicalHistory::class, 'clinical_history_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
