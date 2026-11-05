<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Sign in · GenOne Control Center</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Condensed:wght@600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap">
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
<script>try{var t=localStorage.getItem('cc-theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
</head>
<body>
<div class="login-wrap">
  <form class="login" method="POST" action="{{ url('/login') }}">
    @csrf
    <div class="brand"><span class="brand-mark">G1</span><span>Control Center</span></div>
    <label>Email<input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
    <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
    <label class="remember"><input type="checkbox" name="remember" value="1" checked> Keep me signed in on this device</label>
    @error('email')<p class="err">{{ $message }}</p>@enderror
    <button class="btn btn-primary" type="submit" style="padding:9px">Sign in</button>
  </form>
</div>
</body>
</html>
