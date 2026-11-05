<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:ensure-admin')]
#[Description('Create the login from ADMIN_EMAIL / ADMIN_PASSWORD if it does not exist yet')]
class EnsureAdmin extends Command
{
    // Runs on every deploy (nixpacks start command). It only ever creates the
    // account; once it exists, ADMIN_PASSWORD can be deleted from Railway and
    // nothing changes. To reset the password, set ADMIN_RESET_PASSWORD=1 for
    // one deploy.
    public function handle(): int
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');

        if (! $email || ! $password) {
            $this->line('ADMIN_EMAIL / ADMIN_PASSWORD not set; skipping.');

            return self::SUCCESS;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            User::create(['name' => 'Admin', 'email' => $email, 'password' => Hash::make($password)]);
            $this->info("Created login for {$email}.");
        } elseif (config('app.admin.reset')) {
            $user->update(['password' => Hash::make($password)]);
            $this->info("Reset password for {$email}.");
        }

        return self::SUCCESS;
    }
}
