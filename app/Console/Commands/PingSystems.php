<?php

namespace App\Console\Commands;

use App\Models\HealthCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Throwable;

#[Signature('health:ping')]
#[Description('Call every system\'s health URL once and record status + response time')]
class PingSystems extends Command
{
    public function handle(): int
    {
        $systems = collect(config('systems'))
            ->filter(fn ($s) => is_array($s) && ! empty($s['url']));

        $timeout = (int) config('systems.timeout', 10);
        $started = [];

        // All pings run at the same time, so one slow system doesn't delay
        // the others' readings.
        $responses = Http::pool(function (Pool $pool) use ($systems, $timeout, &$started) {
            foreach ($systems as $key => $s) {
                $started[$key] = microtime(true);
                $pool->as($key)
                    ->connectTimeout(5)
                    ->timeout($timeout)
                    ->withHeaders(['User-Agent' => 'GenOne-ControlCenter/1.0'])
                    ->get(rtrim($s['url'], '/').$s['health']);
            }
        });

        $now = now();

        foreach ($systems as $key => $s) {
            $r = $responses[$key] ?? null;
            $row = ['system' => $key, 'checked_at' => $now];

            if ($r instanceof Throwable) {
                $row += ['ok' => false, 'status' => null, 'ms' => null, 'error' => mb_substr($r->getMessage(), 0, 255)];
            } else {
                // transferStats gives the real request time; fall back to wall clock.
                $ms = $r->transferStats?->getTransferTime();
                $ms = $ms !== null ? (int) round($ms * 1000) : (int) round((microtime(true) - $started[$key]) * 1000);
                $row += [
                    'ok' => $r->successful(),
                    'status' => $r->status(),
                    'ms' => $ms,
                    'error' => $r->successful() ? null : 'HTTP '.$r->status(),
                ];
            }

            HealthCheck::create($row);
            $this->line(sprintf('%-14s %s %s', $key, $row['ok'] ? 'up  ' : 'DOWN', $row['ms'] !== null ? $row['ms'].' ms' : $row['error']));
        }

        return self::SUCCESS;
    }
}
