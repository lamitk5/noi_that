<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_email_and_verify(): void
    {
        $response = $this->post('/register', [
            'username' => 'testuser_email',
            'name' => 'Nguyen Van A',
            'email_or_phone' => 'nguyenvana@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/xac-thuc');
        $this->assertDatabaseHas('users', [
            'username' => 'testuser_email',
            'email' => 'nguyenvana@example.com',
            'is_active' => 0,
        ]);

        $verifyData = session('verify_data');
        $this->assertNotNull($verifyData);
        $this->assertEquals('email', $verifyData['type']);
        $this->assertEquals('nguyenvana@example.com', $verifyData['target']);
        $code = $verifyData['code'];

        // Submit correct verification code
        $verifyResponse = $this->post('/xac-thuc', [
            'code' => $code,
        ]);

        $verifyResponse->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::where('username', 'testuser_email')->first();
        $this->assertTrue((bool) $user->is_active);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_user_can_register_with_phone_and_verify_otp(): void
    {
        $response = $this->post('/register', [
            'username' => 'testuser_phone',
            'name' => 'Tran Van B',
            'email_or_phone' => '0987654321',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/xac-thuc');
        $this->assertDatabaseHas('users', [
            'username' => 'testuser_phone',
            'phone' => '0987654321',
            'is_active' => 0,
        ]);

        $verifyData = session('verify_data');
        $this->assertNotNull($verifyData);
        $this->assertEquals('phone', $verifyData['type']);
        $this->assertEquals('0987654321', $verifyData['target']);
        $otp = $verifyData['code'];

        // Submit correct OTP
        $verifyResponse = $this->post('/xac-thuc', [
            'code' => $otp,
        ]);

        $verifyResponse->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::where('username', 'testuser_phone')->first();
        $this->assertTrue((bool) $user->is_active);
    }

    public function test_user_can_login_with_username_or_email_or_phone(): void
    {
        $user = User::create([
            'username' => 'multiflex',
            'name' => 'Le Van C',
            'email' => 'multiflex@example.com',
            'phone' => '0912345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        // 1. Login with username
        $response1 = $this->post('/login', [
            'email' => 'multiflex',
            'password' => 'password123',
        ]);
        $response1->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        Auth::logout();

        // 2. Login with email
        $response2 = $this->post('/login', [
            'email' => 'multiflex@example.com',
            'password' => 'password123',
        ]);
        $response2->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        Auth::logout();

        // 3. Login with phone
        $response3 = $this->post('/login', [
            'email' => '0912345678',
            'password' => 'password123',
        ]);
        $response3->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::create([
            'username' => 'activeuser',
            'name' => 'Active User',
            'email' => 'active@example.com',
            'phone' => '0933333333',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'activeuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_oauth_warns_when_credentials_not_configured(): void
    {
        config(['services.google.client_id' => '']);
        $response = $this->get('/auth/google/redirect');
        $response->assertRedirect('/login');
        $response->assertSessionHas('error');
    }

    public function test_google_oauth_redirects_to_google_when_configured(): void
    {
        config([
            'services.google.client_id' => 'real-google-client-id-12345',
            'services.google.client_secret' => 'real-google-secret-67890',
            'services.google.redirect' => 'http://127.0.0.1:8000/auth/google/callback',
        ]);

        $response = $this->get('/auth/google/redirect');
        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_registration_succeeds_even_when_mail_server_fails(): void
    {
        \Illuminate\Support\Facades\Mail::shouldReceive('html')
            ->andThrow(new \Exception('Connection to smtp.gmail.com timed out'));

        $response = $this->post('/register', [
            'username' => 'testuser_smtp_fail',
            'name' => 'Nguyen Mail Fail',
            'email_or_phone' => 'mailfail@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/xac-thuc');
        $this->assertDatabaseHas('users', [
            'username' => 'testuser_smtp_fail',
            'email' => 'mailfail@example.com',
        ]);
        $this->assertNotNull(session('verify_data'));
        $this->assertStringContainsString('Mã xác thực tài khoản của bạn là:', session('info'));
    }
}
