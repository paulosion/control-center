<?php

namespace App\Http\Controllers;

use App\Services\HealthStats;
use App\Services\OtherStats;
use App\Services\RfidStats;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Health checks normally come from the scheduler loop in the start
     * command. If none has run for 2 minutes (loop not running), opening a
     * page runs one after the response, at most once a minute.
     */
    public function __construct()
    {
        $last = HealthStats::lastPing();
        if ((! $last || $last->lt(now()->subMinutes(2))) && Cache::add('lazy-health-ping', 1, 60)) {
            \Illuminate\Support\defer(fn () => Artisan::call('health:ping'));
        }
    }

    public function overview()
    {
        $health = HealthStats::all();
        $rfid = RfidStats::today();
        $paymaya = OtherStats::paymaya();
        $lastPing = HealthStats::lastPing();

        return view('pages.overview', [
            'attention' => $this->attention($health, $rfid, $lastPing),
            'health' => $health,
            'rfid' => $rfid,
            'paymaya' => $paymaya,
            'problems' => $this->problems($health, $rfid),
            'todos' => TodoController::listData(),
            'lastPing' => $lastPing,
        ]);
    }

    public function rfid()
    {
        return view('pages.rfid', ['rfid' => RfidStats::today(), 'lastPing' => HealthStats::lastPing()]);
    }

    public function health()
    {
        return view('pages.health', ['health' => HealthStats::all(), 'lastPing' => HealthStats::lastPing()]);
    }

    public function apps()
    {
        return view('pages.apps', [
            'pm' => OtherStats::maintenance(),
            'tickets' => OtherStats::tickets(),
            'paymaya' => OtherStats::paymaya(),
            'lastPing' => HealthStats::lastPing(),
        ]);
    }

    public function gaps()
    {
        return view('pages.gaps', ['lastPing' => HealthStats::lastPing()]);
    }

    public function todo()
    {
        return view('pages.todo', ['todos' => TodoController::listData(), 'lastPing' => HealthStats::lastPing()]);
    }

    /** Up to four "failing or at risk" cards, worst first. */
    private function attention(array $health, array $rfid, ?Carbon $lastPing): array
    {
        $items = [];

        if (! $lastPing || $lastPing->lt(now()->subMinutes(3))) {
            $items[] = ['lvl' => 'crit', 'n' => '!', 'href' => route('health'),
                'title' => 'Health checks stopped',
                'sub' => 'Last check '.($lastPing ? $lastPing->diffForHumans() : 'never').'. The scheduler loop isn\'t running.'];
        }

        foreach ($health as $s) {
            if ($s['state'] === 'crit') {
                $items[] = ['lvl' => 'crit', 'n' => '✕', 'href' => route('health'),
                    'title' => $s['name'].' is down',
                    'sub' => ($s['latest']->error ?? 'No answer').' · checked '.$s['latest']->checked_at->format('H:i')];
            } elseif ($s['state'] === 'warn') {
                $items[] = ['lvl' => 'warn', 'n' => round($s['p95'] / 1000, 1).'s', 'href' => route('health'),
                    'title' => $s['name'].' is slow',
                    'sub' => '95% of checks in the last 24 h answered within '.number_format($s['p95']).' ms.'];
            }
        }

        if ($rfid['ok']) {
            $d = $rfid['data'];
            if ($d['missing_since_last_photo'] > 0 && $d['last_photo']) {
                $items[] = ['lvl' => 'crit', 'n' => $d['missing_since_last_photo'], 'href' => route('rfid'),
                    'title' => 'Taps with no photo since '.$d['last_photo']->format('H:i'),
                    'sub' => 'A camera may be off WiFi. The reader is still clocking people in.'];
            } elseif ($d['missing_count'] > 0) {
                $items[] = ['lvl' => 'warn', 'n' => $d['missing_count'], 'href' => route('rfid'),
                    'title' => 'Taps without a photo today',
                    'sub' => 'Photos came back afterwards, so the camera recovered.'];
            }
            if ($d['face']['blank'] > 0) {
                $items[] = ['lvl' => 'warn', 'n' => $d['face']['blank'], 'href' => route('rfid'),
                    'title' => 'Photos not face-checked',
                    'sub' => 'face-service failed or timed out, or the person has no reference photo.'];
            }
            $compared = $d['face']['matched'] + $d['face']['no_match'];
            if ($compared >= 5 && $d['face']['no_match'] / $compared > 0.2) {
                $items[] = ['lvl' => 'warn', 'n' => $d['face']['no_match'], 'href' => route('rfid'),
                    'title' => 'Faces that didn\'t match',
                    'sub' => round($d['face']['no_match'] / $compared * 100).'% of today\'s compares. Blurry photos, old reference photos, or a borrowed card.'];
            }
        } elseif (empty($rfid['unconfigured'])) {
            $items[] = ['lvl' => 'warn', 'n' => '?', 'href' => route('rfid'),
                'title' => 'Can\'t read my-genone', 'sub' => $rfid['error']];
        }

        usort($items, fn ($a, $b) => ($a['lvl'] === 'crit' ? 0 : 1) <=> ($b['lvl'] === 'crit' ? 0 : 1));

        return $items;
    }

    /** The five most recent problems across sources. */
    private function problems(array $health, array $rfid): array
    {
        $list = [];

        foreach ($health as $s) {
            foreach ($s['failures'] as $f) {
                $list[] = ['at' => $f->checked_at, 'title' => $s['name'].' health check failed', 'sub' => $f->error ?? 'No answer'];
            }
        }

        if ($rfid['ok']) {
            foreach ($rfid['data']['missing'] as $t) {
                $list[] = ['at' => $t['at'], 'title' => 'No photo for '.$t['name'], 'sub' => 'RFID clock-'.$t['kind'].' · camera never sent it'];
            }
        }

        usort($list, fn ($a, $b) => $b['at'] <=> $a['at']);

        return array_slice($list, 0, 5);
    }
}
