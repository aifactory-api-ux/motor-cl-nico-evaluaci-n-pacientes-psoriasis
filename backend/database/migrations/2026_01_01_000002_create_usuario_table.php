<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla USUARIO del contrato de esquema del arquitecto.
     *
     * La clave primaria es un UUID v7 en char(36): el proyecto prohíbe
     * identificadores autoincrementales. Los valores permitidos de "rol"
     * (investigador, gestor) se validan en la capa de Dominio mediante el
     * enum Rol.
     */
    public function up(): void
    {
        Schema::create('USUARIO', function (Blueprint $table): void {
            $table->char('id', 36)->primary();
            $table->string('email', 255)->unique();
            $table->string('nombre', 128);
            $table->string('rol', 32);
        });
    }

    /**
     * Elimina la tabla USUARIO.
     */
    public function down(): void
    {
        Schema::dropIfExists('USUARIO');
    }
};
