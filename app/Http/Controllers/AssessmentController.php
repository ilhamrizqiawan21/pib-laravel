<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Services\AcademicYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $mode = $request->cookie('pib_assessment_mode', 'setoran');
        $target = $mode === 'materi' ? 'penilaian.materi' : 'penilaian.setoran';

        return redirect()->route($target);
    }

    public function setoran(Request $request, AcademicYearService $academicYearService): View
    {
        $activeYear = $academicYearService->active();

        $classes = SchoolClass::query()
            ->with('academicYear')
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('penilaian.setoran', [
            'classes' => $classes,
            'initialClassId' => $request->query('classId'),
            'initialStudentId' => $request->query('studentId'),
        ]);
    }

    public function materi(Request $request, AcademicYearService $academicYearService): View
    {
        $activeYear = $academicYearService->active();

        $classes = SchoolClass::query()
            ->with('academicYear')
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('penilaian.materi', [
            'classes' => $classes,
            'initialClassId' => $request->query('classId'),
            'initialAssessmentId' => $request->query('assessmentId'),
        ]);
    }
}
