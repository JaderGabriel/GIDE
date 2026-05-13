<?php

namespace Tests\Unit;

use App\Models\AccessEvent;
use App\Models\GestorAccessEventDelivery;
use App\Services\Timeline\StudentTimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class StudentTimelineServiceTest extends TestCase
{
    use RefreshDatabase;

    #[TestDox('Timeline inclui evento ignorado (way saída) quando o cod_aluno vem só em name')]
    public function test_timeline_includes_non_entry_access_event_resolved_from_name(): void
    {
        $cod = 884422;

        $access = AccessEvent::query()->create([
            'source' => 'catraca_bearer',
            'event_id' => 'evt-tl-way-exit-1',
            'payload' => ['name' => (string) $cod, 'way' => 'Exit'],
            'occurred_at' => now()->subHour(),
        ]);

        GestorAccessEventDelivery::query()->create([
            'event_id' => 'evt-tl-way-exit-1',
            'inbound_channel' => GestorAccessEventDelivery::CHANNEL_CATRACA_BEARER,
            'access_event_id' => $access->id,
            'inbound_payload' => ['name' => (string) $cod, 'way' => 'Exit'],
            'access_event_was_created' => true,
            'processing_status' => GestorAccessEventDelivery::STATUS_COMPLETED,
            'analysis_json' => [
                'action' => 'ignore',
                'access_path' => 'non_entry',
                'access_way' => 'Exit',
            ],
        ]);

        $svc = new StudentTimelineService;
        $items = $svc->getTimeline($cod, 20);

        $accessItems = $items->where('type', 'access_event');
        $this->assertCount(1, $accessItems);
        $row = $accessItems->first();
        $this->assertSame('exit', $row['data']['timeline_flow'] ?? null);
        $this->assertSame('non_entry', $row['data']['access_path'] ?? null);
        $this->assertStringContainsString('way', strtolower($row['summary'] ?? ''));
    }
}
