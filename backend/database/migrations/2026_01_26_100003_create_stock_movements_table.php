<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->string('type'); // in, out, adjustment
            $table->integer('quantity'); // positive for in, negative for out
            $table->string('reason_code')->nullable(); // sale, return, adjustment, receipt, etc.
            $table->string('reference_type')->nullable(); // order, adjustment, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('created_at');

            $table->index(['product_variant_id', 'warehouse_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
