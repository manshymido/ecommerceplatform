<?php

namespace App\Http\Controllers\Admin;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ShipmentResource;
use App\Modules\Shipping\Application\FulfillmentService;
use App\Modules\Shipping\Domain\ShipmentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
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

        return ApiResponse::collection(ShipmentResource::collection(collect($shipments)));
    }

    /**
     * POST /admin/orders/{orderId}/shipments - Create shipment for a paid order.
     */
    public function store(Request $request, int $orderId): JsonResponse
    {
        $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'carrier_code' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            $shipment = $this->fulfillmentService->createShipment(
                $orderId,
                $request->input('tracking_number'),
                $request->input('carrier_code')
            );
        } catch (\DomainException $e) {
            return ApiResponse::fromDomainException($e);
        }

        return ApiResponse::data(new ShipmentResource($shipment), 201);
    }

    /**
     * PATCH /admin/shipments/{id} - Update shipment (e.g. mark shipped, set tracking).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $shipment = $this->shipmentRepository->findById($id);
        if (! $shipment) {
            return ApiResponse::notFound(ApiMessages::SHIPMENT_NOT_FOUND);
        }

        $request->validate([
            'status' => ['sometimes', 'string', 'in:shipped,delivered'],
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'carrier_code' => ['nullable', 'string', 'max:32'],
        ]);

        $status = $request->input('status');
        if ($status === 'shipped') {
            $this->fulfillmentService->markShipped(
                $id,
                $request->input('tracking_number') ?? $shipment->trackingNumber,
                $request->input('carrier_code') ?? $shipment->carrierCode
            );
        } elseif ($status === 'delivered') {
            $this->fulfillmentService->markDelivered($id);
        } elseif ($request->has('tracking_number') || $request->has('carrier_code')) {
            $data = [];
            if ($request->has('tracking_number')) {
                $data['tracking_number'] = $request->input('tracking_number');
            }
            if ($request->has('carrier_code')) {
                $data['carrier_code'] = $request->input('carrier_code');
            }
            $this->shipmentRepository->update($id, $data);
        }

        $shipment = $this->shipmentRepository->findById($id);

        return ApiResponse::data(new ShipmentResource($shipment));
    }
}
