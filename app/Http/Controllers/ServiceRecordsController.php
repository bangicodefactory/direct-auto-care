<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRecordRequest;
use App\Http\Requests\UpdateServiceRecordRequest;
use App\Models\ServiceRecord;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRecordsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:create,' . ServiceRecord::class, only: ['create', 'store']),
            new Middleware('can:update,service_record', only: ['edit', 'update']),
            new Middleware('can:delete,service_record', only: ['destroy']),
        ];
    }

    public function create(Vehicle $vehicle): Response
    {
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
        $vehicle = $serviceRecord->vehicle()->withTrashed()->firstOrFail();
        $serviceRecord->setRelation('vehicle', $vehicle);
        $serviceRecord->load('serviceTypes');

        return Inertia::render('ServiceRecords/Edit', [
            'record'       => $this->recordPayload($serviceRecord),
            'serviceTypes' => ServiceType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateServiceRecordRequest $request, ServiceRecord $serviceRecord): RedirectResponse
    {
        $data      = $request->validated();
        $vehicleId = $serviceRecord->vehicle_id;
        $vehicle   = $serviceRecord->vehicle()->withTrashed()->first();

        DB::transaction(function () use ($data, $serviceRecord, $vehicle) {
            $serviceRecord->update([
                'mileage_at_service' => $data['mileage_at_service'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'serviced_at'        => $data['serviced_at'],
            ]);

            $serviceRecord->serviceTypes()->sync($data['service_type_ids']);

            if ($vehicle && isset($data['mileage_at_service']) && $data['mileage_at_service'] > $vehicle->mileage) {
                $vehicle->update(['mileage' => $data['mileage_at_service']]);
            }
        });

        return redirect()->route('vehicles.show', $vehicleId)
            ->with('success', 'Fiche de service mise à jour avec succès.');
    }

    public function destroy(ServiceRecord $serviceRecord): RedirectResponse
    {
        $vehicleId = $serviceRecord->vehicle_id;
        $serviceRecord->delete();

        return redirect()->route('vehicles.show', $vehicleId)
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
