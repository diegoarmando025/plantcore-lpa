<?php
namespace Tests\Feature;
use App\Models\Audit;
use App\Models\AuditTemplate;
use App\Models\Plant;
use App\Models\User;
use Database\Seeders\LpaPublicDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ResetPortfolioDemoTest extends TestCase {
 use RefreshDatabase;
 public function test_dry_run_preserves_existing_data(): void {
  (new LpaPublicDemoSeeder())->run();
  $original=Audit::count();
  $this->artisan('portfolio:reset-demo --dry-run')->assertExitCode(0);
  $this->assertSame($original,Audit::count());
 }
 public function test_rebuild_only_demo_plant_and_preserve_real_records_and_users(): void {
  (new LpaPublicDemoSeeder())->run();
  $demo=Plant::where('code','DEMO-PUBLIC')->firstOrFail();
  $user=User::factory()->create();
  $real=Plant::create(['code'=>'PLANT-REAL','name'=>'Planta protegida']);
  $realTemplate=AuditTemplate::create(['plant_id'=>$real->id,'name'=>'Real','area'=>'Calidad','active'=>true]);
  $realAudit=Audit::create(['plant_id'=>$real->id,'audit_template_id'=>$realTemplate->id,'assigned_to'=>$user->id,'created_by'=>$user->id,'scheduled_for'=>today()->toDateString(),'status'=>'planned']);
  $template=AuditTemplate::where('plant_id',$demo->id)->firstOrFail();
  $visitor=Audit::create(['plant_id'=>$demo->id,'audit_template_id'=>$template->id,'assigned_to'=>$user->id,'created_by'=>$user->id,'scheduled_for'=>today()->toDateString(),'status'=>'planned']);
  $users=User::count();
  $this->artisan('portfolio:reset-demo')
   ->expectsConfirmation('¿Confirmas que ya verificaste un respaldo de la base de datos?', 'yes')
   ->expectsQuestion('Escribe RESTABLECER DEMO-PUBLIC para confirmar','RESTABLECER DEMO-PUBLIC')
   ->assertExitCode(0);
  $this->assertDatabaseMissing('audits',['id'=>$visitor->id]);
  $this->assertDatabaseHas('audits',['id'=>$realAudit->id]);
  $this->assertSame($users,User::count());
  $this->assertSame(9,Audit::where('plant_id',$demo->id)->count());
 }
 public function test_does_not_touch_cross_plant_template_references(): void {
  (new LpaPublicDemoSeeder())->run();
  $demo=Plant::where('code','DEMO-PUBLIC')->firstOrFail();
  $template=AuditTemplate::where('plant_id',$demo->id)->firstOrFail();
  $other=Plant::create(['code'=>'OTHER','name'=>'Otra']);
  $user=User::factory()->create();
  $a=Audit::create(['plant_id'=>$other->id,'audit_template_id'=>$template->id,'assigned_to'=>$user->id,'created_by'=>$user->id,'scheduled_for'=>today()->toDateString(),'status'=>'planned']);
  $this->artisan('portfolio:reset-demo --dry-run')->assertExitCode(1);
  $this->assertDatabaseHas('audits',['id'=>$a->id]);
 }
}
