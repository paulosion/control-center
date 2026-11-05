<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Today's RFID taps, read from my-genone's time_entries.
 *
 * my-genone doesn't log API calls yet, so a "tap" is inferred: an office
 * entry with no GPS (web clock-ins always send GPS, the RFID reader never
 * does) that wasn't typed in by an admin. The camera's photo lands on
 * clock_in_photo_path / clock_out_photo_path, and the face-match result of
 * the latest photo on face_match (null = not compared, for any reason).
 */
class RfidStats
{
    public static function today(): array
    {
        if (! Probe::configured('genone')) {
            return ['ok' => false, 'error' => 'Not connected', 'unconfigured' => true];
        }

        return Probe::run('rfid-today', 60, function () {
            $rows = DB::connection('genone')->table('time_entries as t')
                ->join('users as u', 'u.id', '=', 't.user_id')
                ->whereDate('t.date', now()->toDateString())
                ->where('t.work_mode', 'office')
                ->whereNull('t.clock_in_lat')
                ->whereNotNull('t.clock_in_at')
                ->where(fn ($q) => $q->whereNull('t.clock_in_set_by_admin')->orWhere('t.clock_in_set_by_admin', false))
                ->orderBy('t.clock_in_at')
                ->get([
                    'u.name', 't.clock_in_at', 't.clock_out_at', 't.clock_in_photo_path', 't.clock_out_photo_path',
                    't.face_match', 't.face_confidence', 't.clock_out_is_manual', 't.clock_out_set_by_admin',
                ]);

            $taps = [];
            foreach ($rows as $r) {
                $taps[] = [
                    'at' => Carbon::parse($r->clock_in_at),
                    'name' => $r->name,
                    'kind' => 'in',
                    'photo' => $r->clock_in_photo_path !== null,
                ];
                if ($r->clock_out_at && ! $r->clock_out_is_manual && ! $r->clock_out_set_by_admin) {
                    $taps[] = [
                        'at' => Carbon::parse($r->clock_out_at),
                        'name' => $r->name,
                        'kind' => 'out',
                        'photo' => $r->clock_out_photo_path !== null,
                    ];
                }
            }
            usort($taps, fn ($a, $b) => $a['at'] <=> $b['at']);

            $withPhoto = array_filter($taps, fn ($t) => $t['photo']);
            $missing = array_values(array_filter($taps, fn ($t) => ! $t['photo']));
            $lastPhoto = $withPhoto ? end($withPhoto)['at'] : null;
            $lastTap = $taps ? end($taps)['at'] : null;

            // Taps after the last photo that still have none: the signature
            // of a camera that dropped off WiFi while the reader kept going.
            $missingSinceLastPhoto = count(array_filter($missing, fn ($t) => ! $lastPhoto || $t['at']->gt($lastPhoto)));

            $hours = [];
            foreach ($taps as $t) {
                $h = (int) $t['at']->format('G');
                $hours[$h] ??= ['taps' => 0, 'photos' => 0];
                $hours[$h]['taps']++;
                $hours[$h]['photos'] += $t['photo'] ? 1 : 0;
            }
            ksort($hours);

            $photoRows = $rows->filter(fn ($r) => $r->clock_in_photo_path || $r->clock_out_photo_path);

            return [
                'taps' => $taps,
                'tap_count' => count($taps),
                'photo_count' => count($withPhoto),
                'missing' => $missing,
                'missing_count' => count($missing),
                'missing_since_last_photo' => $missingSinceLastPhoto,
                'last_photo' => $lastPhoto,
                'last_tap' => $lastTap,
                'hours' => $hours,
                'people' => $rows->count(),
                'face' => [
                    'matched' => $photoRows->where('face_match', true)->count(),
                    'no_match' => $photoRows->filter(fn ($r) => $r->face_match === false)->count(),
                    'blank' => $photoRows->whereNull('face_match')->count(),
                ],
                'face_by_name' => $rows->mapWithKeys(fn ($r) => [$r->name => [
                    'match' => $r->face_match, 'confidence' => $r->face_confidence,
                ]])->all(),
            ];
        }, 'genone');
    }
}
