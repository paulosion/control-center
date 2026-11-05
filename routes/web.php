<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'overview'])->name('overview');
    Route::get('/rfid', [DashboardController::class, 'rfid'])->name('rfid');
    Route::get('/health', [DashboardController::class, 'health'])->name('health');
    Route::get('/apps', [DashboardController::class, 'apps'])->name('apps');
    Route::get('/not-recorded', [DashboardController::class, 'gaps'])->name('gaps');
    Route::get('/todo', [DashboardController::class, 'todo'])->name('todo');

    Route::post('/todos', [TodoController::class, 'store'])->name('todos.store');
    Route::post('/todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todos.toggle');
    Route::post('/todos/{todo}/delete', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('/todos/clear-done', [TodoController::class, 'clearDone'])->name('todos.clear');
});
