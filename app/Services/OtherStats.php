<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reads from 201 System (maintenance agent runs, tickets) and PayMaya
 * (verifications). Each returns Probe's ok/data/error shape.
 */
class OtherStats
{
    public static function maintenance(): array
    {
        if (! Probe::configured('hr201')) {
            return ['ok' => false, 'error' => 'Not connected', 'unconfigured' => true];
        }

        return Probe::run('pm-runs', 120, function () {
            $db = DB::connection('hr201');
            $since = now()->subDays(7);

            return [
                'week' => $db->table('preventive_maintenance_runs')->where('finished_at', '>=', $since)->count(),
                'threats' => $db->table('preventive_maintenance_runs')->where('finished_at', '>=', $since)->where('threats_found', true)->count(),
                'freed_mb' => (int) $db->table('preventive_maintenance_runs')->where('finished_at', '>=', $since)->sum('freed_mb'),
                'latest' => $db->table('preventive_maintenance_runs')->orderByDesc('finished_at')->limit(8)
                    ->get(['finished_at', 'computer_name', 'employee_name', 'department', 'threats_found', 'freed_mb'])
                    ->map(fn ($r) => (array) $r + ['at' => Carbon::parse($r->finished_at)])->all(),
            ];
        });
    }

    public static function tickets(): array
    {
        if (! Probe::configured('hr201')) {
            return ['ok' => false, 'error' => 'Not connected', 'unconfigured' => true];
        }

        return Probe::run('tickets', 120, function () {
            $db = DB::connection('hr201');
            $open = $db->table('tickets')->whereNull('resolved_at');

            return [
                'open' => (clone $open)->count(),
                'old' => (clone $open)->where('created_at', '<', now()->subDays(3))->count(),
                'today' => $db->table('tickets')->whereDate('created_at', now()->toDateString())->count(),
            ];
        });
    }

    public static function paymaya(): array
    {
        if (! Probe::configured('paymaya')) {
            return ['ok' => false, 'error' => 'Not connected', 'unconfigured' => true];
        }

        return Probe::run('paymaya', 120, function () {
            $db = DB::connection('paymaya');
            // PayMaya stores timestamps in UTC.
            $toLocal = fn ($v) => $v ? Carbon::parse($v, 'UTC')->setTimezone(config('app.timezone')) : null;
            $weekStartUtc = now()->subDays(6)->startOfDay()->utc();

            $daily = $db->table('paymaya_verifications')
                ->where('created_at', '>=', $weekStartUtc)
                ->get(['created_at'])
                ->groupBy(fn ($r) => $toLocal($r->created_at)->toDateString())
                ->map->count();

            $series = [];
            for ($i = 6; $i >= 0; $i--) {
                $series[] = (int) ($daily[now()->subDays($i)->toDateString()] ?? 0);
            }

            return [
                'employees' => $db->table('employees')->count(),
                'verified' => $db->table('paymaya_verifications')->where('status', 'verified')->distinct()->count('employee_id'),
                'week' => array_sum($series),
                'series' => $series,
                'latest' => $db->table('paymaya_verifications as v')
                    ->leftJoin('employees as e', 'e.id', '=', 'v.employee_id')
                    ->orderByDesc('v.created_at')->limit(6)
                    ->get(['v.paymaya_name', 'v.status', 'v.created_at', 'e.full_name'])
                    ->map(fn ($r) => ['name' => $r->full_name ?: $r->paymaya_name, 'status' => $r->status, 'at' => $toLocal($r->created_at)])->all(),
            ];
        });
    }
}
