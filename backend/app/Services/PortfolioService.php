<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PortfolioService
{
    public function __construct(private PriceService $priceService) {}

    /**
     * Calcula el portafolio completo del usuario con precios actuales.
     * Retorna todos los datos necesarios para el Dashboard y el Asesor IA.
     */
    public function getPortfolioSummary(User $user): array
    {
        $assets = $user->assets()->get();

        if ($assets->isEmpty()) {
            return [
                'total_clp'  => 0,
                'total_usd'  => 0,
                'assets'     => [],
                'by_type'    => [],
                'usd_to_clp' => $this->getUSDtoCLP(),
            ];
        }

        $usdToClp = $this->getUSDtoCLP();
        $prices   = $this->priceService->getPricesBatch($assets->toArray());

        $enrichedAssets = [];
        $totalCLP       = 0;
        $byType         = [];

        foreach ($assets as $asset) {
            $priceData    = $prices[$asset->ticker] ?? ['price' => $asset->avg_buy_price, 'currency' => $asset->currency, 'change_24h' => 0];
            $currentPrice = $priceData['price'];
            $currency     = $priceData['currency'] ?? $asset->currency;

            // Valor en la moneda original
            $valueNative = $currentPrice * $asset->quantity;

            // Convertir a CLP
            $valueCLP = $currency === 'CLP'
                ? $valueNative
                : $valueNative * $usdToClp;

            // Costo base en CLP
            $costCLP = $asset->currency === 'CLP'
                ? $asset->cost_basis
                : $asset->cost_basis * $usdToClp;

            $profitLoss    = $valueCLP - $costCLP;
            $profitLossPct = $costCLP > 0 ? ($profitLoss / $costCLP) * 100 : 0;

            $enrichedAsset = [
                'id'             => $asset->id,
                'type'           => $asset->type,
                'ticker'         => $asset->ticker,
                'name'           => $asset->name,
                'quantity'       => $asset->quantity,
                'avg_buy_price'  => $asset->avg_buy_price,
                'current_price'  => $currentPrice,
                'currency'       => $currency,
                'value_native'   => $valueNative,
                'value_clp'      => $valueCLP,
                'cost_clp'       => $costCLP,
                'profit_loss'    => $profitLoss,
                'profit_loss_pct'=> round($profitLossPct, 2),
                'change_24h'     => $priceData['change_24h'] ?? 0,
                'source'         => $priceData['source'] ?? 'unknown',
            ];

            $enrichedAssets[] = $enrichedAsset;
            $totalCLP += $valueCLP;

            // Agrupar por tipo
            $type = $asset->type;
            $byType[$type] = ($byType[$type] ?? 0) + $valueCLP;
        }

        // Calcular porcentajes por tipo y por activo
        foreach ($enrichedAssets as &$a) {
            $a['pct'] = $totalCLP > 0 ? ($a['value_clp'] / $totalCLP) * 100 : 0;
        }

        $byTypeFormatted = [];
        foreach ($byType as $type => $value) {
            $byTypeFormatted[] = [
                'type'     => $type,
                'value_clp'=> $value,
                'pct'      => $totalCLP > 0 ? ($value / $totalCLP) * 100 : 0,
            ];
        }

        return [
            'total_clp'  => $totalCLP,
            'total_usd'  => $totalCLP / $usdToClp,
            'assets'     => $enrichedAssets,
            'by_type'    => $byTypeFormatted,
            'usd_to_clp' => $usdToClp,
        ];
    }

    /**
     * Resumen mensual de ingresos, gastos y ahorro
     */
    public function getMonthlySummary(User $user, int $year, int $month): array
    {
        $transactions = $user->transactions()
            ->ofMonth($year, $month)
            ->get();

        $income   = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');
        $savings  = $income - $expenses;
        $savingsRate = $income > 0 ? ($savings / $income) * 100 : 0;

        // Gastos por categoría (top 5)
        $topCategories = $transactions
            ->where('type', 'expense')
            ->groupBy('category')
            ->map(fn($group) => [
                'category' => $group->first()->category,
                'amount'   => $group->sum('amount'),
                'count'    => $group->count(),
            ])
            ->sortByDesc('amount')
            ->values()
            ->take(5)
            ->toArray();

        return [
            'year'           => $year,
            'month'          => $month,
            'income'         => $income,
            'expenses'       => $expenses,
            'savings'        => $savings,
            'savings_rate'   => round($savingsRate, 2),
            'top_categories' => $topCategories,
            'transactions'   => $transactions->count(),
        ];
    }

    /**
     * Historial de los últimos N meses para el gráfico de evolución
     */
    public function getMonthlyHistory(User $user, int $months = 6): array
    {
        $history = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $history[] = $this->getMonthlySummary($user, $date->year, $date->month);
        }

        return $history;
    }

    /**
     * Contexto financiero completo para el Asesor IA
     */
    public function getFinancialContextForAdvisor(User $user): array
    {
        $portfolio = $this->getPortfolioSummary($user);
        $monthly   = $this->getMonthlySummary($user, now()->year, now()->month);
        $goals     = $user->goals()->where('status', '!=', 'completed')->get()->map(fn($g) => [
            'icon'           => $g->icon,
            'name'           => $g->name,
            'target_amount'  => $g->target_amount,
            'current_amount' => $g->current_amount,
            'progress_pct'   => $g->progress_pct,
            'status'         => $g->status,
        ])->toArray();

        return [
            'user_name' => $user->name,
            'portfolio' => $portfolio,
            'monthly'   => $monthly,
            'goals'     => $goals,
        ];
    }

    /**
     * Obtiene el tipo de cambio USD → CLP.
     * Usa la API de Yahoo Finance para USDCLP=X con caché de 1 hora.
     */
    private function getUSDtoCLP(): float
    {
        return Cache::remember('usd_to_clp', 3600, function () {
            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get('https://query1.finance.yahoo.com/v8/finance/chart/USDCLP=X', [
                        'interval' => '1d',
                        'range'    => '1d',
                    ]);

                if ($response->successful()) {
                    $price = $response->json()['chart']['result'][0]['meta']['regularMarketPrice'] ?? null;
                    if ($price) return (float) $price;
                }
            } catch (\Exception $e) {
                // Silencioso: usar fallback
            }

            return 900.0; // Fallback aproximado
        });
    }

    /**
     * Simula el crecimiento de una inversión a lo largo del tiempo.
     * Retorna puntos mensuales para graficar.
     */
    public function simulateGrowth(
        float $initialAmount,
        float $monthlyContribution,
        int   $years,
        string $instrument
    ): array {
        $annualRates = [
            'sp500'         => 0.07,
            'bitcoin'       => 0.50,  // Alta volatilidad, promedio histórico aproximado
            'fondo_mutuo'   => 0.04,
            'uf'            => 0.05,  // Inflación + 2% aprox
            'deposito_plazo'=> 0.045,
        ];

        $rate = $annualRates[$instrument] ?? 0.05;
        $monthlyRate = $rate / 12;
        $totalMonths = $years * 12;

        $points     = [];
        $balance    = $initialAmount;
        $totalInput = $initialAmount;

        for ($month = 0; $month <= $totalMonths; $month++) {
            $points[] = [
                'month'       => $month,
                'year'        => round($month / 12, 1),
                'balance'     => round($balance, 2),
                'total_input' => round($totalInput, 2),
                'gain'        => round($balance - $totalInput, 2),
            ];

            if ($month < $totalMonths) {
                $balance += $balance * $monthlyRate + $monthlyContribution;
                $totalInput += $monthlyContribution;
            }
        }

        return [
            'instrument'    => $instrument,
            'annual_rate'   => $rate * 100,
            'final_balance' => $balance,
            'total_input'   => $totalInput,
            'total_gain'    => $balance - $totalInput,
            'points'        => $points,
        ];
    }
}
