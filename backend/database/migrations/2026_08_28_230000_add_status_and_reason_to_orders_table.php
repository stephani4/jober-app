<?php

use App\Enums\OrderExecutingStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 32)->default(OrderStatus::Moderate->value)->after('cost');
            $table->text('reason')->nullable()->after('status');
            $table->index('status');
        });

        $completeIds = DB::table('order_executings')
            ->where('status', OrderExecutingStatus::Complete->value)
            ->whereNull('deleted_at')
            ->pluck('order_id');
        $processIds = DB::table('order_executings')
            ->where('status', OrderExecutingStatus::Process->value)
            ->whereNull('deleted_at')
            ->pluck('order_id');

        DB::table('orders')->update(['status' => OrderStatus::Wait->value]);

        if ($processIds->isNotEmpty()) {
            DB::table('orders')->whereIn('id', $processIds)->update([
                'status' => OrderStatus::Process->value,
            ]);
        }
        if ($completeIds->isNotEmpty()) {
            DB::table('orders')->whereIn('id', $completeIds)->update([
                'status' => OrderStatus::Complete->value,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'reason']);
        });
    }
};
