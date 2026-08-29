<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_executing_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_executing_id')->constrained('order_executings')->cascadeOnDelete();
            $table->foreignId('order_point_id')->constrained('order_points')->restrictOnDelete();
            $table->string('status', 32)->default('wait')->index();
            $table->timestamp('process_at')->nullable();
            $table->timestamp('complete_at')->nullable();
            $table->timestamps();

            $table->unique(['order_executing_id', 'order_point_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_executing_points');
    }
};
