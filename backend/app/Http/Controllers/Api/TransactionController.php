<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\PortfolioService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(private PortfolioService $portfolioService) {}

    /**
     * Listar transacciones con filtros opcionales
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->transactions()->orderByDesc('date');

        // Filtro por mes/año
        if ($request->filled('year') && $request->filled('month')) {
            $query->ofMonth($request->integer('year'), $request->integer('month'));
        }

        // Filtro por tipo
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filtro por categoría
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $transactions = $query->paginate(50);

        return response()->json($transactions);
    }

    /**
     * Registrar una nueva transacción
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'sometimes|string|size:3',
            'category'    => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'date'        => 'required|date|before_or_equal:today',
            'tag'         => 'nullable|string|max:50',
        ]);

        $transaction = $request->user()->transactions()->create($validated);

        return response()->json([
            'message'     => 'Transacción registrada.',
            'transaction' => $transaction,
        ], 201);
    }

    /**
     * Mostrar una transacción específica
     */
    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeTransaction($request, $transaction);

        return response()->json(['transaction' => $transaction]);
    }

    /**
     * Actualizar una transacción
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeTransaction($request, $transaction);

        $validated = $request->validate([
            'type'        => 'sometimes|in:income,expense',
            'amount'      => 'sometimes|numeric|min:0.01',
            'currency'    => 'sometimes|string|size:3',
            'category'    => 'sometimes|string|max:50',
            'description' => 'nullable|string|max:255',
            'date'        => 'sometimes|date',
            'tag'         => 'nullable|string|max:50',
        ]);

        $transaction->update($validated);

        return response()->json(['message' => 'Transacción actualizada.', 'transaction' => $transaction->fresh()]);
    }

    /**
     * Eliminar una transacción
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorizeTransaction($request, $transaction);

        $transaction->delete();

        return response()->json(['message' => 'Transacción eliminada.']);
    }

    /**
     * Resumen financiero del mes actual (o el especificado)
     */
    public function monthlySummary(Request $request): JsonResponse
    {
        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $summary = $this->portfolioService->getMonthlySummary($request->user(), $year, $month);

        return response()->json($summary);
    }

    /**
     * Estadísticas por categoría para el mes indicado
     */
    public function categoryBreakdown(Request $request): JsonResponse
    {
        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $transactions = $request->user()->transactions()
            ->ofMonth($year, $month)
            ->where('type', 'expense')
            ->get();

        $breakdown = $transactions
            ->groupBy('category')
            ->map(fn($group) => [
                'category' => $group->first()->category,
                'amount'   => round($group->sum('amount'), 2),
                'count'    => $group->count(),
                'avg'      => round($group->avg('amount'), 2),
            ])
            ->sortByDesc('amount')
            ->values();

        return response()->json(['breakdown' => $breakdown, 'year' => $year, 'month' => $month]);
    }

    /**
     * Historial de balance mensual (últimos N meses)
     */
    public function history(Request $request): JsonResponse
    {
        $months  = $request->integer('months', 6);
        $history = $this->portfolioService->getMonthlyHistory($request->user(), $months);

        return response()->json(['history' => $history]);
    }

    /**
     * Categorías disponibles para el formulario
     */
    public function categories(): JsonResponse
    {
        return response()->json(['categories' => Transaction::availableCategories()]);
    }

    private function authorizeTransaction(Request $request, Transaction $transaction): void
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403, 'No tienes acceso a esta transacción.');
        }
    }
}
