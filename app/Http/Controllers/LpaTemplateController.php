<?php
namespace App\Http\Controllers;

use App\Models\AuditTemplate;
use App\Models\Plant;
use App\Support\LpaAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LpaTemplateController extends Controller
{
    private function visiblePlants(Request $request)
    {
        return Plant::query()->whereIn('id', DB::table('plant_user')
            ->where('user_id', $request->user()->id)->pluck('plant_id'));
    }

    public function index(Request $request)
    {
        $plants = $this->visiblePlants($request)->orderBy('name')->get();
        $templates = AuditTemplate::query()
            ->whereIn('plant_id', $plants->pluck('id'))
            ->withCount(['questions', 'audits'])
            ->orderBy('plant_id')->orderBy('level')->orderBy('area')->paginate(20);
        $canManage = DB::table('plant_user')->where('user_id', $request->user()->id)
            ->where('role', 'supervisor')->exists();
        return view('lpa.templates.index', compact('templates', 'plants', 'canManage'));
    }

    public function create(Request $request)
    {
        $plants = $this->visiblePlants($request)
            ->whereIn('id', DB::table('plant_user')->where('user_id', $request->user()->id)
                ->where('role', 'supervisor')->pluck('plant_id'))
            ->orderBy('name')->get();
        abort_if($plants->isEmpty(), 403);
        return view('lpa.templates.form', ['template' => null, 'plants' => $plants]);
    }

    private function validateTemplate(Request $request): array
    {
        $data = $request->validate([
            'plant_id' => ['required', 'integer', 'exists:plants,id'],
            'name' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'between:1,5'],
            'questions' => ['required', 'string', 'max:9000'],
        ]);
        $questions = array_values(array_filter(array_map(
            'trim', preg_split('/\r\n|\r|\n/', $data['questions'])
        ), fn ($text) => $text !== ''));
        if (count($questions) < 1 || count($questions) > 30 || collect($questions)->contains(fn ($q) => mb_strlen($q) > 255)) {
            throw ValidationException::withMessages(['questions' => 'Escribe entre 1 y 30 preguntas, de hasta 255 caracteres cada una.']);
        }
        $data['questions'] = $questions;
        return $data;
    }

    public function store(Request $request)
    {
        // Permission must be checked before exposing validation outcomes for another plant.
        $plantId = (int) $request->input('plant_id');
        LpaAccess::enforce($request->user()->id, $plantId, 'manage');
        $data = $this->validateTemplate($request);
        $template = DB::transaction(function () use ($data) {
            $template = AuditTemplate::create([
                'plant_id' => $data['plant_id'], 'name' => $data['name'],
                'area' => $data['area'], 'level' => $data['level'], 'active' => true,
            ]);
            foreach ($data['questions'] as $i => $text) {
                $template->questions()->create(['text' => $text, 'sort_order' => $i + 1]);
            }
            return $template;
        });
        return redirect()->route('lpa.templates.show', $template)->with('ok', 'Lista guardada.');
    }

    public function show(Request $request, AuditTemplate $template)
    {
        LpaAccess::enforce($request->user()->id, $template->plant_id, 'read');
        $template->load('questions');
        $used = $template->audits()->exists();
        $canManage = LpaAccess::role($request->user()->id, $template->plant_id) === 'supervisor';
        return view('lpa.templates.show', compact('template', 'used', 'canManage'));
    }

    public function edit(Request $request, AuditTemplate $template)
    {
        LpaAccess::enforce($request->user()->id, $template->plant_id, 'manage');
        // Frozen once scheduled: old audits must continue to reference their original questions.
        abort_if($template->audits()->exists(), 409, 'Esta lista ya tiene auditorías. Crea una nueva versión.');
        $template->load('questions');
        $plants = $this->visiblePlants($request)->whereKey($template->plant_id)->get();
        return view('lpa.templates.form', compact('template', 'plants'));
    }

    public function update(Request $request, AuditTemplate $template)
    {
        LpaAccess::enforce($request->user()->id, $template->plant_id, 'manage');
        abort_if($template->audits()->exists(), 409, 'Esta lista ya tiene auditorías. Crea una nueva versión.');
        $data = $this->validateTemplate($request);
        abort_unless((int) $data['plant_id'] === (int) $template->plant_id, 422, 'No se puede mover la lista de planta.');
        DB::transaction(function () use ($template, $data): void {
            $template->update(['name' => $data['name'], 'area' => $data['area'], 'level' => $data['level']]);
            $template->questions()->delete();
            foreach ($data['questions'] as $i => $text) {
                $template->questions()->create(['text' => $text, 'sort_order' => $i + 1]);
            }
        });
        return redirect()->route('lpa.templates.show', $template)->with('ok', 'Lista actualizada.');
    }

    public function toggle(Request $request, AuditTemplate $template)
    {
        LpaAccess::enforce($request->user()->id, $template->plant_id, 'manage');
        $template->update(['active' => ! $template->active]);
        return redirect()->route('lpa.templates.show', $template)->with('ok', 'Estado actualizado.');
    }
}
