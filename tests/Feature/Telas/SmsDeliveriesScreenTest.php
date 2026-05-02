<?php

namespace Tests\Feature\Telas;

use App\Models\SmsDelivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[Group('telas-sms')]
class SmsDeliveriesScreenTest extends TestCase
{
    use RefreshDatabase;

    #[TestDox('SMS: administrador abre /sms (listagem)')]
    public function test_admin_can_open_sms_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/sms');

        $this->assertHtmlStatusWithReport(
            $response,
            200,
            'GET /sms autenticado como administrador',
            'Permitir que admin veja a lista de entregas SMS.',
            'HTTP 200 e página HTML da listagem.',
        );
    }

    #[TestDox('SMS: utilizador não-admin não acede a /sms')]
    public function test_non_admin_cannot_open_sms_index(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $response = $this->actingAs($user)->get('/sms');

        $this->assertHtmlStatusWithReport(
            $response,
            403,
            'GET /sms autenticado como utilizador sem perfil admin',
            'Restringir entregas SMS a administradores.',
            'HTTP 403 (Forbidden).',
        );
    }

    #[TestDox('SMS: listagem com dados; filtros status= e event_id=')]
    public function test_admin_sees_deliveries_and_filters_by_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        SmsDelivery::query()->create([
            'event_id' => 'ev-a',
            'template_key' => 'presence',
            'to' => '5511999000001',
            'message' => 'Msg A',
            'status' => 'sent',
        ]);
        SmsDelivery::query()->create([
            'event_id' => 'ev-b',
            'template_key' => 'presence',
            'to' => '5511999000002',
            'message' => 'Msg B',
            'status' => 'pending',
        ]);
        SmsDelivery::query()->create([
            'event_id' => 'ev-c',
            'template_key' => 'presence',
            'to' => '5511999000003',
            'message' => 'Msg C',
            'status' => 'failed',
        ]);

        $rAll = $this->actingAs($admin)->get('/sms');
        $this->assertHtmlStatusWithReport(
            $rAll,
            200,
            'GET /sms com três entregas na base (sent, pending, failed)',
            'Listagem deve incluir pelo menos o número da entrega "sent".',
            'HTTP 200; HTML contém 5511999000001.',
        );
        $this->assertStringContainsString(
            '5511999000001',
            (string) $rAll->getContent(),
            'FALHOU: esperado ver o destino 5511999000001 na listagem completa.',
        );

        $rSent = $this->actingAs($admin)->get('/sms?status=sent');
        $this->assertHtmlStatusWithReport(
            $rSent,
            200,
            'GET /sms?status=sent',
            'Filtrar apenas entregas com status sent; não mostrar pending (0002).',
            'HTTP 200; contém 0001; não contém 0002.',
        );
        $htmlSent = (string) $rSent->getContent();
        $this->assertStringContainsString('5511999000001', $htmlSent, 'FALHOU: filtro sent deveria manter 0001.');
        $this->assertStringNotContainsString('5511999000002', $htmlSent, 'FALHOU: filtro sent não deveria mostrar 0002 (pending).');

        $rEv = $this->actingAs($admin)->get('/sms?event_id=ev-c');
        $this->assertHtmlStatusWithReport(
            $rEv,
            200,
            'GET /sms?event_id=ev-c',
            'Filtrar por event_id; mostrar só a entrega failed (0003).',
            'HTTP 200; contém 0003; não contém 0001.',
        );
        $htmlEv = (string) $rEv->getContent();
        $this->assertStringContainsString('5511999000003', $htmlEv, 'FALHOU: filtro event_id=ev-c deveria mostrar 0003.');
        $this->assertStringNotContainsString('5511999000001', $htmlEv, 'FALHOU: filtro event_id=ev-c não deveria mostrar 0001.');

        $this->reportStructuredTestOutcome(
            'Cenário de dados: três SMS + dois filtros de consulta',
            'Garantir que a UI reflete filtros status e event_id de forma consistente.',
            'Três pedidos GET 200; subconjuntos corretos no HTML.',
            'Listagem completa + sent + event_id ev-c validados por presença/ausência de números.',
            'EXITOSO',
        );
    }

    #[TestDox('SMS: administrador abre detalhe /sms/{id}')]
    public function test_admin_can_open_delivery_detail(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $row = SmsDelivery::query()->create([
            'event_id' => 'ev-detail',
            'template_key' => 'presence',
            'to' => '5511888777666',
            'message' => 'Corpo do SMS',
            'status' => 'pending',
            'aluno_id' => '211',
        ]);

        $response = $this->actingAs($admin)->get('/sms/'.$row->id);

        $this->assertHtmlStatusWithReport(
            $response,
            200,
            'GET /sms/'.$row->id.' (entrega pending, aluno_id=211)',
            'Mostrar detalhe da entrega com destino e contexto.',
            'HTTP 200; página contém 5511888777666.',
        );

        $response->assertSee('5511888777666', false);

        $this->reportStructuredTestOutcome(
            'Detalhe de uma entrega SMS',
            'Confirmar assertSee do número e resumo da página.',
            'Corpo HTML inclui 5511888777666.',
            $this->htmlResponseLine($response),
            'EXITOSO',
        );
    }
}
