<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('guest_email')->nullable();
            $table->string('status', 32)->default('pending_payment');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->json('billing_address_json')->nullable();
            $table->json('shipping_address_json')->nullable();
            $table->string('shipping_method_code', 64)->nullable();
            $table->string('shipping_method_name')->nullable();
            $table->json('tax_breakdown_json')->nullable();
            $table->timestamps();

            $table->index('order_number');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
