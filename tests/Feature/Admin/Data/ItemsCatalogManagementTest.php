<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\User;
use App\Models\ItemsCatalog;
use App\Models\SecondaryCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ItemsCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected SecondaryCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create(['user_id' => $this->admin->id]);
        $this->category = SecondaryCategory::factory()->create();
    }

    public function test_admin_can_create_item(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.items-and-categories.items-catalog.create')
            ->set('name', 'Test Item')
            ->set('code', 'TI01')
            ->set('unit', 'piece')
            ->set('secondary_category_id', $this->category->id)
            ->call('save')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'items', 'view' => 'tree']));

        $this->assertDatabaseHas('items_catalog', ['name' => 'Test Item', 'code' => 'TI01']);
    }

    public function test_admin_can_update_item(): void
    {
        $this->actingAs($this->admin);
        $item = ItemsCatalog::factory()->create();

        Livewire::test('admin.data.items-and-categories.items-catalog.edit', ['item' => $item])
            ->set('name', 'Updated Item Name')
            ->set('code', $item->code)
            ->set('unit', $item->unit)
            ->set('secondary_category_id', $item->secondary_category_id)
            ->call('save')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'items', 'view' => 'tree']));

        $this->assertDatabaseHas('items_catalog', ['id' => $item->id, 'name' => 'Updated Item Name']);
    }

    public function test_admin_can_delete_item(): void
    {
        $this->actingAs($this->admin);
        $item = ItemsCatalog::factory()->create();

        Livewire::test('admin.data.items-and-categories.items-catalog.edit', ['item' => $item])
            ->call('delete')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'items', 'view' => 'tree']));

        $this->assertSoftDeleted('items_catalog', ['id' => $item->id]);
    }
}
