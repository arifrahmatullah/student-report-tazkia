<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_landing_page_is_public(): void
    {
        $this->get('/')->assertOk()->assertSee('Daftar sebagai orang tua');
    }
}
