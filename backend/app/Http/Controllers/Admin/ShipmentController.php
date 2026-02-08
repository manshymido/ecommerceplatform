<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiMessages;
use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Admin\StoreShipmentRequest;
use App\Http\Requests\Admin\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Modules\Shipping\Application\FulfillmentService;
use App\Modules\Shipping\Domain\ShipmentRepository;
use Illuminate\Http\JsonResponse;

class ShipmentController extends ApiBaseController
{
    public function __construct(
        private ShipmentRepository $shipmentRepository,
        private FulfillmentService $fulfillmentService
    ) {
    }

    /**
     * GET /admin/orders/{orderId}/shipments - List shipments for an order.
     */
    public function index(int $orderId): JsonResponse
    {
        $shipments = $this->shipmentRepository->findByOrder($orderId);

        return $this->collection(ShipmentResource::collection(collect($shipments)));
    }

    /**
     * POST /admin/orders/{orderId}/shipments - Create shipment for a paid order.
     */
    public function store(StoreShipmentRequest $request, int $orderId): JsonResponse
    {
        $validated = $request->validated();

        try {
            $shipment = $this->fulfillmentService->createShipment(
                $orderId,
                $validated['tracking_number'] ?? null,
                $validated['carrier_code'] ?? null
            );
        } catch (\DomainException $e) {
            return $this->fromDomainException($e);
        }

        return $this->data(new ShipmentResource($shipment), 201);
    }

    /**
     * PATCH /admin/shipments/{id} - Update shipment (e.g. mark shipped, set tracking).
     */
    public function update(UpdateShipmentRequest $request, int $id): JsonResponse
    {
        $shipment = $this->shipmentRepository->findById($id);
        if (! $shipment) {
            return $this->notFound(ApiMessages::SHIPMENT_NOT_FOUND);
        }

        $validated = $request->validated();
        $status = $validated['status'] ?? null;

        if ($status === 'shipped') {
            $this->fulfillmentService->markShipped(
                $id,
                $validated['tracking_number'] ?? $shipment->trackingNumber,
                $validated['carrier_code'] ?? $shipment->carrierCode
            );
        } elseif ($status === 'delivered') {
            $this->fulfillmentService->markDelivered($id);
        } elseif (array_key_exists('tracking_number', $validated) || array_key_exists('carrier_code', $validated)) {
            $data = array_filter([
                'tracking_number' => $validated['tracking_number'] ?? null,
                'carrier_code' => $validated['carrier_code'] ?? null,
            ], fn ($v) => $v !== null);
            if ($data !== []) {
                $this->shipmentRepository->update($id, $data);
            }
        }

        $shipment = $this->shipmentRepository->findById($id);

        return $this->data(new ShipmentResource($shipment));
    }
}
