<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla EVALUACION_CRITERIO del contrato de esquema del arquitecto.
     *
     * Detalla el estado (pass, fail, na) de cada regla aplicada a una
     * evaluación. Los valores admitidos en "estado" se validan en la capa de
     * Dominio mediante el enum EstadoCriterio.
     */
    public function up(): void
    {
        Schema::create('EVALUACION_CRITERIO', function (Blueprint $table): void {
            $table->char('id', 36)->primary();
            $table->char('evaluacion_id', 36);
            $table->char('criterio_id', 36);
            $table->string('estado', 32);
            $table->string('motivo', 255)->nullable();

            $table->unique(['evaluacion_id', 'criterio_id'], 'evaluacion_criterio_evaluacion_criterio_unique');
            $table->index('criterio_id', 'evaluacion_criterio_criterio_id_index');
            $table->index('estado', 'evaluacion_criterio_estado_index');

            $table->foreign('evaluacion_id', 'evaluacion_criterio_evaluacion_id_foreign')
                ->references('id')
                ->on('EVALUACION')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('criterio_id', 'evaluacion_criterio_criterio_id_foreign')
                ->references('id')
                ->on('REGLA_CRITERIO')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Elimina la tabla EVALUACION_CRITERIO.
     */
    public function down(): void
    {
        Schema::dropIfExists('EVALUACION_CRITERIO');
    }
};
