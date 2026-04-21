<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\Goal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra datos de demo para el usuario principal (Alex)
     */
    public function run(): void
    {
        // Usuario administrador principal
        $alex = User::firstOrCreate(
            ['email' => 'alex@wealthos.cl'],
            [
                'name'     => 'Alex',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'currency' => 'CLP',
            ]
        );

        // ── Portafolio de demo ──────────────────────────────────────────────
        $assets = [
            ['type' => 'stock',  'ticker' => 'AAPL',  'name' => 'Apple Inc.',         'quantity' => 10,   'avg_buy_price' => 170.00, 'currency' => 'USD'],
            ['type' => 'stock',  'ticker' => 'MSFT',  'name' => 'Microsoft Corp.',     'quantity' => 5,    'avg_buy_price' => 380.00, 'currency' => 'USD'],
            ['type' => 'stock',  'ticker' => 'NVDA',  'name' => 'NVIDIA Corporation',  'quantity' => 3,    'avg_buy_price' => 450.00, 'currency' => 'USD'],
            ['type' => 'crypto', 'ticker' => 'BTC',   'name' => 'Bitcoin',             'quantity' => 0.05, 'avg_buy_price' => 45000.0,'currency' => 'USD'],
            ['type' => 'crypto', 'ticker' => 'ETH',   'name' => 'Ethereum',            'quantity' => 0.5,  'avg_buy_price' => 2500.0, 'currency' => 'USD'],
            ['type' => 'uf',     'ticker' => 'UF',    'name' => 'Unidad de Fomento',   'quantity' => 10,   'avg_buy_price' => 36500.0,'currency' => 'CLP'],
            ['type' => 'cash',   'ticker' => 'CLP',   'name' => 'Pesos Chilenos',      'quantity' => 2000000, 'avg_buy_price' => 1.0, 'currency' => 'CLP'],
        ];

        foreach ($assets as $assetData) {
            Asset::firstOrCreate(
                ['user_id' => $alex->id, 'ticker' => $assetData['ticker']],
                array_merge($assetData, ['user_id' => $alex->id])
            );
        }

        // ── Transacciones de los últimos 3 meses ───────────────────────────
        $this->seedTransactions($alex->id);

        // ── Metas financieras ──────────────────────────────────────────────
        $goals = [
            [
                'name'           => 'Fondo de Emergencia',
                'icon'           => '🛡️',
                'target_amount'  => 5000000,
                'current_amount' => 3200000,
                'currency'       => 'CLP',
                'target_date'    => Carbon::now()->addMonths(6)->format('Y-m-d'),
                'status'         => 'on_track',
            ],
            [
                'name'           => 'Viaje a Europa',
                'icon'           => '✈️',
                'target_amount'  => 3000000,
                'current_amount' => 800000,
                'currency'       => 'CLP',
                'target_date'    => Carbon::now()->addYear()->format('Y-m-d'),
                'status'         => 'on_track',
            ],
            [
                'name'           => 'MacBook Pro',
                'icon'           => '💻',
                'target_amount'  => 2500000,
                'current_amount' => 2500000,
                'currency'       => 'CLP',
                'target_date'    => Carbon::now()->addMonth()->format('Y-m-d'),
                'status'         => 'completed',
            ],
        ];

        foreach ($goals as $goalData) {
            Goal::firstOrCreate(
                ['user_id' => $alex->id, 'name' => $goalData['name']],
                array_merge($goalData, ['user_id' => $alex->id])
            );
        }

        $this->command->info('✅ Demo data seeded: alex@wealthos.cl / password');
    }

    private function seedTransactions(int $userId): void
    {
        $transactionTemplates = [
            // Ingresos
            ['type' => 'income', 'category' => 'sueldo',      'amount' => 2500000, 'description' => 'Sueldo mensual'],
            ['type' => 'income', 'category' => 'freelance',   'amount' => 350000,  'description' => 'Proyecto freelance web'],
            // Gastos fijos
            ['type' => 'expense','category' => 'vivienda',    'amount' => 550000,  'description' => 'Arriendo departamento'],
            ['type' => 'expense','category' => 'servicios_basicos', 'amount' => 85000,  'description' => 'Luz, agua, internet'],
            // Gastos variables
            ['type' => 'expense','category' => 'alimentacion', 'amount' => 320000,  'description' => 'Supermercado mes'],
            ['type' => 'expense','category' => 'transporte',  'amount' => 95000,   'description' => 'Uber + Bip'],
            ['type' => 'expense','category' => 'entretenimiento', 'amount' => 75000,'description' => 'Netflix + salidas'],
            ['type' => 'expense','category' => 'salud',       'amount' => 45000,   'description' => 'Consulta médica'],
        ];

        // Crear transacciones para los últimos 3 meses
        for ($m = 2; $m >= 0; $m--) {
            $date = Carbon::now()->subMonths($m)->startOfMonth();

            foreach ($transactionTemplates as $tpl) {
                // Variación aleatoria del ±10% en los montos
                $variation = 1 + (rand(-10, 10) / 100);
                $amount    = round($tpl['amount'] * $variation);

                Transaction::create([
                    'user_id'     => $userId,
                    'type'        => $tpl['type'],
                    'amount'      => $amount,
                    'currency'    => 'CLP',
                    'category'    => $tpl['category'],
                    'description' => $tpl['description'],
                    'date'        => $date->copy()->addDays(rand(0, 25))->format('Y-m-d'),
                ]);
            }
        }
    }
}
