<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditTemplate;
use App\Models\Plant;
use App\Models\User;
use Database\Seeders\LpaPublicDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpaPublicDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_demo_is_disabled_by_default(): void
    {
        $this->get('/demo')->assertNotFound();
        $this->get('/demo/audits/1')->assertNotFound();
    }

    public function test_public_demo_is_read_only_and_does_not_expose_other_plants(): void
    {
        $this->seed(LpaPublicDemoSeeder::class);
        config(['lpa.public_demo_enabled' => true]);

        $plant = Plant::where('code', 'DEMO-PUBLIC')->firstOrFail();
        $audit = Audit::where('plant_id', $plant->id)->where('status', 'completed')->firstOrFail();

        $other = Plant::create(['code' => 'INTERNAL', 'name' => 'Planta privada confidencial']);
        $user = User::factory()->create(['name' => 'Usuario confidencial']);
        $template = AuditTemplate::create([
            'plant_id' => $other->id, 'name' => 'Cuestionario reservado',
            'area' => 'Área confidencial', 'level' => 5, 'active' => true,
        ]);
        $privateAudit = Audit::create([
            'plant_id' => $other->id, 'audit_template_id' => $template->id,
            'assigned_to' => $user->id, 'created_by' => $user->id,
            'scheduled_for' => today()->toDateString(), 'status' => 'planned',
        ]);

        $this->get('/demo')
            ->assertOk()
            ->assertSee('Seguridad de estación')
            ->assertSee('Seguimiento de hallazgos')
            ->assertDontSee('Cuestionario reservado')
            ->assertDontSee('Usuario confidencial')
            ->assertDontSee('Planta privada confidencial')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->get('/demo?level=2&status=completed')->assertOk()
            ->assertSee('Control de calidad de proceso')
            ->assertDontSee('Seguridad de estación');

        $this->get('/demo/audits/'.$audit->id)->assertOk()
            ->assertSee('Lista de verificación')
            ->assertDontSee('Usuario confidencial');

        $this->get('/demo/audits/'.$privateAudit->id)->assertNotFound();

        // Public GET pages do not provide write access to any existing LPA POST routes.
        $this->post('/audits', [])->assertRedirect('/login');
        $this->post('/actions/1/close', [])->assertRedirect('/login');
    }

    public function test_public_demo_seeding_can_be_repeated_without_duplicates(): void
    {
        $this->seed(LpaPublicDemoSeeder::class);
        $plant = Plant::where('code', 'DEMO-PUBLIC')->firstOrFail();

        $this->assertSame(3, AuditTemplate::where('plant_id', $plant->id)->count());
        $this->assertSame(9, Audit::where('plant_id', $plant->id)->count());

        $this->seed(LpaPublicDemoSeeder::class);
        $this->assertSame(9, Audit::where('plant_id', $plant->id)->count());
    }
}
