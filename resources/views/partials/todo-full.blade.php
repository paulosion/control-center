<div class="panel" data-todo-panel="full" data-open-count="{{ $open->count() }}">
  <form class="add" method="POST" action="{{ route('todos.store') }}" data-todo autocomplete="off">
    @csrf
    <input name="title" placeholder="Add a task…" aria-label="Task" maxlength="255" required>
    <select name="system" aria-label="System">@foreach($systems as $s)<option>{{ $s }}</option>@endforeach</select>
    <button class="btn btn-primary" type="submit">Add</button>
  </form>
  <ul class="todos">
    @forelse($open->concat($done) as $t)
      @include('partials.todo-item', ['t' => $t, 'p' => 'td'])
    @empty
      <li class="muted" style="display:block">Nothing here yet. Add your first task above.</li>
    @endforelse
  </ul>
  @if($done->count())
    <div class="panel-foot ov-foot"><span>{{ $done->count() }} done</span>
      <form method="POST" action="{{ route('todos.clear') }}" data-todo style="margin-left:auto">@csrf<button class="linkbtn" type="submit">Clear done</button></form></div>
  @endif
</div>
