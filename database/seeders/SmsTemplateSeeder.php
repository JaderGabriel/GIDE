<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use App\Support\SmsTemplateKey;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        SmsTemplate::query()->updateOrCreate(
            ['key' => SmsTemplateKey::PRESENCE_CATRACA],
            [
                'name' => 'Presença na catraca',
                'enabled' => true,
                'body' => 'Presença registada na catraca em {{date}} às {{time}}. Aluno: {{aluno_id}}. Matrícula: {{matricula_id}}.',
            ],
        );

        SmsTemplate::query()->updateOrCreate(
            ['key' => SmsTemplateKey::PRESENCE_IEDUCAR_SYNC],
            [
                'name' => 'Confirmação no iEducar (catraca-frequência)',
                'enabled' => true,
                'body' => 'O iEducar confirmou a frequência (HTTP {{ieducar_http_status}}) em {{date}} às {{time}}. Aluno: {{aluno_id}}.',
            ],
        );
    }
}
