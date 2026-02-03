<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_method_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained()->onDelete('cascade');
            $table->string('country_code', 2);
            $table->string('region', 100)->nullable();
            $table->string('postal_code_pattern', 64)->nullable();
            $table->decimal('min_cart_total', 12, 2)->default(0);
            $table->decimal('max_cart_total', 12, 2)->nullable();
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('per_kg_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->timestamps();

            $table->index(['shipping_method_id', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_method_zones');
    }
};
