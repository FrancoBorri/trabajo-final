<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicalHistory extends Model
{
    protected $fillable = [
        'user_id',
        'professional_id',
        'chief_complaint',
        'medical_history',
        'initial_assessment',
        'clinical_impression',
        'therapeutic_goals',
        'treatment_plan',
        'notes',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function professional()
    {
        return $this->BelongsTo(Professional::class);
    }

    public function clinicalSessions()
    {
        return $this->hasMany(ClinicalSession::class, 'clinical_history_id');
    }


}
