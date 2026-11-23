<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditAnswer;
use App\Models\AuditTemplate;
use App\Models\CorrectiveAction;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CorrectiveActionEvidenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    private function fixture(): array
    {
        $plant = Plant::create(['code' => 'QRO-EV', 'name' => 'Planta de evaluación']);
        $supervisor = User::factory()->create();
        $auditor = User::factory()->create();
        $viewer = User::factory()->create();
        foreach ([[$supervisor, 'supervisor'], [$auditor, 'auditor'], [$viewer, 'viewer']] as [$user, $role]) {
            DB::table('plant_user')->insert(['plant_id' => $plant->id, 'user_id' => $user->id, 'role' => $role]);
        }
        $template = AuditTemplate::create(['plant_id' => $plant->id, 'name' => 'Seguridad', 'area' => 'Producción', 'active' => true]);
        $question = $template->questions()->create(['text' => '¿Existe evidencia?', 'sort_order' => 1]);
        $audit = Audit::create([
            'plant_id' => $plant->id, 'audit_template_id' => $template->id,
            'assigned_to' => $auditor->id, 'created_by' => $supervisor->id,
            'scheduled_for' => '2026-12-01', 'status' => 'completed',
            'completed_at' => now(),
        ]);
        $answer = AuditAnswer::create(['audit_id' => $audit->id, 'audit_question_id' => $question->id,
            'result' => 'fail', 'note' => 'Falta de señalización']);
        $action = CorrectiveAction::create(['audit_answer_id' => $answer->id, 'owner_id' => $auditor->id,
            'description' => 'Colocar señalización', 'status' => 'open']);

        return [$plant, $supervisor, $auditor, $viewer, $audit, $action];
    }

    public function test_assigned_auditor_can_upload_private_pdf_and_authorized_staff_can_download_it(): void
    {
        [$plant, $supervisor, $auditor, $viewer, $audit, $action] = $this->fixture();

        $this->actingAs($auditor)->post('/actions/'.$action->id.'/evidences', [
            'evidence' => UploadedFile::fake()->create('control.pdf', 80, 'application/pdf'),
            'note' => 'Se instaló señalización de seguridad.',
        ])->assertRedirect()->assertSessionHas('ok');

        $evidence = $action->evidences()->firstOrFail();
        $this->assertSame($auditor->id, $evidence->uploaded_by);
        $this->assertSame('control.pdf', $evidence->original_name);
        $this->assertSame('local', $evidence->disk);
        Storage::disk('local')->assertExists($evidence->path);
        Storage::disk('public')->assertMissing($evidence->path);

        $this->actingAs($viewer)->get('/actions/evidences/'.$evidence->id.'/download')
            ->assertOk()->assertDownload('control.pdf');

        $this->actingAs($supervisor)->get('/actions')->assertOk()->assertSee('control.pdf');
        $this->actingAs($viewer)->get('/audits/'.$audit->id)->assertOk()->assertSee('control.pdf');
    }

    public function test_evidence_access_is_scoped_to_the_plant_and_upload_permissions(): void
    {
        [$plant, $supervisor, $auditor, $viewer, $audit, $action] = $this->fixture();
        $outsider = User::factory()->create();
        $otherAuditor = User::factory()->create();
        DB::table('plant_user')->insert(['plant_id' => $plant->id, 'user_id' => $otherAuditor->id, 'role' => 'auditor']);

        $request = fn () => ['evidence' => UploadedFile::fake()->create('control.pdf', 80, 'application/pdf')];

        $this->actingAs($viewer)->post('/actions/'.$action->id.'/evidences', $request())->assertForbidden();
        $this->actingAs($otherAuditor)->post('/actions/'.$action->id.'/evidences', $request())->assertForbidden();
        $this->actingAs($outsider)->post('/actions/'.$action->id.'/evidences', $request())->assertNotFound();
        $this->assertDatabaseCount('corrective_action_evidences', 0);

        $this->actingAs($supervisor)->post('/actions/'.$action->id.'/evidences', $request())
            ->assertRedirect()->assertSessionHas('ok');

        $evidence = $action->evidences()->firstOrFail();
        $this->actingAs($outsider)->get('/actions/evidences/'.$evidence->id.'/download')->assertNotFound();
        $this->get('/actions/evidences/999999/download')->assertNotFound();
    }

    public function test_closed_actions_and_unsupported_or_oversized_files_cannot_be_uploaded(): void
    {
        [$plant, $supervisor, $auditor, $viewer, $audit, $action] = $this->fixture();

        $this->actingAs($supervisor)->post('/actions/'.$action->id.'/evidences', [
            'evidence' => UploadedFile::fake()->create('script.svg', 20, 'image/svg+xml'),
        ])->assertSessionHasErrors('evidence');

        $this->actingAs($supervisor)->post('/actions/'.$action->id.'/evidences', [
            'evidence' => UploadedFile::fake()->create('large.pdf', 4097, 'application/pdf'),
        ])->assertSessionHasErrors('evidence');

        $action->update(['status' => 'closed', 'closed_at' => now()]);
        $this->actingAs($supervisor)->post('/actions/'.$action->id.'/evidences', [
            'evidence' => UploadedFile::fake()->create('control.pdf', 80, 'application/pdf'),
        ])->assertStatus(409);

        $this->assertDatabaseCount('corrective_action_evidences', 0);
    }

    public function test_no_more_than_five_evidences_can_be_attached_to_an_action(): void
    {
        [$plant, $supervisor, $auditor, $viewer, $audit, $action] = $this->fixture();

        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($supervisor)->post('/actions/'.$action->id.'/evidences', [
                'evidence' => UploadedFile::fake()->create('control-'.$i.'.pdf', 60, 'application/pdf'),
            ])->assertRedirect()->assertSessionHas('ok');
        }

        $this->actingAs($supervisor)->post('/actions/'.$action->id.'/evidences', [
            'evidence' => UploadedFile::fake()->create('extra.pdf', 60, 'application/pdf'),
        ])->assertSessionHasErrors('evidence');

        $this->assertSame(5, $action->evidences()->count());
    }
}
