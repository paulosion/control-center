<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#2347C5">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="robots" content="noindex, nofollow">
<title>@yield('title', 'Overview') · GenOne Control Center</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<script>try{var t=localStorage.getItem('cc-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body @if(!empty($autorefresh)) data-autorefresh="1" @endif>
@include('partials.icons')
<header class="top">
  <div class="brand"><span class="brand-mark">G1</span><span>Control Center</span></div>
  @php
    $fresh = $lastPing && $lastPing->gt(now()->subMinutes(3));
    $nav = [
      ['overview', 'Overview', 'i-grid'],
      ['rfid', 'RFID taps', 'i-api'],
      ['health', 'Health', 'i-link'],
      ['apps', '201 & PayMaya', 'i-clock'],
      ['todo', 'To-do', 'i-check'],
    ];
  @endphp
  <span class="live" title="Last health check {{ $lastPing?->format('H:i:s') ?? 'never' }}"><i style="background:{{ $fresh ? 'var(--ok)' : 'var(--crit)' }}"></i><span>{{ $fresh ? 'Live · checked '.$lastPing->diffForHumans() : 'Health checks not running' }}</span></span>
  <div class="top-right">
    <span class="clock num" id="clock"></span>
    <button class="btn" id="theme-btn" type="button">Theme</button>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">Sign out</button></form>
  </div>
</header>

<div class="shell">
  <aside class="rail">
    <nav aria-label="Views">
      @foreach($nav as [$r, $label, $icon])
        <a href="{{ route($r) }}" @if(request()->routeIs($r)) aria-current="page" @endif><svg class="ico"><use href="#{{ $icon }}"/></svg>{{ $label }}@if($r === 'todo')<span class="count" data-todo-count>{{ \App\Models\Todo::whereNull('done_at')->count() }}</span>@endif</a>
      @endforeach
      <span class="sep"></span>
      <a href="{{ route('gaps') }}" @if(request()->routeIs('gaps')) aria-current="page" @endif><svg class="ico"><use href="#i-map"/></svg>Not recorded yet</a>
    </nav>
  </aside>
  <main>
    <section class="view">
      @yield('content')
    </section>
  </main>
</div>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
</body>
</html>
