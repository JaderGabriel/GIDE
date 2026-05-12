<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Enrichment\StudentEnrichmentService;
use App\Services\Timeline\StudentTimelineService;
use Illuminate\Http\Request;

class StudentTimelineController extends Controller
{
    public function show(Request $request, int $codAluno)
    {
        $service = new StudentTimelineService;
        $timeline = $service->getTimeline($codAluno, 100);
        $studentData = $service->getStudentData($codAluno);

        $typeFilter = $request->query('type');
        if ($typeFilter && $typeFilter !== 'all') {
            $timeline = $timeline->where('type', $typeFilter)->values();
        }

        return view('admin.student_timeline', [
            'codAluno' => $codAluno,
            'studentData' => $studentData,
            'timeline' => $timeline,
            'typeFilter' => $typeFilter ?? 'all',
        ]);
    }

    public function refresh(Request $request, int $codAluno)
    {
        $data = (new StudentEnrichmentService)->refresh($codAluno);

        if ($data === null) {
            return redirect()
                ->route('admin.student-timeline', ['cod_aluno' => $codAluno])
                ->with('status', 'Não foi possível buscar dados no iEducar. Verifique se a integração iEducar está habilitada e acessível.')
                ->with('status_level', 'error');
        }

        $hasFields = collect($data)->except('cod_aluno')->filter()->isNotEmpty();

        return redirect()
            ->route('admin.student-timeline', ['cod_aluno' => $codAluno])
            ->with('status', $hasFields
                ? 'Dados do aluno atualizados do iEducar.'
                : 'iEducar respondeu, mas não retornou campos esperados (nome, turma, etc.). Verifique a matrícula do aluno.')
            ->with('status_level', $hasFields ? 'success' : 'warning');
    }
}
