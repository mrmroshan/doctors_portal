<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'email',
        'phone',
        'address',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // Relationships
    public function doctors()
    {
        return $this->belongsToMany(User::class, 'doctor_patient', 'patient_id', 'doctor_id')
                    ->where('role', User::ROLE_DOCTOR)
                    ->withTimestamps();
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }

    public function getPrescriptionsCountAttribute(): int
    {
        return $this->prescriptions()->count();
    }

    // Scopes
    public function scopeSearch($query, $term, User $doctor = null)
{
    $query = $query->where(function($q) use ($term) {
        $q->where('first_name', 'like', "%{$term}%")
          ->orWhere('last_name', 'like', "%{$term}%")
          ->orWhere('email', 'like', "%{$term}%")
          ->orWhere('phone', 'like', "%{$term}%");
    });

    if ($doctor) {
        $query->whereHas('doctors', function($q) use ($doctor) {
            $q->where('users.id', $doctor->id);
        });
    }

    return $query;
}


// Add this scope if you want a dedicated method for filtering by doctor
public function scopeForDoctor($query, User $doctor)
{
    return $query->whereHas('doctors', function($q) use ($doctor) {
        $q->where('users.id', $doctor->id);
    });
}



    // Helper methods
    public function hasDoctor(User $doctor): bool
    {
        return $this->doctors()->where('users.id', $doctor->id)->exists();
    }

    public function getLatestPrescription()
    {
        return $this->prescriptions()->latest()->first();
    }
}