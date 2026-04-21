<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_cache', function (Blueprint $table) {
            $table->string('ticker')->primary();
            $table->decimal('price', 20, 8);
            $table->string('currency', 3)->default('USD');
            $table->decimal('change_24h', 8, 4)->nullable(); // % cambio 24h
            $table->decimal('market_cap', 25, 2)->nullable();
            $table->string('source')->default('yahoo'); // yahoo / coingecko / cmf
            $table->timestamp('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_cache');
    }
};
