<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_coupons', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('cart_id')->constrained('coupons')->nullOnDelete();
        });

        DB::table('cart_coupons')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                $couponId = DB::table('coupons')->where('code', $row->coupon_code)->value('id');
                if ($couponId !== null) {
                    DB::table('cart_coupons')->where('id', $row->id)->update(['coupon_id' => $couponId]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('cart_coupons', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
        });
    }
};
