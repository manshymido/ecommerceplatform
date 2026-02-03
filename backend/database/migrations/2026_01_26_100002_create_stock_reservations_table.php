<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('quantity');
            $table->string('source_type'); // cart, order
            $table->unsignedBigInteger('source_id');
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active'); // active, expired, consumed
            $table->timestamps();

            $table->index(['product_variant_id', 'warehouse_id']);
            $table->index(['source_type', 'source_id']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
