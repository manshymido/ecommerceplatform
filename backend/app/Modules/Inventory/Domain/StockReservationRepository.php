<?php

namespace App\Modules\Inventory\Domain;

interface StockReservationRepository
{
    /** @return StockReservation[] */
    public function getActiveBySource(string $sourceType, int $sourceId): array;

    public function reserve(int $productVariantId, int $warehouseId, int $quantity, string $sourceType, int $sourceId, ?\DateTimeInterface $expiresAt = null): StockReservation;

    public function releaseBySource(string $sourceType, int $sourceId): void;

    public function markConsumedBySource(string $sourceType, int $sourceId): void;

    public function expireStale(): void;
}
