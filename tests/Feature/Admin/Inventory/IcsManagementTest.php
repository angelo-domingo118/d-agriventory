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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IcsManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $nonAdminUser;

    private Employee $employee1;

    private Employee $employee2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an admin user
        $this->adminUser = User::factory()->create();
        AdminUser::factory()->create(['user_id' => $this->adminUser->id]);

        // Create a non-admin user
        $this->nonAdminUser = User::factory()->create();

        // --- Seed necessary data ---

        // Organizational structure
        $division1 = Division::factory()->create(['name' => 'Test Division 1']);
        $division2 = Division::factory()->create(['name' => 'Test Division 2']);
        $position = Position::factory()->create();

        // Employees
        $this->employee1 = Employee::factory()->create(['name' => 'John Doe', 'division_id' => $division1->id, 'position_id' => $position->id]);
        $this->employee2 = Employee::factory()->create(['name' => 'Jane Smith', 'division_id' => $division2->id, 'position_id' => $position->id]);

        // Categories
        $primaryCategory = PrimaryCategory::factory()->create();
        $secondaryCategory = SecondaryCategory::factory()->create(['primary_category_id' => $primaryCategory->id]);

        // Suppliers and Contracts
        $supplier = Supplier::factory()->create();
        $contract = Contract::factory()->create(['supplier_id' => $supplier->id]);

        // Catalog items and specifications
        $item1 = ItemsCatalog::factory()->create(['name' => 'Laptop', 'secondary_category_id' => $secondaryCategory->id]);
        $item2 = ItemsCatalog::factory()->create(['name' => 'Monitor', 'secondary_category_id' => $secondaryCategory->id]);
        $spec1 = ItemSpecification::factory()->create(['item_catalog_id' => $item1->id]);
        $spec2 = ItemSpecification::factory()->create(['item_catalog_id' => $item2->id]);

        // Contract items
        $contractItem1 = ContractItem::factory()->create(['contract_id' => $contract->id, 'item_specification_id' => $spec1->id]);
        $contractItem2 = ContractItem::factory()->create(['contract_id' => $contract->id, 'item_specification_id' => $spec2->id]);

        // Create some ICS records for testing
        IcsNumber::factory()->create([
            'assigned_employee_id' => $this->employee1->id,
            'contract_item_id' => $contractItem1->id,
            'ics_type' => 'SPLV',
        ]);
        IcsNumber::factory()->create([
            'assigned_employee_id' => $this->employee2->id,
            'contract_item_id' => $contractItem2->id,
            'ics_type' => 'SPHV',
        ]);
    }

    #[Test]
    public function unauthenticated_users_are_redirected_to_login()
    {
        $this->get(route('admin.inventory.ics.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function non_admin_users_are_forbidden()
    {
        $this->actingAs($this->nonAdminUser)
            ->get(route('admin.inventory.ics.index'))
            ->assertForbidden();
    }

    #[Test]
    public function admin_users_can_access_the_ics_management_page()
    {
        $this->actingAs($this->adminUser)
            ->get(route('admin.inventory.ics.index'))
            ->assertSuccessful()
            ->assertSeeLivewire('admin.inventory.ics.index');
    }

    #[Test]
    public function component_renders_and_displays_ics_records()
    {
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.index')
            ->assertSee('Laptop')
            ->assertSee('John Doe')
            ->assertSee('Test Division 1')
            ->assertSee('Monitor')
            ->assertSee('Jane Smith')
            ->assertSee('Test Division 2');
    }

    #[Test]
    public function search_by_item_name_filters_results()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test('admin.inventory.ics.index')
            ->set('search', 'Laptop')
            ->assertSee('Laptop')
            ->assertSee('John Doe');

        $this->assertCount(1, $component->get('icsNumbers'));
        $this->assertEquals('Laptop', $component->get('icsNumbers')->first()->contractItem->itemSpecification->itemCatalog->name);
    }

    #[Test]
    public function search_by_employee_name_filters_results()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test('admin.inventory.ics.index')
            ->set('search', 'Jane Smith')
            ->assertSee('Monitor')
            ->assertSee('Jane Smith');

        $this->assertCount(1, $component->get('icsNumbers'));
        $this->assertEquals('Jane Smith', $component->get('icsNumbers')->first()->assignedEmployee->name);
    }

    #[Test]
    public function filter_by_employee_filters_results()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test('admin.inventory.ics.index')
            ->set('filterEmployeeId', $this->employee2->id)
            ->assertSee('Monitor')
            ->assertSee('Jane Smith')
            ->assertSee('Test Division 2');

        $this->assertCount(1, $component->get('icsNumbers'));
        $this->assertEquals($this->employee2->id, $component->get('icsNumbers')->first()->assigned_employee_id);
    }

    #[Test]
    public function filter_by_ics_type_filters_results()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test('admin.inventory.ics.index')
            ->set('filterIcsType', 'SPHV')
            ->assertSee('Monitor') // Associated with the SPHV item
            ->assertSee('Jane Smith');

        $this->assertCount(1, $component->get('icsNumbers'));
        $this->assertEquals('SPHV', $component->get('icsNumbers')->first()->ics_type);
    }

    #[Test]
    public function pagination_works_correctly()
    {
        $this->actingAs($this->adminUser);

        // Create 9 records with the current date. The setUp creates 2. Total 11.
        IcsNumber::factory(9)->create([
            'date_prepared' => now(),
        ]);

        // Create a specific record that should be on the second page by making it the oldest.
        // This 12th record will be on page 2 as perPage is 10.
        $lastIcs = IcsNumber::factory()->create([
            'ics_number' => 'ICS-LAST-PAGE',
            'date_prepared' => now()->subYear(),
        ]);

        Livewire::test('admin.inventory.ics.index')
            ->assertDontSee('ICS-LAST-PAGE') // Should not be on the first page
            ->call('nextPage')
            ->assertSee('ICS-LAST-PAGE'); // Should be on the second page
    }
}
