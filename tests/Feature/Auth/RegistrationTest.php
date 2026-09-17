<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('Đăng ký');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@example.com',
        ]);
        $response->assertRedirect(route('account.index'));
    }

    public function test_name_is_required_for_registration(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    public function test_email_is_required_for_registration(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => '',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_email_must_be_valid_format(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'not-an-email',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn B',
            'email' => 'duplicate@example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'matkhau123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_password_is_required(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_password_confirmation_must_match(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'test@example.com',
            'password' => 'matkhau123',
            'password_confirmation' => 'khongkhop123',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Nguyễn Văn A',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_access_registration_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect();
    }
}