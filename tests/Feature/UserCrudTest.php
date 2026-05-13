<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function makeEmployee(): User
    {
        $user = User::factory()->create();
        $user->assignRole('employee');

        return $user;
    }

    // --- Index ---

    public function test_admin_can_view_users_list(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('users.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Users/Index')->has('users'));
    }

    public function test_employee_cannot_view_users_list(): void
    {
        $this->actingAs($this->makeEmployee())
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_employee_cannot_create_user(): void
    {
        $this->actingAs($this->makeEmployee())
            ->post(route('users.store'), [
                'name'                  => 'Test',
                'email'                 => 'test@example.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
                'role'                  => 'employee',
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_update_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('employee');

        $this->actingAs($this->makeEmployee())
            ->put(route('users.update', $target), [
                'name'      => $target->name,
                'email'     => $target->email,
                'role'      => 'employee',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_employee_cannot_delete_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('employee');

        $this->actingAs($this->makeEmployee())
            ->delete(route('users.destroy', $target))
            ->assertForbidden();
    }

    // --- Create / Store ---

    public function test_admin_can_render_create_form(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('users.create'))
            ->assertInertia(fn (Assert $page) => $page->component('Users/Create')->has('roles'));
    }

    public function test_admin_can_create_user(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Jean Dupont',
                'email' => 'jean@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'employee',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'jean@example.com']);
        $this->assertTrue(User::where('email', 'jean@example.com')->first()->hasRole('employee'));
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->makeAdmin())
            ->post(route('users.store'), [
                'name' => 'Other',
                'email' => 'taken@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'employee',
            ])
            ->assertSessionHasErrors(['email']);
    }

    // --- Edit / Update ---

    public function test_admin_can_render_edit_form(): void
    {
        $target = User::factory()->create();
        $target->assignRole('employee');

        $this->actingAs($this->makeAdmin())
            ->get(route('users.edit', $target))
            ->assertInertia(fn (Assert $page) => $page->component('Users/Edit')->has('user')->has('roles'));
    }

    public function test_admin_can_update_user_role_and_status(): void
    {
        $target = User::factory()->create();
        $target->assignRole('employee');

        $this->actingAs($this->makeAdmin())
            ->put(route('users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'admin',
                'is_active' => false,
            ])
            ->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertTrue($target->hasRole('admin'));
        $this->assertFalse($target->is_active);
    }

    // --- Destroy ---

    public function test_admin_can_soft_delete_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('employee');

        $this->actingAs($this->makeAdmin())
            ->delete(route('users.destroy', $target))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->delete(route('users.destroy', $admin))
            ->assertSessionHasErrors(['delete']);

        $this->assertNotSoftDeleted($admin);
    }

    // --- Disabled user login ---

    public function test_disabled_user_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }
}
