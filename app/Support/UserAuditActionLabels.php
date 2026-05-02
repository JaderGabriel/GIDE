<?php

namespace App\Support;

/**
 * Rótulos PT-BR para o filtro e tabela de auditoria (ação técnica => texto curto).
 */
final class UserAuditActionLabels
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'auth.login' => 'Login',
            'auth.logout' => 'Logout',
            'login_denied_inactive' => 'Login bloqueado (inativo)',
            'session_terminated_inactive' => 'Sessão encerrada (inativo)',
            'user.created' => 'Usuário criado',
            'user.deactivated' => 'Usuário desativado',
            'user.reactivated' => 'Usuário reativado',
            'user.promoted_admin' => 'Usuário promovido a administrador',
            'user.demoted_admin' => 'Administrador rebaixado',
            'integration.overview.test' => 'Teste de integração (visão geral)',
            'integration.bridge.probe' => 'Teste de ponte (JSON)',
            'integration.ieducar.updated' => 'Configuração iEducar salva',
            'integration.ieducar.hmac_rotated' => 'HMAC iEducar rotacionado',
            'integration.gestor.updated' => 'Configuração Gestor salva',
            'integration.gestor.hmac_rotated' => 'HMAC Gestor rotacionado',
            'integration.gestor.webhook_bearer_generated' => 'Token webhook catraca (Gestor) gerado',
            'integration.gestor.test_auth' => 'Teste de auth Gestor',
            'integration.gestor.test_unities' => 'Teste de listagem Unities',
            'integration.sms.updated' => 'Configuração SMS salva',
            'frequencia.preview_enqueued' => 'Frequência iEducar: preview enfileirado',
            'frequencia.apply_enqueued' => 'Frequência iEducar: gravação enfileirada',
            'frequencia.force_send' => 'Frequência iEducar: envio forçado / tentativa',
            'admin.facial.status_refreshed' => 'Facial admin: atualizar status iEducar',
        ];
    }

    public static function label(string $action): string
    {
        return self::all()[$action] ?? $action;
    }

    /**
     * @return list<string>
     */
    public static function filterKeys(): array
    {
        $keys = array_keys(self::all());
        sort($keys);

        return $keys;
    }

    /**
     * Chave técnica do tema (para agrupar o filtro na UI).
     */
    public static function themeIdForAction(string $action): string
    {
        return match (true) {
            str_starts_with($action, 'auth.')
                || in_array($action, ['login_denied_inactive', 'session_terminated_inactive'], true) => 'sessao',
            str_starts_with($action, 'user.') => 'usuarios',
            in_array($action, ['integration.overview.test', 'integration.bridge.probe'], true) => 'integracao_testes',
            str_starts_with($action, 'integration.') => 'integracao_config',
            str_starts_with($action, 'frequencia.') => 'frequencia',
            str_starts_with($action, 'admin.') => 'admin',
            default => 'outros',
        };
    }

    /**
     * Temas ordenados para o filtro (cada um com lista de ações do catálogo).
     *
     * @return list<array{id: string, label: string, actions: list<array{key: string, label: string}>}>
     */
    public static function filterThemes(): array
    {
        $meta = [
            'sessao' => 'Sessão e acesso',
            'usuarios' => 'Gestão de usuários',
            'integracao_testes' => 'Integrações — testes',
            'integracao_config' => 'Integrações — configuração',
            'frequencia' => 'Frequência iEducar',
            'admin' => 'Administração',
            'outros' => 'Outros',
        ];

        $buckets = [];
        foreach (self::all() as $key => $label) {
            $tid = self::themeIdForAction($key);
            $buckets[$tid] ??= [];
            $buckets[$tid][] = ['key' => $key, 'label' => $label];
        }

        foreach ($buckets as &$rows) {
            usort($rows, fn (array $a, array $b): int => strcmp($a['key'], $b['key']));
        }
        unset($rows);

        $order = ['sessao', 'usuarios', 'integracao_testes', 'integracao_config', 'frequencia', 'admin', 'outros'];
        $out = [];
        foreach ($order as $id) {
            if (empty($buckets[$id])) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'label' => $meta[$id] ?? $id,
                'actions' => $buckets[$id],
            ];
        }

        return $out;
    }
}
