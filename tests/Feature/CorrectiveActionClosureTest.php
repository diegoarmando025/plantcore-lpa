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

final class CorrectiveActionClosureTest extends TestCase
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
        $plant = Plant::create(['code' => 'QRO-CLOSE', 'name' => 'Planta ejemplo']);
        $supervisor = User::factory()->create();
        $auditor = User::factory()->create();
        $viewer = User::factory()->create();
        foreach ([[$supervisor, 'supervisor'], [$auditor, 'auditor'], [$viewer, 'viewer']] as [$user, $role]) {
            DB::table('plant_user')->insert(['plant_id' => $plant->id, 'user_id' => $user->id, 'role' => $role]);
        }
        $template = AuditTemplate::create([
            'plant_id' => $plant->id, 'name' => 'Verificación', 'area' => 'Producción', 'level' => 2, 'active' => true,
        ]);
        $question = $template->questions()->create(['text' => '¿Se cumple el estándar?', 'sort_order' => 1]);
        $audit = Audit::create([
            'plant_id' => $plant->id, 'audit_template_id' => $template->id,
            'assigned_to' => $auditor->id, 'created_by' => $supervisor->id,
            'scheduled_for' => '2026-12-01', 'status' => 'completed', 'completed_at' => now(),
        ]);
        $answer = AuditAnswer::create([
            'audit_id' => $audit->id, 'audit_question_id' => $question->id,
            'result' => 'fail', 'note' => 'Falta de control',
        ]);
        $action = CorrectiveAction::create([
            'audit_answer_id' => $answer->id, 'owner_id' => $auditor->id,
            'description' => 'Aplicar control correctivo', 'status' => 'open',
        ]);
        return [$supervisor, $auditor, $viewer, $action, $audit];
    }

    public function test_supervisor_must_document_a_resolution_and_attach_evidence_before_closure(): void
    {
        [$supervisor, $auditor, $viewer, $action, $audit] = $this->fixture();
        $url = '/actions/'.$action->id.'/close';

        $this->actingAs($supervisor)->post($url, [
            'closure_note' => 'Se verificó la acción en la línea.',
        ])->assertSessionHasErrors('closure_note');
        $this->assertSame('open', $action->fresh()->status);

        $this->actingAs($auditor)->post('/actions/'.$action->id.'/evidences', [
            'evidence' => UploadedFile::fake()->create('control.pdf', 40, 'application/pdf'),
            'note' => 'Evidencia posterior a la corrección',
        ])->assertRedirect()->assertSessionHas('ok');

        $this->actingAs($supervisor)->post($url, ['closure_note' => ''])
            ->assertSessionHasErrors('closure_note');

        $this->actingAs($supervisor)->post($url, [
            'closure_note' => 'Corrección verificada y evidencia revisada por supervisión.',
        ])->assertRedirect()->assertSessionHas('ok');

        $fresh = $action->fresh();
        $this->assertSame('closed', $fresh->status);
        $this->assertSame($supervisor->id, $fresh->closed_by);
        $this->assertNotNull($fresh->closed_at);
        $this->assertSame('Corrección verificada y evidencia revisada por supervisión.', $fresh->closure_note);

        $this->actingAs($viewer)->get('/actions')->assertOk()
            ->assertSee('Corrección verificada y evidencia revisada por supervisión.')
            ->assertSee($supervisor->name);
        $this->actingAs($viewer)->get('/audits/'.$audit->id)->assertOk()
            ->assertSee('Corrección verificada y evidencia revisada por supervisión.');

        $this->actingAs($supervisor)->post($url, [
            'closure_note' => 'Intento de cerrar nuevamente',
        ])->assertStatus(409);
        $this->assertSame('Corrección verificada y evidencia revisada por supervisión.', $action->fresh()->closure_note);
    }

    public function test_auditor_viewer_and_outsiders_cannot_close_corrective_actions(): void
    {
        [$supervisor, $auditor, $viewer, $action] = $this->fixture();
        $other = User::factory()->create();
        foreach ([$auditor, $viewer] as $user) {
            $this->actingAs($user)->post('/actions/'.$action->id.'/close', [
                'closure_note' => 'No autorizado',
            ])->assertForbidden();
        }
        $this->actingAs($other)->post('/actions/'.$action->id.'/close', [
            'closure_note' => 'No autorizado',
        ])->assertNotFound();

        $this->assertSame('open', $action->fresh()->status);
    }
}
