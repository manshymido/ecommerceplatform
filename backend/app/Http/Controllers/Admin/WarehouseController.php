<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Admin\StoreWarehouseRequest;
use App\Http\Requests\Admin\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Modules\Inventory\Infrastructure\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class WarehouseController extends ApiBaseController
{
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::withCount('stockItems')->orderBy('code')->get();

        return $this->collection(WarehouseResource::collection($warehouses));
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());

        return $this->data(new WarehouseResource($warehouse), 201);
    }

    public function show(string $id): JsonResponse
    {
        $warehouse = Warehouse::with('stockItems.productVariant')->findOrFail($id);

        return $this->data(new WarehouseResource($warehouse));
    }

    public function update(UpdateWarehouseRequest $request, string $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->validated());

        return $this->data(new WarehouseResource($warehouse));
    }

    public function destroy(string $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return $this->deleted('Warehouse');
    }
}
