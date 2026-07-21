<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_landing_page_renders_the_welcome_component(): void
    {
        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
    }
}
