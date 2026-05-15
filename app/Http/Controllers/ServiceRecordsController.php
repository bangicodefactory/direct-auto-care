<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRecordRequest;
use App\Http\Requests\UpdateServiceRecordRequest;
use App\Models\ServiceRecord;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRecordsController extends Controller
{
    public function create(Vehicle $vehicle): Response
    {
        $this->authorize('create', ServiceRecord::class);

        return Inertia::render('ServiceRecords/Create', [
            'vehicle'      => $vehicle->only('id', 'make', 'model', 'license_plate', 'mileage'),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreServiceRecordRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $vehicle) {
            $record = ServiceRecord::create([
                'vehicle_id'         => $vehicle->id,
                'mileage_at_service' => $data['mileage_at_service'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'serviced_at'        => $data['serviced_at'],
            ]);

            $record->serviceTypes()->sync($data['service_type_ids']);

            if (isset($data['mileage_at_service']) && $data['mileage_at_service'] > $vehicle->mileage) {
                $vehicle->update(['mileage' => $data['mileage_at_service']]);
            }
        });

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Fiche de service créée avec succès.');
    }

    public function edit(ServiceRecord $serviceRecord): Response
    {
        $this->authorize('update', $serviceRecord);

        $serviceRecord->load(['vehicle', 'serviceTypes']);

        return Inertia::render('ServiceRecords/Edit', [
            'record'       => $this->recordPayload($serviceRecord),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateServiceRecordRequest $request, ServiceRecord $serviceRecord): RedirectResponse
    {
        $data    = $request->validated();
        $vehicle = $serviceRecord->vehicle;

        DB::transaction(function () use ($data, $serviceRecord, $vehicle) {
            $serviceRecord->update([
                'mileage_at_service' => $data['mileage_at_service'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'serviced_at'        => $data['serviced_at'],
            ]);

            $serviceRecord->serviceTypes()->sync($data['service_type_ids']);

            if (isset($data['mileage_at_service']) && $data['mileage_at_service'] > $vehicle->mileage) {
                $vehicle->update(['mileage' => $data['mileage_at_service']]);
            }
        });

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Fiche de service mise à jour avec succès.');
    }

    public function destroy(ServiceRecord $serviceRecord): RedirectResponse
    {
        $this->authorize('delete', $serviceRecord);

        $vehicle = $serviceRecord->vehicle;
        $serviceRecord->delete();

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Fiche de service supprimée.');
    }

    private function recordPayload(ServiceRecord $serviceRecord): array
    {
        return [
            'id'                 => $serviceRecord->id,
            'mileage_at_service' => $serviceRecord->mileage_at_service,
            'notes'              => $serviceRecord->notes,
            'serviced_at'        => $serviceRecord->serviced_at?->format('Y-m-d'),
            'service_type_ids'   => $serviceRecord->serviceTypes->pluck('id')->values()->toArray(),
            'vehicle'            => [
                'id'            => $serviceRecord->vehicle->id,
                'make'          => $serviceRecord->vehicle->make,
                'model'         => $serviceRecord->vehicle->model,
                'license_plate' => $serviceRecord->vehicle->license_plate,
            ],
        ];
    }
}
