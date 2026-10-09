<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class CreateAdministrator extends Command
{
    protected $signature = 'system:create-admin';

    protected $description = 'Interactively create a new system administrator; never modifies an existing user.';

    public function handle(): int
    {
        $data = ['name' => $this->ask('Name'), 'email' => $this->ask('Email'), 'password' => $this->secret('Password (12+ characters)'), 'password_confirmation' => $this->secret('Confirm password')];
        $validator = Validator::make($data, ['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email', 'password' => 'required|string|min:12|confirmed']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($data): void {
            $role = Role::query()->where('title', 'superadmin')->firstOrFail();
            $user = User::query()->create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'status' => 4, 'level' => 'admin', 'role_id' => $role->id, 'change_password' => 1]);
            $user->roles()->attach($role);
        });
        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
