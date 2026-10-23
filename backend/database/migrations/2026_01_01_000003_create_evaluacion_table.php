<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla EVALUACION del contrato de esquema del arquitecto.
     *
     * Cada fila es la fotografía clínica que el motor de elegibilidad evalúa
     * contra las reglas de REGLA_CRITERIO. La clave primaria es un UUID v7 en
     * char(36): el proyecto prohíbe identificadores autoincrementales.
     */
    public function up(): void
    {
        Schema::create('EVALUACION', function (Blueprint $table): void {
            $table->char('id', 36)->primary();
            $table->char('paciente_id', 36);
            $table->char('usuario_id', 36);
            $table->integer('edad');
            $table->integer('psoriasis_meses');
            $table->float('pasi');
            $table->float('bsa');
            $table->boolean('historial_tb');
            $table->boolean('embarazo');
            $table->integer('uso_biologicos_meses');
            $table->integer('uso_corticoides_topicos_dias');
            $table->dateTime('evaluada_en');

            $table->index('paciente_id', 'evaluacion_paciente_id_index');
            $table->index('usuario_id', 'evaluacion_usuario_id_index');
            $table->index('evaluada_en', 'evaluacion_evaluada_en_index');

            $table->foreign('paciente_id', 'evaluacion_paciente_id_foreign')
                ->references('id')
                ->on('PACIENTE')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('usuario_id', 'evaluacion_usuario_id_foreign')
                ->references('id')
                ->on('USUARIO')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Elimina la tabla EVALUACION.
     */
    public function down(): void
    {
        Schema::dropIfExists('EVALUACION');
    }
};
