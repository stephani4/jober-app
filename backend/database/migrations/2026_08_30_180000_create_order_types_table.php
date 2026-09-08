<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->timestamps();
        });

        $now = now();

        DB::table('order_types')->insert([
            [
                'id' => 1,
                'name' => 'Купим и привезем',
                'description' => 'Купим товар в магазине и привезем к вам',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Поможем утащить',
                'description' => 'Поможем разгрузить газель, или перетащить, к примеру холодильник',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Поручение',
                'description' => 'Можем распечатать документы или забрать документы и отвезти по адресу',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('order_types', 'id'), (SELECT MAX(id) FROM order_types))");
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('order_type_id')
                ->after('user_id')
                ->default(3)
                ->constrained('order_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_type_id');
        });

        Schema::dropIfExists('order_types');
    }
};
