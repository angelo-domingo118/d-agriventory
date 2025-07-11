<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\Contract;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create(['user_id' => $this->admin->id]);
    }

    public function test_admin_can_create_supplier(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.suppliers-and-contracts.suppliers.create')
            ->set('name', 'Test Supplier')
            ->set('address', '123 Test St')
            ->call('save')
            ->assertRedirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers', 'view' => 'tree']));

        $this->assertDatabaseHas('suppliers', ['name' => 'Test Supplier']);
    }

    public function test_admin_can_update_supplier(): void
    {
        $this->actingAs($this->admin);
        $supplier = Supplier::factory()->create();

        Livewire::test('admin.data.suppliers-and-contracts.suppliers.edit', ['supplier' => $supplier])
            ->set('name', 'Updated Supplier Name')
            ->call('save')
            ->assertRedirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers', 'view' => 'tree']));

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Updated Supplier Name']);
    }

    public function test_admin_can_delete_empty_supplier(): void
    {
        $this->actingAs($this->admin);
        $supplier = Supplier::factory()->create();

        Livewire::test('admin.data.suppliers-and-contracts.suppliers.edit', ['supplier' => $supplier])
            ->call('delete')
            ->assertRedirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'suppliers', 'view' => 'tree']));

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }

    public function test_admin_cannot_delete_supplier_with_contracts(): void
    {
        $this->actingAs($this->admin);
        $supplier = Supplier::factory()->create();
        Contract::factory()->create(['supplier_id' => $supplier->id]);

        Livewire::test('admin.data.suppliers-and-contracts.suppliers.edit', ['supplier' => $supplier])
            ->call('delete');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }
}
