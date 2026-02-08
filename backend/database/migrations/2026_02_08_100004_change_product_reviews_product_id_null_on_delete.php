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
            if (Schema::hasTable('product_reviews_old')) {
                Schema::dropIfExists('product_reviews');
                Schema::rename('product_reviews_old', 'product_reviews');
            }
            Schema::rename('product_reviews', 'product_reviews_old');
            // SQLite keeps index names globally; drop old indexes so we can recreate them on the new table
            DB::statement('DROP INDEX IF EXISTS product_reviews_product_id_status_index');
            DB::statement('DROP INDEX IF EXISTS product_reviews_user_id_product_id_unique');
            Schema::create('product_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->string('status', 32)->default('pending');
                $table->timestamps();

                $table->index(['product_id', 'status']);
                $table->unique(['user_id', 'product_id']);
            });
            DB::statement('INSERT INTO product_reviews (id, user_id, product_id, rating, title, body, status, created_at, updated_at) SELECT id, user_id, product_id, rating, title, body, status, created_at, updated_at FROM product_reviews_old');
            Schema::drop('product_reviews_old');
        } else {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::rename('product_reviews', 'product_reviews_old');
            Schema::create('product_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->unsignedTinyInteger('rating');
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->string('status', 32)->default('pending');
                $table->timestamps();

                $table->index(['product_id', 'status']);
                $table->unique(['user_id', 'product_id']);
            });
            DB::statement('INSERT INTO product_reviews (id, user_id, product_id, rating, title, body, status, created_at, updated_at) SELECT id, user_id, product_id, rating, title, body, status, created_at, updated_at FROM product_reviews_old WHERE product_id IS NOT NULL');
            Schema::drop('product_reviews_old');
        } else {
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
            Schema::table('product_reviews', function (Blueprint $table) {
                $table->foreignId('product_id')->constrained()->onDelete('cascade')->change();
            });
        }
    }
};
