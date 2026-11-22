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

class LpaTrendsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function plantWithAudit(string $code, User $user, string $role = 'supervisor'): array
    {
        $plant = Plant::create(['code' => $code, 'name' => $code]);
        DB::table('plant_user')->insert(['plant_id' => $plant->id, 'user_id' => $user->id, 'role' => $role]);
        $template = AuditTemplate::create(['plant_id' => $plant->id, 'name' => 'EPP', 'area' => 'Producción', 'active' => true]);
        $question = $template->questions()->create(['text' => 'Protección', 'sort_order' => 1]);
        return [$plant, $template, $question];
    }

    private function audit(Plant $plant, AuditTemplate $template, User $user, string $date, string $status): Audit
    {
        return Audit::create([
            'plant_id' => $plant->id, 'audit_template_id' => $template->id,
            'assigned_to' => $user->id, 'created_by' => $user->id,
            'scheduled_for' => $date, 'status' => $status,
            'completed_at' => $status === 'completed' ? '2026-12-30 12:00:00' : null,
        ]);
    }

    public function test_monthly_completion_and_current_action_state_are_calculated_from_visible_plants(): void
    {
        $user = User::factory()->create();
        [$plant, $template, $question] = $this->plantWithAudit('QRO', $user);
        $complete = $this->audit($plant, $template, $user, '2026-12-01', 'completed');
        $this->audit($plant, $template, $user, '2026-12-02', 'planned');
        $answer = AuditAnswer::create(['audit_id' => $complete->id, 'audit_question_id' => $question->id, 'result' => 'fail', 'note' => 'Sin EPP']);
        CorrectiveAction::create(['audit_answer_id' => $answer->id, 'owner_id' => $user->id, 'description' => 'Sin EPP', 'status' => 'open', 'due_date' => '2026-11-01']);
        $this->actingAs($user)->get('/reports/trends?from=2026-12-01&to=2026-12-31')
            ->assertOk()
            ->assertViewHas('rows', fn($rows) => $rows->count() === 1
                && $rows[0]->planned_count === 2 && $rows[0]->completed_count === 1
                && $rows[0]->completion_pct === 50.0 && $rows[0]->actions_count === 1
                && $rows[0]->open_count === 1);
    }

    public function test_foreign_plant_filter_returns_404_and_cannot_export(): void
    {
        $user = User::factory()->create();
        [$own] = $this->plantWithAudit('QRO', $user);
        $another = User::factory()->create();
        [$other, $template] = $this->plantWithAudit('MTY', $another);
        $this->audit($other, $template, $another, '2026-12-01', 'planned');
        $this->actingAs($user)->get('/reports/trends?plant_id='.$other->id)->assertNotFound();
        $this->actingAs($user)->get('/reports/trends/export?plant_id='.$other->id)->assertNotFound();
        $this->actingAs($user)->get('/reports/trends?from=2026-12-01&to=2026-12-31')
            ->assertOk()->assertViewHas('rows', fn($rows) => $rows->isEmpty());
    }

    public function test_export_requires_login_and_range_is_bounded(): void
    {
        $this->get('/reports/trends/export')->assertRedirect('/login');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/reports/trends?from=2024-01-01&to=2026-12-31')
            ->assertSessionHasErrors('from');
        $this->actingAs($user)->get('/reports/trends?from=2026-12-31&to=2026-12-01')
            ->assertSessionHasErrors('to');
        $this->actingAs($user)->get('/reports/trends/export?from=2026-12-01&to=2026-12-31')
            ->assertOk()->assertDownload('lpa-tendencias.csv');
    }
}
