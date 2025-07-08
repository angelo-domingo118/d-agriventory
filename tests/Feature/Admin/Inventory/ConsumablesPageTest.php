<?php

namespace Tests\Feature\Admin\Inventory;

use App\Models\AdminUser;
use App\Models\ConsumableItem;
use App\Models\ConsumableRecord;
use App\Models\Division;
use App\Models\DivisionInventoryManager;
use App\Models\ItemsCatalog;
use App\Models\ItemSpecification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsumablesPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $regularUser;
    private Division $division1;
    private Division $division2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users and roles
        $this->admin = User::factory()->create();
        AdminUser::factory()->create(['user_id' => $this->admin->id]);

        $this->manager = User::factory()->create();
        $this->division1 = Division::factory()->create(['name' => 'Division A']);
        DivisionInventoryManager::factory()->create([
            'user_id' => $this->manager->id,
            'division_id' => $this->division1->id,
        ]);

        $this->regularUser = User::factory()->create();

        // Create another division
        $this->division2 = Division::factory()->create(['name' => 'Division B']);

        // Create consumable items for division 1
        $record1 = ConsumableRecord::factory()->create(['division_id' => $this->division1->id]);
        $catalog1 = ItemsCatalog::factory()->create(['name' => 'Item A']);
        $spec1 = ItemSpecification::factory()->create([
            'item_catalog_id' => $catalog1->id,
            'brand' => 'BrandX',
            'model' => 'Model-A1',
            'detailed_specifications' => 'Specific details for item in Division A'
        ]);
        ConsumableItem::factory()->create([
            'consumable_record_id' => $record1->id,
            'item_specification_id' => $spec1->id,
        ]);

        // Create consumable items for division 2
        $record2 = ConsumableRecord::factory()->create(['division_id' => $this->division2->id]);
        $catalog2 = ItemsCatalog::factory()->create(['name' => 'Item B']);
        $spec2 = ItemSpecification::factory()->create([
            'item_catalog_id' => $catalog2->id,
            'brand' => 'BrandY',
            'model' => 'Model-B1',
            'detailed_specifications' => 'Specific details for item in another division'
        ]);
        ConsumableItem::factory()->create([
            'consumable_record_id' => $record2->id,
            'item_specification_id' => $spec2->id,
        ]);
    }

    public function test_admin_can_view_consumables_page_and_see_all_divisions(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.inventory.consumables.index')
            ->assertSee($this->division1->name)
            ->assertSee($this->division2->name);
    }

    public function test_inventory_manager_can_view_consumables_page_and_see_only_their_division(): void
    {
        $this->actingAs($this->manager);

        Livewire::test('admin.inventory.consumables.index')
            ->assertSee($this->division1->name)
            ->assertDontSee($this->division2->name);
    }

    public function test_unauthorized_user_is_redirected(): void
    {
        $this->actingAs($this->regularUser)
            ->get(route('admin.inventory.consumables.index'))
            ->assertForbidden();
    }
    
    public function test_admin_can_search_by_division_name(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.inventory.consumables.index')
            ->set('search', 'Division A')
            ->assertSee('Division A')
            ->assertDontSee('Item B')
            ->assertViewHas('consumables', function ($consumables) {
                $this->assertCount(1, $consumables);
                $this->assertEquals('Division A', $consumables->first()->division_name);
                return true;
            });
    }
} 