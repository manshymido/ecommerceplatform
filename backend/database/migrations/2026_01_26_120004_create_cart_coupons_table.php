<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->string('coupon_code');
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_currency', 3)->nullable();
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique('cart_id'); // one coupon per cart for Phase 4
            $table->index('cart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_coupons');
    }
};
