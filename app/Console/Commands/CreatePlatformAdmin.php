<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-platform-admin')]
#[Description('Create a platform administrator who approves and manages hospitals')]
class CreatePlatformAdmin extends Command
{
    public function handle(): int
    {
        $name = text('Name', required: true);
        $email = text('Email', required: true, validate: fn (string $value) => Validator::make(
            ['email' => $value], ['email' => ['email', 'unique:users,email']]
        )->errors()->first('email') ?: null);
        $password = password('Password', required: true, validate: fn (string $value) => Validator::make(
            ['password' => $value], ['password' => [Password::defaults()]]
        )->errors()->first('password') ?: null);

        $user = User::create(['name' => $name, 'email' => strtolower($email), 'password' => $password]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignStaffRole(Role::PlatformAdmin);

        $this->components->info("Platform admin {$user->email} created.");

        return self::SUCCESS;
    }
}
