<?php

namespace Tests\Feature;

use Tests\TestCase;

class CategoryPageTest extends TestCase
{
    public function test_guest_is_redirected_from_the_categories_page(): void
    {
        $this->get('/categorias')->assertRedirect('/login');
    }

    public function test_categories_page_renders_for_an_authenticated_request(): void
    {
        $this->withoutMiddleware()->get('/categorias')->assertOk();
    }
}
