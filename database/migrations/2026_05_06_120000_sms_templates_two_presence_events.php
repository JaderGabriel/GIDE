<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sms_templates')->where('key', 'presence_notification')->update(['key' => 'presence_catraca']);

        DB::table('sms_deliveries')->where('template_key', 'presence_notification')->update(['template_key' => 'presence_catraca']);

        SmsTemplate::query()->updateOrCreate(
            ['key' => 'presence_ieducar_sync'],
            [
                'name' => 'Presença confirmada no iEducar (catraca-frequência)',
                'enabled' => true,
                'body' => 'O iEducar confirmou a frequência (HTTP {{ieducar_http_status}}) em {{date}} às {{time}}. Aluno: {{aluno_id}}.',
            ],
        );
    }

    public function down(): void
    {
        SmsTemplate::query()->where('key', 'presence_ieducar_sync')->delete();

        DB::table('sms_deliveries')->where('template_key', 'presence_catraca')->update(['template_key' => 'presence_notification']);

        DB::table('sms_templates')->where('key', 'presence_catraca')->update(['key' => 'presence_notification']);
    }
};
