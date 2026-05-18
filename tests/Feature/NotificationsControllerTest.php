<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin    = User::factory()->create()->assignRole('admin');
        $this->employee = User::factory()->create()->assignRole('employee');
    }

    private function createNotification(User $user, array $data = []): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id'              => Str::uuid(),
            'type'            => \App\Notifications\NewAppointmentNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => array_merge([
                'type'    => 'new_appointment',
                'message' => 'Nouveau rendez-vous',
                'url'     => '/appointments/1',
            ], $data),
        ]);
    }

    // ── markRead ─────────────────────────────────────────────────────────────

    public function test_mark_read_redirects_to_notification_url(): void
    {
        $notification = $this->createNotification($this->admin);

        $this->actingAs($this->admin)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect('/appointments/1');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_read_redirects_to_dashboard_when_no_url(): void
    {
        $notification = $this->createNotification($this->admin, ['url' => null]);

        $this->actingAs($this->admin)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('dashboard'));
    }

    public function test_mark_read_requires_auth(): void
    {
        $notification = $this->createNotification($this->admin);

        $this->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('login'));
    }

    public function test_mark_read_returns_403_for_other_users_notification(): void
    {
        $notification = $this->createNotification($this->admin);

        $this->actingAs($this->employee)
            ->post(route('notifications.read', $notification->id))
            ->assertForbidden();
    }

    public function test_mark_read_returns_404_for_nonexistent_notification(): void
    {
        $this->actingAs($this->admin)
            ->post(route('notifications.read', Str::uuid()))
            ->assertNotFound();
    }

    // ── markAllRead ───────────────────────────────────────────────────────────

    public function test_mark_all_read_marks_only_own_notifications(): void
    {
        $own   = $this->createNotification($this->admin);
        $other = $this->createNotification($this->employee);

        $this->actingAs($this->admin)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
    }

    public function test_mark_all_read_requires_auth(): void
    {
        $this->post(route('notifications.read-all'))
            ->assertRedirect(route('login'));
    }
}
