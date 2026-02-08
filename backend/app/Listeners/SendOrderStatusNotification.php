<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Events\OrderFulfilled;
use App\Events\OrderPlaced;
use App\Events\OrderPaymentSucceeded;
use App\Mail\OrderStatusMail;
use App\Models\User;
use App\Modules\Order\Domain\OrderRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusNotification implements ShouldQueue
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {
    }

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $this->sendForOrder($event->orderId, 'placed', null);
    }

    public function handleOrderPaymentSucceeded(OrderPaymentSucceeded $event): void
    {
        $this->sendForOrder($event->orderId, 'paid', null);
    }

    public function handleOrderFulfilled(OrderFulfilled $event): void
    {
        $this->sendForOrder($event->orderId, 'fulfilled', null);
    }

    public function handleOrderCancelled(OrderCancelled $event): void
    {
        $this->sendForOrder($event->orderId, 'cancelled', $event->reason);
    }

    private function sendForOrder(int $orderId, string $status, ?string $reason): void
    {
        $order = $this->orderRepository->findById($orderId);
        if (! $order) {
            return;
        }

        $email = null;
        if ($order->userId !== null) {
            $user = User::find($order->userId);
            $email = $user?->email;
        }
        if ($email === null && $order->guestEmail !== null) {
            $email = $order->guestEmail;
        }
        if ($email === null) {
            return;
        }

        Mail::to($email)->queue(new OrderStatusMail($order->orderNumber, $status, $reason));
    }
}
