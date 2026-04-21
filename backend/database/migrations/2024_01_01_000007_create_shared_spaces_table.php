<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Espacio compartido (ej: "Finanzas de pareja")
        Schema::create('shared_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name'); // "Finanzas de pareja", "Familia García"
            $table->string('icon')->default('👫');
            $table->string('currency', 3)->default('CLP');
            $table->timestamps();
        });

        // Miembros del espacio compartido
        Schema::create('shared_space_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_space_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'member'])->default('member');
            $table->timestamp('joined_at')->useCurrent();
            $table->unique(['shared_space_id', 'user_id']);
        });

        // Transacciones compartidas
        Schema::create('shared_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_space_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Quién la registró
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('CLP');
            $table->string('category');
            $table->string('description')->nullable();
            $table->date('date');
            $table->string('tag')->nullable();
            $table->timestamps();
            $table->index(['shared_space_id', 'date']);
        });

        // Metas compartidas
        Schema::create('shared_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_space_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('icon')->default('🎯');
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('CLP');
            $table->date('target_date');
            $table->enum('status', ['on_track', 'behind', 'completed'])->default('on_track');
            $table->timestamps();
        });

        // Invitaciones pendientes por email
        Schema::create('shared_space_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_space_id')->constrained()->onDelete('cascade');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->string('email'); // Email del invitado
            $table->string('token', 64)->unique(); // Token para aceptar
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_space_invitations');
        Schema::dropIfExists('shared_goals');
        Schema::dropIfExists('shared_transactions');
        Schema::dropIfExists('shared_space_members');
        Schema::dropIfExists('shared_spaces');
    }
};
