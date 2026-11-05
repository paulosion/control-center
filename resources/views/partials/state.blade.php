@if(empty($url))
  <span class="pill idle">No URL set</span>
@else
  <span class="pill {{ $state }}">{{ ['ok' => 'Up', 'warn' => 'Slow', 'crit' => 'Down', 'idle' => 'Waiting'][$state] }}</span>
@endif
