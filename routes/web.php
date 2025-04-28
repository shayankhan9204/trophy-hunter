<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\LoginController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\TeamController;
use App\Http\Controllers\Portal\EventController;

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'loginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::get('/team/edit/{id}', [TeamController::class, 'edit'])->name('team.edit');
    Route::post('/team/update', [TeamController::class, 'update'])->name('team.update');
    Route::delete('/team/delete/{user}', [TeamController::class, 'delete'])->name('team.delete');
    Route::post('/team/upload', [TeamController::class, 'uploadAnglers'])->name('upload.team');
    Route::get('/team/sample-csv', [TeamController::class, 'downloadSampleCsv'])->name('download.sample.teams.csv');

    Route::get('/event', [EventController::class, 'index'])->name('event.index');
    Route::get('/event/create', [EventController::class, 'create'])->name('event.create');
    Route::post('/event/store', [EventController::class, 'store'])->name('event.store');
    Route::get('/event/edit/{id}', [EventController::class, 'edit'])->name('event.edit');
    Route::post('/event/update', [EventController::class, 'update'])->name('event.update');
    Route::delete('/event/delete/{id}', [EventController::class, 'destroy'])->name('event.delete');

    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

});

Route::get('/artisan/{command}', function ($command) {
    $allowedCommands = [
        'storage-link' => 'storage:link',
        'cache-clear' => 'cache:clear',
        'config-clear' => 'config:clear',
        'config-cache' => 'config:cache',
        'route-clear' => 'route:clear',
        'route-cache' => 'route:cache',
        'view-clear' => 'view:clear',
        'permission-cache-reset' => 'permission:cache-reset',
        'optimize' => 'optimize',
        'migrate' => 'migrate',
        'migrate-refresh' => 'migrate:refresh',
        'migrate-rollback' => 'migrate:rollback',
        'db-seed' => 'db:seed',
        'queue-work' => 'queue:work',
        'queue-restart' => 'queue:restart',
        'make-controller' => 'make:controller',
        'make-model' => 'make:model',
    ];

    if (!array_key_exists($command, $allowedCommands)) {
        abort(403, 'Command not allowed.');
    }

    \Illuminate\Support\Facades\Artisan::call($allowedCommands[$command]);
    return response()->json(['status' => 'success', 'message' => 'Command executed: ' . $allowedCommands[$command]]);
});
