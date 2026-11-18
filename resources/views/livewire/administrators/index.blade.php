<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Manajemen Administrator Gateway</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola pengguna administrator SEAG, penetapan role (Super Admin, Access Admin, Auditor).</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button wire:click="openCreateModal" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-teal-600 hover:bg-teal-700 transition">
                + Tambah Administrator
            </button>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-800">
            {{ session('message') }}
        </div>
    @endif

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3 text-left">Nama Administrator</th>
                        <th class="px-5 py-3 text-left">Email</th>
                        <th class="px-5 py-3 text-left">Role Otorisasi</th>
                        <th class="px-5 py-3 text-left">Login Terakhir</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($administrators as $admin)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-3.5 font-bold text-slate-900">{{ $admin->name }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $admin->email }}</td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider bg-slate-100 text-slate-800 border">
                                {{ $admin->role }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-slate-500">
                            {{ $admin->last_login_at ? $admin->last_login_at->format('d/m/Y H:i') : 'Belum pernah' }}
                        </td>
                        <td class="px-5 py-3.5">
                            @if($admin->status === 'active')
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">Active</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">Suspended</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <button wire:click="openEditModal({{ $admin->id }})" class="text-xs font-medium text-teal-600 hover:text-teal-800">Edit</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                            Tidak ada data administrator.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $administrators->links() }}
        </div>
    </div>

    <!-- Modal Form -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl space-y-4">
            <h3 class="text-lg font-bold text-slate-900">
                {{ $selectedAdminId ? 'Edit Administrator' : 'Tambah Administrator Baru' }}
            </h3>

            <div class="space-y-3 text-sm">
                <div>
                    <label class="block font-medium text-slate-700">Nama Lengkap</label>
                    <input wire:model="name" type="text" required class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                    @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-slate-700">Alamat Email</label>
                    <input wire:model="email" type="email" required class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                    @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-slate-700">
                        Password {{ $selectedAdminId ? '(Kosongkan jika tidak diubah)' : '' }}
                    </label>
                    <input wire:model="password" type="password" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                    @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-medium text-slate-700">Role Otorisasi</label>
                    <select wire:model="role" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="access_admin">Access Administrator (Kelola Akses Pegawai & Mapping)</option>
                        <option value="security_auditor">Security Auditor (Read-only Audit Log)</option>
                        <option value="super_admin">Super Administrator (Akses Penuh)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-medium text-slate-700">Status Akun</label>
                    <select wire:model="status" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-3 border-t">
                <button wire:click="$set('showModal', false)" type="button" class="px-4 py-2 border rounded-lg text-sm text-slate-700 hover:bg-slate-50">Batal</button>
                <button wire:click="saveAdmin" type="button" class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700">Simpan Administrator</button>
            </div>
        </div>
    </div>
    @endif
</div>

