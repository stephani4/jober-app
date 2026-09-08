<?php

namespace Database\Seeders;

use App\Models\OrderType;
use Illuminate\Database\Seeder;

/**
 * Справочник видов заказа. Идемпотентно: миграция уже вставляет те же id.
 */
class OrderTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            OrderType::BUY_AND_DELIVER => [
                'name' => 'Купим и привезем',
                'description' => 'Купим товар в магазине и привезем к вам',
            ],
            OrderType::HELP_CARRY => [
                'name' => 'Поможем утащить',
                'description' => 'Поможем разгрузить газель, или перетащить, к примеру холодильник',
            ],
            OrderType::ERRAND => [
                'name' => 'Поручение',
                'description' => 'Можем распечатать документы или забрать документы и отвезти по адресу',
            ],
        ];

        foreach ($rows as $id => $payload) {
            OrderType::query()->updateOrCreate(['id' => $id], $payload);
        }
    }
}
