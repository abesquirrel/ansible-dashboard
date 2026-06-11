<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController,
    PlaybookController,
    InventoryController,
    TerminalController,
    LogController,
    SettingsController,
    LearningController,
};

// Auth routes
Route::get('/login',  [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout',[\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'active'])->group(function () {

    // Dashboard
    Route::get('/',          [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/status',    [DashboardController::class, 'connectionStatus'])->name('status');

    // Learning
    Route::prefix('learning')->name('learning.')->group(function () {
        Route::get('/',             [LearningController::class, 'index'])->name('index');
        Route::get('/{slug}',       [LearningController::class, 'topic'])->name('topic');
    });

    // Playbooks
    Route::prefix('playbooks')->name('playbooks.')->group(function () {
        Route::get('/',               [PlaybookController::class, 'index'])->name('index');
        Route::post('/run',           [PlaybookController::class, 'run'])->name('run');
        Route::get('/content',        [PlaybookController::class, 'getContent'])->name('content');
    });

    // Jobs
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/{job}',         [PlaybookController::class, 'show'])->name('show');
        Route::post('/{job}/abort',  [PlaybookController::class, 'abort'])->name('abort');
        Route::get('/{job}/output',  [PlaybookController::class, 'outputLines'])->name('output');
    });

    // Inventory
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/',              [InventoryController::class, 'index'])->name('index');
        Route::post('/ping',         [InventoryController::class, 'ping'])->name('ping');
        Route::post('/facts',        [InventoryController::class, 'facts'])->name('facts');
        Route::post('/adhoc',        [InventoryController::class, 'adHoc'])->name('adhoc');
        Route::get('/file',          [InventoryController::class, 'getFile'])->name('file.get');
        Route::post('/file',         [InventoryController::class, 'saveFile'])->name('file.save');
    });

    // Terminal
    Route::prefix('terminal')->name('terminal.')->group(function () {
        Route::get('/',              [TerminalController::class, 'index'])->name('index');
        Route::post('/exec',         [TerminalController::class, 'exec'])->name('exec');
        Route::post('/stream',       [TerminalController::class, 'stream'])->name('stream');
    });

    // Logs
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/',              [LogController::class, 'index'])->name('index');
        Route::get('/jobs',          [LogController::class, 'jobHistory'])->name('jobs');
    });

    // Settings (admin only)
    Route::prefix('settings')->name('settings.')->middleware('admin')->group(function () {
        Route::get('/',              [SettingsController::class, 'index'])->name('index');
        Route::post('/test',         [SettingsController::class, 'testConnection'])->name('test');
        Route::post('/env',          [SettingsController::class, 'updateEnv'])->name('env');
    });
});
