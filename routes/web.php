<?php

use App\Http\Controllers\CircuitController;
use App\Http\Controllers\CircuitNodeController;
use App\Http\Controllers\CircuitPresenceController;
use App\Http\Controllers\CircuitSyncController;
use App\Http\Controllers\CircuitWireController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SessionController::class, 'create'])->name('home');
Route::post('/session', [SessionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('session.store');

Route::middleware('participant')->group(function () {
    Route::post('/session/leave', [SessionController::class, 'destroy'])->name('session.destroy');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/circuits', [CircuitController::class, 'index'])->name('circuits.index');
    Route::post('/circuits', [CircuitController::class, 'store'])->name('circuits.store');
    Route::get('/circuits/{circuit}', [CircuitController::class, 'show'])->name('circuits.show');
    Route::patch('/circuits/{circuit}', [CircuitController::class, 'update'])->name('circuits.update');
    Route::delete('/circuits/{circuit}', [CircuitController::class, 'destroy'])->name('circuits.destroy');

    Route::get('/circuits/{circuit}/sync', CircuitSyncController::class)->name('circuits.sync');
    Route::patch('/circuits/{circuit}/presence', [CircuitPresenceController::class, 'update'])->name('circuits.presence.update');
    Route::delete('/circuits/{circuit}/presence', [CircuitPresenceController::class, 'destroy'])->name('circuits.presence.destroy');

    Route::post('/circuits/{circuit}/nodes', [CircuitNodeController::class, 'store'])->name('circuits.nodes.store');
    Route::patch('/circuits/{circuit}/nodes/{node}', [CircuitNodeController::class, 'update'])
        ->scopeBindings()
        ->name('circuits.nodes.update');
    Route::delete('/circuits/{circuit}/nodes/{node}', [CircuitNodeController::class, 'destroy'])
        ->scopeBindings()
        ->name('circuits.nodes.destroy');

    Route::post('/circuits/{circuit}/wires', [CircuitWireController::class, 'store'])->name('circuits.wires.store');
    Route::delete('/circuits/{circuit}/wires/{wire}', [CircuitWireController::class, 'destroy'])
        ->scopeBindings()
        ->name('circuits.wires.destroy');
});
