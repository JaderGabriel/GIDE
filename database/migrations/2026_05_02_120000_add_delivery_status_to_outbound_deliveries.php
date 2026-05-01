<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_deliveries', function (Blueprint $table) {
            $table->string('delivery_status', 32)->default('pending')->after('next_retry_at')->index();
            $table->timestamp('last_attempt_at')->nullable()->after('delivery_status')->index();
        });

        $max = (int) config('gide.deliveries.max_attempts', 3);

        DB::table('outbound_deliveries')->orderBy('id')->chunkById(200, function ($rows) use ($max) {
            foreach ($rows as $row) {
                $status = $this->computeStatus($row, $max);
                DB::table('outbound_deliveries')->where('id', $row->id)->update(['delivery_status' => $status]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('outbound_deliveries', function (Blueprint $table) {
            $table->dropColumn(['delivery_status', 'last_attempt_at']);
        });
    }

    private function computeStatus(object $row, int $max): string
    {
        if ($row->delivered_at !== null) {
            return 'completed';
        }
        if ((int) $row->attempts >= $max) {
            return 'failed';
        }
        if ($row->next_retry_at !== null && strtotime((string) $row->next_retry_at) > time()) {
            return 'retry_scheduled';
        }

        return 'pending';
    }
};
