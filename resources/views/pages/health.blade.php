@extends('layouts.app', ['autorefresh' => true])
@section('title', 'Health')
@use('App\Support\Svg')
@section('content')
<div class="view-head"><h1>Health</h1>
  <p>Each system's health URL is called once a minute. Response time, uptime and failures over the last 24 hours.</p></div>

@if(! $lastPing || $lastPing->lt(now()->subMinutes(3)))
  <div class="stale">Health checks aren't running. Last check: {{ $lastPing ? $lastPing->diffForHumans() : 'never' }}. Check this service's logs for the schedule:work loop.</div>
@endif

<div class="sys-cards">
  @foreach($health as $s)
    <article class="sys">
      <div class="sys-top"><div><h3>{{ $s['name'] }}</h3><div class="sub">{{ $s['sub'] }}</div></div>@include('partials.state', ['state' => $s['state'], 'url' => $s['url']])</div>
      @if($s['url'])
        <div class="sys-metric">
          <div class="ms num">{{ $s['now_ms'] !== null ? number_format($s['now_ms']) : '—' }}<span> ms</span></div>
          {!! Svg::spark($s['series'] ?: [0, 0], $s['state'] === 'crit' ? 'var(--crit)' : ($s['state'] === 'warn' ? 'var(--warn)' : 'var(--chart-a)')) !!}
        </div>
        <div class="kv">
          <div><span class="label">p95</span><span class="num">{{ $s['p95'] !== null ? number_format($s['p95']).' ms' : '—' }}</span></div>
          <div><span class="label">Uptime</span><span class="num">{{ $s['uptime'] !== null ? $s['uptime'].'%' : '—' }}</span></div>
          <div><span class="label">Failed</span><span class="num" style="{{ $s['fails'] ? 'color:var(--crit);font-weight:600' : '' }}">{{ $s['fails'] }} / {{ $s['checks'] }}</span></div>
        </div>
        @if($s['failures']->count())
          <ul class="facts" style="list-style:none;margin:0;padding:0;font-size:12.5px;display:flex;flex-direction:column;gap:3px">
            @foreach($s['failures']->take(3) as $fl)
              <li><span class="mono muted">{{ $fl->checked_at->format('H:i') }}</span> {{ $fl->error }}</li>
            @endforeach
          </ul>
        @endif
      @else
        <p class="muted" style="font-size:13px">No URL set. Add <span class="mono">FACE_SERVICE_URL</span> to this service's variables to start checking it.</p>
      @endif
      <div class="sys-links">
        @if($s['url'])<a href="{{ $s['url'] }}" target="_blank" rel="noopener">Open</a>@endif
        @if($s['repo'])<a href="{{ $s['repo'] }}" target="_blank" rel="noopener">GitHub</a>@endif
        <a href="https://railway.com/dashboard" target="_blank" rel="noopener">Railway</a>
      </div>
    </article>
  @endforeach
</div>
<p class="muted" style="font-size:12.5px">A check fails when the URL doesn't answer with 2xx within {{ config('systems.timeout') }} seconds. "Slow" means p95 over {{ number_format(config('systems.slow_ms')) }} ms.</p>
@endsection
