<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::post('group',[ChatController::class,'add'])->name('add');
    Route::get('/group/{group}', [ChatController::class, 'show'])->name('show');
    Route::post('/group/{group}', [ChatController::class, 'store'])->name('store');
});

require __DIR__.'/auth.php';
