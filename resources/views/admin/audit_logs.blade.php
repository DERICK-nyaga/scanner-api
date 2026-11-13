@extends('layouts.app')

@section('title', 'PickUp Hub | Published Audit Logs')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">Compliance & Security</span>
            <h1 class="text-2xl font-bold text-slate-900 mt-1 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-emerald-600"></i> Published & Locked Audit Logs
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">View immutable, locked historical daily scan snapshots for auditing, compliance, and tamper-proof verification.</p>
        </div>
        <div>
            <a href="{{ route('admin.dashboard') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium px-3.5 py-2 rounded-lg transition flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- MAIN DATA TABLE CARD -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-lock text-slate-700"></i> Locked Audit Snapshots
            </h3>
            <span class="bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">
                Read-Only / Locked
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Station ID</th>
                        <th class="p-4">Audit Date</th>
                        <th class="p-4">Total Scanned Snapshots</th>
                        <th class="p-4">Published At</th>
                        <th class="p-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($publishedLogs as $log)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4 font-mono font-bold text-slate-900">#{{ $log->id }}</td>
                        <td class="p-4 text-slate-600">Station #{{ $log->station_id }}</td>
                        <td class="p-4 font-semibold text-slate-900">{{ $log->audit_date }}</td>
                        <td class="p-4">
                            <span class="bg-slate-100 text-slate-700 border border-slate-200 font-medium px-2.5 py-1 rounded-full text-[11px]">
                                {{ count($log->snapshot_data ?? []) }} items
                            </span>
                        </td>
                        <td class="p-4 text-slate-400">{{ $log->created_at }}</td>
                        <td class="p-4 text-right">
                            <button type="button" onclick="openSnapshotModal('snapshotModal{{ $log->id }}')" class="bg-hub-50 hover:bg-hub-600 text-hub-700 hover:text-white font-medium py-1.5 px-3 rounded-lg border border-hub-200 transition text-xs shadow-sm inline-flex items-center gap-1.5">
                                <i class="fa-solid fa-eye text-[10px]"></i>
                                <span>View Snapshot</span>
                            </button>
                        </td>
                    </tr>

                    <!-- Snapshot Detail Modal (Tailwind Modal) -->
                    <div id="snapshotModal{{ $log->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
                            <div class="bg-slate-900 text-white p-5 flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                                    <h3 class="text-sm font-bold">Audit Snapshot Details - {{ $log->audit_date }}</h3>
                                </div>
                                <button type="button" onclick="closeSnapshotModal('snapshotModal{{ $log->id }}')" class="text-slate-400 hover:text-white">
                                    <i class="fa-solid fa-xmark text-base"></i>
                                </button>
                            </div>
                            <div class="p-6 space-y-4 text-left">
                                <div class="bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-xl text-xs flex items-center gap-2">
                                    <i class="fa-solid fa-lock text-amber-600"></i>
                                    <span>This record is permanently locked and cannot be edited or deleted.</span>
                                </div>
                                <div class="bg-slate-900 text-emerald-400 font-mono text-[11px] p-4 rounded-xl max-h-72 overflow-y-auto border border-slate-800 shadow-inner">
                                    <pre><code>{{ json_encode($log->snapshot_data, JSON_PRETTY_PRINT) }}</code></pre>
                                </div>
                                <button type="button" onclick="closeSnapshotModal('snapshotModal{{ $log->id }}')" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-2.5 rounded-lg text-xs transition">
                                    Close Window
                                </button>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center text-slate-400 italic">
                            No audit logs published and locked yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Control Script -->
<script>
    function openSnapshotModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
    }
    function closeSnapshotModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }
</script>
@endsection