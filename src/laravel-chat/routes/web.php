<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [GroupController::class, 'index'])->name('index');
    Route::post('group',[GroupController::class,'add'])->name('add');
    Route::get('/group/{group}', [MessageController::class, 'show'])->name('show');
    Route::post('/group/{group}', [MessageController::class, 'store'])->name('store');
    Route::get('/groups/{token}/join/{group}', [MemberController::class, 'join'])->name('join.token');
    Route::post('/groups/{group}/join', [MemberController::class, 'join'])->name('join');
    Route::delete('/groups/{group}/leave', [MemberController::class, 'leave'])->name('leave');
    Route::post('/group/{group}/invite', [InvitationController::class, 'invite'])->name('invite');
    Route::post('/group/{group}/resend/{invitation}', [InvitationController::class, 'resend'])->name('resend');
    Route::get('/group/{group}/edit', [GroupController::class, 'edit'])->name('edit');
    Route::put('/group/{group}/edit', [GroupController::class, 'update'])->name('update');
    Route::delete('/groups/{group}/remove/{user}', [MemberController::class, 'remove'])->name('remove');
});

require __DIR__.'/auth.php';
