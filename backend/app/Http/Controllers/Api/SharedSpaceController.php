<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SharedSpace;
use App\Models\SharedSpaceInvitation;
use App\Models\SharedTransaction;
use App\Models\SharedGoal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SharedSpaceController extends Controller
{
    // ── Espacios ──────────────────────────────────────────────────────────

    /** Lista los espacios compartidos del usuario autenticado */
    public function index(Request $request): JsonResponse
    {
        $spaces = $request->user()
            ->belongsToMany(SharedSpace::class, 'shared_space_members', 'user_id', 'shared_space_id')
            ->withPivot('role', 'joined_at')
            ->with(['members:id,name,email', 'creator:id,name'])
            ->get()
            ->map(fn($s) => $this->formatSpace($s, $request->user()->id));

        return response()->json(['spaces' => $spaces]);
    }

    /** Crea un nuevo espacio compartido (el creador queda como admin) */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'icon'     => 'sometimes|string|max:10',
            'currency' => 'sometimes|string|size:3',
        ]);

        $space = SharedSpace::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        // El creador es miembro admin
        $space->members()->attach($request->user()->id, ['role' => 'admin', 'joined_at' => now()]);

        return response()->json([
            'message' => 'Espacio compartido creado.',
            'space'   => $this->formatSpace($space->load('members:id,name,email'), $request->user()->id),
        ], 201);
    }

    /** Detalle de un espacio */
    public function show(Request $request, SharedSpace $sharedSpace): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        $sharedSpace->load(['members:id,name,email', 'creator:id,name']);

        return response()->json(['space' => $this->formatSpace($sharedSpace, $request->user()->id)]);
    }

    /** Eliminar espacio (solo admin) */
    public function destroy(Request $request, SharedSpace $sharedSpace): JsonResponse
    {
        $this->authorizeAdmin($request, $sharedSpace);
        $sharedSpace->delete();
        return response()->json(['message' => 'Espacio eliminado.']);
    }

    // ── Invitaciones ──────────────────────────────────────────────────────

    /** Invitar a un usuario por email */
    public function invite(Request $request, SharedSpace $sharedSpace): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        // Verificar si ya es miembro
        $user = User::where('email', $validated['email'])->first();
        if ($user && $sharedSpace->isMember($user->id)) {
            return response()->json(['message' => 'Este usuario ya es miembro del espacio.'], 422);
        }

        // Cancelar invitación pendiente previa
        $sharedSpace->invitations()
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->delete();

        $invitation = SharedSpaceInvitation::create([
            'shared_space_id' => $sharedSpace->id,
            'invited_by'      => $request->user()->id,
            'email'           => $validated['email'],
            'token'           => Str::random(40),
            'status'          => 'pending',
        ]);

        // Si el usuario ya existe en el sistema, aceptar automáticamente
        if ($user) {
            $sharedSpace->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
            $invitation->update(['status' => 'accepted']);

            return response()->json([
                'message' => "¡{$user->name} fue agregado al espacio como miembro!",
                'auto_accepted' => true,
            ]);
        }

        return response()->json([
            'message'    => "Invitación enviada a {$validated['email']}. Cuando se registre, podrá unirse.",
            'token'      => $invitation->token,
            'auto_accepted' => false,
        ]);
    }

    /** Aceptar invitación via token (al registrarse) */
    public function acceptInvitation(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        $invitation = SharedSpaceInvitation::where('token', $request->token)
            ->where('status', 'pending')
            ->firstOrFail();

        $user = $request->user();

        // Verificar que el email coincide
        if ($user->email !== $invitation->email) {
            return response()->json(['message' => 'Esta invitación no corresponde a tu email.'], 403);
        }

        $invitation->space->members()->attach($user->id, ['role' => 'member', 'joined_at' => now()]);
        $invitation->update(['status' => 'accepted']);

        return response()->json(['message' => '¡Te has unido al espacio compartido!', 'space_id' => $invitation->shared_space_id]);
    }

    /** Salir de un espacio */
    public function leave(Request $request, SharedSpace $sharedSpace): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        // El creador no puede salir sin transferir admin
        if ($sharedSpace->created_by === $request->user()->id && $sharedSpace->members()->count() > 1) {
            return response()->json(['message' => 'Transfiere el rol de admin antes de salir.'], 422);
        }

        $sharedSpace->members()->detach($request->user()->id);

        return response()->json(['message' => 'Saliste del espacio compartido.']);
    }

    // ── Transacciones compartidas ─────────────────────────────────────────

    public function getTransactions(Request $request, SharedSpace $sharedSpace): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);
        $type  = $request->input('type');

        $query = $sharedSpace->transactions()
            ->with('author:id,name')
            ->ofMonth($year, $month)
            ->orderByDesc('date');

        if ($type) $query->where('type', $type);

        $transactions = $query->get();
        $income   = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');

        return response()->json([
            'transactions' => $transactions,
            'summary'      => [
                'income'       => $income,
                'expenses'     => $expenses,
                'savings'      => $income - $expenses,
                'savings_rate' => $income > 0 ? round((($income - $expenses) / $income) * 100, 2) : 0,
            ],
        ]);
    }

    public function storeTransaction(Request $request, SharedSpace $sharedSpace): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        $validated = $request->validate([
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'sometimes|string|size:3',
            'category'    => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'date'        => 'required|date',
            'tag'         => 'nullable|string|max:50',
        ]);

        $tx = $sharedSpace->transactions()->create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Transacción registrada.', 'transaction' => $tx->load('author:id,name')], 201);
    }

    public function destroyTransaction(Request $request, SharedSpace $sharedSpace, SharedTransaction $transaction): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        // Solo el autor o admin puede eliminar
        if ($transaction->user_id !== $request->user()->id && !$sharedSpace->isAdmin($request->user()->id)) {
            abort(403, 'No tienes permiso para eliminar esta transacción.');
        }

        $transaction->delete();
        return response()->json(['message' => 'Transacción eliminada.']);
    }

    // ── Metas compartidas ─────────────────────────────────────────────────

    public function getGoals(Request $request, SharedSpace $sharedSpace): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        $goals = $sharedSpace->goals()->with('creator:id,name')->get()
            ->map(fn($g) => $this->formatGoal($g));

        return response()->json(['goals' => $goals]);
    }

    public function storeGoal(Request $request, SharedSpace $sharedSpace): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'icon'           => 'sometimes|string|max:10',
            'target_amount'  => 'required|numeric|min:1',
            'current_amount' => 'sometimes|numeric|min:0',
            'currency'       => 'sometimes|string|size:3',
            'target_date'    => 'required|date|after:today',
        ]);

        $goal = $sharedSpace->goals()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);
        $goal->updateStatus();

        return response()->json(['message' => 'Meta creada.', 'goal' => $this->formatGoal($goal)], 201);
    }

    public function contributeGoal(Request $request, SharedSpace $sharedSpace, SharedGoal $goal): JsonResponse
    {
        $this->authorizeMember($request, $sharedSpace);

        $validated = $request->validate(['amount' => 'required|numeric|min:0.01']);
        $goal->current_amount = min($goal->target_amount, $goal->current_amount + $validated['amount']);
        $goal->save();
        $goal->updateStatus();

        return response()->json(['message' => 'Aporte registrado.', 'goal' => $this->formatGoal($goal->fresh())]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function formatSpace(SharedSpace $space, int $userId): array
    {
        return [
            'id'       => $space->id,
            'name'     => $space->name,
            'icon'     => $space->icon,
            'currency' => $space->currency,
            'is_admin' => $space->isAdmin($userId),
            'members'  => $space->members->map(fn($m) => [
                'id'   => $m->id,
                'name' => $m->name,
                'email'=> $m->email,
                'role' => $m->pivot->role,
            ]),
            'created_at' => $space->created_at,
        ];
    }

    private function formatGoal(SharedGoal $goal): array
    {
        return [
            'id'                     => $goal->id,
            'name'                   => $goal->name,
            'icon'                   => $goal->icon,
            'target_amount'          => $goal->target_amount,
            'current_amount'         => $goal->current_amount,
            'currency'               => $goal->currency,
            'target_date'            => $goal->target_date?->format('Y-m-d'),
            'status'                 => $goal->status,
            'progress_pct'           => round($goal->progress_pct, 2),
            'remaining'              => $goal->remaining,
            'months_remaining'       => $goal->months_remaining,
            'monthly_savings_needed' => round($goal->monthly_savings_needed, 2),
            'creator'                => $goal->creator?->name,
        ];
    }

    private function authorizeMember(Request $request, SharedSpace $space): void
    {
        if (!$space->isMember($request->user()->id)) abort(403, 'No eres miembro de este espacio.');
    }

    private function authorizeAdmin(Request $request, SharedSpace $space): void
    {
        if (!$space->isAdmin($request->user()->id)) abort(403, 'Solo el administrador puede realizar esta acción.');
    }
}
