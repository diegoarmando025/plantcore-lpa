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

class LpaTrackingTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->withoutVite(); $this->travelTo(\Carbon\Carbon::parse('2026-12-20 12:00:00')); }

    private function fixture(string $plantCode='QRO',int $level=2):array
    {
        $plant=Plant::create(['code'=>$plantCode,'name'=>'Planta '.$plantCode]);
        $user=User::factory()->create(['name'=>'Responsable '.$plantCode]);
        DB::table('plant_user')->insert(['plant_id'=>$plant->id,'user_id'=>$user->id,'role'=>'supervisor']);
        $template=AuditTemplate::create(['plant_id'=>$plant->id,'name'=>'Control '.$plantCode,'area'=>'Produccion','level'=>$level,'active'=>true]);
        $question=$template->questions()->create(['text'=>'¿Control vigente?','sort_order'=>1]);
        return [$plant,$user,$template,$question];
    }
    private function action(Plant $plant,User $user,AuditTemplate $template,$question,string $due,string $status='open'):CorrectiveAction
    {
        $audit=Audit::create(['plant_id'=>$plant->id,'audit_template_id'=>$template->id,'assigned_to'=>$user->id,'created_by'=>$user->id,'scheduled_for'=>'2026-12-10','status'=>'completed','completed_at'=>now()]);
        $answer=AuditAnswer::create(['audit_id'=>$audit->id,'audit_question_id'=>$question->id,'result'=>'fail','note'=>'Observacion']);
        return CorrectiveAction::create(['audit_answer_id'=>$answer->id,'owner_id'=>$user->id,'description'=>'Accion de control','status'=>$status,'due_date'=>$due]);
    }
    public function test_overdue_filter_excludes_future_and_closed_actions():void
    {
        [$p,$u,$t,$q]=$this->fixture();
        $late=$this->action($p,$u,$t,$q,'2026-12-15');
        $this->action($p,$u,$t,$q,'2026-12-23');
        $this->action($p,$u,$t,$q,'2026-12-10','closed');
        $this->actingAs($u)->get('/actions?due=overdue')->assertOk()->assertViewHas('actions',fn($v)=>$v->total()===1 && $v->first()->id===$late->id)->assertViewHas('summary',fn($v)=>$v['overdue']===1 && $v['open']===2);
    }
    public function test_level_and_owner_reports_ignore_other_plants():void
    {
        [$p,$u,$t,$q]=$this->fixture('QRO',2);
        $this->action($p,$u,$t,$q,'2026-12-15');
        Audit::create(['plant_id'=>$p->id,'audit_template_id'=>$t->id,'assigned_to'=>$u->id,'created_by'=>$u->id,'scheduled_for'=>'2026-12-12','status'=>'planned']);
        [$p2,$u2,$t2,$q2]=$this->fixture('MTY',5);
        $this->action($p2,$u2,$t2,$q2,'2026-12-10');
        $this->actingAs($u)->get('/reports?from=2026-12-01&to=2026-12-31')->assertOk()
            ->assertViewHas('levels',fn($v)=>$v->count()===1 && (int)$v->first()->level===2 && (int)$v->first()->scheduled===2)
            ->assertViewHas('ownerSummary',fn($v)=>$v->count()===1 && (int)$v->first()->overdue_count===1);
    }
    public function test_summary_csv_only_contains_authorized_plant():void
    {
        [$p,$u,$t,$q]=$this->fixture('QRO',2);
        $this->action($p,$u,$t,$q,'2026-12-15');
        [$p2,$u2,$t2,$q2]=$this->fixture('MTY',5);
        $this->action($p2,$u2,$t2,$q2,'2026-12-15');
        $response=$this->actingAs($u)->get('/exports/summary');
        $response->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
        ob_start();$response->baseResponse->sendContent();$csv=ob_get_clean();
        $this->assertStringContainsString('L2',$csv);
        $this->assertStringNotContainsString('L5',$csv);
        $this->assertStringContainsString('Responsable QRO',$csv);
        $this->assertStringNotContainsString('Responsable MTY',$csv);
    }
}
