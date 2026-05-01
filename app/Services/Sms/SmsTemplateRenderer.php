<?php

namespace App\Services\Sms;

class SmsTemplateRenderer
{
    /**
     * Renderiza tags no formato {{tag}}.
     *
     * - Tags não encontradas viram string vazia (para evitar vazar placeholder em produção).
     */
    public function render(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', function ($m) use ($context) {
            $key = (string) ($m[1] ?? '');
            $value = data_get($context, $key);

            if (is_scalar($value)) {
                return (string) $value;
            }

            return '';
        }, $template) ?? $template;
    }
}
