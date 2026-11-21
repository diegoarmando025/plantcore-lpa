<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\CorrectiveAction;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LpaPublicDemoController extends Controller
{
    private function demoPlant(): Plant
    {
        abort_unless(config('lpa.public_demo_enabled'), 404);

        return Plant::query()
            ->where('code', config('lpa.public_demo_plant_code'))
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $plant = $this->demoPlant();
        $filters = $request->validate([
            'level' => ['nullable', 'integer', 'between:1,5'],
            'status' => ['nullable', Rule::in(['planned', 'completed'])],
        ]);

        $query = Audit::query()
            ->where('audits.plant_id', $plant->id);

        $counts = [
            'planned' => (clone $query)->where('status', 'planned')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'open_actions' => CorrectiveAction::query()
                ->where('status', 'open')
                ->whereHas('answerAudit', fn ($q) => $q->where('audits.plant_id', $plant->id))
                ->count(),
        ];

        if (isset($filters['level'])) {
            $query->whereHas('template', fn ($q) => $q->where('level', $filters['level']));
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $audits = $query
            ->with(['template:id,name,area,level'])
            ->orderByDesc('scheduled_for')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $actions = CorrectiveAction::query()
            ->whereHas('answerAudit', fn ($q) => $q->where('audits.plant_id', $plant->id))
            ->with(['answerAudit.template:id,name,area,level'])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', ['open'])
            ->limit(8)->get();

        return response()->view('lpa.public-demo.index', compact('plant', 'filters', 'counts', 'audits', 'actions'))
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }

    public function show(Audit $audit)
    {
        $plant = $this->demoPlant();
        abort_unless((int) $audit->plant_id === (int) $plant->id, 404);

        $audit->load([
            'template.questions',
            'answers.action',
        ]);

        return response()->view('lpa.public-demo.show', compact('audit'))
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }
}
