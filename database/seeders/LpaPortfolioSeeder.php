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

class LpaPortfolioSeeder extends Seeder
{
    /**
     * Adds a small, repeatable scenario without modifying real plant records.
     * No known passwords or client information are included.
     */
    public function run(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 403);

        DB::transaction(function (): void {
            $plant = Plant::firstOrCreate(
                ['code' => 'DEMO-LPA'],
                ['name' => 'Planta de demostración']
            );

            $supervisor = User::firstOrCreate(
                ['email' => 'demo.supervisor@lpa.example.invalid'],
                ['name' => 'Supervisión de procesos', 'password' => Str::random(64)]
            );
            $auditor = User::firstOrCreate(
                ['email' => 'demo.auditor@lpa.example.invalid'],
                ['name' => 'Auditor de producción', 'password' => Str::random(64)]
            );

            foreach ([[$supervisor, 'supervisor'], [$auditor, 'auditor']] as [$user, $role]) {
                DB::table('plant_user')->updateOrInsert(
                    ['plant_id' => $plant->id, 'user_id' => $user->id],
                    ['role' => $role]
                );
            }

            $templates = [
                [
                    'name' => 'Seguridad en línea de ensamble',
                    'area' => 'Ensamble',
                    'level' => 1,
                    'questions' => [
                        '¿Se utiliza el equipo de protección indicado?',
                        '¿Los resguardos y dispositivos de seguridad están habilitados?',
                        '¿Las estaciones están libres de obstáculos?',
                    ],
                ],
                [
                    'name' => 'Verificación de calidad de proceso',
                    'area' => 'Calidad',
                    'level' => 2,
                    'questions' => [
                        '¿El material está identificado conforme al proceso?',
                        '¿La documentación de operación corresponde a la revisión vigente?',
                        '¿Los registros de inspección están completos?',
                    ],
                ],
            ];

            foreach ($templates as $index => $definition) {
                $template = AuditTemplate::firstOrCreate(
                    ['plant_id' => $plant->id, 'name' => $definition['name']],
                    ['area' => $definition['area'], 'level' => $definition['level'], 'active' => true]
                );
                foreach ($definition['questions'] as $position => $question) {
                    $template->questions()->firstOrCreate(
                        ['sort_order' => $position + 1],
                        ['text' => $question]
                    );
                }

                // Two finished audits and one scheduled audit per template.
                foreach ([8, 3, -2] as $slot => $daysAgo) {
                    $date = today()->subDays($daysAgo)->toDateString();
                    $complete = $slot < 2;
                    $audit = Audit::query()
                        ->where('plant_id', $plant->id)
                        ->where('audit_template_id', $template->id)
                        ->where('assigned_to', $auditor->id)
                        ->whereDate('scheduled_for', $date)
                        ->first();

                    if (! $audit) {
                        $audit = Audit::create([
                            'plant_id' => $plant->id,
                            'audit_template_id' => $template->id,
                            'assigned_to' => $auditor->id,
                            'scheduled_for' => $date,
                            'created_by' => $supervisor->id,
                            'status' => $complete ? 'completed' : 'planned',
                            'completed_at' => $complete ? today()->subDays($daysAgo)->setTime(15, 0) : null,
                        ]);
                    }

                    // Do not overwrite user-edited records on a later seed run.
                    if (! $complete || $audit->status !== 'completed') {
                        continue;
                    }

                    foreach ($template->questions as $questionIndex => $question) {
                        $failed = $index === 0 && $slot === 1 && $questionIndex === 0;
                        $answer = AuditAnswer::firstOrCreate(
                            ['audit_id' => $audit->id, 'audit_question_id' => $question->id],
                            [
                                'result' => $failed ? 'fail' : 'pass',
                                'note' => $failed ? 'Falta verificación de EPP en estación de ensamble.' : null,
                            ]
                        );

                        if ($failed && $answer->result === 'fail') {
                            CorrectiveAction::firstOrCreate(
                                ['audit_answer_id' => $answer->id],
                                [
                                    'owner_id' => $auditor->id,
                                    'description' => 'Revisar disponibilidad y uso de EPP por estación.',
                                    'status' => 'open',
                                    'due_date' => today()->subDay()->toDateString(),
                                ]
                            );
                        }
                    }
                }
            }
        });
    }
}
