<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Prescription extends Model
{
    use HasFactory;

    // Sync status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SYNCED = 'synced';
    const STATUS_ERROR = 'error';

    // Time period constants
    const PERIOD_HOUR = 'hour';
    const PERIOD_HOURS = 'hours';
    const PERIOD_DAY = 'day';
    const PERIOD_DAYS = 'days';
    const PERIOD_WEEK = 'week';
    const PERIOD_WEEKS = 'weeks';

    protected $fillable = [
        'patient_id',
        'prescription_date',
        'created_by',
        'odoo_order_id',
        'sync_status',
        'sync_error'
    ];

    protected $casts = [
        'prescription_date' => 'date',
        'as_needed' => 'boolean',
        'sync_attempted_at' => 'datetime',
        'quantity' => 'integer',
        'every' => 'integer',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function medications()
    {
        return $this->hasMany(PrescriptionMedication::class);
    }

    public function getMedicationsListAttribute()
    {
        return $this->medications->pluck('product')->join(', ');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('sync_status', self::STATUS_PENDING);
    }

    public function scopeSynced($query)
    {
        return $query->where('sync_status', self::STATUS_SYNCED);
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', self::STATUS_ERROR);
    }

    // Helper methods
    public function markAsSynced(string $odooOrderId): bool
    {
        return $this->update([
            'sync_status' => self::STATUS_SYNCED,
            'odoo_order_id' => $odooOrderId,
            'sync_attempted_at' => Carbon::now(),
            'sync_error' => null
        ]);
    }

    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'sync_status' => self::STATUS_ERROR,
            'sync_attempted_at' => Carbon::now(),
            'sync_error' => $error
        ]);
    }

    public function getDosageInstructions(): string
    {
        $instructions = "Take {$this->dosage}";
        
        if ($this->every && $this->period) {
            $instructions .= " every {$this->every} {$this->period}";
        }
        
        if ($this->as_needed) {
            $instructions .= " as needed";
        }
        
        return $instructions;
    }

    public function canSync(): bool
    {
        return $this->sync_status === self::STATUS_PENDING || 
               ($this->sync_status === self::STATUS_ERROR && 
                ($this->sync_attempted_at === null || 
                 $this->sync_attempted_at->diffInHours(now()) >= 1));
    }
}