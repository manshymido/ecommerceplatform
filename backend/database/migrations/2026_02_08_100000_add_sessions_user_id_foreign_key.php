<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // Clean up leftover from a previous failed run
            if (Schema::hasTable('sessions_old')) {
                Schema::drop('sessions_old');
            }
            Schema::rename('sessions', 'sessions_old');
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
            // Copy only rows with valid user_id or null so FK is satisfied
            \Illuminate\Support\Facades\DB::statement('INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) SELECT s.id, CASE WHEN u.id IS NOT NULL THEN s.user_id ELSE NULL END, s.ip_address, s.user_agent, s.payload, s.last_activity FROM sessions_old s LEFT JOIN users u ON u.id = s.user_id');
            Schema::drop('sessions_old');
        } else {
            Schema::table('sessions', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            Schema::rename('sessions', 'sessions_old');
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
            Schema::getConnection()->statement('INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity) SELECT id, user_id, ip_address, user_agent, payload, last_activity FROM sessions_old');
            Schema::drop('sessions_old');
        } else {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }
    }
};
