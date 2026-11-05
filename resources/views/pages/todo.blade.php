@extends('layouts.app')
@section('title', 'To-do')
@section('content')
<div class="view-head"><h1>To-do</h1>
  <p>Saved on the server, so it's the same list on your PC and your phone.</p></div>
@include('partials.todo-full', $todos)
@endsection
