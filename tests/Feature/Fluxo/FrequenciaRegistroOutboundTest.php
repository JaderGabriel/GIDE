<?php

namespace Tests\Feature\Fluxo;

use App\Jobs\SendIeducarFrequenciaRegistroJob;
use App\Models\IeducarFrequenciaRegistroDelivery;
use App\Models\Integration;
use App\Support\Ieducar\GideFrequenciaRegistroPlanB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('fluxo-frequencia')]
class FrequenciaRegistroOutboundTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_posts_to_ieducar_and_marks_delivery_completed(): void
    {
        Integration::query()->create([
            'key' => 'ieducar',
            'name' => 'iEducar',
            'enabled' => true,
            'auth_type' => 'none',
            'base_url' => 'https://ieducar.test',
            'auth_token' => 'bearer-for-ieducar',
            'extra' => [],
        ]);

        $payload = GideFrequenciaRegistroPlanB::validateAndNormalize([
            'meta' => ['contract_version' => '1.0'],
            'fonte' => 'gide',
            'presente' => true,
            'identificacao' => ['cod_aluno' => 211],
            'data_ref' => now()->format('Y-m-d'),
        ]);

        $delivery = IeducarFrequenciaRegistroDelivery::query()->create([
            'user_id' => null,
            'mode' => IeducarFrequenciaRegistroDelivery::MODE_PREVIEW,
            'status' => IeducarFrequenciaRegistroDelivery::STATUS_PENDING,
            'payload' => $payload,
        ]);

        Http::fake([
            'ieducar.test/*' => Http::response(['registrado' => true], 200),
        ]);

        SendIeducarFrequenciaRegistroJob::dispatchSync($delivery->id);

        $delivery->refresh();
        $this->assertSame(IeducarFrequenciaRegistroDelivery::STATUS_COMPLETED, $delivery->status);
        $this->assertSame(200, $delivery->http_status);
    }
}
