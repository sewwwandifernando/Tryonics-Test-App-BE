<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for posts with 'api' guard
        $permissions = [
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'view own posts',
            'edit own posts',
            'delete own posts',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'api' // Specify API guard
            ]);
        }

        // Create roles and assign permissions with 'api' guard

        // Admin role - has all permissions
        $adminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'api'
        ]);
        $adminRole->givePermissionTo(Permission::all());

        // Member role - can manage their own posts
        $memberRole = Role::create([
            'name' => 'member',
            'guard_name' => 'api'
        ]);
        $memberRole->givePermissionTo([
            'view posts',
            'create posts',
            'view own posts',
            'edit own posts',
            'delete own posts',
        ]);

        // Guest role - can only view posts
        $guestRole = Role::create([
            'name' => 'guest',
            'guard_name' => 'api'
        ]);
        $guestRole->givePermissionTo('view posts');


        User::find(1)->assignRole('admin');

    }
}