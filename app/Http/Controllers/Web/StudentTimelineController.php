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
        (new StudentEnrichmentService)->refresh($codAluno);

        return redirect()
            ->route('admin.student-timeline', ['cod_aluno' => $codAluno])
            ->with('status', 'Dados do aluno atualizados do iEducar.')
            ->with('status_level', 'success');
    }
}
