<?php

namespace App\Livewire\AuditLogs;

use App\Models\AccessAuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $eventTypeFilter = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = AccessAuditLog::latest('created_at');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'ilike', "%{$this->search}%")
                  ->orWhere('event_type', 'ilike', "%{$this->search}%")
                  ->orWhere('target_user_id', 'ilike', "%{$this->search}%")
                  ->orWhere('source_ip', 'ilike', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('event_status', $this->statusFilter);
        }

        if ($this->eventTypeFilter) {
            $query->where('event_type', $this->eventTypeFilter);
        }

        $eventTypes = AccessAuditLog::select('event_type')->distinct()->pluck('event_type');

        return view('livewire.audit-logs.index', [
            'logs' => $query->paginate(15),
            'eventTypes' => $eventTypes,
        ])->layout('components.layouts.app', ['title' => 'Audit Log Keamanan']);
    }
}
