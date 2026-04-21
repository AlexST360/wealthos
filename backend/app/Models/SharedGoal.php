<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SharedGoal extends Model
{
    protected $fillable = [
        'shared_space_id', 'created_by', 'name', 'icon',
        'target_amount', 'current_amount', 'currency', 'target_date', 'status',
    ];

    protected function casts(): array
    {
        return ['target_amount' => 'float', 'current_amount' => 'float', 'target_date' => 'date'];
    }

    public function space(): BelongsTo   { return $this->belongsTo(SharedSpace::class, 'shared_space_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getProgressPctAttribute(): float
    {
        return $this->target_amount > 0 ? min(100, ($this->current_amount / $this->target_amount) * 100) : 0;
    }

    public function getRemainingAttribute(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    public function getMonthsRemainingAttribute(): int
    {
        return max(0, (int) Carbon::now()->diffInMonths($this->target_date, false));
    }

    public function getMonthlySavingsNeededAttribute(): float
    {
        $m = $this->months_remaining;
        return $m > 0 ? $this->remaining / $m : $this->remaining;
    }

    public function updateStatus(): void
    {
        if ($this->current_amount >= $this->target_amount) $this->status = 'completed';
        elseif (Carbon::now()->gt($this->target_date))    $this->status = 'behind';
        else                                               $this->status = 'on_track';
        $this->save();
    }
}
