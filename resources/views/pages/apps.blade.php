@extends('layouts.app', ['autorefresh' => true])
@section('title', '201 & PayMaya')
@section('content')
<div class="view-head"><h1>201 &amp; PayMaya</h1>
  <p>Maintenance agent runs and tickets from 201 System, and Maya profile verifications.</p></div>

<div class="pair">
  <div class="panel flexcol">
    <div class="panel-head"><h2>Maintenance agent</h2><span class="right muted" style="font-size:12.5px">201 · last 7 days</span></div>
    @if($pm['ok'])
      @php $d = $pm["data"]; @endphp
      <div class="panel-body"><div class="kv">
        <div><span class="label">Runs</span><span class="num" style="font-size:20px;font-weight:600">{{ $d['week'] }}</span></div>
        <div><span class="label">Threats found</span><span class="num" style="font-size:20px;font-weight:600;{{ $d['threats'] ? 'color:var(--crit)' : '' }}">{{ $d['threats'] }}</span></div>
        <div><span class="label">Space freed</span><span class="num" style="font-size:20px;font-weight:600">{{ number_format($d['freed_mb'] / 1024, 1) }} GB</span></div>
      </div></div>
      <ul class="flist" style="border-top:1px solid var(--line)">
        @forelse($d['latest'] as $r)
          <li><span class="t">{{ $r['at']->format($r['at']->isToday() ? 'H:i' : 'M j') }}</span><div><b>{{ $r['computer_name'] }}</b>@if($r['threats_found']) <span class="pill crit">Threat</span>@endif<div class="sub">{{ $r['employee_name'] }}{{ $r['department'] ? ' · '.$r['department'] : '' }} · freed {{ number_format($r['freed_mb']) }} MB</div></div></li>
        @empty
          <li><span></span><span class="muted">No runs yet.</span></li>
        @endforelse
      </ul>
      <div class="panel-foot">Only finished runs are saved. Rejected reports (unfinished steps) aren't recorded yet.</div>
    @else
      <div class="empty-state" style="flex:1"><b>{{ $pm['error'] }}</b>{{ ! empty($pm['unconfigured']) ? 'Set GENONE_DB_URL on this service; 201 System uses the same database.' : 'Trying again shortly.' }}</div>
    @endif
  </div>

  <div class="panel flexcol">
    <div class="panel-head"><h2>PayMaya verifications</h2><span class="right muted" style="font-size:12.5px">All time</span></div>
    @if($paymaya['ok'])
      @php $p = $paymaya["data"]; @endphp
      <div class="panel-body"><div class="kv">
        <div><span class="label">Verified</span><span class="num" style="font-size:20px;font-weight:600">{{ $p['verified'] }}</span></div>
        <div><span class="label">Employees</span><span class="num" style="font-size:20px;font-weight:600">{{ $p['employees'] }}</span></div>
        <div><span class="label">Last 7 days</span><span class="num" style="font-size:20px;font-weight:600">{{ $p['week'] }}</span></div>
      </div>
      <div class="prog" style="margin:12px 0 0"><i style="width:{{ $p['employees'] ? round($p['verified'] / $p['employees'] * 100) : 0 }}%"></i></div></div>
      <ul class="flist" style="border-top:1px solid var(--line)">
        @forelse($p['latest'] as $v)
          <li><span class="t">{{ $v['at']?->format($v['at']->isToday() ? 'H:i' : 'M j') }}</span><div><b>{{ $v['name'] }}</b><div class="sub">{{ ucfirst($v['status'] ?? 'pending') }}</div></div></li>
        @empty
          <li><span></span><span class="muted">No uploads yet.</span></li>
        @endforelse
      </ul>
      <div class="panel-foot">Rejected uploads only show an error to the employee and aren't stored yet.</div>
    @else
      <div class="empty-state" style="flex:1"><b>{{ $paymaya['error'] }}</b>{{ ! empty($paymaya['unconfigured']) ? 'Set PAYMAYA_DB_URL on this service to read PayMaya.' : 'Trying again shortly.' }}</div>
    @endif
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h2>IT tickets</h2><span class="right muted" style="font-size:12.5px">Ticketing Portal · 201 database</span></div>
  @if($tickets['ok'])
    <div class="panel-body"><div class="kv">
      <div><span class="label">Open</span><span class="num" style="font-size:20px;font-weight:600">{{ $tickets['data']['open'] }}</span></div>
      <div><span class="label">Open over 3 days</span><span class="num" style="font-size:20px;font-weight:600;{{ $tickets['data']['old'] ? 'color:var(--warn)' : '' }}">{{ $tickets['data']['old'] }}</span></div>
      <div><span class="label">Filed today</span><span class="num" style="font-size:20px;font-weight:600">{{ $tickets['data']['today'] }}</span></div>
    </div></div>
  @else
    <div class="empty-state"><b>{{ $tickets['error'] }}</b></div>
  @endif
</div>
@endsection
