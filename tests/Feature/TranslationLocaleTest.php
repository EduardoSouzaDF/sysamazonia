<?php

namespace Tests\Feature;

use Tests\TestCase;

class TranslationLocaleTest extends TestCase
{
    /**
     * DADO QUE o idioma da aplicação é pt_BR
     * ENTÃO as mensagens de senha, autenticação e validação vêm traduzidas,
     * e não como a chave crua (ex.: "passwords.token").
     */
    public function test_traducoes_pt_br_sao_carregadas(): void
    {
        app()->setLocale('pt_BR');

        $this->assertSame('Este link de redefinição de senha é inválido ou expirou. Solicite um novo link.', __('passwords.token'));
        $this->assertNotSame('auth.failed', __('auth.failed'));
        $this->assertNotSame('validation.required', __('validation.required'));
    }

    /**
     * DADO QUE as pastas de idioma ficam no servidor Linux (sensível a maiúsculas)
     * ENTÃO o idioma padrão em config/app.php aponta para uma pasta que existe
     * com exatamente o mesmo nome, e o fallback é inglês.
     */
    public function test_idioma_padrao_aponta_para_pasta_existente(): void
    {
        $config = file_get_contents(config_path('app.php'));

        $this->assertStringContainsString("'locale' => env('APP_LOCALE', 'pt_BR')", $config);
        $this->assertStringContainsString("'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en')", $config);
        $this->assertContains('pt_BR', scandir(lang_path()));
    }
}
