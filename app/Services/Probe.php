<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs one read against a watched system and never lets it break the page.
 * Returns ['ok' => true, 'data' => ...] or ['ok' => false, 'error' => '...'].
 * Results are cached briefly so refreshing the dashboard doesn't re-query
 * prod; failures are cached for only 10 s so a fixed variable shows quickly.
 */
class Probe
{
    public static function run(string $key, int $seconds, callable $read, ?string $connection = null): array
    {
        $cacheKey = 'probe:'.$key;
        if (($hit = Cache::get($cacheKey)) !== null) {
            return $hit;
        }

        try {
            $result = ['ok' => true, 'data' => $read()];
        } catch (Throwable $e) {
            Log::warning("Probe {$key} failed", ['error' => $e->getMessage()]);
            $result = ['ok' => false, 'error' => self::shortError($e), 'login' => $connection ? self::describe($connection) : null];
        }

        Cache::put($cacheKey, $result, $result['ok'] ? $seconds : 10);

        return $result;
    }

    public static function configured(string $connection): bool
    {
        $c = config("database.connections.{$connection}");

        return ! empty($c['url']) || ! empty($c['host']);
    }

    /**
     * Which login the app is actually using, minus the password itself, so it
     * can be compared with the database's real settings.
     */
    public static function describe(string $connection): string
    {
        $c = config("database.connections.{$connection}");
        $prefix = strtoupper($connection);

        if (! empty($c['url'])) {
            $u = parse_url($c['url']) ?: [];
            $user = isset($u['user']) ? urldecode($u['user']) : '?';
            $pass = isset($u['pass']) ? urldecode($u['pass']) : '';
            $where = ($u['host'] ?? '?').':'.($u['port'] ?? '5432').($u['path'] ?? '');
            $source = "{$prefix}_DB_URL";
        } else {
            $user = $c['username'] ?? '?';
            $pass = (string) ($c['password'] ?? '');
            $where = ($c['host'] ?? '?').':'.($c['port'] ?? '5432').'/'.($c['database'] ?? '?');
            $source = "{$prefix}_DB_HOST / _USERNAME / _PASSWORD …";
        }

        return "Using {$source}: user \"{$user}\" at {$where}, password ".mb_strlen($pass).' characters'
            .($pass !== trim($pass) ? ' (has spaces at the ends)' : '');
    }

    private static function shortError(Throwable $e): string
    {
        $m = $e->getMessage();
        if (str_contains($m, 'could not translate host') || str_contains($m, 'Connection refused') || str_contains($m, 'timeout expired')) {
            return 'Can\'t reach the database';
        }
        if (str_contains($m, 'password authentication failed')) {
            return 'Database login rejected (wrong user or password)';
        }
        if (str_contains($m, 'does not exist')) {
            return 'Missing table or column';
        }

        return mb_substr(strtok($m, "\n"), 0, 140);
    }
}
