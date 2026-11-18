<?php

namespace App\Livewire\UserMappings;

use App\Models\ExternalAccessRequest;
use App\Models\SimrsUserMapping;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal Form State
    public bool $showModal = false;
    public ?int $selectedMappingId = null;
    public string $issuer = 'https://sso.rsud-tidar.id/realms/rsud';
    public string $subject = '';
    public string $simrs_user_id = '';
    public ?int $access_request_id = null;
    public string $status = 'active';

    protected function rules(): array
    {
        return [
            'issuer' => 'required|url',
            'subject' => 'required|string|max:255',
            'simrs_user_id' => 'required|string|max:64',
            'access_request_id' => 'nullable|exists:external_access_requests,id',
            'status' => 'required|in:active,suspended,revoked',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->reset(['selectedMappingId', 'subject', 'simrs_user_id', 'access_request_id']);
        $this->issuer = env('KEYCLOAK_BASE_URL', 'https://sso.rsud-tidar.id') . '/realms/' . env('KEYCLOAK_REALM', 'rsud');
        $this->status = 'active';
        $this->showModal = true;
    }

    public function openEditModal(int $id)
    {
        $mapping = SimrsUserMapping::findOrFail($id);
        $this->selectedMappingId = $mapping->id;
        $this->issuer = $mapping->issuer;
        $this->subject = $mapping->subject;
        $this->simrs_user_id = $mapping->simrs_user_id;
        $this->access_request_id = $mapping->access_request_id;
        $this->status = $mapping->status;
        $this->showModal = true;
    }

    public function saveMapping()
    {
        $this->validate();

        // Check unique issuer + subject
        $existsQuery = SimrsUserMapping::where('issuer', $this->issuer)
            ->where('subject', $this->subject);

        if ($this->selectedMappingId) {
            $existsQuery->where('id', '!=', $this->selectedMappingId);
        }

        if ($existsQuery->exists()) {
            $this->addError('subject', 'Kombinasi Issuer dan Subject Keycloak ini sudah dipetakan sebelumnya.');
            return;
        }

        DB::transaction(function () {
            if ($this->selectedMappingId) {
                $mapping = SimrsUserMapping::findOrFail($this->selectedMappingId);
                $mapping->update([
                    'issuer' => $this->issuer,
                    'subject' => $this->subject,
                    'simrs_user_id' => $this->simrs_user_id,
                    'access_request_id' => $this->access_request_id,
                    'status' => $this->status,
                ]);

                AuditLogService::log(
                    eventType: 'mapping.updated',
                    actorType: 'administrator',
                    actorId: (string) Auth::id(),
                    targetUserId: $this->simrs_user_id,
                    description: "Mapping Keycloak sub={$this->subject} ke SIMRS user_id={$this->simrs_user_id} diperbarui.",
                    metadata: ['mapping_id' => $mapping->id, 'status' => $this->status]
                );
            } else {
                $created = SimrsUserMapping::create([
                    'issuer' => $this->issuer,
                    'subject' => $this->subject,
                    'simrs_user_id' => $this->simrs_user_id,
                    'access_request_id' => $this->access_request_id,
                    'status' => $this->status,
                ]);

                AuditLogService::log(
                    eventType: 'mapping.created',
                    actorType: 'administrator',
                    actorId: (string) Auth::id(),
                    targetUserId: $this->simrs_user_id,
                    description: "Mapping Keycloak sub={$this->subject} ke SIMRS user_id={$this->simrs_user_id} dibuat.",
                    metadata: ['mapping_id' => $created->id]
                );
            }
        });

        $this->showModal = false;
        session()->flash('message', 'SIMRS User Mapping berhasil disimpan.');
    }

    public function toggleStatus(int $id)
    {
        $mapping = SimrsUserMapping::findOrFail($id);
        $newStatus = $mapping->status === 'active' ? 'suspended' : 'active';
        $mapping->update(['status' => $newStatus]);

        AuditLogService::log(
            eventType: 'mapping.status_changed',
            actorType: 'administrator',
            actorId: (string) Auth::id(),
            targetUserId: $mapping->simrs_user_id,
            description: "Status mapping user {$mapping->simrs_user_id} diubah menjadi {$newStatus}.",
            metadata: ['mapping_id' => $mapping->id, 'new_status' => $newStatus]
        );

        session()->flash('message', "Status mapping diubah menjadi {$newStatus}.");
    }

    public function render()
    {
        $query = SimrsUserMapping::with('accessRequest')->latest('updated_at');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('simrs_user_id', 'ilike', "%{$this->search}%")
                  ->orWhere('subject', 'ilike', "%{$this->search}%")
                  ->orWhere('issuer', 'ilike', "%{$this->search}%");
            });
        }

        $eligibleRequests = ExternalAccessRequest::where('status', 'approved')->get();

        return view('livewire.user-mappings.index', [
            'mappings' => $query->paginate(10),
            'eligibleRequests' => $eligibleRequests,
        ])->layout('components.layouts.app', ['title' => 'SIMRS User Mapping']);
    }
}
