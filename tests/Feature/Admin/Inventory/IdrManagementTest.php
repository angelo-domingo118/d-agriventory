<?php

namespace Tests\Feature\Admin\Inventory;

use App\Models\AdminUser;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Division;
use App\Models\Employee;
use App\Models\IdrNumber;
use App\Models\IdrItemBatch;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\Position;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IdrManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $nonAdminUser;
    private Employee $employee1;
    private Employee $employee2;
    private ContractItem $contractItem1;
    private ContractItem $contractItem2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        AdminUser::factory()->create(['user_id' => $this->adminUser->id]);
        $this->nonAdminUser = User::factory()->create();

        $division1 = Division::factory()->create(['name' => 'Test Division 1']);
        $position = Position::factory()->create();
        $this->employee1 = Employee::factory()->create(['name' => 'John Doe', 'division_id' => $division1->id, 'position_id' => $position->id]);
        $this->employee2 = Employee::factory()->create(['name' => 'Jane Smith', 'division_id' => $division1->id, 'position_id' => $position->id]);

        $primaryCategory = PrimaryCategory::factory()->create();
        $secondaryCategory = SecondaryCategory::factory()->create(['primary_category_id' => $primaryCategory->id]);
        $supplier = Supplier::factory()->create();
        $contract = Contract::factory()->create(['supplier_id' => $supplier->id]);

        $item1 = ItemsCatalog::factory()->create(['name' => 'Printer', 'secondary_category_id' => $secondaryCategory->id]);
        $item2 = ItemsCatalog::factory()->create(['name' => 'Scanner', 'secondary_category_id' => $secondaryCategory->id]);
        $spec1 = ItemSpecification::factory()->create(['item_catalog_id' => $item1->id]);
        $spec2 = ItemSpecification::factory()->create(['item_catalog_id' => $item2->id]);
        $this->contractItem1 = ContractItem::factory()->create(['contract_id' => $contract->id, 'item_specification_id' => $spec1->id, 'unit_price' => 15000]);
        $this->contractItem2 = ContractItem::factory()->create(['contract_id' => $contract->id, 'item_specification_id' => $spec2->id, 'unit_price' => 8000]);

        IdrNumber::factory()->create([
            'number' => '2024-01-0001',
            'assigned_employee_id' => $this->employee1->id,
            'approving_employee_id' => $this->employee2->id,
            'received_by_id' => $this->employee1->id,
            'received_from_id' => $this->employee2->id,
            'contract_item_id' => $this->contractItem1->id,
        ]);
        IdrNumber::factory()->create([
             'number' => '2024-01-0002',
            'assigned_employee_id' => $this->employee2->id,
            'approving_employee_id' => $this->employee1->id,
            'received_by_id' => $this->employee2->id,
            'received_from_id' => $this->employee1->id,
            'contract_item_id' => $this->contractItem2->id,
        ]);
    }

    #[Test]
    public function unauthenticated_users_are_redirected_to_login()
    {
        $this->get(route('admin.inventory.idr.index'))->assertRedirect(route('login'));
        $this->get(route('admin.inventory.idr.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function non_admin_users_are_forbidden()
    {
        $this->actingAs($this->nonAdminUser);
        $this->get(route('admin.inventory.idr.index'))->assertForbidden();
        $this->get(route('admin.inventory.idr.create'))->assertForbidden();
    }

    #[Test]
    public function admin_can_access_the_idr_management_page()
    {
        $this->actingAs($this->adminUser);
        $this->get(route('admin.inventory.idr.index'))->assertSuccessful()->assertSeeLivewire('admin.inventory.idr.index');
    }

    #[Test]
    public function component_renders_and_displays_idr_records()
    {
        $this->actingAs($this->adminUser);
        Livewire::test('admin.inventory.idr.index')
            ->assertSee('Printer')
            ->assertSee('John Doe')
            ->assertSee('Scanner')
            ->assertSee('Jane Smith');
    }

    #[Test]
    public function search_by_item_name_filters_results()
    {
        $this->actingAs($this->adminUser);
        $component = Livewire::test('admin.inventory.idr.index')
            ->set('search', 'Printer')
            ->assertSee('Printer');
        $this->assertCount(1, $component->get('idrNumbers'));
    }

    #[Test]
    public function admin_can_create_a_new_idr_record()
    {
        $this->actingAs($this->adminUser);

        $newIdrData = [
            'contract_id' => $this->contractItem1->contract_id,
            'contract_item_id' => $this->contractItem1->id,
            'assigned_employee_id' => $this->employee1->id,
            'approving_employee_id' => $this->employee2->id,
            'received_by_id' => $this->employee1->id,
            'received_from_id' => $this->employee2->id,
            'quantity' => 1,
            'inventory_code' => 'NEW-CODE-123',
            'ors' => 'ORS-NEW-456',
            'date_prepared' => now()->format('Y-m-d'),
            'date_accepted' => now()->format('Y-m-d'),
            'date' => now()->format('Y-m-d'),
            'remarks' => 'Test remarks',
            'batches' => [
                ['identification_data' => 'SN12345']
            ]
        ];

        Livewire::test('admin.inventory.idr.create')
            ->set($newIdrData)
            ->call('store')
            ->assertRedirect(route('admin.inventory.idr.index'));
        
        $this->assertDatabaseHas('idr_number', [ 'inventory_code' => 'NEW-CODE-123' ]);
        $this->assertDatabaseHas('idr_item_batches', [ 'identification_data' => 'SN12345' ]);
    }

    #[Test]
    public function admin_can_show_an_idr_record()
    {
        $this->actingAs($this->adminUser);
        $idr = IdrNumber::first();
        $this->get(route('admin.inventory.idr.show', $idr))
            ->assertSuccessful()
            ->assertSee($idr->number)
            ->assertSee($idr->contractItem->itemSpecification->catalogItem->name);
    }
    
    #[Test]
    public function admin_can_update_an_idr_record()
    {
        $this->actingAs($this->adminUser);
        $idr = IdrNumber::first();

        Livewire::test('admin.inventory.idr.edit', ['idrNumber' => $idr])
            ->set('remarks', 'Updated remarks here')
            ->call('update')
            ->assertRedirect(route('admin.inventory.idr.show', $idr));

        $this->assertDatabaseHas('idr_number', ['id' => $idr->id, 'remarks' => 'Updated remarks here']);
    }

    #[Test]
    public function admin_can_delete_an_idr_record()
    {
        $this->actingAs($this->adminUser);
        $idr = IdrNumber::first();

        Livewire::test('admin.inventory.idr.edit', ['idrNumber' => $idr])
            ->call('destroy')
            ->assertRedirect(route('admin.inventory.idr.index'));
            
        $this->assertDatabaseMissing('idr_number', ['id' => $idr->id]);
    }
} 