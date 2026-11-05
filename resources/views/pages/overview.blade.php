@extends('layouts.app', ['autorefresh' => true])
@section('title', 'Overview')
@section('content')
@use('App\Support\Svg')
@php
  $shown = array_slice($attention, 0, 4);
  $extra = count($attention) - count($shown);
  $up = collect($health)->filter(fn ($s) => $s['url'])->count();
  $upNow = collect($health)->where('state', '!=', 'crit')->filter(fn ($s) => $s['url'] && $s['latest'])->count();
  $slowest = collect($health)->filter(fn ($s) => $s['p95'])->sortByDesc('p95')->first();
  $genone = $health['my-genone'] ?? null;
@endphp

<div>
  <div class="label" style="margin-bottom:8px">Failing or at risk</div>
  @if($shown)
    <div class="attn" style="grid-template-columns:repeat({{ count($shown) }},minmax(0,1fr))" data-attn>
      @foreach($shown as $a)
        <a class="{{ $a['lvl'] }}" href="{{ $a['href'] }}"><span class="big num">{{ $a['n'] }}</span><span><b>{{ $a['title'] }}</b><small>{{ $a['sub'] }}</small></span></a>
      @endforeach
    </div>
    @if($extra > 0)<p class="muted" style="font-size:12.5px;margin-top:6px">+{{ $extra }} more on the other pages</p>@endif
  @else
    <div class="allclear"><b>All clear.</b><span class="muted">Every system answered, and every RFID tap today has a photo.</span></div>
  @endif
</div>

<div>
  <div class="label" style="margin-bottom:8px">Today, 00:00 to now</div>
  <div class="tiles quad">
    {{-- RFID taps --}}
    <a class="tile" href="{{ route('rfid') }}"><span class="label">RFID taps</span>
      @if($rfid['ok'])
        @php $d = $rfid['data']; @endphp
        <span class="v num">{{ $d['tap_count'] }}</span>
        {!! Svg::spark(collect(range(6, 19))->map(fn ($h) => $d['hours'][$h]['taps'] ?? 0)->all()) !!}
        <span class="sub">@if($d['missing_count'])<span class="bad">{{ $d['missing_count'] }} with no photo</span>@else All have photos @endif · {{ $d['people'] }} people</span>
      @else
        <span class="v num">—</span><span class="sub">{{ $rfid['error'] }}</span>
      @endif
    </a>

    {{-- Face matches --}}
    <a class="tile" href="{{ route('rfid') }}"><span class="label">Face matches</span>
      @if($rfid['ok'])
        @php $f = $rfid['data']['face']; @endphp
        <span class="v num">{{ $f['matched'] }}<small>/ {{ $f['matched'] + $f['no_match'] + $f['blank'] }}</small></span>
        {!! Svg::split([[$f['matched'], 'var(--ok)'], [$f['no_match'], 'var(--warn)'], [$f['blank'], 'var(--crit)']]) !!}
        <span class="sub">{{ $f['no_match'] }} no match · {{ $f['blank'] }} not checked</span>
      @else
        <span class="v num">—</span><span class="sub">{{ $rfid['error'] }}</span>
      @endif
    </a>

    {{-- Systems --}}
    <a class="tile" href="{{ route('health') }}"><span class="label">Systems up</span>
      <span class="v num">{{ $upNow }}<small>/ {{ $up }}</small></span>
      {!! Svg::spark($genone['series'] ?: [0, 0], 'var(--chart-a)') !!}
      <span class="sub">@if($slowest)Slowest: {{ $slowest['name'] }}, {{ number_format($slowest['p95']) }} ms @else No checks yet @endif</span>
    </a>

    {{-- PayMaya --}}
    <a class="tile" href="{{ route('apps') }}"><span class="label">PayMaya verified</span>
      @if($paymaya['ok'])
        @php $p = $paymaya['data']; @endphp
        <span class="v num">{{ $p['verified'] }}<small>/ {{ $p['employees'] }}</small></span>
        {!! Svg::spark($p['series']) !!}
        <span class="sub">+{{ $p['week'] }} uploads in the last 7 days</span>
      @else
        <span class="v num">—</span><span class="sub">{{ $paymaya['error'] }}</span>
      @endif
    </a>
  </div>
</div>

<div class="pair">
  @include('partials.todo-mini', $todos)
  <div class="panel flexcol">
    <div class="panel-head"><h2>Latest problems</h2><span class="right muted" style="font-size:12.5px">Last 24 hours</span></div>
    @if($problems)
      <ul class="flist">
        @foreach($problems as $pr)
          <li><span class="t">{{ $pr['at']->format('H:i') }}</span><div><b>{{ $pr['title'] }}</b><div class="sub">{{ $pr['sub'] }}{{ $pr['at']->isToday() ? '' : ' · '.$pr['at']->format('M j') }}</div></div></li>
        @endforeach
      </ul>
    @else
      <div class="empty-state" style="flex:1;display:grid;place-content:center"><b>No problems in the last 24 hours</b>No failed health checks and no RFID taps missing a photo.</div>
    @endif
    <div class="panel-foot ov-foot"><a href="{{ route('health') }}">Health details</a><a href="{{ route('rfid') }}">RFID taps</a></div>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h2>Per app</h2><span class="right muted" style="font-size:12.5px">Last 24 hours · p95 = 95% of checks answered faster</span></div>
  <div class="tbl-wrap"><table>
    <thead><tr><th>App</th><th>Status</th><th class="r">Now</th><th class="r">p95</th><th class="r">Uptime</th><th>Last failure</th></tr></thead>
    <tbody>
    @foreach($health as $s)
      <tr>
        <td>{{ $s['name'] }}</td>
        <td>@include('partials.state', ['state' => $s['state'], 'url' => $s['url']])</td>
        <td class="r num">{{ $s['now_ms'] !== null ? number_format($s['now_ms']).' ms' : '—' }}</td>
        <td class="r num">{{ $s['p95'] !== null ? number_format($s['p95']).' ms' : '—' }}</td>
        <td class="r num">{{ $s['uptime'] !== null ? $s['uptime'].'%' : '—' }}</td>
        <td class="num">{{ $s['last_fail'] ? $s['last_fail']->checked_at->format($s['last_fail']->checked_at->isToday() ? 'H:i' : 'M j, H:i') : 'None' }}</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@endsection
