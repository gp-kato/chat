<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;
use App\Events\MessageEvent;

Route::middleware(['auth', 'verified'])->group(function () {
    // 認証後の着地点を /groups に統一（ハンドラ重複を避けるため '/' は一覧へ委譲）
    Route::get('/', fn () => redirect()->route('groups.index'));

    // グループ管理
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::post('/', [GroupController::class, 'add'])->name('add');
        Route::put('/{group}/edit', [GroupController::class, 'update'])->name('update');
        Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');

        // 特定のグループに対する操作
        Route::prefix('{group}')->group(function () {
            // メッセージ管理
            Route::prefix('messages')->name('messages.')->group(function () {
                Route::get('/', [MessageController::class, 'show'])->name('show');
                Route::post('/', [MessageController::class, 'store'])->name('store');
                Route::get('/fetch', [MessageController::class, 'fetch'])->name('fetch');
            });

            // メンバー管理
            Route::prefix('members')->name('members.')->group(function () {
                Route::post('/', [MemberController::class, 'join'])->name('join');
                Route::delete('/me', [MemberController::class, 'leave'])->name('leave');
                Route::delete('/{user}', [MemberController::class, 'remove'])->name('remove');
            });

            // 招待管理
            Route::prefix('invitations')->name('invitations.')->group(function () {
                Route::post('/', [InvitationController::class, 'invite'])->name('invite');
                Route::get('/{token}/join', [MemberController::class, 'join'])->name('join.token');
                Route::post('/{invitation}/resend', [InvitationController::class, 'resend'])->name('resend');
            });
        });
    });
});

require __DIR__.'/auth.php';
