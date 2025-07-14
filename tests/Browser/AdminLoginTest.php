<?php

namespace Tests\Browser;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_login(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('input[wire\\:model="username"]', 'admin')
                ->type('input[wire\\:model="password"]', 'password')
                ->press('button[type="submit"]')
                ->waitForText('Add New Item')
                ->assertPathIs('/admin/main/dashboard');
        });
    }
}
