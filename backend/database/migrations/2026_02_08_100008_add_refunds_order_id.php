<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('payment_id')->constrained('orders')->nullOnDelete();
        });

        DB::table('refunds')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $orderId = DB::table('payments')->where('id', $row->payment_id)->value('order_id');
                if ($orderId !== null) {
                    DB::table('refunds')->where('id', $row->id)->update(['order_id' => $orderId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
    }
};
