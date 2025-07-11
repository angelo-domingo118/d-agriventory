<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\User;
use App\Models\Division;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DivisionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create([
            'user_id' => $this->admin->id,
        ]);

        // Create a regular user
        $this->regularUser = User::factory()->create();
    }

    public function test_admin_can_access_divisions_index_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.data.employees-and-divisions.divisions.index'))
            ->assertStatus(200);
    }

    public function test_regular_user_cannot_access_divisions_index_page(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('admin.data.employees-and-divisions.divisions.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_division(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.employees-and-divisions.divisions.create')
            ->set('name', 'Test Division')
            ->set('code', 'TD01')
            ->call('save')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'divisions', 'view' => 'tree']));

        $this->assertDatabaseHas('divisions', [
            'name' => 'Test Division',
            'code' => 'TD01',
        ]);
    }

    public function test_admin_cannot_create_a_division_with_invalid_data(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.employees-and-divisions.divisions.create')
            ->set('name', '')
            ->set('code', '')
            ->call('save')
            ->assertHasErrors(['name', 'code']);
    }

    public function test_admin_can_update_a_division(): void
    {
        $this->actingAs($this->admin);
        $division = Division::factory()->create();

        Livewire::test('admin.data.employees-and-divisions.divisions.edit', ['division' => $division])
            ->set('name', 'Updated Division Name')
            ->set('code', 'UDN')
            ->call('save')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'divisions', 'view' => 'tree']));

        $this->assertDatabaseHas('divisions', [
            'id' => $division->id,
            'name' => 'Updated Division Name',
            'code' => 'UDN',
        ]);
    }

    public function test_admin_can_delete_an_empty_division(): void
    {
        $this->actingAs($this->admin);
        $division = Division::factory()->create();

        Livewire::test('admin.data.employees-and-divisions.divisions.edit', ['division' => $division])
            ->call('delete')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'divisions', 'view' => 'tree']));

        $this->assertDatabaseMissing('divisions', ['id' => $division->id]);
    }

    public function test_admin_cannot_delete_a_division_with_employees(): void
    {
        $this->actingAs($this->admin);
        $division = Division::factory()->create();
        Employee::factory()->create(['division_id' => $division->id]);

        Livewire::test('admin.data.employees-and-divisions.divisions.edit', ['division' => $division])
            ->call('delete');

        $this->assertDatabaseHas('divisions', ['id' => $division->id]);
    }
} 