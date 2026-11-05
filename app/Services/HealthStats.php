<?php

namespace App\Services;

use App\Models\HealthCheck;

/**
 * Turns the last 24 hours of health:ping rows into per-system status:
 * current state, response time now, p50/p95, uptime and the last failure.
 */
class HealthStats
{
    public static function all(): array
    {
        $systems = collect(config('systems'))->filter(fn ($s) => is_array($s));
        $checks = HealthCheck::where('checked_at', '>=', now()->subDay())
            ->orderBy('checked_at')
            ->get()
            ->groupBy('system');

        $slow = (int) config('systems.slow_ms', 2000);
        $out = [];

        foreach ($systems as $key => $s) {
            $rows = $checks[$key] ?? collect();
            $latest = $rows->last();
            $ms = $rows->where('ok', true)->pluck('ms')->filter()->sort()->values();
            $lastFail = $rows->where('ok', false)->last();

            $state = match (true) {
                empty($s['url']) => 'idle',
                ! $latest => 'idle',
                ! $latest->ok => 'crit',
                self::pct($ms, 95) > $slow => 'warn',
                default => 'ok',
            };

            $out[$key] = $s + [
                'key' => $key,
                'state' => $state,
                'latest' => $latest,
                'now_ms' => $latest?->ms,
                'p50' => self::pct($ms, 50),
                'p95' => self::pct($ms, 95),
                'uptime' => $rows->count() ? round($rows->where('ok', true)->count() / $rows->count() * 100, 1) : null,
                'checks' => $rows->count(),
                'fails' => $rows->where('ok', false)->count(),
                'last_fail' => $lastFail,
                // One point per ~16 minutes so the sparkline reads as a day.
                'series' => $rows->chunk(max(1, (int) ceil($rows->count() / 90)))
                    ->map(fn ($c) => (int) round($c->avg('ms') ?? 0))->values()->all(),
                'failures' => $rows->where('ok', false)->sortByDesc('checked_at')->take(5)->values(),
            ];
        }

        return $out;
    }

    /** When did the ping job last run? Used as this app's own heartbeat. */
    public static function lastPing(): ?\Carbon\Carbon
    {
        return HealthCheck::max('checked_at') ? \Carbon\Carbon::parse(HealthCheck::max('checked_at')) : null;
    }

    public static function pct($sorted, int $p): ?int
    {
        $n = $sorted->count();
        if (! $n) {
            return null;
        }

        return (int) $sorted[min($n - 1, (int) ceil($p / 100 * $n) - 1)];
    }
}
