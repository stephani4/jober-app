<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Общая таблица загруженных на диск файлов.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // исходное имя файла от клиента
            $table->string('extension', 16);        // расширение без точки
            $table->unsignedBigInteger('size');     // размер в байтах
            $table->string('path');                 // путь относительно корня диска загрузок
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
