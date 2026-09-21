<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_executing_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_executing_id')->unique()->constrained('order_executings')->cascadeOnDelete();
            $table->unsignedSmallInteger('rating')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_executing_ratings');
    }
};
