<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Clean up test uploaded files if any created
        $files = File::glob(public_path('uploads/profil/profil_test_*'));
        foreach ($files as $file) {
            File::delete($file);
        }

        parent::tearDown();
    }

    public function test_guest_cannot_access_profile_page(): void
    {
        $response = $this->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Fauzi Ahmad',
            'username' => 'fauziahmad',
            'email' => 'fauzi@example.com',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Fauzi Ahmad');
        $response->assertSee('fauziahmad');
        $response->assertSee('fauzi@example.com');
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'username' => 'olduser',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'New Name',
            'name_gelar' => 'Dr. New Name, M.Ag',
            'username' => 'newuser_attempt',
            'email' => 'new@example.com',
            'nohp' => '081234567890',
            'jk' => 'L',
            'alamat' => 'Jl. Merdeka No. 10',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('Dr. New Name, M.Ag', $user->name_gelar);
        // Username is immutable and must remain olduser
        $this->assertEquals('olduser', $user->username);
        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('081234567890', $user->nohp);
        $this->assertEquals('L', $user->jk);
        $this->assertEquals('Jl. Merdeka No. 10', $user->alamat);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        $user = User::factory()->create([
            'username' => 'photouser',
            'email' => 'photo@example.com',
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'foto' => $file,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->foto);
        $this->assertFileExists(public_path('uploads/profil/'.$user->foto));

        // Clean up file
        if (File::exists(public_path('uploads/profil/'.$user->foto))) {
            File::delete(public_path('uploads/profil/'.$user->foto));
        }
    }

    public function test_user_can_remove_existing_profile_photo(): void
    {
        $user = User::factory()->create([
            'username' => 'removephotouser',
            'email' => 'remove@example.com',
        ]);

        File::ensureDirectoryExists(public_path('uploads/profil'));
        $dummyFilename = 'profil_test_'.time().'.jpg';
        file_put_contents(public_path('uploads/profil/'.$dummyFilename), 'dummy');

        $user->foto = $dummyFilename;
        $user->save();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'hapus_foto' => '1',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->foto);
        $this->assertFileDoesNotExist(public_path('uploads/profil/'.$dummyFilename));
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::factory()->create([
            'username' => 'passuser',
            'email' => 'pass@example.com',
            'password' => Hash::make('old-secret-123'),
        ]);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password_current' => 'old-secret-123',
            'password' => 'new-secret-456',
            'password_confirmation' => 'new-secret-456',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('new-secret-456', $user->password));
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'username' => 'wrongpassuser',
            'email' => 'wrongpass@example.com',
            'password' => Hash::make('correct-secret'),
        ]);

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password_current' => 'wrong-secret',
            'password' => 'new-secret-456',
            'password_confirmation' => 'new-secret-456',
        ]);

        $response->assertSessionHasErrors('password_current');

        $user->refresh();
        $this->assertTrue(Hash::check('correct-secret', $user->password));
    }
}
