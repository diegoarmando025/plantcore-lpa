<?php
namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\CorrectiveAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LpaSummaryExportController extends Controller
{
    private function safe(?string $value): string
    {
        $value=(string) $value;
        return preg_match('/^\s*[=+\-@]/u',$value) ? "'".$value : $value;
    }

    public function __invoke(Request $request)
    {
        $dates=$request->validate([
            'from'=>['nullable','date_format:Y-m-d'],
            'to'=>['nullable','date_format:Y-m-d','after_or_equal:from'],
        ]);
        $ids=DB::table('plant_user')->where('user_id',$request->user()->id)->pluck('plant_id');
        $audits=Audit::query()->whereIn('audits.plant_id',$ids)
            ->when(!empty($dates['from']),fn($q)=>$q->whereDate('audits.scheduled_for','>=',$dates['from']))
            ->when(!empty($dates['to']),fn($q)=>$q->whereDate('audits.scheduled_for','<=',$dates['to']))
            ->join('audit_templates','audit_templates.id','=','audits.audit_template_id')
            ->selectRaw('audit_templates.level as level, COUNT(*) as total, SUM(CASE WHEN audits.status = ? THEN 1 ELSE 0 END) as completed',['completed'])
            ->groupBy('audit_templates.level')->orderBy('audit_templates.level')->get();
        $actions=CorrectiveAction::query()->join('audit_answers','audit_answers.id','=','corrective_actions.audit_answer_id')
            ->join('audits','audits.id','=','audit_answers.audit_id')
            ->join('users','users.id','=','corrective_actions.owner_id')
            ->whereIn('audits.plant_id',$ids)
            ->when(!empty($dates['from']),fn($q)=>$q->whereDate('audits.scheduled_for','>=',$dates['from']))
            ->when(!empty($dates['to']),fn($q)=>$q->whereDate('audits.scheduled_for','<=',$dates['to']))
            ->selectRaw('users.name as owner, COUNT(*) as total, SUM(CASE WHEN corrective_actions.status = ? THEN 1 ELSE 0 END) as open_count, SUM(CASE WHEN corrective_actions.status = ? AND corrective_actions.due_date < ? THEN 1 ELSE 0 END) as overdue_count',['open','open',today()->toDateString()])
            ->groupBy('users.id','users.name')->orderBy('users.name')->get();
        return response()->streamDownload(function () use ($audits,$actions):void {
            $f=fopen('php://output','w');
            fwrite($f,"\xEF\xBB\xBF");
            fputcsv($f,['Resumen por nivel LPA']);
            fputcsv($f,['Nivel','Programadas','Completadas','Cumplimiento (%)']);
            foreach($audits as $row){
                fputcsv($f,['L'.$row->level,$row->total,$row->completed,$row->total ? round($row->completed*100/$row->total,1) : '']);
            }
            fputcsv($f,[]);
            fputcsv($f,['Acciones por responsable']);
            fputcsv($f,['Responsable','Total','Abiertas','Vencidas']);
            foreach($actions as $row){
                fputcsv($f,[$this->safe($row->owner),$row->total,$row->open_count,$row->overdue_count]);
            }
            fclose($f);
        },'resumen-lpa.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
}
