<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\PortfolioService;
use App\Services\PriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(
        private PortfolioService $portfolioService,
        private PriceService     $priceService,
    ) {}

    /**
     * Resumen completo del portafolio con precios actuales
     */
    public function summary(Request $request): JsonResponse
    {
        $summary = $this->portfolioService->getPortfolioSummary($request->user());

        return response()->json($summary);
    }

    /**
     * Listar activos del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $assets = $request->user()->assets()->orderBy('type')->orderBy('name')->get();

        return response()->json(['assets' => $assets]);
    }

    /**
     * Agregar un nuevo activo al portafolio
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'          => 'required|in:stock,crypto,uf,fund,cash',
            'ticker'        => 'nullable|string|max:20|uppercase',
            'name'          => 'required|string|max:100',
            'quantity'      => 'required|numeric|min:0.00000001',
            'avg_buy_price' => 'required|numeric|min:0',
            'currency'      => 'sometimes|string|size:3',
            'notes'         => 'nullable|string|max:500',
        ]);

        // Verificar si ya existe el mismo ticker para este usuario
        $existing = $request->user()->assets()
            ->where('ticker', $validated['ticker'])
            ->first();

        if ($existing) {
            // Calcular nuevo precio promedio ponderado
            $totalQuantity = $existing->quantity + $validated['quantity'];
            $newAvgPrice   = (
                ($existing->quantity * $existing->avg_buy_price) +
                ($validated['quantity'] * $validated['avg_buy_price'])
            ) / $totalQuantity;

            $existing->update([
                'quantity'      => $totalQuantity,
                'avg_buy_price' => $newAvgPrice,
            ]);

            return response()->json([
                'message' => 'Posición actualizada con precio promedio ponderado.',
                'asset'   => $existing->fresh(),
            ]);
        }

        $asset = $request->user()->assets()->create($validated);

        return response()->json(['message' => 'Activo agregado.', 'asset' => $asset], 201);
    }

    /**
     * Mostrar un activo con precio actual
     */
    public function show(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        $priceData = $this->priceService->getPrice($asset->ticker, $asset->type);

        return response()->json([
            'asset'      => $asset,
            'price_data' => $priceData,
        ]);
    }

    /**
     * Actualizar cantidad o precio de compra de un activo
     */
    public function update(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        $validated = $request->validate([
            'quantity'      => 'sometimes|numeric|min:0',
            'avg_buy_price' => 'sometimes|numeric|min:0',
            'name'          => 'sometimes|string|max:100',
            'notes'         => 'nullable|string|max:500',
        ]);

        $asset->update($validated);

        return response()->json(['message' => 'Activo actualizado.', 'asset' => $asset->fresh()]);
    }

    /**
     * Eliminar un activo del portafolio
     */
    public function destroy(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        $asset->delete();

        return response()->json(['message' => 'Activo eliminado del portafolio.']);
    }

    /**
     * Forzar actualización de precio de un activo
     */
    public function refreshPrice(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeAsset($request, $asset);

        $priceData = $this->priceService->refreshPrice($asset->ticker, $asset->type);

        return response()->json(['price_data' => $priceData]);
    }

    /**
     * Historial mensual del portafolio
     */
    public function history(Request $request): JsonResponse
    {
        $months  = $request->integer('months', 6);
        $history = $this->portfolioService->getMonthlyHistory($request->user(), $months);

        return response()->json(['history' => $history]);
    }

    private function authorizeAsset(Request $request, Asset $asset): void
    {
        if ($asset->user_id !== $request->user()->id) {
            abort(403, 'No tienes acceso a este activo.');
        }
    }
}
