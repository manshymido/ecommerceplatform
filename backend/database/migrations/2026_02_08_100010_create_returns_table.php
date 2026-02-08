<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('status', 32)->default('requested');
            $table->foreignId('refund_id')->nullable()->constrained('refunds')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
            $table->index('refund_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
