<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionMedication extends Model
{
    protected $fillable = [
        'product',
        'quantity',
        'dosage',
        'every',
        'period',
        'as_needed',
        'directions'
    ];

    protected $casts = [
        'as_needed' => 'boolean',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function getDosageInstructions()
    {
        $instructions = $this->dosage;
        
        if ($this->every && $this->period) {
            $instructions .= " every {$this->every} {$this->period}";
        }
        
        if ($this->as_needed) {
            $instructions .= " (as needed)";
        }
        
        return $instructions;
    }
}