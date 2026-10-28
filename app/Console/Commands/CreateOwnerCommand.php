<?php

namespace App\Console\Commands;

use App\Enums\Discipline;
use App\Models\Role;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\PermissionCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * First-run helper for production: creates (or promotes) an Owner account.
 */
#[Signature('rgoli:create-owner {email} {--name=Owner} {--password=}')]
#[Description('Create an Owner user with every discipline (prompts for a password when --password is omitted)')]
class CreateOwnerCommand extends Command
{
    public function handle(UserRepositoryInterface $users): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $password = (string) ($this->option('password') ?: $this->secret('Password (min 8 characters)'));

        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        $role = Role::query()->where('slug', PermissionCatalog::OWNER)->first();
        if ($role === null) {
            $this->error('Run `php artisan db:seed --class=RolesAndPermissionsSeeder` first.');

            return self::FAILURE;
        }

        $user = $users->findByEmail($email);
        $user = $user === null
            ? $users->create(['name' => (string) $this->option('name'), 'email' => $email, 'password' => $password, 'is_active' => true])
            : $users->update($user, ['password' => $password, 'is_active' => true]);

        $users->assignRole($user, $role);
        $users->syncDisciplines($user, Discipline::values());

        $this->info("Owner ready: {$email}");

        return self::SUCCESS;
    }
}
