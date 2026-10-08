<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSuperAdmin extends Command
{
    protected $signature = 'staff:create-super-admin';

    protected $description = 'Create a super admin using a securely prompted password';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Run this command interactively to enter credentials securely.');

            return self::FAILURE;
        }
        $data = [
            'name' => $this->ask('Full name'),
            'email' => $this->ask('Email address'),
            'password' => $this->secret('Password (at least 12 characters)'),
            'password_confirmation' => $this->secret('Confirm password'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }
        $user = new User($validator->validated());
        $user->role = 'super_admin';
        $user->is_active = true;
        $user->save();
        $this->info('Super admin created. Sign in at /login.');

        return self::SUCCESS;
    }
}
