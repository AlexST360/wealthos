<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Tipo de activo: stock, crypto, uf, fund, cash
            $table->enum('type', ['stock', 'crypto', 'uf', 'fund', 'cash']);

            // Ticker o símbolo (AAPL, BTC, etc.)
            $table->string('ticker')->nullable();
            $table->string('name'); // Nombre legible del activo

            // Cantidad y precio de compra promedio
            $table->decimal('quantity', 20, 8);
            $table->decimal('avg_buy_price', 20, 4);
            $table->string('currency', 3)->default('USD'); // Moneda del activo

            // Notas opcionales
            $table->text('notes')->nullable();

            $table->timestamps();

            // Un usuario no puede tener dos activos con el mismo ticker
            $table->unique(['user_id', 'ticker']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
