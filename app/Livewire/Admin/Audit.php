<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Riwayat Audit'])]
class Audit extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $entity = '';

    #[Url]
    public string $action = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEntity(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'entity', 'action']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = AuditLog::with('user');

        if ($this->entity !== '') {
            $query->where('entity', $this->entity);
        }

        if ($this->action !== '') {
            $query->where('action', $this->action);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('summary', 'LIKE', '%'.$this->search.'%')
                    ->orWhere('entity_id', 'LIKE', '%'.$this->search.'%')
                    ->orWhereHas('user', function ($u) {
                        $u->where('name', 'LIKE', '%'.$this->search.'%')
                            ->orWhere('email', 'LIKE', '%'.$this->search.'%');
                    });
            });
        }

        $logs = $query->orderByDesc('id')->paginate(25);

        // Ambil daftar entitas unik dan aksi unik untuk dropdown filter
        $availableEntities = [
            'score' => 'Nilai',
            'student' => 'Siswa',
            'class' => 'Kelas',
            'curriculum' => 'Kurikulum',
            'backup' => 'Backup',
            'restore' => 'Restore',
            'user' => 'Pengguna',
            'auth' => 'Autentikasi',
            'setting' => 'Pengaturan',
        ];

        $availableActions = [
            'CREATE' => 'CREATE (Tambah)',
            'UPDATE' => 'UPDATE (Ubah)',
            'DELETE' => 'DELETE (Hapus)',
            'UPSERT' => 'UPSERT (Simpan Nilai)',
            'CLEAR' => 'CLEAR (Kosongkan)',
            'SNAPSHOT' => 'SNAPSHOT (Backup)',
            'RESTORE' => 'RESTORE (Pulihkan)',
            'FAILED' => 'FAILED (Gagal)',
            'LOGIN_FAILED' => 'LOGIN_FAILED (Gagal Masuk)',
            'STATUS_CHANGE' => 'STATUS_CHANGE (Status)',
            'ROLE_CHANGE' => 'ROLE_CHANGE (Peran)',
            'RESET_PASSWORD' => 'RESET_PASSWORD (Sandi)',
        ];

        return view('livewire.admin.audit', [
            'logs' => $logs,
            'availableEntities' => $availableEntities,
            'availableActions' => $availableActions,
        ]);
    }
}
