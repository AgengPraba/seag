<?php

namespace App\Livewire\Administrators;

use App\Models\Administrator;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal State
    public bool $showModal = false;
    public ?int $selectedAdminId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'access_admin';
    public string $status = 'active';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->reset(['selectedAdminId', 'name', 'email', 'password']);
        $this->role = 'access_admin';
        $this->status = 'active';
        $this->showModal = true;
    }

    public function openEditModal(int $id)
    {
        $admin = Administrator::findOrFail($id);
        $this->selectedAdminId = $admin->id;
        $this->name = $admin->name;
        $this->email = $admin->email;
        $this->password = '';
        $this->role = $admin->role;
        $this->status = $admin->status;
        $this->showModal = true;
    }

    public function saveAdmin()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:administrators,email,' . ($this->selectedAdminId ?? 'NULL'),
            'role' => 'required|in:super_admin,access_admin,security_auditor',
            'status' => 'required|in:active,suspended',
        ];

        if (! $this->selectedAdminId) {
            $rules['password'] = 'required|min:8';
        } else {
            $rules['password'] = 'nullable|min:8';
        }

        $this->validate($rules);

        if ($this->selectedAdminId) {
            $admin = Administrator::findOrFail($this->selectedAdminId);
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
                'status' => $this->status,
            ];

            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            $admin->update($data);

            AuditLogService::log(
                eventType: 'admin.updated',
                actorType: 'administrator',
                actorId: (string) Auth::id(),
                targetUserId: (string) $admin->id,
                description: "Akun administrator {$admin->name} ({$admin->email}) diperbarui.",
                metadata: ['role' => $this->role, 'status' => $this->status]
            );
        } else {
            $created = Administrator::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'status' => $this->status,
            ]);

            AuditLogService::log(
                eventType: 'admin.created',
                actorType: 'administrator',
                actorId: (string) Auth::id(),
                targetUserId: (string) $created->id,
                description: "Administrator baru {$created->name} ({$created->email}, {$created->role}) didaftarkan.",
                metadata: ['role' => $this->role]
            );
        }

        $this->showModal = false;
        session()->flash('message', 'Data administrator berhasil disimpan.');
    }

    public function render()
    {
        $query = Administrator::latest('created_at');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'ilike', "%{$this->search}%")
                  ->orWhere('email', 'ilike', "%{$this->search}%")
                  ->orWhere('role', 'ilike', "%{$this->search}%");
            });
        }

        return view('livewire.administrators.index', [
            'administrators' => $query->paginate(10),
        ])->layout('components.layouts.app', ['title' => 'Manajemen Administrator']);
    }
}

