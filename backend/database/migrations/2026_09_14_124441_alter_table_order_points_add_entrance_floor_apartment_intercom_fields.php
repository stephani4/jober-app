<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_points', function (Blueprint $table) {
            $table->unsignedInteger('entrance')->nullable();
            $table->unsignedInteger('floor')->nullable();
            $table->string('apartment', 20)->nullable();
            $table->unsignedInteger('intercom')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_points', function (Blueprint $table) {
            $table->dropColumns(['entrance', 'floor', 'apartment', 'intercom']);
        });
    }
};
