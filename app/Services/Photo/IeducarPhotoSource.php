<?php

namespace App\Services\Photo;

use App\Models\Integration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class IeducarPhotoSource implements PhotoSource
{
    public function __construct(private readonly Integration $integration) {}

    public function fetch(array $context): Response
    {
        $photoUrl = (string) ($context['photo_url'] ?? '');

        if ($photoUrl === '') {
            $template = (string) data_get($this->integration->extra, 'photo_url_template', '');
            if ($template !== '') {
                $photoUrl = strtr($template, [
                    '{aluno_id}' => (string) ($context['aluno_id'] ?? ''),
                    '{matricula_id}' => (string) ($context['matricula_id'] ?? ''),
                ]);
            }
        }

        if ($photoUrl === '') {
            throw new \RuntimeException('Fonte da foto não configurada.');
        }

        // Importante: stream=true para não carregar tudo em memória.
        return Http::withOptions(['stream' => true])->get($photoUrl);
    }
}
