<?php
namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditTemplate;
use App\Support\LpaAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LpaBatchController extends Controller
{
    public function create(Request $request)
    {
        $plantIds = DB::table('plant_user')
            ->where('user_id', $request->user()->id)
            ->whereIn('role', ['auditor', 'supervisor'])->pluck('plant_id');
        abort_if($plantIds->isEmpty(), 403);
        $templates = AuditTemplate::whereIn('plant_id', $plantIds)
            ->where('active', true)->orderBy('name')->get();
        $auditors = DB::table('plant_user')->join('users', 'users.id', '=', 'plant_user.user_id')
            ->whereIn('plant_user.plant_id', $plantIds)->whereIn('plant_user.role', ['auditor', 'supervisor'])
            ->select('users.id', 'users.name')->distinct()->orderBy('users.name')->get();
        return view('lpa.batch', compact('templates', 'auditors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'audit_template_id' => ['required', 'integer', 'exists:audit_templates,id'],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'frequency' => ['required', Rule::in(['daily', 'weekly'])],
        ]);
        $template = AuditTemplate::findOrFail($data['audit_template_id']);
        LpaAccess::enforce($request->user()->id, $template->plant_id, 'audit');
        abort_unless($template->active, 422, 'La lista de verificación está inactiva.');
        abort_unless(DB::table('plant_user')->where('plant_id', $template->plant_id)
            ->where('user_id', $data['assigned_to'])->whereIn('role', ['auditor', 'supervisor'])->exists(), 422,
            'El auditor no pertenece a esta planta.');

        $start = CarbonImmutable::parse($data['start_date'])->startOfDay();
        $end = CarbonImmutable::parse($data['end_date'])->startOfDay();
        $step = $data['frequency'] === 'weekly' ? 7 : 1;
        $count = intdiv((int) $start->diffInDays($end), $step) + 1;
        if ($count > 60) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'end_date' => 'La programación admite hasta 60 auditorías por operación.',
            ]);
        }

        $created = DB::transaction(function () use ($template, $data, $start, $end, $step, $request): int {
            $total = 0;
            for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDays($step)) {
                $day = $date->toDateString();
                $exists = Audit::where('plant_id', $template->plant_id)
                    ->where('audit_template_id', $template->id)
                    ->where('assigned_to', $data['assigned_to'])
                    ->whereDate('scheduled_for', $day)->exists();
                if ($exists) continue;
                Audit::create([
                    'plant_id' => $template->plant_id,
                    'audit_template_id' => $template->id,
                    'assigned_to' => $data['assigned_to'],
                    'created_by' => $request->user()->id,
                    'scheduled_for' => $day,
                    'status' => 'planned',
                ]);
                $total++;
            }
            return $total;
        });

        return redirect()->route('lpa.dashboard')
            ->with('ok', "{$created} auditoría(s) programada(s). Las fechas repetidas se omitieron.");
    }
}
