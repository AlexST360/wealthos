<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PriceService
{
    // TTL del caché Redis: 15 minutos
    private const CACHE_TTL = 900;

    // Tickers de cripto reconocidos por CoinGecko
    private const CRYPTO_IDS = [
        'BTC'  => 'bitcoin',
        'ETH'  => 'ethereum',
        'SOL'  => 'solana',
        'ADA'  => 'cardano',
        'DOT'  => 'polkadot',
        'LINK' => 'chainlink',
        'AVAX' => 'avalanche-2',
        'MATIC'=> 'matic-network',
        'UNI'  => 'uniswap',
        'ATOM' => 'cosmos',
    ];

    /**
     * Obtiene el precio de un ticker. Detecta el tipo y consulta la fuente correcta.
     */
    public function getPrice(string $ticker, string $type): array
    {
        $cacheKey = "price:{$ticker}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($ticker, $type) {
            return match ($type) {
                'crypto' => $this->fetchCryptoPrice($ticker),
                'stock', 'fund' => $this->fetchStockPrice($ticker),
                'uf'  => $this->fetchUFPrice(),
                'cash'=> ['price' => 1, 'currency' => 'CLP', 'change_24h' => 0, 'source' => 'internal'],
                default => throw new \InvalidArgumentException("Tipo de activo desconocido: {$type}"),
            };
        });
    }

    /**
     * Obtiene precios para múltiples activos en batch.
     * Retorna array indexado por ticker.
     */
    public function getPricesBatch(array $assets): array
    {
        $prices = [];

        foreach ($assets as $asset) {
            // Soportar tanto objetos Eloquent como arrays
            $ticker   = is_array($asset) ? $asset['ticker']        : $asset->ticker;
            $type     = is_array($asset) ? $asset['type']          : $asset->type;
            $buyPrice = is_array($asset) ? $asset['avg_buy_price']  : $asset->avg_buy_price;
            $currency = is_array($asset) ? $asset['currency']       : $asset->currency;

            try {
                $prices[$ticker] = $this->getPrice($ticker, $type);
            } catch (\Exception $e) {
                Log::warning("No se pudo obtener precio para {$ticker}: " . $e->getMessage());
                $prices[$ticker] = [
                    'price'     => $buyPrice,
                    'currency'  => $currency,
                    'change_24h'=> 0,
                    'source'    => 'fallback',
                    'error'     => $e->getMessage(),
                ];
            }
        }

        return $prices;
    }

    /**
     * Precio de cripto desde CoinGecko API (gratuita, sin key)
     */
    private function fetchCryptoPrice(string $ticker): array
    {
        $coinId = self::CRYPTO_IDS[strtoupper($ticker)]
            ?? strtolower($ticker);

        $response = Http::timeout(10)
            ->get("https://api.coingecko.com/api/v3/simple/price", [
                'ids'                   => $coinId,
                'vs_currencies'         => 'usd',
                'include_24hr_change'   => 'true',
                'include_market_cap'    => 'true',
            ]);

        if (!$response->successful() || !isset($response[$coinId])) {
            throw new \RuntimeException("CoinGecko no retornó precio para {$ticker}");
        }

        $data = $response[$coinId];

        return [
            'price'      => (float) $data['usd'],
            'currency'   => 'USD',
            'change_24h' => (float) ($data['usd_24h_change'] ?? 0),
            'market_cap' => (float) ($data['usd_market_cap'] ?? 0),
            'source'     => 'coingecko',
        ];
    }

    /**
     * Precio de acciones/fondos desde Yahoo Finance (via microservicio Python
     * o directamente con la URL no-oficial de Yahoo).
     */
    private function fetchStockPrice(string $ticker): array
    {
        // Si hay un microservicio Python configurado, usarlo primero
        $pythonServiceUrl = config('services.yahoo_finance.url');

        if ($pythonServiceUrl) {
            try {
                return $this->fetchFromPythonService($ticker, $pythonServiceUrl);
            } catch (\Exception $e) {
                Log::warning("Microservicio Python falló para {$ticker}, usando Yahoo directo");
            }
        }

        // Fallback: Yahoo Finance v8 (no oficial pero funcional)
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get("https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}", [
                'interval' => '1d',
                'range'    => '1d',
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("Yahoo Finance no respondió para {$ticker}");
        }

        $data = $response->json();
        $meta = $data['chart']['result'][0]['meta'] ?? null;

        if (!$meta) {
            throw new \RuntimeException("Yahoo Finance no tiene datos para {$ticker}");
        }

        $price = $meta['regularMarketPrice'] ?? $meta['previousClose'];
        $prevClose = $meta['chartPreviousClose'] ?? $meta['previousClose'];
        $change24h = $prevClose > 0 ? (($price - $prevClose) / $prevClose) * 100 : 0;

        return [
            'price'      => (float) $price,
            'currency'   => $meta['currency'] ?? 'USD',
            'change_24h' => round($change24h, 2),
            'market_cap' => null,
            'source'     => 'yahoo',
        ];
    }

    /**
     * Precio de la UF desde CMF Chile
     */
    private function fetchUFPrice(): array
    {
        $apiKey = config('services.cmf.key');
        $fecha  = Carbon::now()->format('Y/m/d');

        $response = Http::timeout(10)
            ->get("https://api.cmfchile.cl/api-sbifv3/recursos_api/uf/{$fecha}/dias", [
                'apikey' => $apiKey,
                'formato'=> 'json',
            ]);

        if ($response->successful()) {
            $uf = $response->json()['UFs'][0]['Valor'] ?? null;
            if ($uf) {
                $price = (float) str_replace(['.', ','], ['', '.'], $uf);
                return ['price' => $price, 'currency' => 'CLP', 'change_24h' => 0, 'source' => 'cmf'];
            }
        }

        // Fallback: valor aproximado de la UF si falla la API
        Log::warning('CMF API falló, usando valor UF aproximado');
        return ['price' => 38000.0, 'currency' => 'CLP', 'change_24h' => 0, 'source' => 'fallback'];
    }

    private function fetchFromPythonService(string $ticker, string $baseUrl): array
    {
        $response = Http::timeout(8)->get("{$baseUrl}/price/{$ticker}");

        if (!$response->successful()) {
            throw new \RuntimeException("Python service error");
        }

        return $response->json();
    }

    /**
     * Fuerza la actualización del caché para un ticker específico
     */
    public function refreshPrice(string $ticker, string $type): array
    {
        Cache::forget("price:{$ticker}");
        return $this->getPrice($ticker, $type);
    }
}
