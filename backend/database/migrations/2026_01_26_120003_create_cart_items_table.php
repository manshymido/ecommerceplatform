<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_amount', 12, 2);
            $table->string('unit_price_currency', 3);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_currency', 3)->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'product_variant_id']);
            $table->index('cart_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
