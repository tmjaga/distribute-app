<?php

use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MapController::class, 'getMap']);
Route::get('/drivers', [MapController::class, 'getDivers'])->name('get-divers');
Route::get('/restaurants', [MapController::class, 'getRestaurants'])->name('get-restaurants');
