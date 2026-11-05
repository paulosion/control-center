<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:ensure-admin')]
#[Description('Make ADMIN_EMAIL / ADMIN_PASSWORD the login (create it, or update the password)')]
class EnsureAdmin extends Command
{
    // Runs on every deploy (nixpacks start command). ADMIN_EMAIL and
    // ADMIN_PASSWORD are always the login: the account is created if missing
    // and its password is updated whenever ADMIN_PASSWORD changes. Any other
    // accounts (e.g. from an earlier ADMIN_EMAIL) are removed, since this is
    // a single-user app.
    public function handle(): int
    {
        $email = strtolower(trim((string) config('app.admin.email')));
        $password = (string) config('app.admin.password');

        if ($email === '' || $password === '') {
            $this->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set; nobody can sign in.');

            return self::SUCCESS;
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        if (! $user) {
            $user = User::create(['name' => 'Admin', 'email' => $email, 'password' => Hash::make($password)]);
            $this->info("Login created for {$email}.");
        } elseif (! Hash::check($password, $user->password)) {
            $user->update(['password' => Hash::make($password)]);
            $this->info("Password updated for {$email}.");
        } else {
            $this->line("Login for {$email} is up to date.");
        }

        $removed = User::whereKeyNot($user->id)->delete();
        if ($removed) {
            $this->line("Removed {$removed} old login(s).");
        }

        return self::SUCCESS;
    }
}
