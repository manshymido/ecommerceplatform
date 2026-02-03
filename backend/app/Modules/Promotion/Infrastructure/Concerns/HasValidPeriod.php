<?php

namespace App\Modules\Promotion\Infrastructure\Concerns;

trait HasValidPeriod
{
    protected function isWithinPeriod(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if (isset($this->starts_at) && $this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }
        if (isset($this->ends_at) && $this->ends_at && now()->gt($this->ends_at)) {
            return false;
        }

        return true;
    }
}
