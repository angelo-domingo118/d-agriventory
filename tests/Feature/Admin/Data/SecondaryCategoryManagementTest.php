<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\ItemsCatalog;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecondaryCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected PrimaryCategory $primaryCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create(['user_id' => $this->admin->id]);
        $this->primaryCategory = PrimaryCategory::factory()->create();
    }

    public function test_admin_can_create_secondary_category(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.items-and-categories.secondary-categories.create')
            ->set('name', 'New Secondary Category')
            ->set('code', 'NSC')
            ->set('primary_category_id', $this->primaryCategory->id)
            ->call('save')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'secondary', 'view' => 'tree']));

        $this->assertDatabaseHas('secondary_categories', ['name' => 'New Secondary Category']);
    }

    public function test_admin_can_update_secondary_category(): void
    {
        $this->actingAs($this->admin);
        $category = SecondaryCategory::factory()->create();

        Livewire::test('admin.data.items-and-categories.secondary-categories.edit', ['category' => $category])
            ->set('name', 'Updated Secondary Category')
            ->set('code', 'USC')
            ->set('primary_category_id', $this->primaryCategory->id)
            ->call('save')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'secondary', 'view' => 'tree']));

        $this->assertDatabaseHas('secondary_categories', ['id' => $category->id, 'name' => 'Updated Secondary Category']);
    }

    public function test_admin_can_delete_empty_secondary_category(): void
    {
        $this->actingAs($this->admin);
        $category = SecondaryCategory::factory()->create();

        Livewire::test('admin.data.items-and-categories.secondary-categories.edit', ['category' => $category])
            ->call('delete')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'secondary', 'view' => 'tree']));

        $this->assertSoftDeleted('secondary_categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_secondary_category_with_items(): void
    {
        $this->actingAs($this->admin);
        $category = SecondaryCategory::factory()->create();
        ItemsCatalog::factory()->create(['secondary_category_id' => $category->id]);

        Livewire::test('admin.data.items-and-categories.secondary-categories.edit', ['category' => $category])
            ->call('delete');

        $this->assertDatabaseHas('secondary_categories', ['id' => $category->id]);
    }
}
