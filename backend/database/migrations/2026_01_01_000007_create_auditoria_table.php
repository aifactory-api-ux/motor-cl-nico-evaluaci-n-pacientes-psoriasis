<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla AUDITORIA del contrato de esquema del arquitecto.
     *
     * Trazabilidad de las decisiones del motor: quién realizó cada acción y
     * sobre qué evaluación. Los valores admitidos en "accion" se validan en la
     * capa de Dominio (crear_evaluacion, consultar_resolucion,
     * exportar_auditoria, consultar_estadisticas).
     */
    public function up(): void
    {
        Schema::create('AUDITORIA', function (Blueprint $table): void {
            $table->char('id', 36)->primary();
            $table->char('usuario_id', 36);
            $table->char('evaluacion_id', 36);
            $table->string('accion', 64);
            $table->dateTime('timestamp');

            $table->index('usuario_id', 'auditoria_usuario_id_index');
            $table->index('evaluacion_id', 'auditoria_evaluacion_id_index');
            $table->index('accion', 'auditoria_accion_index');
            $table->index('timestamp', 'auditoria_timestamp_index');

            $table->foreign('usuario_id', 'auditoria_usuario_id_foreign')
                ->references('id')
                ->on('USUARIO')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('evaluacion_id', 'auditoria_evaluacion_id_foreign')
                ->references('id')
                ->on('EVALUACION')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Elimina la tabla AUDITORIA.
     */
    public function down(): void
    {
        Schema::dropIfExists('AUDITORIA');
    }
};
