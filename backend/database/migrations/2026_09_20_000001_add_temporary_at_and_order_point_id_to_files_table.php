<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Временные загрузки и привязка файла к точке заказа.
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->timestamp('temporary_at')->nullable()->after('path');
            $table->foreignId('order_point_id')
                ->nullable()
                ->after('temporary_at')
                ->constrained('order_points')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_point_id');
            $table->dropColumn('temporary_at');
        });
    }
};
