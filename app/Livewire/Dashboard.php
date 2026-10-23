<?php

namespace App\Livewire;

use App\Repositories\SettingsRepository;
use App\Services\AcademicYearService;
use App\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    #[Url(as: 'periode')]
    public ?int $selected_academic_year_id = null;

    public function mount(AcademicYearService $academicYearService): void
    {
        if ($this->selected_academic_year_id === null) {
            $activeYear = $academicYearService->active();
            $this->selected_academic_year_id = $activeYear?->id;
        }
    }

    public function render(
        AcademicYearService $academicYearService,
        DashboardService $dashboardService,
        SettingsRepository $settingsRepository
    ): View {
        $academicYears = $academicYearService->all();
        $teacherName = $settingsRepository->teacherName() ?: 'Guru';
        $schoolName = $settingsRepository->schoolName();

        $setup = $dashboardService->getSetupSteps();
        $summary = $dashboardService->getSummary($this->selected_academic_year_id);
        $priorityClasses = $dashboardService->getPriorityClasses($this->selected_academic_year_id, 5);
        $recentActivities = $dashboardService->getRecentActivities(5);

        return view('livewire.dashboard', [
            'academicYears' => $academicYears,
            'teacherName' => $teacherName,
            'schoolName' => $schoolName,
            'setup' => $setup,
            'summary' => $summary,
            'priorityClasses' => $priorityClasses,
            'recentActivities' => $recentActivities,
        ]);
    }
}
