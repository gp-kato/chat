<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::post('group',[ChatController::class,'add'])->name('add');
    Route::get('/group/{group}', [ChatController::class, 'show'])->name('show');
    Route::post('/group/{group}', [ChatController::class, 'store'])->name('store');
    Route::get('/groups/{token}/join/{group}', [ChatController::class, 'join'])->name('join.token');
    Route::post('/groups/{group}/join', [ChatController::class, 'join'])->name('join');
    Route::delete('/groups/{group}/leave', [ChatController::class, 'leave'])->name('leave');
    Route::get('/group/{group}/search', [ChatController::class, 'search'])->name('search');
    Route::post('/group/{group}/invite', [ChatController::class, 'invite'])->name('invite');
    Route::get('/group/{group}/edit', [ChatController::class, 'edit'])->name('edit');
    Route::put('/group/{group}/edit', [ChatController::class, 'update'])->name('update');
    Route::delete('/groups/{group}/remove/{user}', [ChatController::class, 'remove'])->name('remove');
});

require __DIR__.'/auth.php';
