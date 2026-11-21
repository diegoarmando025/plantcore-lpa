<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditAnswer;
use App\Models\CorrectiveAction;
use App\Models\Plant;
use App\Models\User;
use Database\Seeders\LpaPortfolioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LpaPortfolioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_scenario_is_repeatable_and_isolated_from_other_plants(): void
    {
        $other = Plant::create(['code' => 'OTHER', 'name' => 'Otra planta']);
        $this->seed(LpaPortfolioSeeder::class);

        $plant = Plant::where('code', 'DEMO-LPA')->firstOrFail();
        $auditor = User::where('email', 'demo.auditor@lpa.example.invalid')->firstOrFail();
        $supervisor = User::where('email', 'demo.supervisor@lpa.example.invalid')->firstOrFail();

        $this->assertNotEquals($other->id, $plant->id);
        $this->assertSame(2, DB::table('audit_templates')->where('plant_id', $plant->id)->count());
        $this->assertSame(6, Audit::where('plant_id', $plant->id)->count());
        $this->assertSame(4, Audit::where('plant_id', $plant->id)->where('status', 'completed')->count());
        $this->assertSame(1, CorrectiveAction::count());
        $this->assertSame(12, AuditAnswer::count());
        $this->assertDatabaseHas('plant_user', [
            'plant_id' => $plant->id, 'user_id' => $auditor->id, 'role' => 'auditor',
        ]);

        $this->seed(LpaPortfolioSeeder::class);
        $this->assertSame(6, Audit::where('plant_id', $plant->id)->count());
        $this->assertSame(12, AuditAnswer::count());
        $this->assertSame(1, CorrectiveAction::count());
        $this->assertSame(0, Audit::where('plant_id', $other->id)->count());

        $this->withoutVite();
        $this->actingAs($supervisor)->get('/audits')->assertOk();
        $this->actingAs($supervisor)->get('/reports')->assertOk();
        $this->actingAs($supervisor)->get('/actions')->assertOk();
    }
}
