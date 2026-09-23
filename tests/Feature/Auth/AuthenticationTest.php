<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Đăng nhập');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'matkhau123',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'matkhau123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_authenticate_using_username(): void
    {
        $user = User::factory()->create([
            'name' => 'mocan_user',
            'email' => 'mocan@example.com',
            'password' => 'matkhau123',
        ]);

        $response = $this->post(route('login.store'), [
            'login' => 'mocan_user',
            'password' => 'matkhau123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_authenticate_using_login_field_with_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Nguyen Van A',
            'email' => 'nguyen@example.com',
            'password' => 'matkhau123',
        ]);

        $response = $this->post(route('login.store'), [
            'login' => 'nguyen@example.com',
            'password' => 'matkhau123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_authenticate_using_phone_number(): void
    {
        $user = User::factory()->create([
            'phone' => '0901234567',
            'password' => 'matkhau123',
        ]);

        $response = $this->post(route('login.store'), [
            'login' => '0901234567',
            'password' => 'matkhau123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_authenticate_using_formatted_phone_number(): void
    {
        $user = User::factory()->create([
            'phone' => '0901234567',
            'password' => 'matkhau123',
        ]);

        $response = $this->post(route('login.store'), [
            'login' => '090 123 4567',
            'password' => 'matkhau123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'matkhau123',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_users_can_not_authenticate_with_non_existing_email(): void
    {
        $response = $this->post(route('login.store'), [
            'email' => 'nonexistent@example.com',
            'password' => 'matkhau123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect();
    }

    public function test_remember_me_functionality(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'matkhau123',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'matkhau123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $response->assertCookieNotExpired(\Illuminate\Support\Facades\Auth::getRecallerName());
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    public function test_logout_cannot_be_performed_via_get_method(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dang-xuat');

        $response->assertStatus(405);
    }
}