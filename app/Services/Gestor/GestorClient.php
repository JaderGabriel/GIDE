<?php

namespace App\Services\Gestor;

use App\Models\Integration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GestorClient
{
    public function __construct(private readonly Integration $integration) {}

    /**
     * Porta de integração para o Porter/Kiper SDK.
     *
     * Auth: POST /Auth/Signin (username/password) => Bearer token
     * Headers obrigatórios: ApplicationKey + Authorization: Bearer <token>
     */
    public function signIn(): string
    {
        $username = (string) data_get($this->integration->extra, 'auth.username', '');
        $password = (string) data_get($this->integration->extra, 'auth.password', '');
        if ($username === '' || $password === '') {
            throw new \RuntimeException('Credenciais do Gestor não configuradas (integrations.extra.auth.username/password).');
        }

        $baseRaw = rtrim((string) ($this->integration->base_url ?? ''), '/');
        if ($baseRaw === '') {
            throw new \RuntimeException('base_url do gestor não configurada.');
        }

        $payload = [
            'username' => $username,
            'password' => $password,
        ];

        // Normaliza base_url:
        // - permite que o usuário tenha configurado por engano a URL completa do Signin
        // - permite base_url com ou sem sufixo /SDK
        $baseNormalized = preg_replace('~/(SDK/)?Auth/Signin$~i', '', $baseRaw) ?? $baseRaw;
        $baseNormalized = rtrim($baseNormalized, '/');
        if ($baseNormalized === '') {
            throw new \RuntimeException('base_url do gestor inválida.');
        }

        $hostRoot = preg_replace('~/SDK$~i', '', $baseNormalized) ?? $baseNormalized;
        $hostRoot = rtrim($hostRoot, '/');
        $sdkRoot = $hostRoot.'/SDK';

        // Prioriza /SDK/Auth/Signin (seu ambiente hom), mas mantém fallback /Auth/Signin.
        $candidateUrls = array_values(array_unique([
            $sdkRoot.'/Auth/Signin',   // => /SDK/Auth/Signin
            $hostRoot.'/Auth/Signin',  // => /Auth/Signin
        ]));

        $resp = null;
        $tried = [];
        foreach ($candidateUrls as $url) {
            $tried[] = $url;
            $r = Http::timeout(30)
                ->withHeaders([
                    // Em alguns ambientes o Signin também exige ApplicationKey.
                    'ApplicationKey' => $this->applicationKey(),
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $resp = $r;
            if ($r->status() === 404) {
                continue;
            }
            break;
        }

        if ($resp && $resp->status() === 404) {
            throw new \RuntimeException('Signin retornou 404. Verifique base_url (com/sem /SDK). Tentativas: '.implode(' | ', $tried));
        }

        if (! $resp->successful()) {
            throw new \RuntimeException('Falha ao autenticar no Gestor (Signin). HTTP '.$resp->status().': '.mb_substr((string) $resp->body(), 0, 500));
        }

        $token = (string) (
            $resp->json('token')
            ?? $resp->json('Token')
            ?? $resp->json('access_token')
            ?? $resp->json('accessToken')
            ?? $resp->json('data.token')
            ?? $resp->json('data.Token')
            ?? $resp->json('result.token')
            ?? $resp->json('result.Token')
            ?? $resp->json('auth.token')
            ?? $resp->json('authToken')
            ?? ''
        );
        if ($token === '') {
            throw new \RuntimeException('Token não encontrado na resposta de Signin. Body: '.mb_substr((string) $resp->body(), 0, 500));
        }

        $this->integration->auth_type = 'bearer';
        $this->integration->auth_token = $token;
        $this->integration->save();

        return $token;
    }

    private function applicationKey(): string
    {
        $key = (string) data_get($this->integration->extra, 'application_key', '');
        if ($key === '') {
            throw new \RuntimeException('ApplicationKey não configurada (integrations.extra.application_key).');
        }

        return $key;
    }

    private function bearerToken(): string
    {
        if ($this->integration->auth_type === 'bearer' && is_string($this->integration->auth_token) && $this->integration->auth_token !== '') {
            return $this->integration->auth_token;
        }

        return $this->signIn();
    }

    private function baseUrl(): string
    {
        $baseRaw = rtrim((string) ($this->integration->base_url ?? ''), '/');
        if ($baseRaw === '') {
            throw new \RuntimeException('base_url do gestor não configurada.');
        }

        // Normaliza base_url para ser sempre o "host root" (sem /SDK e sem /Auth/Signin),
        // assim paths que já começam com /SDK/... não viram /SDK/SDK/...
        $baseNormalized = preg_replace('~/(SDK/)?Auth/Signin$~i', '', $baseRaw) ?? $baseRaw;
        $baseNormalized = rtrim($baseNormalized, '/');
        if ($baseNormalized === '') {
            throw new \RuntimeException('base_url do gestor inválida.');
        }

        $hostRoot = preg_replace('~/SDK$~i', '', $baseNormalized) ?? $baseNormalized;
        $hostRoot = rtrim($hostRoot, '/');
        if ($hostRoot === '') {
            throw new \RuntimeException('base_url do gestor inválida.');
        }

        return $hostRoot;
    }

    /**
     * Faz request autenticado com retry de 401 (refaz Signin uma vez).
     */
    public function request(string $method, string $path, array $options = [])
    {
        $url = $this->baseUrl().'/'.ltrim($path, '/');

        $token = $this->bearerToken();
        $req = Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['ApplicationKey' => $this->applicationKey()]);

        $resp = $req->{$method}($url, $options);
        if ($resp->status() !== 401) {
            return $resp;
        }

        // Token expirou/invalidou: reauth e tenta mais uma vez.
        $token = $this->signIn();

        return Http::timeout(30)
            ->withToken($token)
            ->withHeaders(['ApplicationKey' => $this->applicationKey()])
            ->{$method}($url, $options);
    }

    /**
     * Enroll de facial no Gestor.
     *
     * Observação: o endpoint exato depende da documentação final do SDK.
     * Quando definido, basta configurar em integrations.extra.endpoints.face_enroll_path.
     *
     * @param  mixed  $photoStream  Pode ser resource stream ou Response(stream=true).
     */
    public function enrollFace(string $externalId, mixed $photoStream, ?string $mime = null)
    {
        $configuredPath = (string) data_get($this->integration->extra, 'endpoints.face_enroll_path', '');
        $candidatePaths = [];
        if ($configuredPath !== '') {
            $candidatePaths[] = $configuredPath;
        }
        // Fallbacks comuns do SDK (permite operar sem configuração inicial).
        // Se o endpoint real for diferente, a configuração em integrations.extra.endpoints.face_enroll_path
        // continua tendo prioridade.
        $candidatePaths[] = '/SDK/Face/Enroll';
        $candidatePaths[] = '/SDK/Face/EnrollFace';
        $candidatePaths[] = '/SDK/Face/Enrollment';
        $candidatePaths[] = '/SDK/Face/Enrollments';

        $mime = $mime ?: 'image/jpeg';

        if ($photoStream instanceof Response) {
            // Resposta em stream do Http client do Laravel.
            $body = $photoStream->toPsrResponse()->getBody();
            $stream = $body->detach();
        } else {
            $stream = $photoStream;
        }

        if (! is_resource($stream)) {
            throw new \RuntimeException('Stream da foto inválido.');
        }

        // Envia multipart sem persistir arquivo em disco.
        // Tentamos o path configurado (se houver) e, se der 404, tentamos fallbacks.
        $lastResp = null;
        foreach ($candidatePaths as $path) {
            $url = $this->baseUrl().'/'.ltrim((string) $path, '/');
            $token = $this->bearerToken();

            $resp = Http::timeout(60)
                ->withToken($token)
                ->withHeaders(['ApplicationKey' => $this->applicationKey()])
                ->attach('photo', $stream, 'photo.jpg', ['Content-Type' => $mime])
                ->post($url, [
                    'external_id' => $externalId,
                ]);

            $lastResp = $resp;

            // 404: tenta próximo candidato. Qualquer outro status devolve para o caller
            // (inclusive 4xx/5xx) para não mascarar erro real do endpoint correto.
            if ($resp->status() === 404) {
                continue;
            }

            return $resp;
        }

        if ($configuredPath === '') {
            throw new \RuntimeException('Endpoint de enroll facial não encontrado: configure integrations.extra.endpoints.face_enroll_path.');
        }

        // Se havia path configurado e mesmo assim tudo deu 404, devolve o último response.
        return $lastResp;
    }

    /**
     * Face create para um Guest já existente (Porter API).
     *
     * Endpoint: POST /SDK/Invite/Guest/{guestId}/Face
     * Multipart field: Face (arquivo)
     */
    public function createGuestFace(int|string $guestId, mixed $photoStream, ?string $mime = null): Response
    {
        $mime = $mime ?: 'image/jpeg';

        if ($photoStream instanceof Response) {
            $body = $photoStream->toPsrResponse()->getBody();
            $stream = $body->detach();
        } else {
            $stream = $photoStream;
        }

        if (! is_resource($stream)) {
            throw new \RuntimeException('Stream da foto inválido.');
        }

        $token = $this->bearerToken();
        $url = $this->guestFaceEnrollAbsoluteUrl($guestId);

        return Http::timeout(60)
            ->withToken($token)
            ->withHeaders(['ApplicationKey' => $this->applicationKey(), 'Accept' => 'application/json'])
            ->attach('Face', $stream, 'face.jpg', ['Content-Type' => $mime])
            ->post($url);
    }

    public function getInvite(int|string $inviteId): Response
    {
        return $this->request('get', '/SDK/Invite/'.urlencode((string) $inviteId));
    }

    /**
     * URL absoluta do GET de Invite (mesmo host/path que {@see getInvite}, ex.: base Kiper + `/SDK/Invite/{id}`).
     */
    public function inviteGetAbsoluteUrl(int|string $inviteId): string
    {
        return $this->baseUrl().'/SDK/Invite/'.rawurlencode((string) $inviteId);
    }

    public function guestFaceEnrollAbsoluteUrl(int|string $guestId): string
    {
        return $this->baseUrl().'/SDK/Invite/Guest/'.rawurlencode((string) $guestId).'/Face';
    }

    public function listInvites(int $limit = 200): Response
    {
        $limit = max(1, min(500, $limit));
        $resp = $this->request('get', '/SDK/Invite?limit='.urlencode((string) $limit));
        if ($resp->status() === 404) {
            $resp = $this->request('get', '/SDK/Invites?limit='.urlencode((string) $limit));
        }

        return $resp;
    }

    public function listUnitiesByCondominium(string|int $condominiumId)
    {
        return $this->request('get', '/SDK/Unity', [
            'include' => ['UnityGroup', 'Condominium'],
            'where' => 'w.Condominium.Id='.$condominiumId,
        ]);
    }

    public function getUnityById(string|int $id)
    {
        return $this->request('get', '/SDK/unity/'.urlencode((string) $id), [
            'include' => ['UnityGroup', 'Condominium'],
        ]);
    }

    public function listUnitiesAll()
    {
        return $this->request('get', '/SDK/Unity', [
            'include' => ['UnityGroup', 'Condominium'],
        ]);
    }
}
