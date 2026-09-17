<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSwitcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_successfully(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
    }

    public function test_layout_contains_theme_switcher_with_all_five_themes(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('moc-an-theme');
        $response->assertSee("setTheme('moss')", false);
        $response->assertSee("setTheme('wood')", false);
        $response->assertSee("setTheme('cream')", false);
        $response->assertSee("setTheme('blue')", false);
        $response->assertSee("setTheme('black')", false);
    }

    public function test_layout_uses_semantic_theme_tokens(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('bg-page');
        $response->assertSee('text-body');
        $response->assertSee('bg-header');
        $response->assertSee('border-ui-border');
        $response->assertSee('bg-footer');
    }

    public function test_home_sections_use_semantic_surface_tokens(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('bg-surface');
        $response->assertSee('bg-surface-alt');
        $response->assertSee('text-heading');
    }
}