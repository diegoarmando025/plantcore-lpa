<?php
namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\CorrectiveAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LpaExportController extends Controller
{
    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
    }

    private function safeCell(?string $cell): string
    {
        $cell = (string) $cell;
        // Prevent spreadsheet software from interpreting user input as a formula.
        return preg_match('/^\s*[=+\-@]/u', $cell) ? "'".$cell : $cell;
    }

    public function audits(Request $request)
    {
        $filters = $this->filters($request);
        $plantIds = DB::table('plant_user')->where('user_id', $request->user()->id)->pluck('plant_id');
        $query = Audit::query()->whereIn('plant_id', $plantIds)
            ->with(['template:id,name,area,level', 'assignee:id,name']);
        if (!empty($filters['from'])) $query->whereDate('scheduled_for', '>=', $filters['from']);
        if (!empty($filters['to'])) $query->whereDate('scheduled_for', '<=', $filters['to']);

        return response()->streamDownload(function () use ($query) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Folio','Fecha programada','Nivel','Lista','Área','Auditor','Estado','Completada']);
            foreach ($query->orderBy('id')->lazy(200) as $audit) {
                fputcsv($f, [
                    $audit->id, $audit->scheduled_for?->format('Y-m-d'),
                    $audit->template?->level,
                    $this->safeCell($audit->template?->name),
                    $this->safeCell($audit->template?->area),
                    $this->safeCell($audit->assignee?->name),
                    $audit->status, $audit->completed_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($f);
        }, 'auditorias-lpa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function actions(Request $request)
    {
        $filters = $this->filters($request);
        $plantIds = DB::table('plant_user')->where('user_id', $request->user()->id)->pluck('plant_id');
        $query = CorrectiveAction::query()
            ->with(['answerAudit.template', 'owner:id,name'])
            ->whereHas('answerAudit', function ($q) use ($plantIds, $filters) {
                $q->whereIn('plant_id', $plantIds);
                if (!empty($filters['from'])) $q->whereDate('scheduled_for', '>=', $filters['from']);
                if (!empty($filters['to'])) $q->whereDate('scheduled_for', '<=', $filters['to']);
            });
        return response()->streamDownload(function () use ($query) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF");
            fputcsv($f, ['Acción','Auditoría','Área','Responsable','Hallazgo','Estado','Compromiso','Cierre']);
            foreach ($query->orderBy('id')->lazy(200) as $action) {
                $audit = $action->answerAudit;
                fputcsv($f, [
                    $action->id, $audit?->id, $this->safeCell($audit?->template?->area),
                    $this->safeCell($action->owner?->name), $this->safeCell($action->description),
                    $action->status, $action->due_date?->format('Y-m-d'),
                    $action->closed_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($f);
        }, 'acciones-lpa.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
