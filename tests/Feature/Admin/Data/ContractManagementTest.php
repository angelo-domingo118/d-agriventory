<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\ItemsCatalog;
use App\Models\User;
use App\Models\Contract;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContractManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Supplier $supplier;
    protected ItemsCatalog $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create(['user_id' => $this->admin->id]);
        $this->supplier = Supplier::factory()->create();
        $this->item = ItemsCatalog::factory()->create();
    }

    public function test_admin_can_create_contract(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.suppliers-and-contracts.contracts.create')
            ->set('supplier_id', $this->supplier->id)
            ->set('contract_po_ib_number', 'C-123')
            ->set('items.0.item_catalog_id', $this->item->id)
            ->set('items.0.unit_price', 100)
            ->set('items.0.detailed_specifications', 'some specs')
            ->set('items.0.item_type', 'ICS')
            ->call('save')
            ->assertRedirect(route('admin.data.suppliers-and-contracts.contracts.index'));

        $this->assertDatabaseHas('contracts', ['contract_po_ib_number' => 'C-123']);
        $this->assertDatabaseHas('contract_items', ['unit_price' => 100, 'item_type' => 'ICS']);
    }

    public function test_admin_can_update_contract(): void
    {
        $this->actingAs($this->admin);
        $contract = Contract::factory()->create();

        Livewire::test('admin.data.suppliers-and-contracts.contracts.edit', ['contract' => $contract])
            ->set('supplier_id', $contract->supplier_id)
            ->set('contract_po_ib_number', 'Updated-C-123')
            ->call('save')
            ->assertRedirect(route('admin.data.suppliers-and-contracts.contracts.index'));

        $this->assertDatabaseHas('contracts', ['id' => $contract->id, 'contract_po_ib_number' => 'Updated-C-123']);
    }

    public function test_admin_can_delete_contract(): void
    {
        $this->actingAs($this->admin);
        $contract = Contract::factory()->create();

        Livewire::test('admin.data.suppliers-and-contracts.contracts.edit', ['contract' => $contract])
            ->call('deleteContract')
            ->assertRedirect(route('admin.data.suppliers-and-contracts.contracts.index'));

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    }
}
