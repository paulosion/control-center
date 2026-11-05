<?php

namespace App\Support;

/** Small server-rendered charts. Colors are CSS variables so both themes work. */
class Svg
{
    public static function spark(array $series, string $color = 'var(--chart-a)'): string
    {
        $series = array_values($series);
        if (count($series) < 2) {
            $series = array_pad($series, 2, $series[0] ?? 0);
        }
        $w = 120; $h = 34;
        $min = min($series); $max = max($series); $r = ($max - $min) ?: 1;
        $n = count($series);
        $pts = [];
        foreach ($series as $i => $v) {
            $pts[] = [round($i * ($w / ($n - 1)), 1), round($h - 3 - (($v - $min) / $r) * ($h - 8), 1)];
        }
        $line = implode(' ', array_map(fn ($p) => $p[0].','.$p[1], $pts));
        [$lx, $ly] = end($pts);

        return '<svg viewBox="0 0 '.$w.' '.$h.'" preserveAspectRatio="none" aria-hidden="true">'
            .'<polygon points="0,'.$h.' '.$line.' '.$w.','.$h.'" fill="'.$color.'" opacity=".12"/>'
            .'<polyline points="'.$line.'" fill="none" stroke="'.$color.'" stroke-width="1.6" vector-effect="non-scaling-stroke"/>'
            .'<circle cx="'.$lx.'" cy="'.$ly.'" r="2.5" fill="'.$color.'"/></svg>';
    }

    /** Taps vs photos per hour. $hours = [hour => ['taps' => n, 'photos' => n]] */
    public static function tapBars(array $hours): string
    {
        if (! $hours) {
            return '';
        }
        $from = min(7, min(array_keys($hours)));
        $to = max(array_keys($hours));
        $W = 640; $H = 190; $l = 30; $rp = 6; $t = 10; $b = 24;
        $iw = $W - $l - $rp; $ih = $H - $t - $b;
        $peak = max(array_map(fn ($h) => $h['taps'], $hours));
        $step = max(1, (int) ceil($peak / 4));
        $max = $step * 4;
        $slots = $to - $from + 1;
        $gw = $iw / $slots; $bw = min(16, $gw / 3);
        $y = fn ($v) => $t + $ih - ($v / $max) * $ih;

        $s = '<svg viewBox="0 0 '.$W.' '.$H.'" role="img" aria-label="Taps and photos per hour">';
        for ($v = 0; $v <= $max; $v += $step) {
            $s .= '<line x1="'.$l.'" x2="'.($W - $rp).'" y1="'.$y($v).'" y2="'.$y($v).'" stroke="var(--line)"/>'
                .'<text x="'.($l - 6).'" y="'.($y($v) + 3.5).'" text-anchor="end" font-size="10" fill="var(--muted)" font-family="JetBrains Mono,monospace">'.$v.'</text>';
        }
        for ($h = $from; $h <= $to; $h++) {
            $d = $hours[$h] ?? ['taps' => 0, 'photos' => 0];
            $x = $l + ($h - $from) * $gw + $gw / 2;
            $s .= '<rect x="'.($x - $bw - 1).'" y="'.$y($d['taps']).'" width="'.$bw.'" height="'.($y(0) - $y($d['taps'])).'" fill="var(--chart-b)" rx="2"/>';
            $fill = $d['photos'] < $d['taps'] ? 'var(--crit)' : 'var(--chart-a)';
            $s .= '<rect x="'.($x + 1).'" y="'.$y($d['photos']).'" width="'.$bw.'" height="'.($y(0) - $y($d['photos'])).'" fill="'.$fill.'" rx="2"/>';
            $s .= '<text x="'.$x.'" y="'.($H - 7).'" text-anchor="middle" font-size="10" fill="var(--muted)" font-family="JetBrains Mono,monospace">'.str_pad($h, 2, '0', STR_PAD_LEFT).'</text>';
        }

        return $s.'</svg>';
    }

    /** One 100% bar split into colored parts: [[value, cssColor], ...] */
    public static function split(array $parts): string
    {
        $total = array_sum(array_column($parts, 0)) ?: 1;
        $s = '<div style="display:flex;height:12px;border-radius:6px;overflow:hidden;background:var(--surface-2);margin-block:11px">';
        foreach ($parts as [$v, $c]) {
            if ($v > 0) {
                $s .= '<i style="display:block;width:'.round($v / $total * 100, 2).'%;background:'.$c.'"></i>';
            }
        }

        return $s.'</div>';
    }
}
