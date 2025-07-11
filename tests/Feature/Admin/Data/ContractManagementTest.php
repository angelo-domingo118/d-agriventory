<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ItemSpecification;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Supplier $supplier;

    protected ItemSpecification $specification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create(['user_id' => $this->admin->id]);
        $this->supplier = Supplier::factory()->create();
        $this->specification = ItemSpecification::factory()->create();
    }

    public function test_admin_can_create_contract(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.suppliers-and-contracts.contracts.create')
            ->set('supplier_id', $this->supplier->id)
            ->set('contract_po_ib_number', 'C-123')
            ->set('items.0.item_specification_id', $this->specification->id)
            ->set('items.0.unit_price', 100)
            ->set('items.0.item_type', 'ICS')
            ->call('save')
            ->assertRedirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => 'tree']));

        $this->assertDatabaseHas('contracts', ['contract_po_ib_number' => 'C-123']);
        $this->assertDatabaseHas('contract_items', [
            'item_specification_id' => $this->specification->id,
            'unit_price' => 100,
            'item_type' => 'ICS',
        ]);
    }

    public function test_admin_can_update_contract(): void
    {
        $this->actingAs($this->admin);
        $contract = Contract::factory()
            ->has(ContractItem::factory()->state(['item_specification_id' => $this->specification->id]), 'contractItems')
            ->create();

        Livewire::test('admin.data.suppliers-and-contracts.contracts.edit', ['contract' => $contract])
            ->set('supplier_id', $contract->supplier_id)
            ->set('contract_po_ib_number', 'Updated-C-123')
            ->call('save')
            ->assertRedirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => 'tree']));

        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'contract_po_ib_number' => 'Updated-C-123']);
    }

    public function test_admin_can_delete_contract(): void
    {
        $this->actingAs($this->admin);
        $contract = Contract::factory()->create();

        Livewire::test('admin.data.suppliers-and-contracts.contracts.edit', ['contract' => $contract])
            ->call('deleteContract')
            ->assertRedirect(route('admin.data.suppliers-and-contracts', ['currentTab' => 'contracts', 'view' => 'tree']));

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }
}
