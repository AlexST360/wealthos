<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'target_amount',
        'current_amount',
        'currency',
        'target_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'target_amount'  => 'float',
            'current_amount' => 'float',
            'target_date'    => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Porcentaje de progreso actual
    public function getProgressPctAttribute(): float
    {
        if ($this->target_amount == 0) return 0;
        return min(100, ($this->current_amount / $this->target_amount) * 100);
    }

    // Monto que falta por ahorrar
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    // Meses restantes hasta la fecha objetivo
    public function getMonthsRemainingAttribute(): int
    {
        return max(0, (int) Carbon::now()->diffInMonths($this->target_date, false));
    }

    // Cuánto hay que ahorrar por mes para llegar a tiempo
    public function getMonthlySavingsNeededAttribute(): float
    {
        $months = $this->months_remaining;
        if ($months <= 0) return $this->remaining_amount;
        return $this->remaining_amount / $months;
    }

    // Actualizar el estado según el progreso
    public function updateStatus(): void
    {
        if ($this->current_amount >= $this->target_amount) {
            $this->status = 'completed';
        } elseif (Carbon::now()->greaterThan($this->target_date)) {
            $this->status = 'behind';
        } else {
            $this->status = 'on_track';
        }
        $this->save();
    }
}
