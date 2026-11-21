<?php
namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LpaCalendarController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'plant_id' => ['nullable', 'integer'],
            'level' => ['nullable', 'integer', 'between:1,5'],
        ]);
        $month = $filters['month'] ?? now()->format('Y-m');
        $start = \Carbon\CarbonImmutable::createFromFormat('!Y-m-d', $month.'-01');
        if (! $start || $start->format('Y-m') !== $month) {
            throw ValidationException::withMessages(['month' => 'Mes inválido.']);
        }
        $plants = Plant::query()->whereIn('id', DB::table('plant_user')
            ->where('user_id', $request->user()->id)->pluck('plant_id'))->orderBy('name')->get();
        if (! empty($filters['plant_id']) && ! $plants->contains('id', (int) $filters['plant_id'])) {
            abort(404);
        }
        $query = Audit::query()->with(['template:id,name,area,level','assignee:id,name'])
            ->whereIn('plant_id', $plants->pluck('id'))
            ->whereDate('scheduled_for', '>=', $start->toDateString())
            ->whereDate('scheduled_for', '<', $start->addMonth()->toDateString());
        if (! empty($filters['plant_id'])) $query->where('plant_id', $filters['plant_id']);
        if (! empty($filters['level'])) $query->whereHas('template', fn ($q) => $q->where('level', $filters['level']));
        $audits = $query->orderBy('scheduled_for')->orderBy('id')->get()->groupBy(fn ($audit) => $audit->scheduled_for->format('Y-m-d'));
        $padding = $start->dayOfWeekIso - 1;
        $days = [];
        for ($i = 0; $i < $padding; $i++) $days[] = null;
        for ($day = 1; $day <= $start->daysInMonth; $day++) $days[] = $start->setDay($day);
        while (count($days) % 7 !== 0) $days[] = null;
        return view('lpa.calendar', compact('days','audits','start','plants','filters'));
    }
}
