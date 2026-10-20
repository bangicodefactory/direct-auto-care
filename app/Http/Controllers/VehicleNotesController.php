<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleNoteRequest;
use App\Http\Requests\UpdateVehicleNoteRequest;
use App\Models\Vehicle;
use App\Models\VehicleNote;
use Illuminate\Http\RedirectResponse;

class VehicleNotesController extends Controller
{
    public function store(StoreVehicleNoteRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->notes()->create([
            'user_id' => $request->user()->id,
            'body'    => $request->validated('body'),
        ]);

        return redirect()->route('fleet-notes.show', $vehicle)
            ->with('success', 'Note ajoutée.');
    }

    public function update(UpdateVehicleNoteRequest $request, VehicleNote $vehicleNote): RedirectResponse
    {
        $vehicleNote->update($request->validated());

        return redirect()->route('fleet-notes.show', $vehicleNote->vehicle_id)
            ->with('success', 'Note mise à jour.');
    }

    public function destroy(VehicleNote $vehicleNote): RedirectResponse
    {
        $this->authorize('delete', $vehicleNote);

        $vehicleNote->delete();

        return redirect()->route('fleet-notes.show', $vehicleNote->vehicle_id)
            ->with('success', 'Note supprimée.');
    }
}
