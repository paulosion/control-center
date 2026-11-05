<?php

namespace App\Console\Commands;

use App\Support\Admin;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ensure-admin')]
#[Description('Make ADMIN_EMAIL / ADMIN_PASSWORD the login (create it, or update the password)')]
class EnsureAdmin extends Command
{
    public function handle(): int
    {
        $this->line(Admin::sync());

        return self::SUCCESS;
    }
}
