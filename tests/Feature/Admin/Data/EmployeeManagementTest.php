<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\User;
use App\Models\Employee;
use App\Models\Division;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Division $division;
    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create(['user_id' => $this->admin->id]);
        $this->division = Division::factory()->create();
        $this->position = Position::factory()->create();
    }

    public function test_admin_can_access_employees_index_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.data.employees-and-divisions'))
            ->assertStatus(200);
    }

    public function test_admin_can_create_an_employee(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.employees-and-divisions.employees.create')
            ->set('name', 'John Doe')
            ->set('employee_number', '12345')
            ->set('position_id', $this->position->id)
            ->set('division_id', $this->division->id)
            ->call('save')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'employees']));

        $this->assertDatabaseHas('employees', [
            'name' => 'John Doe',
            'employee_number' => '12345',
        ]);
    }

    public function test_admin_can_update_an_employee(): void
    {
        $this->actingAs($this->admin);
        $employee = Employee::factory()->create();

        Livewire::test('admin.data.employees-and-divisions.employees.edit', ['employee' => $employee])
            ->set('name', 'Updated Name')
            ->set('employee_number', $employee->employee_number)
            ->set('position_id', $employee->position_id)
            ->set('division_id', $employee->division_id)
            ->call('save')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'employees']));

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_admin_can_delete_an_employee(): void
    {
        $this->actingAs($this->admin);
        $employee = Employee::factory()->create();

        Livewire::test('admin.data.employees-and-divisions.employees.edit', ['employee' => $employee])
            ->call('delete')
            ->assertRedirect(route('admin.data.employees-and-divisions', ['currentTab' => 'employees']));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }
} 