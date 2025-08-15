<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
Route::get('/', [GroupController::class, 'index'])->name('index');
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::post('/',[GroupController::class,'add'])->name('add');
        Route::put('{group}/edit', [GroupController::class, 'update'])->name('update');
        Route::get('{group}/edit', [GroupController::class, 'edit'])->name('edit');
    });
    Route::get('/groups/{group}', [MessageController::class, 'show'])->name('show');
    Route::prefix('groups/{group}/messages')->name('messages.')->group(function () {
        Route::post('/', [MessageController::class, 'store'])->name('store');
    });
    Route::prefix('groups/{group}')->name('groups.')->group(function () {
        Route::post('join', [MemberController::class, 'join'])->name('join');
        Route::delete('leave', [MemberController::class, 'leave'])->name('leave');
        Route::delete('remove/{user}', [MemberController::class, 'remove'])->name('remove');
    });
    Route::get('/groups/{token}/join/{group}', [MemberController::class, 'join'])->name('join.token');
    Route::prefix('groups/{group}')->name('invitation.')->group(function () {
        Route::post('invite', [InvitationController::class, 'invite'])->name('invite');
        Route::post('resend/{invitation}', [InvitationController::class, 'resend'])->name('resend');
    });
});

require __DIR__.'/auth.php';
