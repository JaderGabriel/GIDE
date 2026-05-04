<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacialGestorCatracaHistory extends Model
{
    public const EVENT_SOLICITACAO = 'solicitacao';

    public const EVENT_ENROLL_RESPONSE = 'enroll_response';

    protected $table = 'facial_gestor_catraca_histories';

    protected $fillable = [
        'aluno_id',
        'facial_send_request_id',
        'event_type',
        'invite_id',
        'guest_id',
        'http_status',
        'ok',
        'response_body',
        'effective_url',
    ];

    protected function casts(): array
    {
        return [
            'invite_id' => 'integer',
            'guest_id' => 'integer',
            'http_status' => 'integer',
            'ok' => 'boolean',
        ];
    }

    public function facialSendRequest(): BelongsTo
    {
        return $this->belongsTo(FacialSendRequest::class, 'facial_send_request_id');
    }

    public static function recordSolicitacao(FacialSendRequest $request, string $alunoId): void
    {
        if ($alunoId === '') {
            return;
        }
        static::query()->create([
            'aluno_id' => $alunoId,
            'facial_send_request_id' => $request->id,
            'event_type' => self::EVENT_SOLICITACAO,
            'invite_id' => null,
            'guest_id' => null,
            'http_status' => null,
            'ok' => null,
            'response_body' => null,
            'effective_url' => null,
        ]);
    }

    /**
     * @param  \Illuminate\Http\Client\Response|null  $gestorResp
     */
    public static function recordEnrollResponse(
        FacialSendRequest $request,
        string $alunoId,
        ?int $inviteId,
        ?int $guestId,
        $gestorResp,
        string $effectiveUrl,
    ): void {
        if ($alunoId === '') {
            return;
        }
        $ok = null;
        $http = null;
        $body = null;
        if ($gestorResp && method_exists($gestorResp, 'successful')) {
            $ok = (bool) $gestorResp->successful();
            $http = method_exists($gestorResp, 'status') ? (int) $gestorResp->status() : null;
            $body = method_exists($gestorResp, 'body') ? mb_substr((string) $gestorResp->body(), 0, 50_000) : null;
        }
        static::query()->create([
            'aluno_id' => $alunoId,
            'facial_send_request_id' => $request->id,
            'event_type' => self::EVENT_ENROLL_RESPONSE,
            'invite_id' => $inviteId,
            'guest_id' => $guestId,
            'http_status' => $http,
            'ok' => $ok,
            'response_body' => $body,
            'effective_url' => mb_substr($effectiveUrl, 0, 512),
        ]);
    }
}
