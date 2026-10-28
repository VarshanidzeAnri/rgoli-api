<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds every permission and the six default roles from resources/permissions.json. Idempotent.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (PermissionCatalog::roles() as $slug => $definition) {
            /** @var Role $role */
            $role = Role::query()->firstOrNew(['slug' => $slug]);
            $role->fill(['name' => $role->exists ? $role->name : $definition['name'], 'guard_name' => 'web']);
            $role->is_system = true;
            $role->save();
            $role->syncPermissions($definition['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
