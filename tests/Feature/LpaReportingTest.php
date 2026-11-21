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
class LpaReportingTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void {parent::setUp();$this->withoutVite();}
 private function fixtures():array {
  $p=Plant::create(['code'=>'QRO','name'=>'Querétaro']);
  $u=User::factory()->create(); DB::table('plant_user')->insert(['plant_id'=>$p->id,'user_id'=>$u->id,'role'=>'supervisor']);
  $t=AuditTemplate::create(['plant_id'=>$p->id,'name'=>'Seguridad','area'=>'Producción','active'=>true]);
  $q=$t->questions()->create(['text'=>'¿Usa EPP?','sort_order'=>1]);
  return [$p,$u,$t,$q];
 }
 public function test_report_calculates_completed_audits_and_conformity():void {
  [$p,$u,$t,$q]=$this->fixtures();
  $done=Audit::create(['plant_id'=>$p->id,'audit_template_id'=>$t->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-01','status'=>'completed','completed_at'=>now()]);
  AuditAnswer::create(['audit_id'=>$done->id,'audit_question_id'=>$q->id,'result'=>'pass']);
  Audit::create(['plant_id'=>$p->id,'audit_template_id'=>$t->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-02']);
  $this->actingAs($u)->get('/reports?from=2026-12-01&to=2026-12-31')->assertOk()->assertSee('50%')->assertSee('100%');
 }
 public function test_report_is_plant_isolated():void {
  [$p,$u,$t,$q]=$this->fixtures();
  $other=Plant::create(['code'=>'MTY','name'=>'Monterrey']);
  $otherTemplate=AuditTemplate::create(['plant_id'=>$other->id,'name'=>'Other','area'=>'Paint']);
  Audit::create(['plant_id'=>$other->id,'audit_template_id'=>$otherTemplate->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-01']);
  $this->actingAs($u)->get('/reports')->assertOk()->assertViewHas('data',fn($v)=>$v['planned']===0);
 }
 public function test_supervisor_can_set_due_date_and_viewer_cannot():void {
  [$p,$u,$t,$q]=$this->fixtures();
  $a=Audit::create(['plant_id'=>$p->id,'audit_template_id'=>$t->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-01','status'=>'completed']);
  $answer=AuditAnswer::create(['audit_id'=>$a->id,'audit_question_id'=>$q->id,'result'=>'fail','note'=>'Falta EPP']);
  $action=CorrectiveAction::create(['audit_answer_id'=>$answer->id,'owner_id'=>$u->id,'description'=>'Falta EPP','status'=>'open']);
  $this->actingAs($u)->post('/actions/'.$action->id.'/due-date',['due_date'=>'2026-12-15'])->assertRedirect('/actions');
$this->assertDatabaseHas('corrective_actions', [
    'id' => $action->id,
    'due_date' => '2026-12-15 00:00:00'
]);  $viewer=User::factory()->create();DB::table('plant_user')->insert(['plant_id'=>$p->id,'user_id'=>$viewer->id,'role'=>'viewer']);
  $this->actingAs($viewer)->post('/actions/'.$action->id.'/due-date',['due_date'=>'2026-12-20'])->assertForbidden();
 }
 public function test_audit_filters_do_not_show_unrelated_statuses():void {
  [$p,$u,$t]=$this->fixtures();
  Audit::create(['plant_id'=>$p->id,'audit_template_id'=>$t->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-01','status'=>'planned']);
  Audit::create(['plant_id'=>$p->id,'audit_template_id'=>$t->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-02','status'=>'completed']);
  $this->actingAs($u)->get('/audits?status=planned')->assertOk()->assertViewHas('audits',fn($v)=>$v->total()===1);
 }
}
