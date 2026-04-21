<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'currency',
        'category',
        'description',
        'date',
        'tag',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'date'   => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scope para filtrar por mes/año
    public function scopeOfMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    // Scope para ingresos
    public function scopeIncomes(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    // Scope para gastos
    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    // Categorías predefinidas disponibles
    public static function availableCategories(): array
    {
        return [
            'income'  => ['sueldo', 'freelance', 'inversiones', 'arriendo', 'otros_ingresos'],
            'expense' => [
                'alimentacion', 'transporte', 'vivienda', 'salud',
                'educacion', 'entretenimiento', 'ropa', 'tecnologia',
                'servicios_basicos', 'seguros', 'viajes', 'otros_gastos',
            ],
        ];
    }
}
