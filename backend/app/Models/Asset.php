<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'ticker',
        'name',
        'quantity',
        'avg_buy_price',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity'       => 'float',
            'avg_buy_price'  => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Costo total de la posición (precio promedio × cantidad)
    public function getCostBasisAttribute(): float
    {
        return $this->quantity * $this->avg_buy_price;
    }

    // Ganancia/pérdida dado un precio actual
    public function getProfitLoss(float $currentPrice): float
    {
        return ($currentPrice - $this->avg_buy_price) * $this->quantity;
    }

    // % de ganancia/pérdida
    public function getProfitLossPct(float $currentPrice): float
    {
        if ($this->avg_buy_price == 0) return 0;
        return (($currentPrice - $this->avg_buy_price) / $this->avg_buy_price) * 100;
    }
}
