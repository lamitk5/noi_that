<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_account_page(): void
    {
        $response = $this->get(route('account.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_account_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Trần Thị B',
            'email' => 'tranthib@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertStatus(200);
        $response->assertSee('Trần Thị B');
        $response->assertSee('tranthib@example.com');
    }

    public function test_header_renders_auth_navigation_when_authenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('account.index'));

        $response->assertStatus(200);
        $response->assertSee('Tài khoản của tôi');
        $response->assertSee('Lịch sử đơn hàng');
        $response->assertSee('Đăng xuất');
        $response->assertDontSee('Đăng nhập');
    }

    public function test_guest_cannot_access_profile_edit_page(): void
    {
        $response = $this->get(route('account.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_profile_edit_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Lê Văn C',
            'email' => 'levanc@example.com',
        ]);

        $response = $this->actingAs($user)->get(route('account.edit'));

        $response->assertStatus(200);
        $response->assertSee('Lê Văn C');
        $response->assertSee('levanc@example.com');
    }

    public function test_user_can_update_name_successfully(): void
    {
        $user = User::factory()->create([
            'name' => 'Tên Cũ',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Tên Mới Được Đổi',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Tên Mới Được Đổi',
        ]);
    }

    public function test_user_can_update_email_successfully(): void
    {
        $user = User::factory()->create([
            'name' => 'Nguyễn Văn An',
            'email' => 'old_email@example.com',
        ]);

        $response = $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Nguyễn Văn An',
            'email' => 'new_email@example.com',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new_email@example.com',
        ]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create(['email' => 'myemail@example.com']);

        $response = $this->actingAs($user)->patch(route('account.update'), [
            'name' => $user->name,
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertEquals('myemail@example.com', $user->fresh()->email);
    }

    public function test_user_can_keep_same_email_when_updating_profile(): void
    {
        $user = User::factory()->create(['email' => 'same@example.com']);

        $response = $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Tên Mới Khác',
            'email' => 'same@example.com',
        ]);

        $response->assertRedirect(route('account.index'));
        $response->assertSessionHasNoErrors();
        $this->assertEquals('Tên Mới Khác', $user->fresh()->name);
    }

    public function test_invalid_email_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Tên Hợp Lệ',
            'email' => 'not-a-valid-email',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_name_is_required_for_profile_update(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('account.update'), [
            'name' => '',
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_password_is_not_modified_when_updating_profile(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('SuperSecretPassword123'),
        ]);
        $oldPasswordHash = $user->password;

        $response = $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Tên Mới',
            'email' => $user->email,
            'password' => 'HackedPassword456',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertEquals($oldPasswordHash, $user->fresh()->password);
    }

    public function test_user_cannot_escalate_role_via_profile_update(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Customer Hacker',
            'email' => $user->email,
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('account.index'));
        $this->assertEquals('customer', $user->fresh()->role);
        $this->assertFalse($user->fresh()->isAdmin());
    }
}