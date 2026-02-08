<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('order_id')->constrained('products')->nullOnDelete();
        });

        // Backfill from product_variants
        DB::table('order_lines')
            ->whereNotNull('product_variant_id')
            ->orderBy('id')
            ->chunk(500, function ($lines) {
                foreach ($lines as $line) {
                    $productId = DB::table('product_variants')
                        ->where('id', $line->product_variant_id)
                        ->value('product_id');
                    if ($productId !== null) {
                        DB::table('order_lines')
                            ->where('id', $line->id)
                            ->update(['product_id' => $productId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
    }
};
