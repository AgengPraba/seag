<?php

namespace App\Livewire\AccessRequests;

use App\Models\ExternalAccessRequest;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    // Modal Form State
    public bool $showCreateModal = false;
    public string $employee_id = '';
    public string $employee_name = '';
    public string $department = '';
    public string $position = '';
    public ?string $expires_at = null;
    public string $reason = '';

    // Action Confirmation State
    public ?int $selectedRequestId = null;
    public string $actionType = ''; // approve, reject, revoke
    public string $actionReason = '';
    public bool $showActionModal = false;

    protected $rules = [
        'employee_id' => 'required|string|max:64',
        'employee_name' => 'required|string|max:255',
        'department' => 'nullable|string|max:100',
        'position' => 'nullable|string|max:100',
        'expires_at' => 'nullable|date|after:today',
        'reason' => 'nullable|string|max:500',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->reset(['employee_id', 'employee_name', 'department', 'position', 'expires_at', 'reason']);
        $this->showCreateModal = true;
    }

    public function createRequest()
    {
        $this->validate();

        $request = DB::transaction(function () {
            $created = ExternalAccessRequest::create([
                'employee_id' => $this->employee_id,
                'employee_name' => $this->employee_name,
                'department' => $this->department,
                'position' => $this->position,
                'status' => 'pending',
                'requested_by' => Auth::id(),
                'requested_at' => now(),
                'expires_at' => $this->expires_at,
                'reason' => $this->reason,
            ]);

            AuditLogService::log(
                eventType: 'access_request.created',
                actorType: 'administrator',
                actorId: (string) Auth::id(),
                targetUserId: $this->employee_id,
                description: "Permintaan akses eksternal diajukan untuk {$this->employee_name} ({$this->employee_id}).",
                metadata: ['request_id' => $created->id]
            );

            return $created;
        });

        $this->showCreateModal = false;
        session()->flash('message', 'Permintaan akses eksternal berhasil diajukan.');
    }

    public function confirmAction(int $id, string $type)
    {
        $this->selectedRequestId = $id;
        $this->actionType = $type;
        $this->actionReason = '';
        $this->showActionModal = true;
    }

    public function executeAction()
    {
        if (! $this->selectedRequestId) {
            return;
        }

        $request = ExternalAccessRequest::findOrFail($this->selectedRequestId);
        $adminId = Auth::id();

        DB::transaction(function () use ($request, $adminId) {
            if ($this->actionType === 'approve') {
                $request->update([
                    'status' => 'approved',
                    'approved_by' => $adminId,
                    'approved_at' => now(),
                ]);

                AuditLogService::log(
                    eventType: 'access_request.approved',
                    actorType: 'administrator',
                    actorId: (string) $adminId,
                    targetUserId: $request->employee_id,
                    description: "Permintaan akses eksternal untuk {$request->employee_name} disetujui.",
                    metadata: ['request_id' => $request->id]
                );
            } elseif ($this->actionType === 'reject') {
                $request->update([
                    'status' => 'rejected',
                    'rejected_by' => $adminId,
                    'rejected_at' => now(),
                    'reason' => $this->actionReason ?: $request->reason,
                ]);

                AuditLogService::log(
                    eventType: 'access_request.rejected',
                    actorType: 'administrator',
                    actorId: (string) $adminId,
                    targetUserId: $request->employee_id,
                    description: "Permintaan akses eksternal untuk {$request->employee_name} ditolak. Alasan: {$this->actionReason}",
                    metadata: ['request_id' => $request->id, 'reason' => $this->actionReason]
                );
            } elseif ($this->actionType === 'revoke') {
                $request->update([
                    'status' => 'revoked',
                    'revoked_by' => $adminId,
                    'revoked_at' => now(),
                    'reason' => $this->actionReason ?: $request->reason,
                ]);

                // Also suspend associated mapping if present
                if ($request->mapping) {
                    $request->mapping->update(['status' => 'revoked']);
                }

                AuditLogService::log(
                    eventType: 'access.revoked',
                    actorType: 'administrator',
                    actorId: (string) $adminId,
                    targetUserId: $request->employee_id,
                    description: "Hak akses eksternal {$request->employee_name} dicabut. Alasan: {$this->actionReason}",
                    metadata: ['request_id' => $request->id, 'reason' => $this->actionReason]
                );
            }
        });

        $this->showActionModal = false;
        session()->flash('message', "Aksi {$this->actionType} berhasil diproses.");
    }

    public function render()
    {
        $query = ExternalAccessRequest::with(['requestedBy', 'approvedBy', 'mapping'])
            ->latest('created_at');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('employee_name', 'ilike', "%{$this->search}%")
                  ->orWhere('employee_id', 'ilike', "%{$this->search}%")
                  ->orWhere('department', 'ilike', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.access-requests.index', [
            'requests' => $query->paginate(10),
        ])->layout('components.layouts.app', ['title' => 'Manajemen Akses Eksternal']);
    }
}
