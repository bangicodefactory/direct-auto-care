<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleNote;

class VehicleNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'employee']);
    }

    /**
     * Notes can only be written on a vehicle whose owner is a fleet client.
     */
    public function create(User $user, Vehicle $vehicle): bool
    {
        return $user->hasAnyRole(['admin', 'employee'])
            && (bool) $vehicle->customer?->fleet_notes_enabled;
    }

    public function update(User $user, VehicleNote $vehicleNote): bool
    {
        return $this->isAdminOrAuthor($user, $vehicleNote);
    }

    public function delete(User $user, VehicleNote $vehicleNote): bool
    {
        return $this->isAdminOrAuthor($user, $vehicleNote);
    }

    /**
     * Same fleet rule as {@see create()}: once the owner is no longer a fleet
     * client (or the vehicle is deleted), its notes are read-only history.
     */
    private function isAdminOrAuthor(User $user, VehicleNote $vehicleNote): bool
    {
        if (! $vehicleNote->vehicle?->customer?->fleet_notes_enabled) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('employee') && $vehicleNote->user()->is($user);
    }
}
