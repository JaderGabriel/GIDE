<?php

namespace App\Console\Commands;

use App\Jobs\ProcessGestorAccessEventDeliveryJob;
use App\Models\GestorAccessEventDelivery;
use App\Support\DateDisplay;
use Illuminate\Console\Command;

class GideGestorAccessEventDeliveriesDispatchPendingCommand extends Command
{
    protected $signature = 'gide:gestor-access-event-deliveries:dispatch-pending {--limit=100 : Máximo de IDs a enfileirar por execução}';

    protected $description = 'Enfileira jobs de preview catraca-frequência (iEducar) para entregas access-event em status pending';

    public function handle(): int
    {
        $limit = max(1, min(500, (int) $this->option('limit')));
        $ids = GestorAccessEventDelivery::query()
            ->where('processing_status', GestorAccessEventDelivery::STATUS_PENDING)
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        $n = 0;
        foreach ($ids as $id) {
            ProcessGestorAccessEventDeliveryJob::dispatch((int) $id);
            $n++;
        }

        $this->info('Enfileirados: '.$n.' (pendentes encontrados: '.$ids->count().').');
        $this->comment(DateDisplay::cliReferenceLine());

        return 0;
    }
}
