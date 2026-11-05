@extends('layouts.app', ['autorefresh' => true])
@section('title', 'RFID taps')
@use('App\Support\Svg')
@section('content')
<div class="view-head"><h1>RFID taps</h1>
  <p>Today's taps on the office readers, whether the camera's photo arrived, and what face matching said.</p></div>

@if(! $rfid['ok'])
  <div class="note"><b>{{ $rfid['error'] }}.</b> {{ ! empty($rfid['unconfigured']) ? 'Set GENONE_DB_URL (or the GENONE_DB_* variables) on this service.' : 'Compare the login below with my-genone\'s database. This page tries again every minute.' }}
    @if(! empty($rfid['login']))<div class="mono" style="margin-top:6px;color:var(--ink)">{{ $rfid['login'] }}</div>@endif</div>
@else
  @php
    $d = $rfid['data'];
    $f = $d['face'];
  @endphp
  <div class="tiles quad">
    <div class="tile"><span class="label">Taps</span><span class="v num">{{ $d['tap_count'] }}</span><span class="sub">{{ $d['people'] }} people · last {{ $d['last_tap']?->format('H:i') ?? '—' }}</span></div>
    <div class="tile"><span class="label">Photos received</span><span class="v num">{{ $d['photo_count'] }}</span><span class="sub">Last photo {{ $d['last_photo']?->format('H:i') ?? '—' }}</span></div>
    <div class="tile"><span class="label">No photo</span><span class="v num" style="{{ $d['missing_count'] ? 'color:var(--crit)' : '' }}">{{ $d['missing_count'] }}</span><span class="sub">{{ $d['missing_since_last_photo'] ? $d['missing_since_last_photo'].' since the last photo' : 'Camera is sending' }}</span></div>
    <div class="tile"><span class="label">Not face-checked</span><span class="v num" style="{{ $f['blank'] ? 'color:var(--crit)' : '' }}">{{ $f['blank'] }}</span><span class="sub">{{ $f['no_match'] }} compared but didn't match</span></div>
  </div>

  <div class="pair">
    <div class="panel flexcol">
      <div class="panel-head"><h2>Taps vs photos per hour</h2></div>
      <div class="panel-body chart" style="flex:1">
        @if($d['hours'])
          {!! Svg::tapBars($d['hours']) !!}
          <div class="legend"><span><i style="background:var(--chart-b)"></i>Taps</span><span><i style="background:var(--chart-a)"></i>Photos</span><span><i style="background:var(--crit)"></i>Photos short</span></div>
        @else
          <div class="empty-state"><b>No taps yet today</b>The first tap on either floor shows up here within a minute.</div>
        @endif
      </div>
    </div>
    <div class="panel flexcol">
      <div class="panel-head"><h2>Face matching</h2></div>
      <div class="panel-body" style="flex:1;display:flex;flex-direction:column;gap:10px">
        @php $max = max(1, $f["matched"], $f["no_match"], $f["blank"]); @endphp
        <div class="hbars">
          <div class="hb"><span>Matched</span><span class="track"><i style="width:{{ $f['matched'] / $max * 100 }}%;background:var(--ok)"></i></span><span class="num mono" style="text-align:right">{{ $f['matched'] }}</span></div>
          <div class="hb"><span>No match</span><span class="track"><i class="w" style="width:{{ $f['no_match'] / $max * 100 }}%"></i></span><span class="num mono" style="text-align:right">{{ $f['no_match'] }}</span></div>
          <div class="hb"><span>Not checked</span><span class="track"><i class="c" style="width:{{ $f['blank'] / $max * 100 }}%"></i></span><span class="num mono" style="text-align:right">{{ $f['blank'] }}</span></div>
        </div>
        <p class="muted" style="font-size:12.5px;margin-top:auto">"Not checked" covers face-service errors, timeouts and people without a reference photo. my-genone doesn't save which one yet.</p>
      </div>
    </div>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Today's taps</h2>
      <div class="right"><div class="seg" data-filter-for="#tap-list" role="group" aria-label="Filter taps">
        <button type="button" aria-pressed="true" data-f="all">All</button><button type="button" aria-pressed="false" data-f="bad">Problems</button>
      </div></div></div>
    @if($d['taps'])
      <ul class="taps" id="tap-list">
        @foreach(array_reverse($d['taps']) as $t)
          @php
            $fm = $d['face_by_name'][$t['name']] ?? null;
            $bad = ! $t['photo'] || ($fm && $fm['match'] !== true);
            // face-service reports confidence as a percentage (e.g. 71.13).
            $conf = $fm && $fm['confidence'] !== null
                ? round($fm['confidence'] <= 1 ? $fm['confidence'] * 100 : $fm['confidence']).'%'
                : '';
          @endphp
          <li data-bad="{{ $bad ? 1 : 0 }}">
            <span class="t">{{ $t['at']->format('H:i:s') }}</span>
            <span><b style="font-weight:500">{{ $t['name'] }}</b> <span class="k">clock-{{ $t['kind'] }}</span></span>
            <span>
              @if(! $t['photo'])<span class="pill crit">No photo</span>
              @elseif($fm && $fm['match'] === true)<span class="pill ok">Match {{ $conf }}</span>
              @elseif($fm && $fm['match'] === false)<span class="pill warn">No match {{ $conf }}</span>
              @else<span class="pill crit">Not checked</span>@endif
            </span>
          </li>
        @endforeach
      </ul>
    @else
      <div class="empty-state"><b>No taps yet today</b></div>
    @endif
    <div class="panel-foot">Face result is per person for the day (the latest photo's). Floor and device aren't known until the terminals send a device ID.</div>
  </div>
@endif
@endsection
