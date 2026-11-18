<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen Akses Eksternal Pegawai</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar permohonan, proses approval, masa berlaku, dan pencabutan hak akses.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button wire:click="openCreateModal" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-teal-600 hover:bg-teal-700 transition">
                + Tambah Permintaan Akses
            </button>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-800">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama, NIP, atau departemen..."
                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
        </div>
        <div class="w-full sm:w-48">
            <select wire:model.live="statusFilter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-teal-500 focus:border-teal-500">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="revoked">Revoked</option>
            </select>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3 text-left">Pegawai</th>
                        <th class="px-5 py-3 text-left">Departemen</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Expired At</th>
                        <th class="px-5 py-3 text-left">Keycloak & SIMRS</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($requests as $req)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-3.5">
                            <div class="font-bold text-slate-900">{{ $req->employee_name }}</div>
                            <div class="text-xs text-slate-500">NIP: {{ $req->employee_id }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div>{{ $req->department ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $req->position ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($req->status === 'approved')
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">Approved</span>
                            @elseif($req->status === 'pending')
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">Pending</span>
                            @elseif($req->status === 'revoked')
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">Revoked</span>
                            @else
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-800">{{ $req->status }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-xs text-slate-600">
                            {{ $req->expires_at ? $req->expires_at->format('d M Y') : 'Tidak dibatasi' }}
                        </td>
                        <td class="px-5 py-3.5 text-xs">
                            @if($req->mapping)
                                <span class="text-teal-700 font-semibold block">SIMRS ID: {{ $req->mapping->simrs_user_id }}</span>
                                <span class="text-slate-400 block truncate max-w-xs">Sub: {{ $req->mapping->subject }}</span>
                            @else
                                <span class="text-amber-600 italic">Belum dipetakan</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-2">
                            @if($req->status === 'pending')
                                <button wire:click="confirmAction({{ $req->id }}, 'approve')" class="px-2.5 py-1 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700">Setujui</button>
                                <button wire:click="confirmAction({{ $req->id }}, 'reject')" class="px-2.5 py-1 text-xs font-medium rounded bg-slate-200 text-slate-700 hover:bg-slate-300">Tolak</button>
                            @elseif($req->status === 'approved')
                                <button wire:click="confirmAction({{ $req->id }}, 'revoke')" class="px-2.5 py-1 text-xs font-medium rounded bg-rose-600 text-white hover:bg-rose-700">Cabut Akses</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                            Tidak ada data permintaan akses eksternal.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $requests->links() }}
        </div>
    </div>

    <!-- Create Modal -->
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-slate-900">Tambah Permintaan Akses Pegawai</h3>
            
            <div class="space-y-3 text-sm">
                <div>
                    <label class="block font-medium text-slate-700">Nomor Induk Pegawai (NIP)</label>
                    <input wire:model="employee_id" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                    @error('employee_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-slate-700">Nama Lengkap</label>
                    <input wire:model="employee_name" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                    @error('employee_name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block font-medium text-slate-700">Departemen / Unit</label>
                    <input wire:model="department" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block font-medium text-slate-700">Jabatan</label>
                    <input wire:model="position" type="text" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block font-medium text-slate-700">Masa Berlaku Hingga (Opsional)</label>
                    <input wire:model="expires_at" type="date" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>
                <div>
                    <label class="block font-medium text-slate-700">Alasan Permintaan Akses</label>
                    <textarea wire:model="reason" rows="2" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500"></textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-3 border-t">
                <button wire:click="$set('showCreateModal', false)" type="button" class="px-4 py-2 border rounded-lg text-sm text-slate-700 hover:bg-slate-50">Batal</button>
                <button wire:click="createRequest" type="button" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700">Simpan Permintaan</button>
            </div>
        </div>
    </div>
    @endif

    <!-- Action Confirmation Modal -->
    @if($showActionModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-sm w-full p-6 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-slate-900 capitalize">Konfirmasi {{ $actionType }} Akses</h3>
            <p class="text-sm text-slate-600">
                Apakah Anda yakin ingin memproses aksi <strong>{{ $actionType }}</strong> untuk permohonan ini?
            </p>

            @if(in_array($actionType, ['reject', 'revoke']))
            <div>
                <label class="block text-xs font-semibold text-slate-700">Alasan / Catatan (Wajib)</label>
                <textarea wire:model="actionReason" rows="2" required class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></textarea>
            </div>
            @endif

            <div class="flex justify-end space-x-2 pt-3 border-t">
                <button wire:click="$set('showActionModal', false)" class="px-3 py-1.5 border rounded-lg text-sm text-slate-600 hover:bg-slate-50">Batal</button>
                <button wire:click="executeAction" class="px-3 py-1.5 rounded-lg text-sm font-semibold text-white {{ $actionType === 'revoke' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-teal-600 hover:bg-teal-700' }}">
                    Ya, Proses
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

