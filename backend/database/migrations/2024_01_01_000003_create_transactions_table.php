<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Tipo: ingreso o gasto
            $table->enum('type', ['income', 'expense']);

            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('CLP');

            // Categorías predefinidas más personalizadas
            $table->string('category'); // ej: 'alimentacion', 'transporte', 'sueldo'
            $table->string('description')->nullable();

            // Fecha real de la transacción (puede ser diferente a created_at)
            $table->date('date');

            // Etiqueta opcional para agrupar
            $table->string('tag')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
