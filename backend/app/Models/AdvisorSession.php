<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'messages',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Agregar un mensaje a la sesión
    public function addMessage(string $role, string $content): void
    {
        $messages = $this->messages ?? [];
        $messages[] = [
            'role'      => $role,
            'content'   => $content,
            'timestamp' => now()->toISOString(),
        ];
        $this->messages = $messages;

        // Auto-título con el primer mensaje del usuario
        if ($this->title === null && $role === 'user') {
            $this->title = substr($content, 0, 60) . (strlen($content) > 60 ? '...' : '');
        }

        $this->save();
    }

    // Obtener mensajes en el formato que espera la API de Groq
    public function getGroqMessages(): array
    {
        return collect($this->messages ?? [])
            ->map(fn($msg) => ['role' => $msg['role'], 'content' => $msg['content']])
            ->toArray();
    }
}
