<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_social_login_buttons_are_visible_on_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Google');
        $response->assertSee('GitHub');
        $response->assertSee(route('auth.social.redirect', 'google'));
        $response->assertSee(route('auth.social.redirect', 'github'));
    }

    public function test_social_login_buttons_are_visible_on_register_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('Google');
        $response->assertSee('GitHub');
        $response->assertSee(route('auth.social.redirect', 'google'));
        $response->assertSee(route('auth.social.redirect', 'github'));
    }

    public function test_social_redirect_works_for_google(): void
    {
        $response = $this->get(route('auth.social.redirect', 'google'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->getTargetUrl());
    }

    public function test_social_redirect_works_for_github(): void
    {
        $response = $this->get(route('auth.social.redirect', 'github'));

        $response->assertRedirect();
        $this->assertStringContainsString('github.com/login/oauth/authorize', $response->getTargetUrl());
    }

    public function test_social_redirect_rejects_unsupported_provider(): void
    {
        $response = $this->get(route('auth.social.redirect', 'facebook'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_social_callback_creates_new_user_and_authenticates(): void
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('google-123456');
        $socialUser->shouldReceive('getName')->andReturn('Bảo Lam');
        $socialUser->shouldReceive('getNickname')->andReturn('baolam');
        $socialUser->shouldReceive('getEmail')->andReturn('baolam@gmail.com');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.social.callback', 'google'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::where('email', 'baolam@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Bảo Lam', $user->name);
        $this->assertEquals('google', $user->provider);
        $this->assertEquals('google-123456', $user->provider_id);
        $this->assertEquals('https://lh3.googleusercontent.com/avatar.jpg', $user->avatar);
    }

    public function test_social_callback_logs_in_existing_user_and_links_provider(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@gmail.com',
            'name' => 'Existing User',
        ]);

        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getId')->andReturn('github-9999');
        $socialUser->shouldReceive('getName')->andReturn('Existing User GitHub');
        $socialUser->shouldReceive('getNickname')->andReturn('existing');
        $socialUser->shouldReceive('getEmail')->andReturn('existing@gmail.com');
        $socialUser->shouldReceive('getAvatar')->andReturn('https://avatars.githubusercontent.com/u/9999');

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('github')->andReturn($provider);

        $response = $this->get(route('auth.social.callback', 'github'));

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertEquals('github', $user->provider);
        $this->assertEquals('github-9999', $user->provider_id);
    }

    public function test_social_callback_handles_oauth_exception_gracefully(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \Exception('OAuth token expired or denied'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.social.callback', 'google'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }
}
