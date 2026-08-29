<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_executings', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('complete_at');
            $table->decimal('lon', 10, 7)->nullable()->after('lat');
            $table->timestamp('location_at')->nullable()->after('lon');
        });
    }

    public function down(): void
    {
        Schema::table('order_executings', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lon', 'location_at']);
        });
    }
};
