<?php
namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditAnswer;
use App\Models\AuditTemplate;
use App\Models\CorrectiveAction;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LpaBatchExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->withoutVite(); }

    private function setupCase(string $role = 'supervisor'): array
    {
        $plant = Plant::create(['code' => 'QRO', 'name' => 'Querétaro']);
        $user = User::factory()->create();
        DB::table('plant_user')->insert(['plant_id' => $plant->id, 'user_id' => $user->id, 'role' => $role]);
        $template = AuditTemplate::create(['plant_id' => $plant->id, 'name' => 'Lista EPP', 'area' => 'Ensamble', 'active' => true]);
        $question = $template->questions()->create(['text' => '¿Usa EPP?', 'sort_order' => 1]);
        return [$plant, $user, $template, $question];
    }

    private function batch(int $templateId, int $userId): array
    {
        return [
            'audit_template_id' => $templateId,
            'assigned_to' => $userId,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-22',
            'frequency' => 'weekly',
        ];
    }

    public function test_weekly_batch_creates_four_audits_and_skips_repeated_dates(): void
    {
        [$plant, $user, $template] = $this->setupCase();
        $payload = $this->batch($template->id, $user->id);
        $this->actingAs($user)->post('/audits/batch', $payload)->assertRedirect('/audits');
        $this->assertSame(4, Audit::count());
        $this->actingAs($user)->post('/audits/batch', $payload)->assertRedirect('/audits');
        $this->assertSame(4, Audit::count());
        $this->assertDatabaseHas('audits', [
    'plant_id' => $plant->id,
    'scheduled_for' => '2026-12-15 00:00:00'
]);
    }

    public function test_batch_limits_number_of_audits_and_rejects_viewers(): void
    {
        [$plant, $supervisor, $template] = $this->setupCase();
        $tooMany = [...$this->batch($template->id, $supervisor->id), 'frequency' => 'daily', 'end_date' => '2027-03-01'];
        $this->actingAs($supervisor)->post('/audits/batch', $tooMany)->assertSessionHasErrors('end_date');
        $this->assertSame(0, Audit::count());
        $viewer = User::factory()->create();
        DB::table('plant_user')->insert(['plant_id' => $plant->id, 'user_id' => $viewer->id, 'role' => 'viewer']);
        $this->actingAs($viewer)->get('/audits/batch')->assertForbidden();
        $this->actingAs($viewer)->post('/audits/batch', $this->batch($template->id, $supervisor->id))->assertForbidden();
    }

    public function test_batch_cannot_assign_user_outside_the_template_plant(): void
    {
        [$plant, $supervisor, $template] = $this->setupCase();
        $outsider = User::factory()->create();
        $this->actingAs($supervisor)->post('/audits/batch', $this->batch($template->id, $outsider->id))->assertStatus(422);
        $this->assertSame(0, Audit::count());
    }

    public function test_csv_exports_only_include_visible_plant_and_escape_formulas(): void
    {
        [$plant, $user, $template, $question] = $this->setupCase();
        $visible = Audit::create(['plant_id' => $plant->id, 'audit_template_id' => $template->id,
            'assigned_to' => $user->id, 'created_by' => $user->id, 'scheduled_for' => '2026-12-15']);
        $answer = AuditAnswer::create(['audit_id' => $visible->id, 'audit_question_id' => $question->id,
            'result' => 'fail', 'note' => 'Falta EPP']);
        CorrectiveAction::create(['audit_answer_id' => $answer->id, 'owner_id' => $user->id,
            'description' => '=HYPERLINK("bad")', 'status' => 'open']);

        $other = Plant::create(['code' => 'MTY', 'name' => 'Monterrey']);
        $privateTemplate = AuditTemplate::create(['plant_id' => $other->id, 'name' => 'Privado', 'area' => 'Calidad']);
        Audit::create(['plant_id' => $other->id, 'audit_template_id' => $privateTemplate->id,
            'assigned_to' => $user->id, 'created_by' => $user->id, 'scheduled_for' => '2026-12-15']);

        $audits = $this->actingAs($user)->get('/exports/audits?from=2026-12-01&to=2026-12-31');
        $audits->assertOk()->assertDownload('auditorias-lpa.csv');
        $csv = $audits->streamedContent();
        $this->assertStringContainsString('Lista EPP', $csv);
        $this->assertStringNotContainsString('Privado', $csv);

        $actions = $this->actingAs($user)->get('/exports/actions');
        $actions->assertOk()->assertDownload('acciones-lpa.csv');
        $this->assertStringContainsString("'=HYPERLINK", $actions->streamedContent());
    }
}
