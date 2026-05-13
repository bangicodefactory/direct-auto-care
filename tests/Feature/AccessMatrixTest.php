<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Access matrix smoke test — documents which roles can reach which routes.
 *
 * GET routes:
 * | Route                  | Guest        | Employee | Admin |
 * |------------------------|--------------|----------|-------|
 * | GET /dashboard         | →/login      | 200      | 200   |
 * | GET /profile           | →/login      | 200      | 200   |
 * | GET /users             | →/login      | 403      | 200   |
 * | GET /users/create      | →/login      | 403      | 200   |
 * | GET /users/{id}/edit   | →/login      | 403      | 200   |
 *
 * Write routes (POST /users, PUT /users/{id}, DELETE /users/{id}):
 * covered in UserCrudTest — see that class for the full write matrix.
 */
class AccessMatrixTest extends TestCase
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

    // -------------------------------------------------------------------------
    // Guest — all protected routes redirect to login
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_profile(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_users_index(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_users_create(): void
    {
        $this->get(route('users.create'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_users_edit(): void
    {
        $target = User::factory()->create();
        $this->get(route('users.edit', $target))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_users_destroy(): void
    {
        $target = User::factory()->create();
        $this->delete(route('users.destroy', $target))->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Employee — shared routes accessible, admin-only routes return 403
    // -------------------------------------------------------------------------

    public function test_employee_can_access_dashboard(): void
    {
        $this->actingAs($this->makeEmployee())->get(route('dashboard'))->assertOk();
    }

    public function test_employee_can_access_profile(): void
    {
        $this->actingAs($this->makeEmployee())->get(route('profile.edit'))->assertOk();
    }

    public function test_employee_cannot_access_users_index(): void
    {
        $this->actingAs($this->makeEmployee())->get(route('users.index'))->assertForbidden();
    }

    public function test_employee_cannot_access_users_create(): void
    {
        $this->actingAs($this->makeEmployee())->get(route('users.create'))->assertForbidden();
    }

    public function test_employee_cannot_access_users_edit(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->makeEmployee())->get(route('users.edit', $target))->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Admin — all routes accessible
    // -------------------------------------------------------------------------

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('dashboard'))->assertOk();
    }

    public function test_admin_can_access_profile(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('profile.edit'))->assertOk();
    }

    public function test_admin_can_access_users_index(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('users.index'))->assertOk();
    }

    public function test_admin_can_access_users_create(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('users.create'))->assertOk();
    }

    public function test_admin_can_access_users_edit(): void
    {
        $target = User::factory()->create();
        $this->actingAs($this->makeAdmin())->get(route('users.edit', $target))->assertOk();
    }
}
