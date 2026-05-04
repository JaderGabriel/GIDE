<?php

namespace Database\Seeders;

use App\Models\Integration;
use Illuminate\Database\Seeder;

class IntegrationSeeder extends Seeder
{
    public function run(): void
    {
        Integration::query()->updateOrCreate(
            ['key' => 'ieducar'],
            [
                'name' => 'iEducar 2.11',
                'enabled' => false,
                'base_url' => null,
                'auth_type' => 'none',
                'extra' => [
                    'access_key' => null,
                    'presence' => [
                        'ignore_exit_events' => true,
                        'windows' => [
                            ['name' => 'entrada_manha', 'start' => '06:30', 'end' => '09:30', 'tolerance_minutes' => 15],
                            ['name' => 'entrada_tarde', 'start' => '12:00', 'end' => '14:30', 'tolerance_minutes' => 15],
                            ['name' => 'entrada_noite', 'start' => '18:00', 'end' => '20:30', 'tolerance_minutes' => 15],
                        ],
                        'payload_map' => [
                            'aluno_id' => 'aluno_id',
                            'matricula_id' => 'matricula_id',
                            'event_type' => 'type',
                        ],
                    ],
                    'photo_url_template' => null,
                ],
            ],
        );

        Integration::query()->updateOrCreate(
            ['key' => 'gestor'],
            [
                'name' => 'Gestor (Porter/Kiper SDK)',
                'enabled' => false,
                'base_url' => null,
                'auth_type' => 'bearer',
                'auth_token' => null,
                'extra' => [
                    'application_key' => null,
                    'auth' => [
                        'username' => null,
                        'password' => null,
                    ],
                    'endpoints' => [
                        // Path do endpoint do SDK que receberá o payload de matrícula/aluno.
                        // Depende da documentação final do Gestor.
                        'enrollment_sync_path' => null,
                        // Path do endpoint do SDK para cadastrar/atualizar facial.
                        // Depende da documentação final do Gestor.
                        'face_enroll_path' => null,
                    ],
                    'onboarding' => [
                        'condominium_id' => null,
                        'access_profile_id' => null,
                    ],
                ],
            ],
        );

        Integration::query()->updateOrCreate(
            ['key' => 'sms'],
            [
                'name' => 'SMS',
                'enabled' => false,
                'base_url' => config('integrations.sms.default_base_url'),
                'auth_type' => 'api_token',
                'auth_token' => null, // X-API-TOKEN
                'extra' => [
                    'provider' => 'zenvia',
                    'from' => null, // identificador "from" exigido pelo provedor
                    'payload_map' => [
                        // campo no payload do Gestor com telefone do responsável
                        'phone' => 'phone',
                    ],
                ],
            ],
        );
    }
}
