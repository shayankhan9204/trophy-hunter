<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ProfileController;

Route::post('/login', [AuthController::class, 'login']);


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::group(['prefix' => 'events'], function () {
        Route::get('/', [EventController::class, 'myEvents']);

    });

    Route::group(['prefix' => 'user'], function () {
        Route::post('/profile/update', [ProfileController::class, 'update']);

    });

});
