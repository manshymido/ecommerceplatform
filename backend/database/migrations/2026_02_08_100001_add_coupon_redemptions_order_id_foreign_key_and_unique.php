<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Clean up leftover from a previous failed run
            if (Schema::hasTable('coupon_redemptions_old')) {
                Schema::dropIfExists('coupon_redemptions');
                Schema::rename('coupon_redemptions_old', 'coupon_redemptions');
            }
            Schema::rename('coupon_redemptions', 'coupon_redemptions_old');
            // SQLite keeps index names globally; drop the old index so we can recreate it on the new table
            DB::statement('DROP INDEX IF EXISTS coupon_redemptions_coupon_id_user_id_index');
            Schema::create('coupon_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->timestamp('redeemed_at');
                $table->timestamps();

                $table->index(['coupon_id', 'user_id']);
            });
            DB::statement('INSERT INTO coupon_redemptions (id, coupon_id, user_id, order_id, redeemed_at, created_at, updated_at) SELECT id, coupon_id, user_id, order_id, redeemed_at, created_at, updated_at FROM coupon_redemptions_old');
            Schema::drop('coupon_redemptions_old');
        } else {
            Schema::table('coupon_redemptions', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            });
        }

        // One redemption per coupon per order (when order_id is set). MySQL allows multiple NULLs in UNIQUE.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX coupon_redemptions_coupon_order_unique ON coupon_redemptions(coupon_id, order_id) WHERE order_id IS NOT NULL');
        } elseif ($driver === 'mysql') {
            Schema::table('coupon_redemptions', function (Blueprint $table) {
                $table->unique(['coupon_id', 'order_id'], 'coupon_redemptions_coupon_order_unique');
            });
        } else {
            DB::statement('CREATE UNIQUE INDEX coupon_redemptions_coupon_order_unique ON coupon_redemptions(coupon_id, order_id) WHERE order_id IS NOT NULL');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite' || $driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS coupon_redemptions_coupon_order_unique');
        } else {
            try {
                Schema::table('coupon_redemptions', function (Blueprint $table) {
                    $table->dropUnique('coupon_redemptions_coupon_order_unique');
                });
            } catch (\Throwable $e) {
                // Index may not exist
            }
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            Schema::rename('coupon_redemptions', 'coupon_redemptions_old');
            Schema::create('coupon_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->timestamp('redeemed_at');
                $table->timestamps();
                $table->index(['coupon_id', 'user_id']);
            });
            DB::statement('INSERT INTO coupon_redemptions (id, coupon_id, user_id, order_id, redeemed_at, created_at, updated_at) SELECT id, coupon_id, user_id, order_id, redeemed_at, created_at, updated_at FROM coupon_redemptions_old');
            Schema::drop('coupon_redemptions_old');
        } else {
            Schema::table('coupon_redemptions', function (Blueprint $table) {
                $table->dropForeign(['order_id']);
            });
        }
    }
};

