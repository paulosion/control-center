<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TodoController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'system' => ['required', Rule::in(Todo::SYSTEMS)],
        ]);
        Todo::create($data);

        return $this->respond($request);
    }

    public function toggle(Request $request, Todo $todo)
    {
        $todo->update(['done_at' => $todo->done_at ? null : now()]);

        return $this->respond($request);
    }

    public function destroy(Request $request, Todo $todo)
    {
        $todo->delete();

        return $this->respond($request);
    }

    public function clearDone(Request $request)
    {
        Todo::whereNotNull('done_at')->delete();

        return $this->respond($request);
    }

    /** fetch() gets the re-rendered panel back; a plain form post goes back. */
    private function respond(Request $request)
    {
        if (! $request->ajax()) {
            return back();
        }

        $panel = $request->input('panel') === 'mini' ? 'mini' : 'full';

        return view('partials.todo-'.$panel, TodoController::listData());
    }

    public static function listData(): array
    {
        $open = Todo::whereNull('done_at')->latest()->get();
        $done = Todo::whereNotNull('done_at')->orderByDesc('done_at')->get();

        return ['open' => $open, 'done' => $done, 'systems' => Todo::SYSTEMS];
    }
}
