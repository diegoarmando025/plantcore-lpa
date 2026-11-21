<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\AuditAnswer;
use App\Models\AuditTemplate;
use App\Models\CorrectiveAction;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LpaPublicDemoSeeder extends Seeder
{
    /**
     * Reserved plant only. Production execution is controlled by the CLI command.
     * Records are fictional and should never include external customer information.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $plant = Plant::firstOrCreate(
                ['code' => 'DEMO-PUBLIC'],
                ['name' => 'Planta de ensamble · Ejemplo']
            );

            $supervisor = User::firstOrCreate(
                ['email' => 'demo-public-supervisor@lpa.example.invalid'],
                ['name' => 'Supervisor de ejemplo', 'password' => Str::random(64)]
            );
            $auditor = User::firstOrCreate(
                ['email' => 'demo-public-auditor@lpa.example.invalid'],
                ['name' => 'Auditor de ejemplo', 'password' => Str::random(64)]
            );

            // Neither account is granted plant membership: public demo is read-only via GET.
            $specs = [
                ['Seguridad de estación', 'Ensamble', 1, [
                    '¿El operador utiliza EPP requerido?',
                    '¿Están habilitados los resguardos?',
                    '¿La estación está libre de obstáculos?',
                ]],
                ['Control de calidad de proceso', 'Calidad', 2, [
                    '¿Los componentes tienen identificación vigente?',
                    '¿Se encuentran completos los registros de inspección?',
                    '¿Se usa la instrucción de trabajo actualizada?',
                ]],
                ['Verificación de supervisión', 'Producción', 3, [
                    '¿Se revisaron los indicadores de turno?',
                    '¿Se documentaron las desviaciones?',
                    '¿Se confirmó el seguimiento de acciones?',
                ]],
            ];

            foreach ($specs as $index => [$name, $area, $level, $questions]) {
                $template = AuditTemplate::firstOrCreate(
                    ['plant_id' => $plant->id, 'name' => $name],
                    ['area' => $area, 'level' => $level, 'active' => true]
                );

                foreach ($questions as $pos => $question) {
                    $template->questions()->firstOrCreate(
                        ['sort_order' => $pos + 1],
                        ['text' => $question]
                    );
                }

                // No duplicates when the seeder runs again: existing scenario records stay intact.
                if ($template->audits()->exists()) {
                    continue;
                }

                foreach ([9, 4, -3] as $slot => $daysAgo) {
                    $complete = $slot !== 2;
                    $audit = Audit::create([
                        'plant_id' => $plant->id,
                        'audit_template_id' => $template->id,
                        'assigned_to' => $auditor->id,
                        'created_by' => $supervisor->id,
                        'scheduled_for' => today()->subDays($daysAgo)->toDateString(),
                        'status' => $complete ? 'completed' : 'planned',
                        'completed_at' => $complete ? today()->subDays($daysAgo)->setTime(12, 0) : null,
                    ]);

                    if (! $complete) {
                        continue;
                    }

                    foreach ($template->questions as $questionIndex => $question) {
                        $failed = ($index === 0 && $slot === 1 && $questionIndex === 0)
                            || ($index === 1 && $slot === 0 && $questionIndex === 1);
                        $note = $failed
                            ? ($index === 0
                                ? 'Se detectó una estación sin verificación de equipo de protección.'
                                : 'El registro de inspección del turno está incompleto.')
                            : null;

                        $answer = AuditAnswer::create([
                            'audit_id' => $audit->id,
                            'audit_question_id' => $question->id,
                            'result' => $failed ? 'fail' : 'pass',
                            'note' => $note,
                        ]);

                        if ($failed) {
                            CorrectiveAction::create([
                                'audit_answer_id' => $answer->id,
                                'owner_id' => $auditor->id,
                                'description' => $index === 0
                                    ? 'Verificar entrega y uso de EPP por estación.'
                                    : 'Completar y verificar registros de inspección.',
                                'status' => 'open',
                                'due_date' => today()->subDays($index === 0 ? 1 : -5)->toDateString(),
                            ]);
                        }
                    }
                }
            }
        });
    }
}
