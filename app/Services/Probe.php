<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs one read against a watched system and never lets it break the page.
 * Returns ['ok' => true, 'data' => ...] or ['ok' => false, 'error' => '...'].
 * Results are cached briefly so refreshing the dashboard doesn't re-query prod.
 */
class Probe
{
    public static function run(string $key, int $seconds, callable $read): array
    {
        return Cache::remember('probe:'.$key, $seconds, function () use ($key, $read) {
            try {
                return ['ok' => true, 'data' => $read()];
            } catch (Throwable $e) {
                Log::warning("Probe {$key} failed", ['error' => $e->getMessage()]);

                return ['ok' => false, 'error' => self::shortError($e)];
            }
        });
    }

    public static function configured(string $connection): bool
    {
        $c = config("database.connections.{$connection}");

        return ! empty($c['url']) || ! empty($c['host']);
    }

    private static function shortError(Throwable $e): string
    {
        $m = $e->getMessage();
        if (str_contains($m, 'could not translate host') || str_contains($m, 'Connection refused') || str_contains($m, 'timeout expired')) {
            return 'Can\'t reach the database';
        }
        if (str_contains($m, 'password authentication failed')) {
            return 'Database login rejected';
        }
        if (str_contains($m, 'does not exist')) {
            return 'Missing table or column';
        }

        return mb_substr(strtok($m, "\n"), 0, 140);
    }
}
