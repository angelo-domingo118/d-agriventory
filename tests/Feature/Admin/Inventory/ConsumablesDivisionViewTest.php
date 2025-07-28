<?php

use App\Models\AdminUser;
use App\Models\User;
use App\Models\Division;
use App\Models\ConsumableRecord;
use App\Models\ConsumableItem;
use App\Models\ItemSpecification;
use App\Models\ItemsCatalog;
use App\Models\SecondaryCategory;
use App\Models\PrimaryCategory;

test('admin can view consumables grouped by division', function () {
    // Create admin user
    $admin = User::factory()->create();
    AdminUser::factory()->create([
        'user_id' => $admin->id,
        'view_inventory' => true,
    ]);

    // Create test data
    $division1 = Division::factory()->create(['name' => 'IT Division', 'code' => 'IT']);
    $division2 = Division::factory()->create(['name' => 'Finance Division', 'code' => 'FIN']);

    $primaryCategory = PrimaryCategory::factory()->create();
    $secondaryCategory = SecondaryCategory::factory()->create(['primary_category_id' => $primaryCategory->id]);
    $itemCatalog = ItemsCatalog::factory()->create(['secondary_category_id' => $secondaryCategory->id]);
    $itemSpec = ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id]);

    $record1 = ConsumableRecord::factory()->create(['division_id' => $division1->id]);
    $record2 = ConsumableRecord::factory()->create(['division_id' => $division2->id]);

    ConsumableItem::factory()->create([
        'consumable_record_id' => $record1->id,
        'item_specification_id' => $itemSpec->id,
        'initial_quantity' => 100,
        'current_quantity' => 80,
    ]);

    ConsumableItem::factory()->create([
        'consumable_record_id' => $record2->id,
        'item_specification_id' => $itemSpec->id,
        'initial_quantity' => 50,
        'current_quantity' => 30,
    ]);

    // Test the division view
    $response = $this->actingAs($admin)->get(route('admin.inventory.consumables.index'));

    $response->assertOk()
        ->assertSee('Consumables by Division')
        ->assertSee('IT Division')
        ->assertSee('Finance Division')
        ->assertSee('80 Available') // IT Division current quantity
        ->assertSee('30 Available'); // Finance Division current quantity
});

test('admin can navigate to detailed view from division view', function () {
    // Create admin user
    $admin = User::factory()->create();
    AdminUser::factory()->create([
        'user_id' => $admin->id,
        'view_inventory' => true,
    ]);

    // Create test data
    $division = Division::factory()->create(['name' => 'Test Division']);

    // Test the detailed view
    $response = $this->actingAs($admin)->get(route('admin.inventory.consumables.details', ['filterDivisionId' => $division->id]));

    $response->assertOk()
        ->assertSee('Detailed Consumables Inventory')
        ->assertSee('Back to Division View');
});

test('division inventory manager sees only their division data', function () {
    // Create division inventory manager
    $user = User::factory()->create();
    $division = Division::factory()->create(['name' => 'Manager Division']);
    $otherDivision = Division::factory()->create(['name' => 'Other Division']);
    
    $user->divisionInventoryManager()->create(['division_id' => $division->id]);

    // Create consumable data for both divisions
    $primaryCategory = PrimaryCategory::factory()->create();
    $secondaryCategory = SecondaryCategory::factory()->create(['primary_category_id' => $primaryCategory->id]);
    $itemCatalog = ItemsCatalog::factory()->create(['secondary_category_id' => $secondaryCategory->id]);
    $itemSpec = ItemSpecification::factory()->create(['item_catalog_id' => $itemCatalog->id]);

    $record1 = ConsumableRecord::factory()->create(['division_id' => $division->id]);
    $record2 = ConsumableRecord::factory()->create(['division_id' => $otherDivision->id]);

    ConsumableItem::factory()->create([
        'consumable_record_id' => $record1->id,
        'item_specification_id' => $itemSpec->id,
    ]);

    ConsumableItem::factory()->create([
        'consumable_record_id' => $record2->id,
        'item_specification_id' => $itemSpec->id,
    ]);

    // Test that only manager's division is shown
    $response = $this->actingAs($user)->get(route('admin.inventory.consumables.index'));

    $response->assertOk()
        ->assertSee('Manager Division')
        ->assertDontSee('Other Division');
}); 