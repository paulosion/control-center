@php
  $shownOpen = $open->take(4);
  $shownDone = $done->take(max(1, 5 - $shownOpen->count()));
  $total = $open->count() + $done->count();
@endphp
<div class="panel flexcol" data-todo-panel="mini" data-open-count="{{ $open->count() }}">
  <div class="panel-head"><h2>To-do</h2><span class="right muted num" style="font-size:12.5px">{{ $total ? $done->count().' of '.$total.' done' : '' }}</span></div>
  <div class="panel-body" style="padding-block:12px">
    <div class="prog" aria-hidden="true"><i style="width:{{ $total ? round($done->count() / $total * 100) : 0 }}%"></i></div>
    <form class="quick" method="POST" action="{{ route('todos.store') }}" data-todo autocomplete="off">
      @csrf
      <input name="title" placeholder="Add a task, press Enter" aria-label="New task" maxlength="255" required>
      <select name="system" aria-label="System">@foreach($systems as $s)<option>{{ $s }}</option>@endforeach</select>
      <button class="btn btn-primary" type="submit">Add</button>
    </form>
  </div>
  <ul class="todos compact">
    @forelse($shownOpen->concat($shownDone) as $t)
      @include('partials.todo-item', ['t' => $t, 'p' => 'ov'])
    @empty
      <li class="muted" style="display:block">All clear. Add a task above.</li>
    @endforelse
  </ul>
  <div class="panel-foot ov-foot">
    <a href="{{ route('todo') }}">All tasks</a>
    <span>{{ $open->count() > 4 ? '+'.($open->count() - 4).' more open' : '' }}</span>
    @if($done->count())
      <form method="POST" action="{{ route('todos.clear') }}" data-todo style="margin-left:auto">@csrf<button class="linkbtn" type="submit">Clear done</button></form>
    @endif
  </div>
</div>
