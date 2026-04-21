<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SimulatorController extends Controller
{
    public function __construct(private PortfolioService $portfolioService) {}

    /**
     * Simular el crecimiento de una inversión
     */
    public function simulate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'initial_amount'      => 'required|numeric|min:0',
            'monthly_contribution'=> 'required|numeric|min:0',
            'years'               => 'required|integer|min:1|max:50',
            'instrument'          => 'required|in:sp500,bitcoin,fondo_mutuo,uf,deposito_plazo',
        ]);

        $result = $this->portfolioService->simulateGrowth(
            initialAmount:       $validated['initial_amount'],
            monthlyContribution: $validated['monthly_contribution'],
            years:               $validated['years'],
            instrument:          $validated['instrument'],
        );

        return response()->json($result);
    }

    /**
     * Comparar múltiples instrumentos simultáneamente (hasta 3)
     */
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'initial_amount'      => 'required|numeric|min:0',
            'monthly_contribution'=> 'required|numeric|min:0',
            'years'               => 'required|integer|min:1|max:50',
            'instruments'         => 'required|array|min:1|max:3',
            'instruments.*'       => 'required|in:sp500,bitcoin,fondo_mutuo,uf,deposito_plazo',
        ]);

        $comparisons = [];
        foreach ($validated['instruments'] as $instrument) {
            $comparisons[$instrument] = $this->portfolioService->simulateGrowth(
                initialAmount:       $validated['initial_amount'],
                monthlyContribution: $validated['monthly_contribution'],
                years:               $validated['years'],
                instrument:          $instrument,
            );
        }

        // Calcular hitos para la tabla resumen
        $hitos = $this->buildMilestones($comparisons, $validated['years']);

        return response()->json([
            'comparisons' => $comparisons,
            'milestones'  => $hitos,
            'params'      => [
                'initial_amount'       => $validated['initial_amount'],
                'monthly_contribution' => $validated['monthly_contribution'],
                'years'                => $validated['years'],
            ],
        ]);
    }

    /**
     * Instrumentos disponibles con metadata
     */
    public function instruments(): JsonResponse
    {
        return response()->json([
            'instruments' => [
                [
                    'id'          => 'sp500',
                    'name'        => 'S&P 500',
                    'description' => 'Índice bursátil de las 500 mayores empresas de EEUU',
                    'annual_rate' => 7.0,
                    'currency'    => 'USD',
                    'risk'        => 'medio',
                    'color'       => '#3B82F6',
                ],
                [
                    'id'          => 'bitcoin',
                    'name'        => 'Bitcoin',
                    'description' => 'Criptomoneda descentralizada, alta volatilidad',
                    'annual_rate' => 50.0,
                    'currency'    => 'USD',
                    'risk'        => 'muy_alto',
                    'color'       => '#F59E0B',
                ],
                [
                    'id'          => 'fondo_mutuo',
                    'name'        => 'Fondo Mutuo CLP',
                    'description' => 'Fondo de renta fija en pesos chilenos',
                    'annual_rate' => 4.0,
                    'currency'    => 'CLP',
                    'risk'        => 'bajo',
                    'color'       => '#10B981',
                ],
                [
                    'id'          => 'uf',
                    'name'        => 'Inversión en UF',
                    'description' => 'Unidad de Fomento + retorno real estimado del 2%',
                    'annual_rate' => 5.0,
                    'currency'    => 'CLP',
                    'risk'        => 'bajo',
                    'color'       => '#8B5CF6',
                ],
                [
                    'id'          => 'deposito_plazo',
                    'name'        => 'Depósito a Plazo',
                    'description' => 'Depósito bancario a plazo fijo en Chile',
                    'annual_rate' => 4.5,
                    'currency'    => 'CLP',
                    'risk'        => 'muy_bajo',
                    'color'       => '#6B7280',
                ],
            ],
        ]);
    }

    /**
     * Construye tabla de hitos (año 1, 5, 10, etc.) para comparación
     */
    private function buildMilestones(array $comparisons, int $totalYears): array
    {
        $milestoneYears = array_filter([1, 2, 5, 10, 15, 20, 25, 30], fn($y) => $y <= $totalYears);
        if (!in_array($totalYears, $milestoneYears)) {
            $milestoneYears[] = $totalYears;
        }
        sort($milestoneYears);

        $milestones = [];
        foreach ($milestoneYears as $year) {
            $row = ['year' => $year];
            foreach ($comparisons as $instrument => $data) {
                $monthIndex = $year * 12;
                $point = collect($data['points'])->firstWhere('month', $monthIndex);
                $row[$instrument] = $point ? $point['balance'] : null;
            }
            $milestones[] = $row;
        }

        return $milestones;
    }
}
