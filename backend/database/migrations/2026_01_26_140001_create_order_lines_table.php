<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->nullable()->constrained()->onDelete('set null');
            $table->string('product_name_snapshot');
            $table->string('sku_snapshot', 64);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_amount', 12, 2);
            $table->string('unit_price_currency', 3);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_currency', 3)->nullable();
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_line_amount', 12, 2);
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
