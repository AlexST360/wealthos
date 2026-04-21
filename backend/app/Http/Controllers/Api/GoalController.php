<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    /**
     * Listar todas las metas del usuario
     */
    public function index(Request $request): JsonResponse
    {
        $goals = $request->user()->goals()
            ->orderByRaw("FIELD(status, 'on_track', 'behind', 'completed')")
            ->orderBy('target_date')
            ->get()
            ->map(fn($goal) => $this->enrichGoal($goal));

        return response()->json(['goals' => $goals]);
    }

    /**
     * Crear una nueva meta financiera
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'icon'           => 'sometimes|string|max:10',
            'target_amount'  => 'required|numeric|min:1',
            'current_amount' => 'sometimes|numeric|min:0',
            'currency'       => 'sometimes|string|size:3',
            'target_date'    => 'required|date|after:today',
            'notes'          => 'nullable|string|max:500',
        ]);

        $goal = $request->user()->goals()->create($validated);
        $goal->updateStatus();

        return response()->json([
            'message' => 'Meta creada.',
            'goal'    => $this->enrichGoal($goal),
        ], 201);
    }

    /**
     * Mostrar una meta específica
     */
    public function show(Request $request, Goal $goal): JsonResponse
    {
        $this->authorizeGoal($request, $goal);

        return response()->json(['goal' => $this->enrichGoal($goal)]);
    }

    /**
     * Actualizar una meta
     */
    public function update(Request $request, Goal $goal): JsonResponse
    {
        $this->authorizeGoal($request, $goal);

        $validated = $request->validate([
            'name'           => 'sometimes|string|max:100',
            'icon'           => 'sometimes|string|max:10',
            'target_amount'  => 'sometimes|numeric|min:1',
            'current_amount' => 'sometimes|numeric|min:0',
            'currency'       => 'sometimes|string|size:3',
            'target_date'    => 'sometimes|date',
            'notes'          => 'nullable|string|max:500',
        ]);

        $goal->update($validated);
        $goal->updateStatus();

        return response()->json(['message' => 'Meta actualizada.', 'goal' => $this->enrichGoal($goal->fresh())]);
    }

    /**
     * Registrar un aporte a una meta (suma al monto actual)
     */
    public function addContribution(Request $request, Goal $goal): JsonResponse
    {
        $this->authorizeGoal($request, $goal);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $goal->current_amount = min(
            $goal->target_amount,
            $goal->current_amount + $validated['amount']
        );
        $goal->save();
        $goal->updateStatus();

        return response()->json([
            'message' => 'Aporte registrado.',
            'goal'    => $this->enrichGoal($goal->fresh()),
        ]);
    }

    /**
     * Eliminar una meta
     */
    public function destroy(Request $request, Goal $goal): JsonResponse
    {
        $this->authorizeGoal($request, $goal);

        $goal->delete();

        return response()->json(['message' => 'Meta eliminada.']);
    }

    /**
     * Enriquece el modelo Goal con atributos calculados
     */
    private function enrichGoal(Goal $goal): array
    {
        return [
            'id'                    => $goal->id,
            'name'                  => $goal->name,
            'icon'                  => $goal->icon,
            'target_amount'         => $goal->target_amount,
            'current_amount'        => $goal->current_amount,
            'currency'              => $goal->currency,
            'target_date'           => $goal->target_date?->format('Y-m-d'),
            'status'                => $goal->status,
            'notes'                 => $goal->notes,
            'progress_pct'          => round($goal->progress_pct, 2),
            'remaining_amount'      => $goal->remaining_amount,
            'months_remaining'      => $goal->months_remaining,
            'monthly_savings_needed'=> round($goal->monthly_savings_needed, 2),
            'created_at'            => $goal->created_at,
        ];
    }

    private function authorizeGoal(Request $request, Goal $goal): void
    {
        if ($goal->user_id !== $request->user()->id) {
            abort(403, 'No tienes acceso a esta meta.');
        }
    }
}
