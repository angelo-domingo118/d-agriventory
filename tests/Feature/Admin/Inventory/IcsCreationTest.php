<?php

namespace Tests\Feature\Admin\Inventory;

use App\Models\AdminUser;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Division;
use App\Models\Employee;
use App\Models\IcsNumber;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\Position;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IcsCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $nonAdminUser;

    private Contract $contract;

    private ContractItem $contractItem;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
        AdminUser::factory()->create(['user_id' => $this->adminUser->id]);
        $this->nonAdminUser = User::factory()->create();

        // Seed necessary data
        Division::factory()->create();
        Position::factory()->create();
        PrimaryCategory::factory()->create();
        SecondaryCategory::factory()->create();

        $supplier = Supplier::factory()->create();
        $this->contract = Contract::factory()->create(['supplier_id' => $supplier->id]);

        $itemCatalog = ItemsCatalog::factory()->create();
        $itemSpecification = ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id]);
        $this->contractItem = ContractItem::factory()->create([
            'contract_id' => $this->contract->id,
            'item_specification_id' => $itemSpecification->id,
        ]);
        $this->employee = Employee::factory()->create();
    }

    #[Test]
    public function unauthenticated_users_cannot_access_create_page()
    {
        $this->get(route('admin.inventory.ics.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function non_admin_users_are_forbidden_from_create_page()
    {
        $this->actingAs($this->nonAdminUser)
            ->get(route('admin.inventory.ics.create'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_can_access_the_create_page()
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.inventory.ics.create'))
            ->assertSuccessful()
            ->assertSeeLivewire('admin.inventory.ics.create');
    }

    #[Test]
    public function it_can_create_an_ics_record_successfully()
    {
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.create')
            ->set('ics_number', 'ICS-2024-01')
            ->set('contract_id', $this->contract->id)
            ->set('contract_item_id', $this->contractItem->id)
            ->set('assigned_employee_id', $this->employee->id)
            ->set('quantity', 5)
            ->set('estimated_useful_life', 3)
            ->set('date_prepared', now()->format('Y-m-d'))
            ->call('store')
            ->assertRedirect(route('admin.inventory.ics.index'));

        $this->assertTrue(IcsNumber::where('ics_number', 'ICS-2024-01')->exists());
        $this->assertDatabaseHas('ics_number', [
            'ics_number' => 'ICS-2024-01',
            'quantity' => 5,
        ]);
    }

    #[Test]
    public function validation_fails_for_required_fields()
    {
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.create')
            ->call('store')
            ->assertHasErrors([
                'ics_number' => 'required',
                'contract_id' => 'required',
                'contract_item_id' => 'required',
                'assigned_employee_id' => 'required',
                'quantity' => 'required',
                'estimated_useful_life' => 'required',
                'date_prepared' => 'required',
            ]);
    }

    #[Test]
    public function validation_fails_for_duplicate_ics_number()
    {
        IcsNumber::factory()->create(['ics_number' => 'ICS-DUPLICATE']);
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.create')
            ->set('ics_number', 'ICS-DUPLICATE')
            ->call('store')
            ->assertHasErrors(['ics_number' => 'unique']);
    }
}
