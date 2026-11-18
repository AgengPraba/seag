<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">SIMRS User Mapping</h1>
            <p class="text-sm text-slate-500 mt-1">Pemetaan identitas stabil OIDC Keycloak (Issuer + Subject) dengan Akun Pengguna SIMRS.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button wire:click="openCreateModal" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-teal-600 hover:bg-teal-700 transition">
                + Tambah Mapping Akun
            </button>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-800">
            {{ session('message') }}
        </div>
    @endif

    <!-- Search -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari SIMRS User ID, Subject Keycloak, atau Realm..."
            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3 text-left">SIMRS User ID</th>
                        <th class="px-5 py-3 text-left">Keycloak Identity (Issuer & Subject)</th>
                        <th class="px-5 py-3 text-left">Tautan Permintaan Akses</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($mappings as $map)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-bold bg-slate-100 text-slate-900 border border-slate-200">
                                {{ $map->simrs_user_id }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="font-mono text-xs text-slate-900 font-semibold truncate max-w-sm">Sub: {{ $map->subject }}</div>
                            <div class="text-[11px] text-slate-400 truncate max-w-sm">Iss: {{ $map->issuer }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-xs">
                            @if($map->accessRequest)
                                <div class="font-semibold text-slate-800">{{ $map->accessRequest->employee_name }}</div>
                                <div class="text-slate-500">NIP: {{ $map->accessRequest->employee_id }} ({{ $map->accessRequest->status }})</div>
                            @else
                                <span class="text-slate-400 italic">Tanpa tautan</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if($map->status === 'active')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">Active</span>
                            @elseif($map->status === 'suspended')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">Suspended</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">{{ $map->status }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-2">
                            <button wire:click="openEditModal({{ $map->id }})" class="text-xs font-medium text-teal-600 hover:text-teal-800">Edit</button>
                            <button wire:click="toggleStatus({{ $map->id }})" class="text-xs font-medium text-slate-600 hover:text-slate-900">
                                {{ $map->status === 'active' ? 'Suspend' : 'Aktifkan' }}
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-400">
                            Belum ada pemetaan identitas Keycloak - SIMRS.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $mappings->links() }}
        </div>
    </div>

    <!-- Modal Form -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-slate-900">
                {{ $selectedMappingId ? 'Edit Pemetaan Identitas' : 'Tambah Pemetaan Identitas Baru' }}
            </h3>

            <div class="space-y-3 text-sm">
                <div>
                    <label class="block font-medium text-slate-700">OIDC Issuer (Keycloak Realm URL)</label>
                    <input wire:model="issuer" type="url" required class="w-full mt-1 px-3 py-2 border rounded-lg text-sm font-mono focus:ring-teal-500 focus:border-teal-500">
                    @error('issuer') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-slate-700">OIDC Subject Identifier (sub UUID Keycloak)</label>
                    <input wire:model="subject" type="text" required placeholder="misal: 74d86b97-9008-410a-8d77-6ef7d31fbe61" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm font-mono focus:ring-teal-500 focus:border-teal-500">
                    @error('subject') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-slate-700">SIMRS User ID Target (app_user.user_id)</label>
                    <input wire:model="simrs_user_id" type="text" required placeholder="User ID pada database SIMRS CI3" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                    @error('simrs_user_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-slate-700">Tautkan ke Permintaan Akses Pegawai</label>
                    <select wire:model="access_request_id" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="">-- Pilih Permintaan Akses (Opsional) --</option>
                        @foreach($eligibleRequests as $er)
                            <option value="{{ $er->id }}">{{ $er->employee_name }} (NIP: {{ $er->employee_id }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-700">Status Pemetaan</label>
                    <select wire:model="status" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="revoked">Revoked</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-3 border-t">
                <button wire:click="$set('showModal', false)" type="button" class="px-4 py-2 border rounded-lg text-sm text-slate-700 hover:bg-slate-50">Batal</button>
                <button wire:click="saveMapping" type="button" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700">Simpan Mapping</button>
            </div>
        </div>
    </div>
    @endif
</div>
