<?php

namespace App\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The watched systems' connections reuse each app's own (writable)
        // login. Every new session on them is switched to READ ONLY before
        // any query runs, so an INSERT/UPDATE/DELETE from this app fails at
        // the database instead of touching prod. statement_timeout keeps a
        // slow dashboard query from piling up on a live database.
        Event::listen(function (ConnectionEstablished $event) {
            if (! $event->connection->getConfig('watched')) {
                return;
            }

            $pdo = $event->connection->getPdo();
            $pdo->exec('SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY');
            $pdo->exec("SET statement_timeout = '5s'");
        });
    }
}
