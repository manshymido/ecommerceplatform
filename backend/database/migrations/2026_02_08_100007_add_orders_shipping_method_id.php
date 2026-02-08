<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shipping_method_id')->nullable()->after('shipping_address_json')->constrained('shipping_methods')->nullOnDelete();
        });

        DB::table('orders')->whereNotNull('shipping_method_code')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $methodId = DB::table('shipping_methods')->where('code', $row->shipping_method_code)->value('id');
                if ($methodId !== null) {
                    DB::table('orders')->where('id', $row->id)->update(['shipping_method_id' => $methodId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
        });
    }
};
