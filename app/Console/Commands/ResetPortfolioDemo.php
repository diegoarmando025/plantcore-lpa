<?php

namespace App\Console\Commands;

use App\Models\Plant;
use Database\Seeders\LpaPublicDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ResetPortfolioDemo extends Command
{
    protected $signature = 'portfolio:reset-demo {--dry-run : Mostrar el alcance sin modificar datos}';
    protected $description = 'Restaurar únicamente auditorías y cuestionarios ficticios de DEMO-PUBLIC.';

    public function handle(): int
    {
        $plant = Plant::query()->where('code', 'DEMO-PUBLIC')->first();
        if (! $plant) {
            $this->error('La planta DEMO-PUBLIC no existe. Operación cancelada.');
            return self::FAILURE;
        }

        $ids = DB::table('audit_templates')->where('plant_id', $plant->id)->pluck('id');
        // A misconfigured cross-plant reference would make a destructive reset unsafe.
        if (DB::table('audits')->whereIn('audit_template_id', $ids)
            ->where('plant_id', '<>', $plant->id)->exists()) {
            $this->error('Existen referencias a cuestionarios demo desde otras plantas. Cancelado.');
            return self::FAILURE;
        }
        $auditIds = DB::table('audits')->where('plant_id', $plant->id)->pluck('id');
        $answerIds = DB::table('audit_answers')->whereIn('audit_id', $auditIds)->pluck('id');
        $counts = [
            'auditorias' => $auditIds->count(),
            'respuestas' => $answerIds->count(),
            'acciones' => DB::table('corrective_actions')->whereIn('audit_answer_id', $answerIds)->count(),
            'cuestionarios' => $ids->count(),
        ];
        $this->table(['Recurso ficticio', 'Registros actuales'], collect($counts)->map(fn($v, $k) => [$k, $v])->values()->all());

        if ($this->option('dry-run')) {
            $this->info('Simulación: no se modificó ningún registro.');
            return self::SUCCESS;
        }
        if (! $this->input->isInteractive()) {
            $this->error('El restablecimiento requiere terminal SSH interactiva.');
            return self::FAILURE;
        }

        $this->warn('Se perderán los cambios realizados por visitantes en DEMO-PUBLIC. No se borran usuarios, membresías ni otras plantas.');
        if (! $this->confirm('¿Confirmas que ya verificaste un respaldo de la base de datos?', false)) {
            return self::FAILURE;
        }
        if ((string) $this->ask('Escribe RESTABLECER DEMO-PUBLIC para confirmar') !== 'RESTABLECER DEMO-PUBLIC') {
            $this->error('Confirmación incorrecta. No se modificaron datos.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($plant): void {
                // Serializes resets against other reset processes on MariaDB.
                DB::table('plants')->where('id', $plant->id)->lockForUpdate()->first();
                $templateIds = DB::table('audit_templates')->where('plant_id', $plant->id)->pluck('id');
                if (DB::table('audits')->whereIn('audit_template_id', $templateIds)
                    ->where('plant_id', '<>', $plant->id)->exists()) {
                    throw new \RuntimeException('Se encontraron referencias de otras plantas.');
                }
                $auditIds = DB::table('audits')->where('plant_id', $plant->id)->pluck('id');
                $answerIds = DB::table('audit_answers')->whereIn('audit_id', $auditIds)->pluck('id');
                DB::table('corrective_actions')->whereIn('audit_answer_id', $answerIds)->delete();
                DB::table('audit_answers')->whereIn('audit_id', $auditIds)->delete();
                DB::table('audits')->where('plant_id', $plant->id)->delete();
                DB::table('audit_questions')->whereIn('audit_template_id', $templateIds)->delete();
                DB::table('audit_templates')->where('plant_id', $plant->id)->delete();
                // Existing demo identities are reused, never deleted or reprovisioned.
                (new LpaPublicDemoSeeder())->run();
            }, 3);
        } catch (Throwable $e) {
            report($e);
            $this->error('Falló el restablecimiento. Revisa los logs; la transacción fue revertida.');
            return self::FAILURE;
        }

        $this->info('Escenario ficticio DEMO-PUBLIC restaurado.');
        return self::SUCCESS;
    }
}
