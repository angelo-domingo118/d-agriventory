<?php

namespace Tests\Feature\Admin\Inventory;

/**
 * Tests for the creation of ICS (Inventory Custodian Slip) records.
 *
 * This test suite ensures that the ICS creation form functions correctly,
 * including validation, authorization, and successful record creation.
 *
 * After creation, the user is redirected to the ICS index page.
 * The display logic for that page is handled by its own set of components
 * and is tested in IcsManagementTest. It's important to be aware of the
 * data transformations that happen for display (e.g., combining brand/model
 * with specifications) when debugging.
 */

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
            ->set('quantity', 1)
            ->set('estimated_useful_life', 3)
            ->set('ics_type', 'SPHV')
            ->set('remarks', 'Test remarks')
            ->set('date_prepared', now()->format('m/d/Y'))
            ->set('date_accepted', now()->format('m/d/Y'))
            ->call('store')
            ->assertRedirect(route('admin.inventory.ics.index'));

        $this->assertTrue(IcsNumber::where('ics_number', 'ICS-2024-01')->exists());
        $this->assertDatabaseHas('ics_number', [
            'ics_number' => 'ICS-2024-01',
            'quantity' => 1,
            'ics_type' => 'SPHV',
            'remarks' => 'Test remarks',
        ]);
    }

    #[Test]
    public function validation_fails_for_required_fields()
    {
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.create')
            ->set('ics_number', '')
            ->set('contract_id', null)
            ->set('contract_item_id', null)
            ->set('assigned_employee_id', null)
            ->set('estimated_useful_life', null)
            ->set('date_prepared', '')
            ->set('date_accepted', '')
            ->call('store')
            ->assertHasErrors([
                'ics_number' => 'required',
                'contract_id' => 'required',
                'contract_item_id' => 'required',
                'assigned_employee_id' => 'required',
                'estimated_useful_life' => 'required',
                'date_prepared' => 'required',
                'date_accepted' => 'required',
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

    #[Test]
    public function brand_autocomplete_returns_unique_brands_from_database()
    {
        $this->actingAs($this->adminUser);

        // Create some item specifications with brands
        $itemCatalog = ItemsCatalog::factory()->create();
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'brand' => 'HP']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'brand' => 'Dell']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'brand' => 'Samsung']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'brand' => 'HP']); // Duplicate

        Livewire::test('admin.inventory.ics.create')
            ->call('showAllBrands')
            ->assertSet('brand_suggestions', [
                ['id' => 'Dell', 'name' => 'Dell', 'type' => 'existing'],
                ['id' => 'HP', 'name' => 'HP', 'type' => 'existing'],
                ['id' => 'Samsung', 'name' => 'Samsung', 'type' => 'existing'],
            ])
            ->assertSet('show_brand_suggestions', true);
    }

    #[Test]
    public function model_autocomplete_returns_unique_models_from_database()
    {
        $this->actingAs($this->adminUser);

        // Create some item specifications with models
        $itemCatalog = ItemsCatalog::factory()->create();
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'model' => 'ProBook 450 G9']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'model' => 'XPS 15']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'model' => 'Galaxy Book']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'model' => 'ProBook 450 G9']); // Duplicate

        Livewire::test('admin.inventory.ics.create')
            ->call('showAllModels')
            ->assertSet('model_suggestions', [
                ['id' => 'Galaxy Book', 'name' => 'Galaxy Book', 'type' => 'existing'],
                ['id' => 'ProBook 450 G9', 'name' => 'ProBook 450 G9', 'type' => 'existing'],
                ['id' => 'XPS 15', 'name' => 'XPS 15', 'type' => 'existing'],
            ])
            ->assertSet('show_model_suggestions', true);
    }

    #[Test]
    public function brand_search_filters_results_by_query()
    {
        $this->actingAs($this->adminUser);

        // Create some item specifications with brands
        $itemCatalog = ItemsCatalog::factory()->create();
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'brand' => 'HP']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'brand' => 'Dell']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'brand' => 'Samsung']);

        Livewire::test('admin.inventory.ics.create')
            ->set('brand_search', 'HP')
            ->call('searchBrands', 'HP')
            ->assertSet('brand_suggestions', [
                ['id' => 'HP', 'name' => 'HP', 'type' => 'existing'],
            ])
            ->assertSet('show_brand_suggestions', true);
    }

    #[Test]
    public function model_search_filters_results_by_query()
    {
        $this->actingAs($this->adminUser);

        // Create some item specifications with models
        $itemCatalog = ItemsCatalog::factory()->create();
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'model' => 'ProBook 450 G9']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'model' => 'XPS 15']);
        ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id, 'model' => 'Galaxy Book']);

        Livewire::test('admin.inventory.ics.create')
            ->set('model_search', 'ProBook')
            ->call('searchModels', 'ProBook')
            ->assertSet('model_suggestions', [
                ['id' => 'ProBook 450 G9', 'name' => 'ProBook 450 G9', 'type' => 'existing'],
            ])
            ->assertSet('show_model_suggestions', true);
    }

    #[Test]
    public function selecting_existing_brand_sets_correct_values()
    {
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.create')
            ->call('selectBrand', ['type' => 'existing', 'name' => 'HP'])
            ->assertSet('main_item_brand', 'HP')
            ->assertSet('brand_search', 'HP')
            ->assertSet('selected_brand', 'HP')
            ->assertSet('creating_new_brand', false)
            ->assertSet('show_brand_suggestions', false);
    }

    #[Test]
    public function selecting_new_brand_sets_correct_values()
    {
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.create')
            ->call('selectBrand', ['type' => 'new', 'name' => 'NewBrand'])
            ->assertSet('main_item_brand', 'NewBrand')
            ->assertSet('brand_search', 'NewBrand')
            ->assertSet('selected_brand', 'NewBrand (new)')
            ->assertSet('creating_new_brand', true)
            ->assertSet('show_brand_suggestions', false);
    }

    #[Test]
    public function selecting_existing_model_sets_correct_values()
    {
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.create')
            ->call('selectModel', ['type' => 'existing', 'name' => 'ProBook 450 G9'])
            ->assertSet('main_item_model', 'ProBook 450 G9')
            ->assertSet('model_search', 'ProBook 450 G9')
            ->assertSet('selected_model', 'ProBook 450 G9')
            ->assertSet('creating_new_model', false)
            ->assertSet('show_model_suggestions', false);
    }

    #[Test]
    public function selecting_new_model_sets_correct_values()
    {
        $this->actingAs($this->adminUser);

        Livewire::test('admin.inventory.ics.create')
            ->call('selectModel', ['type' => 'new', 'name' => 'NewModel'])
            ->assertSet('main_item_model', 'NewModel')
            ->assertSet('model_search', 'NewModel')
            ->assertSet('selected_model', 'NewModel (new)')
            ->assertSet('creating_new_model', true)
            ->assertSet('show_model_suggestions', false);
    }
}
