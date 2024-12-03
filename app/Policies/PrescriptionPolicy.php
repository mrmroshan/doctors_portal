<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function update(User $user, Prescription $prescription)
    {
        return $user->isAdmin() || $prescription->created_by === $user->id;
    }

    public function view(User $user, Prescription $prescription)
    {
        return $user->isAdmin() || $prescription->created_by === $user->id;
    }
}