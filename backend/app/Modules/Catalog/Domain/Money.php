<?php

namespace App\Modules\Catalog\Domain;

class Money
{
    public function __construct(
        public readonly string $amount,
        public readonly string $currency,
    ) {
        if (! is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric');
        }

        if (strlen($currency) !== 3) {
            throw new \InvalidArgumentException('Currency must be 3 characters (ISO 4217)');
        }
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add money with different currencies');
        }

        return new Money(
            (string) (bcadd($this->amount, $other->amount, 2)),
            $this->currency
        );
    }

    public function subtract(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract money with different currencies');
        }

        return new Money(
            (string) (bcsub($this->amount, $other->amount, 2)),
            $this->currency
        );
    }
}
