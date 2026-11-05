<li class="{{ $t->done_at ? 'done' : '' }}">
  <form method="POST" action="{{ route('todos.toggle', $t) }}" data-todo>@csrf<input type="checkbox" id="{{ $p }}-{{ $t->id }}" @checked($t->done_at) aria-label="Done"></form>
  <div><label for="{{ $p }}-{{ $t->id }}">{{ $t->title }}</label>
    <div class="meta"><span class="tag">{{ $t->system }}</span><span class="age">{{ $t->done_at ? 'done '.$t->done_at->diffForHumans() : 'added '.$t->created_at->diffForHumans() }}</span></div></div>
  <form method="POST" action="{{ route('todos.destroy', $t) }}" data-todo>@csrf<button class="x" type="submit" aria-label="Delete task">×</button></form>
</li>
