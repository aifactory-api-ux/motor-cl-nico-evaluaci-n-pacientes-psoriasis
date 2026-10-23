<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla RESULTADO_EVALUACION del contrato de esquema del arquitecto.
     *
     * Cada evaluación produce exactamente un resultado con la decisión final y
     * el detalle de trazabilidad de los criterios evaluados.
     */
    public function up(): void
    {
        Schema::create('RESULTADO_EVALUACION', function (Blueprint $table): void {
            $table->char('id', 36)->primary();
            $table->char('evaluacion_id', 36);
            $table->string('resultado', 32);
            $table->text('detalle_trazabilidad');
            $table->dateTime('creada_en');

            $table->unique('evaluacion_id', 'resultado_evaluacion_evaluacion_id_unique');
            $table->index('resultado', 'resultado_evaluacion_resultado_index');

            $table->foreign('evaluacion_id', 'resultado_evaluacion_evaluacion_id_foreign')
                ->references('id')
                ->on('EVALUACION')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Elimina la tabla RESULTADO_EVALUACION.
     */
    public function down(): void
    {
        Schema::dropIfExists('RESULTADO_EVALUACION');
    }
};
