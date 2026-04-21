<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdvisorSession;
use App\Services\GroqService;
use App\Services\PortfolioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvisorController extends Controller
{
    public function __construct(
        private GroqService      $groqService,
        private PortfolioService $portfolioService,
    ) {}

    /**
     * Listar sesiones de conversación del usuario
     */
    public function sessions(Request $request): JsonResponse
    {
        $sessions = $request->user()
            ->advisorSessions()
            ->orderByDesc('created_at')
            ->select(['id', 'title', 'created_at', 'updated_at'])
            ->limit(20)
            ->get();

        return response()->json(['sessions' => $sessions]);
    }

    /**
     * Obtener el historial completo de una sesión
     */
    public function getSession(Request $request, AdvisorSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        return response()->json(['session' => $session]);
    }

    /**
     * Crear una nueva sesión de conversación
     */
    public function createSession(Request $request): JsonResponse
    {
        $session = $request->user()->advisorSessions()->create([
            'messages' => [],
        ]);

        return response()->json(['session' => $session], 201);
    }

    /**
     * Enviar un mensaje al asesor IA y obtener respuesta.
     * Carga el contexto financiero completo del usuario automáticamente.
     */
    public function sendMessage(Request $request, AdvisorSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $validated = $request->validate([
            'message' => 'required|string|min:1|max:2000',
        ]);

        // Guardar mensaje del usuario
        $session->addMessage('user', $validated['message']);

        // Construir contexto financiero del usuario
        $financialContext = $this->portfolioService->getFinancialContextForAdvisor($request->user());
        $systemPrompt     = $this->groqService->buildFinancialContext($financialContext);

        // Obtener historial para mantener contexto de la conversación
        $history = $session->getGroqMessages();

        // Llamar a la API de Groq
        try {
            $response = $this->groqService->chat($history, $systemPrompt);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'El asesor no está disponible en este momento. Intenta nuevamente.',
            ], 503);
        }

        // Guardar respuesta del asistente
        $session->addMessage('assistant', $response);

        return response()->json([
            'response' => $response,
            'session'  => [
                'id'       => $session->id,
                'title'    => $session->title,
                'messages' => $session->messages,
            ],
        ]);
    }

    /**
     * Iniciar una nueva sesión con un mensaje de bienvenida contextualizado
     */
    public function quickStart(Request $request): JsonResponse
    {
        $user    = $request->user();
        $session = $user->advisorSessions()->create(['messages' => []]);

        // Contexto financiero para el mensaje de bienvenida
        $context   = $this->portfolioService->getFinancialContextForAdvisor($user);
        $portfolio = $context['portfolio'];
        $monthly   = $context['monthly'];

        $totalCLP = number_format($portfolio['total_clp'] ?? 0, 0, ',', '.');
        $savings  = number_format($monthly['savings'] ?? 0, 0, ',', '.');

        $welcome = "¡Hola {$user->name}! 👋 Soy tu asesor financiero WealthOS.\n\n"
            . "Veo que tu portafolio actual es de **\${$totalCLP} CLP** y este mes tienes un ahorro de **\${$savings} CLP**.\n\n"
            . "¿En qué puedo ayudarte hoy? Por ejemplo puedes preguntarme:\n"
            . "- *¿Estoy bien diversificado?*\n"
            . "- *¿Cómo va mi ahorro este mes?*\n"
            . "- *¿Qué hacer con $500.000?*";

        $session->addMessage('assistant', $welcome);

        return response()->json([
            'session'  => $session,
            'response' => $welcome,
        ], 201);
    }

    /**
     * Eliminar una sesión de conversación
     */
    public function deleteSession(Request $request, AdvisorSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $session->delete();

        return response()->json(['message' => 'Sesión eliminada.']);
    }

    private function authorizeSession(Request $request, AdvisorSession $session): void
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'No tienes acceso a esta sesión.');
        }
    }
}
