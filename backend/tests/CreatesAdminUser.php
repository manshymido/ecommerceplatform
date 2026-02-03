<?php

namespace Tests;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

trait CreatesAdminUser
{
    protected function ensureRolesExist(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'customer']);
    }

    protected function createAdminUser(): User
    {
        $this->ensureRolesExist();
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /** Call in tests that need to act as admin. Requires $this->admin to be set (e.g. in setUp). */
    protected function actingAsAdmin(): self
    {
        Sanctum::actingAs($this->admin, ['*']);

        return $this;
    }
}
