<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla REGLA_CRITERIO del contrato de esquema del arquitecto.
     *
     * Catálogo de reglas del protocolo ALM-PSO-2026: criterios de inclusión
     * (inclusion) y de exclusión (exclusion). Los valores admitidos en "tipo"
     * se validan en la capa de Dominio mediante el enum TipoCriterio.
     */
    public function up(): void
    {
        Schema::create('REGLA_CRITERIO', function (Blueprint $table): void {
            $table->char('id', 36)->primary();
            $table->string('codigo', 64)->unique();
            $table->string('nombre', 128);
            $table->string('tipo', 32);
            $table->string('descripcion', 255);
            $table->boolean('activo')->default(true);

            $table->index('tipo', 'regla_criterio_tipo_index');
            $table->index('activo', 'regla_criterio_activo_index');
        });
    }

    /**
     * Elimina la tabla REGLA_CRITERIO.
     */
    public function down(): void
    {
        Schema::dropIfExists('REGLA_CRITERIO');
    }
};
