<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_home_page_redirects_to_login_when_logged_out(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }
}
