<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\SimulatorController;
use App\Http\Controllers\Api\AdvisorController;
use App\Http\Controllers\Api\SharedSpaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WealthOS API Routes
|--------------------------------------------------------------------------
| Todas las rutas protegidas requieren autenticación via Sanctum.
| Prefijo global: /api (configurado en bootstrap/app.php)
*/

// ── Autenticación (pública) ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// ── Rutas protegidas con Sanctum ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout']);
        Route::get('/me',       [AuthController::class, 'me']);
        Route::put('/profile',  [AuthController::class, 'updateProfile']);
    });

    // ── Portafolio ──────────────────────────────────────────────────────
    Route::prefix('portfolio')->group(function () {
        Route::get('/summary',           [PortfolioController::class, 'summary']);
        Route::get('/history',           [PortfolioController::class, 'history']);
        Route::get('/',                  [PortfolioController::class, 'index']);
        Route::post('/',                 [PortfolioController::class, 'store']);
        Route::get('/{asset}',           [PortfolioController::class, 'show']);
        Route::put('/{asset}',           [PortfolioController::class, 'update']);
        Route::delete('/{asset}',        [PortfolioController::class, 'destroy']);
        Route::post('/{asset}/refresh',  [PortfolioController::class, 'refreshPrice']);
    });

    // ── Transacciones ──────────────────────────────────────────────────
    Route::prefix('transactions')->group(function () {
        Route::get('/summary',    [TransactionController::class, 'monthlySummary']);
        Route::get('/breakdown',  [TransactionController::class, 'categoryBreakdown']);
        Route::get('/history',    [TransactionController::class, 'history']);
        Route::get('/categories', [TransactionController::class, 'categories']);
        Route::get('/',           [TransactionController::class, 'index']);
        Route::post('/',          [TransactionController::class, 'store']);
        Route::get('/{transaction}',    [TransactionController::class, 'show']);
        Route::put('/{transaction}',    [TransactionController::class, 'update']);
        Route::delete('/{transaction}', [TransactionController::class, 'destroy']);
    });

    // ── Metas Financieras ──────────────────────────────────────────────
    Route::prefix('goals')->group(function () {
        Route::get('/',        [GoalController::class, 'index']);
        Route::post('/',       [GoalController::class, 'store']);
        Route::get('/{goal}',  [GoalController::class, 'show']);
        Route::put('/{goal}',  [GoalController::class, 'update']);
        Route::delete('/{goal}',              [GoalController::class, 'destroy']);
        Route::post('/{goal}/contribution',   [GoalController::class, 'addContribution']);
    });

    // ── Simulador ──────────────────────────────────────────────────────
    Route::prefix('simulator')->group(function () {
        Route::get('/instruments', [SimulatorController::class, 'instruments']);
        Route::post('/simulate',   [SimulatorController::class, 'simulate']);
        Route::post('/compare',    [SimulatorController::class, 'compare']);
    });

    // ── Espacio Compartido ─────────────────────────────────────────────
    Route::prefix('shared')->group(function () {
        Route::get('/',                                           [SharedSpaceController::class, 'index']);
        Route::post('/',                                          [SharedSpaceController::class, 'store']);
        Route::get('/{sharedSpace}',                             [SharedSpaceController::class, 'show']);
        Route::delete('/{sharedSpace}',                          [SharedSpaceController::class, 'destroy']);
        Route::post('/{sharedSpace}/invite',                     [SharedSpaceController::class, 'invite']);
        Route::post('/{sharedSpace}/leave',                      [SharedSpaceController::class, 'leave']);
        Route::post('/accept-invitation',                        [SharedSpaceController::class, 'acceptInvitation']);
        // Transacciones compartidas
        Route::get('/{sharedSpace}/transactions',                [SharedSpaceController::class, 'getTransactions']);
        Route::post('/{sharedSpace}/transactions',               [SharedSpaceController::class, 'storeTransaction']);
        Route::delete('/{sharedSpace}/transactions/{transaction}',[SharedSpaceController::class, 'destroyTransaction']);
        // Metas compartidas
        Route::get('/{sharedSpace}/goals',                       [SharedSpaceController::class, 'getGoals']);
        Route::post('/{sharedSpace}/goals',                      [SharedSpaceController::class, 'storeGoal']);
        Route::post('/{sharedSpace}/goals/{goal}/contribution',  [SharedSpaceController::class, 'contributeGoal']);
    });

    // ── Asesor IA ──────────────────────────────────────────────────────
    Route::prefix('advisor')->group(function () {
        Route::get('/sessions',                       [AdvisorController::class, 'sessions']);
        Route::post('/sessions',                      [AdvisorController::class, 'createSession']);
        Route::post('/sessions/quick-start',          [AdvisorController::class, 'quickStart']);
        Route::get('/sessions/{session}',             [AdvisorController::class, 'getSession']);
        Route::post('/sessions/{session}/message',    [AdvisorController::class, 'sendMessage']);
        Route::delete('/sessions/{session}',          [AdvisorController::class, 'deleteSession']);
    });
});
