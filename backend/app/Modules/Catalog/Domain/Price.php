<?php

namespace App\Modules\Catalog\Domain;

class Price
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $productVariantId,
        public readonly Money $money,
        public readonly ?Money $compareAtMoney,
        public readonly ?string $channel,
        public readonly ?\DateTimeImmutable $validFrom,
        public readonly ?\DateTimeImmutable $validTo,
    ) {
    }

    public function isActive(\DateTimeImmutable $now): bool
    {
        if ($this->validFrom && $now < $this->validFrom) {
            return false;
        }

        if ($this->validTo && $now > $this->validTo) {
            return false;
        }

        return true;
    }

    public function hasDiscount(): bool
    {
        return $this->compareAtMoney !== null
            && $this->compareAtMoney->amount > $this->money->amount;
    }
}
