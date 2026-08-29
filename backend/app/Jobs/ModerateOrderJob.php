<?php

namespace App\Jobs;

use App\Services\OrderModerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Фоновая автоматическая модерация заказа.
 */
class ModerateOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
    ) {}

    /**
     * Прогоняет заказ по правилам модерации.
     */
    public function handle(OrderModerationService $moderation): void
    {
        $moderation->process($this->orderId);
    }
}
