<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        SmsTemplate::query()->updateOrCreate(
            ['key' => 'presence_notification'],
            [
                'name' => 'Presença registrada (Gestor → iEducar)',
                'enabled' => true,
                'body' => 'Presença registrada em {{date}} às {{time}}. Aluno: {{aluno_id}}. Matrícula: {{matricula_id}}.',
            ],
        );
    }
}
