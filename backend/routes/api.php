<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\DeleteDocumentController;
use App\Http\Controllers\Api\ListDocumentsController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\ProcessDocumentController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UploadDocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)
        ->middleware('throttle:30,1')
        ->name('login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', LogoutController::class)->name('logout');
        Route::get('me', ProfileController::class)->name('me');
    });
});
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('documents')->group(function () {
    Route::post('/', UploadDocumentController::class)->middleware('throttle:upload');
    Route::get('/', ListDocumentsController::class);
    Route::post('/{document}/process', ProcessDocumentController::class)->name('documents.process');
    Route::delete('/{document}', DeleteDocumentController::class)->name('documents.delete');
    Route::post('/search', SearchController::class)->name('search');
});
Route::middleware(['auth:sanctum', 'throttle:chat'])->prefix('chat')->group(function () {
    Route::post('/', [ChatController::class, 'store'])->name('chat.send');
    Route::get('/conversations', [ChatController::class, 'conversations'])->name('chat.conversations');
    Route::get('/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.show');
    Route::patch('/conversations/{conversation}', [ChatController::class, 'update'])->name('chat.update');
    Route::delete('/conversations/{conversation}', [ChatController::class, 'destroy'])->name('chat.destroy');
});
