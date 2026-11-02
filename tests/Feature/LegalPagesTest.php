<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_terms_and_account_deletion_pages_are_public(): void
    {
        config(['app.support_email' => 'suporte@orbgem.test']);

        $this->get('/privacidade')->assertOk()->assertSee('Política de Privacidade')->assertSee('suporte@orbgem.test');
        $this->get('/termos')->assertOk()->assertSee('Termos de Uso');
        $this->get('/excluir-conta')->assertOk()->assertSee('Mais')->assertSee('suporte@orbgem.test');
    }
}
