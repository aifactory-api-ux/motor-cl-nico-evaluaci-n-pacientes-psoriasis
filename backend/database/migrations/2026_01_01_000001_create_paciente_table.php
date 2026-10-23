<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla PACIENTE del contrato de esquema del arquitecto.
     *
     * La clave primaria es un UUID v7 en char(36): el proyecto prohíbe
     * identificadores autoincrementales.
     */
    public function up(): void
    {
        Schema::create('PACIENTE', function (Blueprint $table): void {
            $table->char('id', 36)->primary();
            $table->string('numero_historia_clinica', 64)->unique();
            $table->date('fecha_nacimiento');
            $table->string('genero', 32);
            $table->string('ciudad', 128);
        });
    }

    /**
     * Elimina la tabla PACIENTE.
     */
    public function down(): void
    {
        Schema::dropIfExists('PACIENTE');
    }
};
