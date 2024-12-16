<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable,HasFactory; 


    // Role constants
    const ROLE_ADMIN = 'admin';
    const ROLE_DOCTOR = 'doctor';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'phone',
        'odoo_doctor_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Role checking methods
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isDoctor(): bool
    {
        return $this->role === self::ROLE_DOCTOR;
    }

    // Relationships
    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'doctor_patient', 'doctor_id', 'patient_id')
                    ->withTimestamps();
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDoctors($query)
    {
        return $query->where('role', self::ROLE_DOCTOR);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    // Helper methods
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getPatientsCountAttribute(): int
    {
        return $this->patients()->count();
    }

    public function getPrescriptionsCountAttribute(): int
    {
        return $this->prescriptions()->count();
    }
}