<?php
namespace Tests\Feature;
use App\Models\Audit;
use App\Models\AuditTemplate;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
class LpaFlowTest extends TestCase {
 use RefreshDatabase;
 protected function setUp(): void {parent::setUp();$this->withoutVite();}
 private function fixtures(string $role='supervisor'):array {
  $p=Plant::create(['code'=>'QRO','name'=>'Querétaro']); $u=User::factory()->create();
  DB::table('plant_user')->insert(['plant_id'=>$p->id,'user_id'=>$u->id,'role'=>$role]);
  $t=AuditTemplate::create(['plant_id'=>$p->id,'name'=>'Seguridad','area'=>'Producción','active'=>true]);
  $q=$t->questions()->create(['text'=>'¿Se utiliza EPP?','sort_order'=>1]);return [$p,$u,$t,$q];
 }
 public function test_guest_cannot_see_audits():void {$this->get('/audits')->assertRedirect('/login');}
 public function test_completed_audit_creates_action_for_failed_question():void {
  [$p,$u,$t,$q]=$this->fixtures();
  $this->actingAs($u)->post('/audits',['audit_template_id'=>$t->id,'assigned_to'=>$u->id,'scheduled_for'=>'2026-12-01'])->assertRedirect();
  $a=Audit::firstOrFail();
  $this->actingAs($u)->post("/audits/$a->id/complete",['answers'=>[$q->id=>['result'=>'fail','note'=>'Falta protección']]])->assertRedirect();
  $this->assertSame('completed',$a->fresh()->status);
  $this->assertDatabaseHas('corrective_actions',['description'=>'Falta protección','status'=>'open']);
  $this->actingAs($u)->get('/actions')->assertOk()->assertSee('Falta protección');
  $id=DB::table('corrective_actions')->value('id');
  $this->actingAs($u)->post("/actions/$id/close")->assertRedirect();
  $this->assertDatabaseHas('corrective_actions',['id'=>$id,'status'=>'closed']);
 }
 public function test_viewer_cannot_schedule_or_complete():void {
  [$p,$u,$t,$q]=$this->fixtures('viewer');
  $this->actingAs($u)->get('/audits/create')->assertForbidden();
  $this->actingAs($u)->post('/audits',['audit_template_id'=>$t->id,'assigned_to'=>$u->id,'scheduled_for'=>'2026-12-01'])->assertForbidden();
 }
 public function test_missing_note_is_rejected_on_failure():void {
  [$p,$u,$t,$q]=$this->fixtures();
  $a=Audit::create(['plant_id'=>$p->id,'audit_template_id'=>$t->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-01']);
  $this->actingAs($u)->post("/audits/$a->id/complete",['answers'=>[$q->id=>['result'=>'fail','note'=>'']]])->assertSessionHasErrors();
  $this->assertSame('planned',$a->fresh()->status);
 }
 public function test_plant_isolation():void {
  [$p,$u,$t,$q]=$this->fixtures();
  $other=Plant::create(['code'=>'MTY','name'=>'Monterrey']);
  $t2=AuditTemplate::create(['plant_id'=>$other->id,'name'=>'Safety','area'=>'Assembly']);
  $a=Audit::create(['plant_id'=>$other->id,'audit_template_id'=>$t2->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-01']);
  $this->actingAs($u)->get("/audits/$a->id")->assertNotFound();
  $this->actingAs($u)->post("/audits/$a->id/complete",[])->assertNotFound();
 }
}
