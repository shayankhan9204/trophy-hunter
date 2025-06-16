<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Portal\LoginController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\TeamController;
use App\Http\Controllers\Portal\EventController;
use App\Http\Controllers\Portal\SpecieController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\ReportsController;

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'loginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::get('/team', [TeamController::class, 'index'])->name('team.index');
    Route::get('/team/edit/{id}', [TeamController::class, 'edit'])->name('team.edit');
    Route::post('/team/update', [TeamController::class, 'update'])->name('team.update');
    Route::get('/team-detach', [TeamController::class, 'detachTeamFromEvent'])->name('team.detach');
    Route::post('/team/upload', [TeamController::class, 'uploadAnglers'])->name('upload.team');
    Route::get('/team/sample-csv', [TeamController::class, 'downloadSampleCsv'])->name('download.sample.teams.csv');
    Route::delete('/team/delete-event-teams', [TeamController::class, 'deleteEventTeams'])->name('delete.event.teams');
    Route::get('/teams/export', [TeamController::class, 'export'])->name('teams.export');

    Route::get('/event', [EventController::class, 'index'])->name('event.index');
    Route::get('/event/create', [EventController::class, 'create'])->name('event.create');
    Route::post('/event/store', [EventController::class, 'store'])->name('event.store');
    Route::get('/event/edit/{id}', [EventController::class, 'edit'])->name('event.edit');
    Route::post('/event/update', [EventController::class, 'update'])->name('event.update');
    Route::delete('/event/delete/{id}', [EventController::class, 'destroy'])->name('event.delete');
    Route::get('/event/{id}/export-catch', [EventController::class, 'exportCatch'])->name('event.export.catch');

    Route::get('/specie', [SpecieController::class, 'index'])->name('specie.index');
    Route::get('/specie/create', [SpecieController::class, 'create'])->name('specie.create');
    Route::post('/specie/store', [SpecieController::class, 'store'])->name('specie.store');
    Route::get('/specie/edit/{id}', [SpecieController::class, 'edit'])->name('specie.edit');
    Route::post('/specie/update', [SpecieController::class, 'update'])->name('specie.update');
    Route::delete('/specie/delete/{id}', [SpecieController::class, 'destroy'])->name('specie.delete');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notification.index');
    Route::get('/notification/create', [NotificationController::class, 'create'])->name('notification.create');
    Route::post('/notification/store', [NotificationController::class, 'store'])->name('notification.store');

    Route::get('/team-ranking-report', [ReportsController::class, 'teamRankingReport'])->name('team.ranking.report');
    Route::get('/individual-fish-report', [ReportsController::class, 'individualFishReport'])->name('individual.fish.report');

    Route::get('/get-species-by-event', [EventController::class, 'getSpeciesByEvent'])->name('get.species.by.event');

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
