<?php
namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditTemplate;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LpaCalendarTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->withoutVite(); }

    private function setupRole(string $role = 'supervisor'): array
    {
        $plant = Plant::create(['code' => 'QRO', 'name' => 'Querétaro']);
        $user = User::factory()->create();
        DB::table('plant_user')->insert(['plant_id' => $plant->id, 'user_id' => $user->id, 'role' => $role]);
        return [$plant, $user];
    }

    private function templateData(int $plantId): array
    {
        return ['plant_id' => $plantId, 'name' => 'Seguridad L2', 'area' => 'Ensamble',
            'level' => 2, 'questions' => "¿Se usa EPP?\n¿Hay instrucciones vigentes?"];
    }

    public function test_supervisor_creates_and_edits_questionnaire(): void
    {
        [$plant, $user] = $this->setupRole();
        $this->actingAs($user)->post('/templates', $this->templateData($plant->id))->assertRedirect();
        $template = AuditTemplate::firstOrFail();
        $this->assertEquals(2, $template->level);
        $this->assertSame(2, $template->questions()->count());
        $this->actingAs($user)->put("/templates/{$template->id}", [
            ...$this->templateData($plant->id), 'name' => 'Seguridad L2 revisada', 'questions' => '¿Se usa EPP?',
        ])->assertRedirect();
        $this->assertSame(1, $template->questions()->count());
        $this->actingAs($user)->get("/templates/{$template->id}")->assertOk()->assertSee('Seguridad L2 revisada');
    }

    public function test_viewer_cannot_manage_questionnaires(): void
    {
        [$plant, $user] = $this->setupRole('viewer');
        $this->actingAs($user)->get('/templates/create')->assertForbidden();
        $this->actingAs($user)->post('/templates', $this->templateData($plant->id))->assertForbidden();
    }

    public function test_other_plant_cannot_modify_questionnaire(): void
    {
        [$plant, $user] = $this->setupRole();
        $other = Plant::create(['code' => 'MTY', 'name' => 'Monterrey']);
        $template = AuditTemplate::create(['plant_id' => $other->id,'name' => 'No visible','area' => 'Almacén','level' => 3]);
        $this->actingAs($user)->get("/templates/{$template->id}")->assertNotFound();
        $this->actingAs($user)->post("/templates/{$template->id}/toggle")->assertNotFound();
    }

    public function test_scheduled_templates_cannot_change_questions_but_can_be_deactivated(): void
    {
        [$plant, $user] = $this->setupRole();
        $template = AuditTemplate::create(['plant_id' => $plant->id, 'name' => 'Lista','area' => 'Producción','level' => 1]);
        $template->questions()->create(['text' => '¿Se usa EPP?', 'sort_order' => 1]);
        Audit::create(['plant_id' => $plant->id,'audit_template_id' => $template->id,
            'assigned_to' => $user->id,'created_by' => $user->id,'scheduled_for' => '2026-12-15']);
        $this->actingAs($user)->put("/templates/{$template->id}", $this->templateData($plant->id))->assertStatus(409);
        $this->actingAs($user)->post("/templates/{$template->id}/toggle")->assertRedirect();
        $this->assertFalse((bool) $template->fresh()->active);
    }

    public function test_calendar_is_filtered_to_assigned_plants_and_level(): void
    {
        [$plant, $user] = $this->setupRole();
        $other = Plant::create(['code' => 'MTY','name' => 'Monterrey']);
        $visibleTemplate = AuditTemplate::create(['plant_id' => $plant->id,'name' => 'Visible L2','area' => 'Ensamble','level' => 2]);
        $hiddenTemplate = AuditTemplate::create(['plant_id' => $other->id,'name' => 'Privado L2','area' => 'Calidad','level' => 2]);
        foreach ([[$plant,$visibleTemplate],[$other,$hiddenTemplate]] as [$p,$template]) {
            Audit::create(['plant_id' => $p->id,'audit_template_id' => $template->id,
                'assigned_to' => $user->id,'created_by' => $user->id,'scheduled_for' => '2026-12-15']);
        }
        $this->actingAs($user)->get('/calendar?month=2026-12&level=2')
            ->assertOk()->assertSee('Ensamble')->assertDontSee('Calidad');
        $this->actingAs($user)->get('/calendar?month=2026-12&plant_id='.$other->id)->assertNotFound();
    }
}
