<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();


        $permissions = [
            'space.create',
            'space.update',
            'space.delete',
            'space.view',
            'space.manage_members',

            'task.create',
            'task.view.all',
            'task.view.own',
            'task.update.all',
            'task.update.own',
            'task.delete.all',
            'task.delete.own',
            'task.assign',
            'task.approve',

            'task.update.deadline.any',

            'comment.create',
            'comment.delete.own',
            'comment.delete.any',

            'attachment.upload',
            'attachment.delete.own',
            'attachment.delete.any',

            'admin.access',
            'admin.manage_roles',
            'admin.manage_employees',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }


        $admin = Role::firstOrCreate(['name' => UserRole::Administrator->value, 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $exec = Role::firstOrCreate(['name' => UserRole::ExecutiveManager->value, 'guard_name' => 'web']);
        $exec->syncPermissions([
            'space.view',
            'space.manage_members',
            'task.create',
            'task.view.all',
            'task.update.all',
            'task.delete.all',
            'task.assign',
            'task.approve',
            'task.update.deadline.any',
            'comment.create',
            'comment.delete.any',
            'attachment.upload',
            'attachment.delete.any',
        ]);

        $senior = Role::firstOrCreate(['name' => UserRole::SeniorManager->value, 'guard_name' => 'web']);
        $senior->syncPermissions([
            'space.view',
            'space.manage_members',
            'task.create',
            'task.view.all',
            'task.update.all',
            'task.delete.all',
            'task.assign',
            'task.approve',
            'task.update.deadline.any',
            'comment.create',
            'comment.delete.any',
            'attachment.upload',
            'attachment.delete.own',
        ]);

        $middle = Role::firstOrCreate(['name' => UserRole::MiddleManager->value, 'guard_name' => 'web']);
        $middle->syncPermissions([
            'space.view',
            'task.create',
            'task.view.own',
            'task.update.own',
            'task.delete.own',
            'task.assign',
            'comment.create',
            'comment.delete.own',
            'attachment.upload',
            'attachment.delete.own',
        ]);

        $employee = Role::firstOrCreate(['name' => UserRole::Employee->value, 'guard_name' => 'web']);
        $employee->syncPermissions([
            'space.view',
            'task.create',
            'task.view.own',
            'task.update.own',
            'comment.create',
            'comment.delete.own',
            'attachment.upload',
            'attachment.delete.own',
        ]);

        $this->command->info('✅ Rollar və icazələr uğurla yaradıldı.');
    }
}
