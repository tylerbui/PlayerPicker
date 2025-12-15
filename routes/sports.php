<?php

use App\Http\Controllers\SportsController;
use Illuminate\Support\Facades\Route;

Route::get('/sports', [SportsController::class, 'index'])->name('sports.index');
Route::get('/sports/{sports}', [SportsController::class, 'show'])->name('sports.show');
Route::post('/sports', [SportsController::class, 'store'])->name('sports.store');
Route::put('/sports/{sports}', [SportsController::class, 'update'])->name('sports.update');
Route::delete('/sports/{sports}', [SportsController::class, 'destroy'])->name('sports.destroy');