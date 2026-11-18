<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Audit Log Keamanan</h1>
        <p class="text-sm text-slate-500 mt-1">Jejak aktivitas keamanan, autentikasi, verifikasi eligibility, dan perubahan kebijakan akses.</p>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari deskripsi, event, target user ID, atau IP..."
                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
        </div>
        <div class="w-full sm:w-48">
            <select wire:model.live="statusFilter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                <option value="">Semua Status</option>
                <option value="success">Success</option>
                <option value="failed">Failed</option>
                <option value="denied">Denied</option>
            </select>
        </div>
        <div class="w-full sm:w-56">
            <select wire:model.live="eventTypeFilter" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-teal-500 focus:border-teal-500">
                <option value="">Semua Tipe Event</option>
                @foreach($eventTypes as $et)
                    <option value="{{ $et }}">{{ $et }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3 text-left">Waktu</th>
                        <th class="px-5 py-3 text-left">Event Type</th>
                        <th class="px-5 py-3 text-left">Aktor & Target</th>
                        <th class="px-5 py-3 text-left">Deskripsi</th>
                        <th class="px-5 py-3 text-left">IP / Origin</th>
                        <th class="px-5 py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-mono text-xs">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-3 whitespace-nowrap text-slate-500 font-sans">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-5 py-3 whitespace-nowrap">
                            <span class="font-bold text-slate-800 uppercase tracking-wide bg-slate-100 px-2 py-0.5 rounded">
                                {{ $log->event_type }}
                            </span>
                        </td>
                        <td class="px-5 py-3 font-sans">
                            <div class="text-slate-900 font-medium">Aktor: {{ $log->actor_type }}:{{ $log->actor_id ?? '-' }}</div>
                            <div class="text-[11px] text-slate-400">Target: {{ $log->target_user_id ?? '-' }}</div>
                        </td>
                        <td class="px-5 py-3 font-sans max-w-md">
                            <p class="text-slate-700 truncate" title="{{ $log->description }}">{{ $log->description }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-500">
                            {{ $log->source_ip ?? '127.0.0.1' }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if($log->event_status === 'success')
                                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-emerald-100 text-emerald-800 font-sans">SUCCESS</span>
                            @elseif($log->event_status === 'denied')
                                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-rose-100 text-rose-800 font-sans">DENIED</span>
                            @else
                                <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-amber-100 text-amber-800 font-sans">{{ strtoupper($log->event_status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-8 text-center text-slate-400 font-sans">
                            Belum ada catatan audit log.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>

