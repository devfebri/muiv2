<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'menu_permissions' => null,
        ]);
    }

    public function test_admin_can_view_operator_permissions_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.operator-permissions.index'));

        $response->assertStatus(200);
        $response->assertSee('Hak Akses & Tugas Operator');
        $response->assertSee('operators-table');
    }

    public function test_operator_cannot_access_operator_permissions_page(): void
    {
        $response = $this->actingAs($this->operator)->get(route('admin.operator-permissions.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_update_operator_menu_permissions(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('admin.operator-permissions.update', $this->operator->id),
            ['permissions' => ['berita', 'livechat']]
        );

        $response->assertRedirect(route('admin.operator-permissions.index'));
        $this->operator->refresh();

        $this->assertTrue($this->operator->hasMenuPermission('berita'));
        $this->assertTrue($this->operator->hasMenuPermission('livechat'));
        $this->assertFalse($this->operator->hasMenuPermission('fatwa'));
        $this->assertFalse($this->operator->hasMenuPermission('surat'));
    }

    public function test_operator_with_permission_can_access_allowed_menu(): void
    {
        $this->operator->update(['menu_permissions' => ['berita']]);

        $response = $this->actingAs($this->operator)->get(route('operator.berita.index'));

        $response->assertStatus(200);
    }

    public function test_operator_without_permission_is_forbidden_from_restricted_menu(): void
    {
        $this->operator->update(['menu_permissions' => ['berita']]);

        $response = $this->actingAs($this->operator)->get(route('operator.fatwa.index'));

        $response->assertStatus(403);
    }

    public function test_operator_livechat_permission_access_control(): void
    {
        // 1. Without livechat permission -> 403
        $this->operator->update(['menu_permissions' => ['berita', 'fatwa']]);
        $forbiddenResponse = $this->actingAs($this->operator)->get(route('admin.livechat.index'));
        $forbiddenResponse->assertStatus(403);

        // 2. With livechat permission -> 200
        $this->operator->update(['menu_permissions' => ['berita', 'livechat']]);
        $allowedResponse = $this->actingAs($this->operator)->get(route('admin.livechat.index'));
        $allowedResponse->assertStatus(200);
    }

    public function test_admin_can_grant_all_and_revoke_all_permissions(): void
    {
        // Grant All
        $this->actingAs($this->admin)->post(route('admin.operator-permissions.grant-all', $this->operator->id));
        $this->operator->refresh();
        $this->assertCount(count(User::OPERATOR_PERMISSIONS), $this->operator->menu_permissions);

        // Revoke All
        $this->actingAs($this->admin)->post(route('admin.operator-permissions.revoke-all', $this->operator->id));
        $this->operator->refresh();
        $this->assertSame([], $this->operator->menu_permissions);
        $this->assertFalse($this->operator->hasMenuPermission('berita'));
    }

    public function test_operator_with_kategori_permission_can_manage_kategori(): void
    {
        // 1. Without kategori permission -> 403
        $this->operator->update(['menu_permissions' => ['berita']]);
        $this->actingAs($this->operator)->get(route('operator.kategori.index'))->assertStatus(403);

        // 2. With kategori permission -> 200 and can store kategori
        $this->operator->update(['menu_permissions' => ['kategori']]);
        $this->actingAs($this->operator)->get(route('operator.kategori.index'))->assertStatus(200);

        $storeResponse = $this->actingAs($this->operator)->postJson(route('operator.kategori.store'), [
            'nama' => 'Pendidikan Islam',
            'warna' => '#007f5f',
            'deskripsi' => 'Seputar dunia pendidikan Islam',
            'aktif' => true,
        ]);

        $storeResponse->assertStatus(201);
        $this->assertDatabaseHas('kategoris', ['nama' => 'Pendidikan Islam']);
    }

    public function test_new_operator_created_gets_default_permissions_for_berita_and_layanan(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('admin.users.store'), [
            'name' => 'Operator Baru',
            'username' => 'operatorbaru',
            'role' => 'operator',
            'email' => 'operatorbaru@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $newOperator = User::where('username', 'operatorbaru')->first();
        $this->assertNotNull($newOperator);
        $this->assertEquals(['berita', 'livechat', 'konsultasi'], $newOperator->menu_permissions);

        // Can access Berita & Layanan
        $this->assertTrue($newOperator->hasMenuPermission('berita'));
        $this->assertTrue($newOperator->hasMenuPermission('livechat'));
        $this->assertTrue($newOperator->hasMenuPermission('konsultasi'));

        // Cannot access others by default
        $this->assertFalse($newOperator->hasMenuPermission('kategori'));
        $this->assertFalse($newOperator->hasMenuPermission('surat'));
        $this->assertFalse($newOperator->hasMenuPermission('fatwa'));
        $this->assertFalse($newOperator->hasMenuPermission('kategori-fatwa'));
    }

    public function test_ajax_crud_operations_work_seamlessly(): void
    {
        // 1. DataTables AJAX JSON index
        $ajaxJson = $this->actingAs($this->admin)->getJson(route('admin.operator-permissions.index'));
        $ajaxJson->assertStatus(200);
        $ajaxJson->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);

        // 2. Search < 3 chars -> not filtered
        $shortSearch = $this->actingAs($this->admin)->getJson(route('admin.operator-permissions.index', [
            'search' => ['value' => 'ab'],
        ]));
        $shortSearch->assertStatus(200);
        $this->assertEquals($shortSearch->json('recordsTotal'), $shortSearch->json('recordsFiltered'));

        // 3. Search >= 3 chars matching operator -> filtered
        $validSearch = $this->actingAs($this->admin)->getJson(route('admin.operator-permissions.index', [
            'search' => ['value' => substr($this->operator->name, 0, 4)],
        ]));
        $validSearch->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $validSearch->json('recordsFiltered'));

        // 4. AJAX update permissions
        $ajaxUpdate = $this->actingAs($this->admin)->putJson(
            route('admin.operator-permissions.update', $this->operator->id),
            ['permissions' => ['berita', 'kategori']]
        );
        $ajaxUpdate->assertStatus(200);
        $ajaxUpdate->assertJsonPath('success', true);
        $ajaxUpdate->assertJsonPath('user.permissions', ['berita', 'kategori']);

        // 5. AJAX grant all
        $ajaxGrantAll = $this->actingAs($this->admin)->postJson(
            route('admin.operator-permissions.grant-all', $this->operator->id)
        );
        $ajaxGrantAll->assertStatus(200);
        $ajaxGrantAll->assertJsonPath('success', true);

        // 6. AJAX revoke all
        $ajaxRevokeAll = $this->actingAs($this->admin)->postJson(
            route('admin.operator-permissions.revoke-all', $this->operator->id)
        );
        $ajaxRevokeAll->assertStatus(200);
        $ajaxRevokeAll->assertJsonPath('success', true);
        $ajaxRevokeAll->assertJsonPath('permissions', []);
    }
}
