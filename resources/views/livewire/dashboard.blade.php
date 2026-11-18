<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard Monitoring Akses Eksternal</h1>
            <p class="text-sm text-slate-500 mt-1">Status integrasi, permohonan akses eksternal, dan aktivitas sistem terkini.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            @if(auth()->user()->isAccessAdmin())
            <a href="{{ route('access-requests.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-teal-600 hover:bg-teal-700 transition">
                Kelola Akses Eksternal
            </a>
            @endif
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Pengajuan</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-slate-900">{{ $totalRequests }}</span>
                <span class="text-xs font-medium text-slate-400">Pegawai</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-teal-700 uppercase tracking-wider block">Akses Aktif (Approved)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-teal-600">{{ $approvedRequests }}</span>
                <span class="text-xs font-medium text-teal-600 font-semibold">Memiliki Izin</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block">Menunggu Approval</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-amber-500">{{ $pendingRequests }}</span>
                <span class="text-xs font-medium text-amber-600 font-semibold">Pending</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider block">Akses Dicabut (Revoked)</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-3xl font-extrabold text-rose-500">{{ $revokedRequests }}</span>
                <span class="text-xs font-medium text-rose-600 font-semibold">Non-Aktif</span>
            </div>
        </div>
    </div>

    <!-- Recent Tables Split Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Permintaan Akses Terkini -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-900">Permintaan Akses Terkini</h3>
                @if(auth()->user()->isAccessAdmin())
                <a href="{{ route('access-requests.index') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700">Lihat Semua &rarr;</a>
                @endif
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentRequests as $req)
                <div class="px-5 py-3.5 flex items-center justify-between text-sm">
                    <div>
                        <div class="font-semibold text-slate-800">{{ $req->employee_name }}</div>
                        <div class="text-xs text-slate-500">NIP: {{ $req->employee_id }} &bull; {{ $req->department ?? 'Umum' }}</div>
                    </div>
                    <div>
                        @if($req->status === 'approved')
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">Approved</span>
                        @elseif($req->status === 'pending')
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">Pending</span>
                        @elseif($req->status === 'revoked')
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-rose-100 text-rose-800">Revoked</span>
                        @else
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-800">{{ $req->status }}</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-slate-500">
                    Belum ada data pengajuan akses eksternal.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Log Audit Terkini -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-900">Audit Aktivitas Keamanan Terkini</h3>
                @if(auth()->user()->isSecurityAuditor())
                <a href="{{ route('audit-logs.index') }}" class="text-xs font-medium text-teal-600 hover:text-teal-700">Lihat Semua &rarr;</a>
                @endif
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($recentAudits as $log)
                <div class="px-5 py-3.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800 tracking-wide uppercase font-mono">{{ $log->event_type }}</span>
                        <span class="text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-slate-600 mt-1">{{ $log->description }}</p>
                    <div class="mt-1 text-[11px] text-slate-400">IP: {{ $log->source_ip ?? '127.0.0.1' }} &bull; Status: <span class="font-medium {{ $log->event_status === 'success' ? 'text-emerald-600' : 'text-rose-600' }}">{{ $log->event_status }}</span></div>
                </div>
                @empty
                <div class="px-5 py-6 text-center text-sm text-slate-500">
                    Belum ada catatan aktivitas keamanan.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

