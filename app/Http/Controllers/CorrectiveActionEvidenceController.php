<?php

namespace App\Http\Controllers;

use App\Models\CorrectiveAction;
use App\Models\CorrectiveActionEvidence;
use App\Support\LpaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class CorrectiveActionEvidenceController extends Controller
{
    private function authorizeRead(Request $request, CorrectiveAction $action): void
    {
        $audit = $action->answerAudit()->firstOrFail();
        LpaAccess::enforce((int) $request->user()->id, (int) $audit->plant_id, 'read');
    }

    private function authorizeWrite(Request $request, CorrectiveAction $action): void
    {
        $audit = $action->answerAudit()->firstOrFail();
        $role = LpaAccess::role((int) $request->user()->id, (int) $audit->plant_id);
        abort_if($role === null, 404);
        abort_unless($role === 'supervisor'
            || ($role === 'auditor' && (int) $audit->assigned_to === (int) $request->user()->id), 403);
    }

    public function store(Request $request, CorrectiveAction $action): RedirectResponse
    {
        $this->authorizeWrite($request, $action);
        $data = $request->validate([
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $path = null;
        try {
            DB::transaction(function () use ($request, $action, $data, &$path): void {
                $locked = CorrectiveAction::query()->lockForUpdate()->findOrFail($action->id);
                $this->authorizeWrite($request, $locked);
                abort_if($locked->status !== 'open', 409, 'No se pueden agregar evidencias a una acción cerrada.');

                if ($locked->evidences()->count() >= 5) {
                    throw ValidationException::withMessages([
                        'evidence' => 'Esta acción ya cuenta con el máximo de cinco evidencias.',
                    ]);
                }

                $file = $data['evidence'];
                $path = $file->store('lpa/evidence/'.$locked->id, 'local');
                if ($path === false) {
                    throw ValidationException::withMessages(['evidence' => 'No se pudo almacenar el archivo.']);
                }

                $locked->evidences()->create([
                    'uploaded_by' => $request->user()->id,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                    'note' => $data['note'] ?? null,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('ok', 'Evidencia adjuntada a la acción correctiva.');
    }

    public function download(Request $request, CorrectiveActionEvidence $evidence): StreamedResponse
    {
        $action = $evidence->action()->firstOrFail();
        $this->authorizeRead($request, $action);

        abort_unless($evidence->disk === 'local'
            && str_starts_with($evidence->path, 'lpa/evidence/'.$action->id.'/')
            && Storage::disk('local')->exists($evidence->path), 404);

        return Storage::disk('local')->download(
            $evidence->path,
            $evidence->original_name,
            ['X-Content-Type-Options' => 'nosniff']
        );
    }
}
