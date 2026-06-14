<?php

namespace App\Http\Controllers;

use App\Enums\ServiceOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreServiceOrderItemRequest;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Services\ServiceOrderService;
use Illuminate\Http\RedirectResponse;

class ServiceOrderItemController extends Controller
{
    public function store(
        StoreServiceOrderItemRequest $request,
        ServiceOrder $serviceOrder,
        ServiceOrderService $service,
    ): RedirectResponse {
        $this->ensureInProgress($serviceOrder);

        try {
            $service->addItem($serviceOrder, $request->validated(), $request->user());
        } catch (InsufficientStockException $e) {
            // Surface as an inline validation error rather than a 422 modal.
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('success', 'Pièce ajoutée à l\'ordre de service.');
    }

    public function destroy(
        ServiceOrder $serviceOrder,
        ServiceOrderItem $item,
        ServiceOrderService $service,
    ): RedirectResponse {
        $this->authorize('update', $serviceOrder);

        abort_unless($item->service_order_id === $serviceOrder->id, 404);
        $this->ensureInProgress($serviceOrder);

        $service->removeItem($item, request()->user());

        return back()->with('success', 'Pièce retirée de l\'ordre de service.');
    }

    /**
     * Parts may only be added or removed while the order is being worked on.
     */
    private function ensureInProgress(ServiceOrder $serviceOrder): void
    {
        abort_unless(
            $serviceOrder->status === ServiceOrderStatus::InProgress,
            422,
            'Les pièces ne peuvent être modifiées que pendant la réparation.',
        );
    }
}
