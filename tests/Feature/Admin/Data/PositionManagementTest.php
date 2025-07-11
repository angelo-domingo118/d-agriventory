<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\User;
use App\Models\Position;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PositionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create(['user_id' => $this->admin->id]);
        $this->regularUser = User::factory()->create();
    }

    public function test_admin_can_access_positions_index_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.data.employees-and-divisions'))
            ->assertStatus(200);
    }

    public function test_regular_user_cannot_access_positions_index_page(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('admin.data.employees-and-divisions'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_position(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.employees-and-divisions.positions.create')
            ->set('title', 'Test Position')
            ->set('position_type', 'OFFICER')
            ->call('save')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'positions', 'view' => 'tree']));

        $this->assertDatabaseHas('positions', ['title' => 'Test Position']);
    }

    public function test_admin_cannot_create_a_position_with_invalid_data(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.employees-and-divisions.positions.create')
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title']);
    }

    public function test_admin_can_update_a_position(): void
    {
        $this->actingAs($this->admin);
        $position = Position::factory()->create();

        Livewire::test('admin.data.employees-and-divisions.positions.edit', ['position' => $position])
            ->set('title', 'Updated Position Title')
            ->set('position_type', $position->position_type)
            ->call('save')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'positions', 'view' => 'tree']));

        $this->assertDatabaseHas('positions', [
            'id' => $position->id,
            'title' => 'Updated Position Title',
        ]);
    }

    public function test_admin_can_delete_an_empty_position(): void
    {
        $this->actingAs($this->admin);
        $position = Position::factory()->create();

        Livewire::test('admin.data.employees-and-divisions.positions.edit', ['position' => $position])
            ->call('delete')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'positions', 'view' => 'tree']));

        $this->assertSoftDeleted('positions', ['id' => $position->id]);
    }

    public function test_admin_cannot_delete_a_position_with_employees(): void
    {
        $this->actingAs($this->admin);
        $position = Position::factory()->create();
        Employee::factory()->create(['position_id' => $position->id]);

        Livewire::test('admin.data.employees-and-divisions.positions.edit', ['position' => $position])
            ->call('delete');

        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }
} 