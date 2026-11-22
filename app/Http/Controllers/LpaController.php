<?php
namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditAnswer;
use App\Models\AuditTemplate;
use App\Models\CorrectiveAction;
use App\Support\LpaAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LpaController extends Controller
{
    private function plantIds(Request $r): array {
        return DB::table('plant_user')->where('user_id', $r->user()->id)->pluck('plant_id')->all();
    }
    private function check(Request $r, Audit $audit, string $action):void {
        LpaAccess::enforce($r->user()->id, $audit->plant_id, $action);
    }
    public function dashboard(Request $r) {
        $ids=$this->plantIds($r);
        $base=Audit::query()->whereIn('plant_id',$ids);
        $counts=['planned'=>(clone $base)->where('status','planned')->count(),
                 'completed'=>(clone $base)->where('status','completed')->count(),
                 'open_actions'=>CorrectiveAction::query()->whereHas('answerAudit',fn($q)=>$q->whereIn('plant_id',$ids))->where('status','open')->count()];
        $filters=$r->validate([
            'status'=>['nullable',Rule::in(['planned','completed'])],
            'from'=>['nullable','date'],
            'to'=>['nullable','date','after_or_equal:from'],
        ]);
        if(!empty($filters['status'])) $base->where('status',$filters['status']);
        if(!empty($filters['from'])) $base->whereDate('scheduled_for','>=',$filters['from']);
        if(!empty($filters['to'])) $base->whereDate('scheduled_for','<=',$filters['to']);
        $audits=$base->with(['template:id,name,area','assignee:id,name'])->latest('id')->paginate(15)->withQueryString();
        $canSchedule=DB::table('plant_user')->where('user_id',$r->user()->id)->whereIn('role',['auditor','supervisor'])->exists();
        return view('lpa.dashboard',compact('counts','audits','filters','canSchedule'));
    }
    public function create(Request $r) {
        $ids=DB::table('plant_user')->where('user_id',$r->user()->id)->whereIn('role',['auditor','supervisor'])->pluck('plant_id');
        abort_if($ids->isEmpty(),403);
        $templates=AuditTemplate::query()->whereIn('plant_id',$ids)->where('active',true)->orderBy('name')->get();
        $auditors=DB::table('users')->join('plant_user','users.id','=','plant_user.user_id')
            ->whereIn('plant_user.plant_id',$ids)->whereIn('plant_user.role',['auditor','supervisor'])
            ->select('users.id','users.name')->distinct()->orderBy('users.name')->get();
        return view('lpa.plan',compact('templates','auditors'));
    }
    public function store(Request $r) {
        $data=$r->validate(['audit_template_id'=>['required','integer','exists:audit_templates,id'],
            'scheduled_for'=>['required','date'], 'assigned_to'=>['required','integer','exists:users,id']]);
        $tpl=AuditTemplate::query()->findOrFail($data['audit_template_id']);
        LpaAccess::enforce($r->user()->id,$tpl->plant_id,'audit');
        abort_unless($tpl->active,422);
        abort_unless(DB::table('plant_user')->where('plant_id',$tpl->plant_id)->where('user_id',$data['assigned_to'])->whereIn('role',['auditor','supervisor'])->exists(),422,'El responsable no pertenece a la planta.');
        $audit=Audit::create(['plant_id'=>$tpl->plant_id,'audit_template_id'=>$tpl->id,
            'assigned_to'=>$data['assigned_to'],'created_by'=>$r->user()->id,'scheduled_for'=>$data['scheduled_for'],'status'=>'planned']);
        return redirect()->route('lpa.audits.show',$audit)->with('ok','Auditoría programada.');
    }
    public function show(Request $r, Audit $audit) {
        $this->check($r,$audit,'read');
        $audit->load(['template.questions','assignee:id,name']);
        $answers=$audit->answers()->get()->keyBy('audit_question_id');
        $role=LpaAccess::role($r->user()->id,$audit->plant_id);
        $canComplete=$audit->status==='planned' && ($role==='supervisor' || ($role==='auditor' && (int)$audit->assigned_to===(int)$r->user()->id));
        return view('lpa.show',compact('audit','answers','canComplete'));
    }
    public function complete(Request $r, Audit $audit) {
        $this->check($r,$audit,'audit');
        abort_unless($audit->assigned_to===$r->user()->id || LpaAccess::role($r->user()->id,$audit->plant_id)==='supervisor',403);
        abort_if($audit->status==='completed',409,'La auditoría ya fue completada.');
        $questions=$audit->template->questions()->get();
        abort_if($questions->isEmpty(),422,'La auditoría no tiene preguntas.');
        $rules=[];
        foreach($questions as $q){
            $rules["answers.$q->id.result"]=['required',Rule::in(['pass','fail','na'])];
            $rules["answers.$q->id.note"]=['nullable','string','max:1000'];
        }
        $validated=$r->validate($rules);
        foreach($questions as $q){
            $answer=$validated['answers'][$q->id];
            if($answer['result']==='fail' && blank($answer['note']??null)){
                throw \Illuminate\Validation\ValidationException::withMessages(["answers.$q->id.note"=>'Describe el hallazgo.']);
            }
        }
        DB::transaction(function()use($audit,$questions,$validated,$r){
            $locked=Audit::query()->lockForUpdate()->findOrFail($audit->id);
            LpaAccess::enforce($r->user()->id,$locked->plant_id,'audit');
            abort_unless($locked->assigned_to===$r->user()->id || LpaAccess::role($r->user()->id,$locked->plant_id)==='supervisor',403);
            abort_if($locked->status==='completed',409,'La auditoría ya fue completada.');
            foreach($questions as $q){
                $answer=$validated['answers'][$q->id];
                $record=AuditAnswer::create(['audit_id'=>$locked->id,'audit_question_id'=>$q->id,'result'=>$answer['result'],'note'=>$answer['note']??null]);
                if($answer['result']==='fail') CorrectiveAction::create([
                    'audit_answer_id'=>$record->id,'owner_id'=>$locked->assigned_to,'description'=>$answer['note'],'status'=>'open'
                ]);
            }
            $locked->update(['status'=>'completed','completed_at'=>now()]);
        });
        return redirect()->route('lpa.audits.show',$audit)->with('ok','Auditoría completada.');
    }
    public function actions(Request $r){
        $ids=$this->plantIds($r);
        $filters=$r->validate([
            'status'=>['nullable',Rule::in(['open','closed'])],
            'owner_id'=>['nullable','integer'],
            'due'=>['nullable',Rule::in(['overdue','next_7','no_date'])],
        ]);
        $query=CorrectiveAction::query()->with(['answerAudit.template','owner:id,name'])
            ->whereHas('answerAudit',fn($q)=>$q->whereIn('plant_id',$ids));
        if(!empty($filters['status'])) $query->where('status',$filters['status']);
        if(!empty($filters['owner_id'])) $query->where('owner_id',$filters['owner_id']);
        if(!empty($filters['due'])){
            if($filters['due']==='overdue') $query->where('status','open')->whereDate('due_date','<',today());
            if($filters['due']==='next_7') $query->where('status','open')->whereBetween('due_date',[today()->toDateString(),today()->addDays(7)->toDateString()]);
            if($filters['due']==='no_date') $query->where('status','open')->whereNull('due_date');
        }
        $actions=$query->latest('id')->paginate(20)->withQueryString();
        $owners=DB::table('users')->join('plant_user','users.id','=','plant_user.user_id')
            ->whereIn('plant_user.plant_id',$ids)->select('users.id','users.name')->distinct()->orderBy('users.name')->get();
        $base=CorrectiveAction::query()->whereHas('answerAudit',fn($q)=>$q->whereIn('plant_id',$ids))->where('status','open');
        $summary=[
            'open'=>(clone $base)->count(),
            'overdue'=>(clone $base)->whereDate('due_date','<',today())->count(),
            'next_7'=>(clone $base)->whereBetween('due_date',[today()->toDateString(),today()->addDays(7)->toDateString()])->count(),
            'no_date'=>(clone $base)->whereNull('due_date')->count(),
        ];
        return view('lpa.actions',compact('actions','filters','owners','summary'));
    }
    public function updateAction(Request $r,CorrectiveAction $action){
        $audit=$action->answerAudit()->firstOrFail();
        LpaAccess::enforce($r->user()->id,$audit->plant_id,'manage');
        abort_if($action->status==='closed',409,'La acción ya está cerrada.');
        $data=$r->validate(['due_date'=>['required','date']]);
        $action->update(['due_date'=>$data['due_date']]);
        return redirect()->route('lpa.actions')->with('ok','Fecha compromiso actualizada.');
    }
    public function report(Request $r){
        $ids=$this->plantIds($r);
        $filters=$r->validate(['from'=>['nullable','date'],
            'to'=>['nullable','date','after_or_equal:from']]);
        $query=Audit::query()->whereIn('audits.plant_id',$ids);
        if(!empty($filters['from'])) $query->whereDate('audits.scheduled_for','>=',$filters['from']);
        if(!empty($filters['to'])) $query->whereDate('audits.scheduled_for','<=',$filters['to']);
        $planned=(clone $query)->count();
        $completed=(clone $query)->where('audits.status','completed')->count();
        $finished=(clone $query)->where('audits.status','completed')->pluck('audits.id');
        $answerCounts=AuditAnswer::query()->whereIn('audit_id',$finished)
            ->selectRaw('result, COUNT(*) as total')->groupBy('result')->pluck('total','result');
        $pass=(int)($answerCounts['pass']??0);
        $fail=(int)($answerCounts['fail']??0);
        $na=(int)($answerCounts['na']??0);
        $applicable=$pass+$fail;
        $data=['planned'=>$planned,'completed'=>$completed,
            'completion_pct'=>$planned ? round($completed*100/$planned,1) : null,
            'pass'=>$pass,'fail'=>$fail,'na'=>$na,
            'conformity_pct'=>$applicable ? round($pass*100/$applicable,1) : null];
        // The level summary is computed in SQL; each row only counts audits in visible plants and selected dates.
        $levels=(clone $query)->join('audit_templates','audit_templates.id','=','audits.audit_template_id')
            ->selectRaw('audit_templates.level as level, COUNT(*) as scheduled, SUM(CASE WHEN audits.status = ? THEN 1 ELSE 0 END) as finished',['completed'])
            ->groupBy('audit_templates.level')->orderBy('audit_templates.level')->get();
        $ownerSummary=CorrectiveAction::query()
            ->join('audit_answers','audit_answers.id','=','corrective_actions.audit_answer_id')
            ->join('audits','audits.id','=','audit_answers.audit_id')
            ->join('users','users.id','=','corrective_actions.owner_id')
            ->whereIn('audits.plant_id',$ids)
            ->when(!empty($filters['from']),fn($q)=>$q->whereDate('audits.scheduled_for','>=',$filters['from']))
            ->when(!empty($filters['to']),fn($q)=>$q->whereDate('audits.scheduled_for','<=',$filters['to']))
            ->selectRaw('users.id as owner_id, users.name as owner_name, COUNT(*) as total, SUM(CASE WHEN corrective_actions.status = ? THEN 1 ELSE 0 END) as open_count, SUM(CASE WHEN corrective_actions.status = ? AND corrective_actions.due_date < ? THEN 1 ELSE 0 END) as overdue_count',['open','open',today()->toDateString()])
            ->groupBy('users.id','users.name')->orderBy('users.name')->get();
        return view('lpa.report',compact('data','filters','levels','ownerSummary'));
    }
    public function closeAction(Request $r,CorrectiveAction $action){
        $audit=$action->answerAudit()->firstOrFail();
        LpaAccess::enforce($r->user()->id,$audit->plant_id,'manage');
        abort_if($action->status==='closed',409);
        $action->update(['status'=>'closed','closed_at'=>now()]);
        return redirect()->route('lpa.actions')->with('ok','Acción cerrada.');
    }
}
