<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SharedTransaction extends Model
{
    protected $fillable = [
        'shared_space_id', 'user_id', 'type', 'amount',
        'currency', 'category', 'description', 'date', 'tag',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float', 'date' => 'date'];
    }

    public function space(): BelongsTo   { return $this->belongsTo(SharedSpace::class, 'shared_space_id'); }
    public function author(): BelongsTo  { return $this->belongsTo(User::class, 'user_id'); }

    public function scopeOfMonth(Builder $q, int $year, int $month): Builder
    {
        return $q->whereYear('date', $year)->whereMonth('date', $month);
    }
}
