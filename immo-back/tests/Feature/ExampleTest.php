<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Le site public est servi par le front Next.js : la racine mène à l'administration.
     */
    public function test_root_redirects_to_administration(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }
}
