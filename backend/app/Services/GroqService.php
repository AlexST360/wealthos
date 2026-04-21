<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.groq.com/openai/v1';

    public function __construct()
    {
        $this->apiKey = config('services.groq.key');
        $this->model  = config('services.groq.model', 'llama-3.3-70b-versatile');
    }

    /**
     * Envía una conversación al modelo y retorna la respuesta del asistente.
     *
     * @param  array  $messages  Array de {role: string, content: string}
     * @param  string $systemPrompt  Contexto financiero del usuario
     * @return string  Texto de respuesta del modelo
     */
    public function chat(array $messages, string $systemPrompt = ''): string
    {
        $allMessages = [];

        // Siempre incluir el system prompt con el contexto financiero
        if ($systemPrompt) {
            $allMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $allMessages = array_merge($allMessages, $messages);

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'       => $this->model,
                'messages'    => $allMessages,
                'max_tokens'  => 1024,
                'temperature' => 0.7,
                'stream'      => false,
            ]);

        if (!$response->successful()) {
            Log::error('Groq API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Error al comunicarse con el asesor IA: ' . $response->body());
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? 'No pude generar una respuesta.';
    }

    /**
     * Construye el system prompt con el contexto financiero completo del usuario.
     * Este contexto se inyecta automáticamente en cada conversación.
     */
    public function buildFinancialContext(array $context): string
    {
        $portfolioSummary = $this->formatPortfolioSummary($context['portfolio'] ?? []);
        $monthSummary     = $this->formatMonthlySummary($context['monthly'] ?? []);
        $goalsSummary     = $this->formatGoalsSummary($context['goals'] ?? []);

        return <<<PROMPT
Eres WealthOS Advisor, un asesor financiero personal inteligente y empático.
Tienes acceso al perfil financiero completo de {$context['user_name']}.
Responde SIEMPRE en español de Chile. Usa términos financieros claros pero accesibles.
Sé directo, útil y basa tus respuestas en los datos reales del usuario.

## PORTAFOLIO ACTUAL
{$portfolioSummary}

## BALANCE DEL MES ACTUAL
{$monthSummary}

## METAS FINANCIERAS
{$goalsSummary}

## INSTRUCCIONES
- Si el usuario pregunta algo fuera del contexto financiero, redirige la conversación.
- Usa los datos reales para dar consejos específicos y personalizados.
- Cuando menciones cifras en CLP, formatea con puntos de miles (ej: $1.250.000).
- Cuando menciones cifras en USD, usa el símbolo $.
- Si detectas riesgos o oportunidades importantes, menciónalolos proactivamente.
- Mantén un tono profesional pero cercano, como un asesor de confianza.
PROMPT;
    }

    private function formatPortfolioSummary(array $portfolio): string
    {
        if (empty($portfolio)) return 'Sin activos registrados.';

        $total = number_format($portfolio['total_clp'] ?? 0, 0, ',', '.');
        $lines = ["Patrimonio total: $\${$total} CLP"];

        foreach ($portfolio['assets'] ?? [] as $asset) {
            $value = number_format($asset['value_clp'] ?? 0, 0, ',', '.');
            $pct   = number_format($asset['pct'] ?? 0, 1);
            $lines[] = "- {$asset['name']} ({$asset['ticker']}): $\${$value} CLP ({$pct}% del portafolio)";
        }

        return implode("\n", $lines);
    }

    private function formatMonthlySummary(array $monthly): string
    {
        if (empty($monthly)) return 'Sin transacciones este mes.';

        $income   = number_format($monthly['income'] ?? 0, 0, ',', '.');
        $expenses = number_format($monthly['expenses'] ?? 0, 0, ',', '.');
        $savings  = number_format($monthly['savings'] ?? 0, 0, ',', '.');
        $rate     = number_format($monthly['savings_rate'] ?? 0, 1);

        $lines = [
            "Ingresos: $\${$income} CLP",
            "Gastos:   $\${$expenses} CLP",
            "Ahorro:   $\${$savings} CLP (tasa: {$rate}%)",
            "Categorías principales de gasto:",
        ];

        foreach ($monthly['top_categories'] ?? [] as $cat) {
            $amt = number_format($cat['amount'], 0, ',', '.');
            $lines[] = "  - {$cat['category']}: $\${$amt} CLP";
        }

        return implode("\n", $lines);
    }

    private function formatGoalsSummary(array $goals): string
    {
        if (empty($goals)) return 'Sin metas financieras registradas.';

        $lines = [];
        foreach ($goals as $goal) {
            $target   = number_format($goal['target_amount'], 0, ',', '.');
            $current  = number_format($goal['current_amount'], 0, ',', '.');
            $pct      = number_format($goal['progress_pct'], 1);
            $status   = match ($goal['status']) {
                'on_track'  => 'En camino',
                'behind'    => 'Atrasada',
                'completed' => 'Completada',
                default     => $goal['status'],
            };
            $lines[] = "- {$goal['icon']} {$goal['name']}: $\${$current}/$\${$target} CLP ({$pct}%) — {$status}";
        }

        return implode("\n", $lines);
    }
}
