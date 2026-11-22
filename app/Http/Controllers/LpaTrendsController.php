<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LpaTrendsController extends Controller
{
    private function reportData(Request $request): array
    {
        $input = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'plant_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $to = Carbon::parse($input['to'] ?? today()->toDateString())->startOfDay();
        $from = Carbon::parse($input['from'] ?? $to->copy()->startOfMonth()->subMonths(5)->toDateString())->startOfDay();
        if ($from->greaterThan($to) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['from' => 'Selecciona un intervalo válido de hasta 366 días.']);
        }

        $plants = DB::table('plants')
            ->join('plant_user', 'plant_user.plant_id', '=', 'plants.id')
            ->where('plant_user.user_id', $request->user()->id)
            ->select('plants.id', 'plants.name', 'plants.code')
            ->orderBy('plants.name')->get();

        $allowed = $plants->pluck('id')->all();
        $selected = isset($input['plant_id']) ? (int) $input['plant_id'] : null;
        if ($selected !== null) {
            abort_unless(in_array($selected, $allowed, true), 404);
        }
        $ids = $selected !== null ? [$selected] : $allowed;
        $range = [$from->toDateString(), $to->toDateString()];

        // DATE string prefix works in both SQLite (CI) and MariaDB (production).
        // Period attribution is the scheduled audit date, not the completion date.
        $audits = DB::table('audits')
            ->join('plants', 'plants.id', '=', 'audits.plant_id')
            ->whereIn('audits.plant_id', $ids)
            ->whereBetween('audits.scheduled_for', $range)
            ->selectRaw("audits.plant_id, plants.name as plant_name, substr(audits.scheduled_for, 1, 7) as month, COUNT(*) as planned_count, SUM(CASE WHEN audits.status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            ->groupBy('audits.plant_id', 'plants.name')
            ->groupByRaw('substr(audits.scheduled_for, 1, 7)')
            ->orderBy('month')->orderBy('plants.name')->get();

        // These are *current* action states for audits scheduled in the period,
        // not historical snapshots of what was open on a past date.
        $actions = DB::table('corrective_actions')
            ->join('audit_answers', 'audit_answers.id', '=', 'corrective_actions.audit_answer_id')
            ->join('audits', 'audits.id', '=', 'audit_answers.audit_id')
            ->whereIn('audits.plant_id', $ids)
            ->whereBetween('audits.scheduled_for', $range)
            ->selectRaw("audits.plant_id, substr(audits.scheduled_for, 1, 7) as month, COUNT(*) as actions_count, SUM(CASE WHEN corrective_actions.status = 'open' THEN 1 ELSE 0 END) as open_count, SUM(CASE WHEN corrective_actions.status = 'open' AND corrective_actions.due_date < ? THEN 1 ELSE 0 END) as overdue_count", [today()->toDateString()])
            ->groupBy('audits.plant_id')->groupByRaw('substr(audits.scheduled_for, 1, 7)')->get()
            ->keyBy(fn($item) => $item->plant_id.'|'.$item->month);

        $rows = $audits->map(function ($item) use ($actions) {
            $action = $actions->get($item->plant_id.'|'.$item->month);
            $item->planned_count = (int) $item->planned_count;
            $item->completed_count = (int) $item->completed_count;
            $item->completion_pct = $item->planned_count ? round(100 * $item->completed_count / $item->planned_count, 1) : null;
            $item->actions_count = (int) ($action->actions_count ?? 0);
            $item->open_count = (int) ($action->open_count ?? 0);
            $item->overdue_count = (int) ($action->overdue_count ?? 0);
            return $item;
        });
        return compact('rows', 'plants', 'selected', 'from', 'to');
    }

    public function index(Request $request)
    {
        return view('lpa.trends', $this->reportData($request));
    }

    public function export(Request $request)
    {
        $data = $this->reportData($request);
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Mes', 'Planta', 'Programadas', 'Completadas', 'Cumplimiento %', 'Acciones generadas', 'Abiertas actuales', 'Vencidas actuales']);
            foreach ($data['rows'] as $row) {
                // Prefix any user-controlled text that could execute as a spreadsheet formula.
                $plant = (string) $row->plant_name;
                if (preg_match('/^[\s]*[=+\-@]/u', $plant)) {
                    $plant = "'".$plant;
                }
                fputcsv($out, [$row->month, $plant, $row->planned_count, $row->completed_count, $row->completion_pct ?? '', $row->actions_count, $row->open_count, $row->overdue_count]);
            }
            fclose($out);
        }, 'lpa-tendencias.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
