<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_username_field(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('name="username"', false);
    }

    public function test_user_can_login_using_username(): void
    {
        $user = User::factory()->create([
            'username' => 'petugas_mui',
            'email' => 'petugas@mui.or.id',
            'role' => 'admin',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'petugas_mui',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_using_email_in_username_field(): void
    {
        $user = User::factory()->create([
            'username' => 'admin_pusat',
            'email' => 'admin.pusat@mui.or.id',
            'role' => 'admin',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'admin.pusat@mui.or.id',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_validation_requires_username_field(): void
    {
        $response = $this->post(route('login'), [
            'username' => '',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('username');
        $response->assertSessionDoesntHaveErrors('email');
    }

    public function test_invalid_password_fails_authentication(): void
    {
        User::factory()->create([
            'username' => 'operator_user',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'operator_user',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }
}
