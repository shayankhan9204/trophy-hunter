<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\ProfileController;

Route::post('/login', [AuthController::class, 'login']);


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::group(['prefix' => 'events'], function () {
        Route::get('/', [EventController::class, 'myEvents']);
        Route::get('/detail/{id}', [EventController::class, 'eventDetail']);

    });

    Route::group(['prefix' => 'user'], function () {
        Route::post('/profile/update', [ProfileController::class, 'update']);
        Route::get('/team', [ProfileController::class, 'myTeam']);

    });

    Route::get('/notifications', [EventController::class, 'notifications']);

});
