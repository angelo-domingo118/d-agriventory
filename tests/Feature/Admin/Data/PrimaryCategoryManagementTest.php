<?php

namespace Tests\Feature\Admin\Data;

use App\Models\AdminUser;
use App\Models\User;
use App\Models\PrimaryCategory;
use App\Models\SecondaryCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrimaryCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        AdminUser::factory()->admin()->create(['user_id' => $this->admin->id]);
    }

    public function test_admin_can_create_primary_category(): void
    {
        $this->actingAs($this->admin);

        Livewire::test('admin.data.items-and-categories.primary-categories.create')
            ->set('name', 'New Primary Category')
            ->set('code', 'NPC')
            ->call('save')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'primary', 'view' => 'tree']));

        $this->assertDatabaseHas('primary_categories', ['name' => 'New Primary Category', 'code' => 'NPC']);
    }

    public function test_admin_can_update_primary_category(): void
    {
        $this->actingAs($this->admin);
        $category = PrimaryCategory::factory()->create();

        Livewire::test('admin.data.items-and-categories.primary-categories.edit', ['category' => $category])
            ->set('name', 'Updated Primary Category')
            ->set('code', 'UPC')
            ->call('save')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'primary', 'view' => 'tree']));

        $this->assertDatabaseHas('primary_categories', ['id' => $category->id, 'name' => 'Updated Primary Category', 'code' => 'UPC']);
    }

    public function test_admin_can_delete_empty_primary_category(): void
    {
        $this->actingAs($this->admin);
        $category = PrimaryCategory::factory()->create();

        Livewire::test('admin.data.items-and-categories.primary-categories.edit', ['category' => $category])
            ->call('delete')
            ->assertRedirect(route('admin.data.items-and-categories', ['currentTab' => 'primary', 'view' => 'tree']));

        $this->assertSoftDeleted('primary_categories', ['id' => $category->id]);
    }

    public function test_admin_cannot_delete_primary_category_with_secondary_categories(): void
    {
        $this->actingAs($this->admin);
        $category = PrimaryCategory::factory()->create();
        SecondaryCategory::factory()->create(['primary_category_id' => $category->id]);

        Livewire::test('admin.data.items-and-categories.primary-categories.edit', ['category' => $category])
            ->call('delete');
        
        $this->assertDatabaseHas('primary_categories', ['id' => $category->id]);
    }
} 