@extends('layouts.app')

@section('title', 'PickUp Hub | Station Admin Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <span class="bg-hub-50 text-hub-700 border border-hub-200 text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">Control Panel</span>
            <h1 class="text-2xl font-bold text-slate-900 mt-1 flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-hub-600"></i> Station Admin Dashboard
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage live manifests, reverse logistics, audit snapshots, and operator security controls.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="/app/" target="_blank" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-medium px-3.5 py-2 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-box-open"></i> Open Station PWA Scanner
            </a>
            <a href="{{ route('admin.historical.master') }}" class="bg-white border border-slate-300 hover:border-slate-400 text-slate-700 text-xs font-medium px-3.5 py-2 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left"></i> Historical Master Lists
            </a>
            <a href="{{ route('admin.stations.index') }}" class="bg-hub-600 hover:bg-hub-700 text-white text-xs font-medium px-3.5 py-2 rounded-lg shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-building-shield"></i> Manage Stations
            </a>
        </div>
    </div>

    <!-- SESSION FEEDBACK ALERTS -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="text-emerald-600 hover:text-emerald-800 font-bold" onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs flex justify-between items-center shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-600 text-sm"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" class="text-red-600 hover:text-red-800 font-bold" onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif

    <!-- QUICK ACCESS METRIC / NAVIGATION CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Returns & Jumia Reverse Logistics Portal -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-rose-300 transition group">
            <div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Returns & OTP Portal</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Manage packages returning to Jumia network, assign pickup agents, and verify OTP codes.</p>
            </div>
            <a href="{{ route('admin.returns') }}" class="mt-6 w-full bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white text-xs font-semibold py-2 px-3 rounded-lg text-center transition flex items-center justify-center gap-1.5">
                <span>Open Returns Portal</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <!-- Published & Locked Audit Logs -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-emerald-300 transition group">
            <div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Published Audit Logs</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">View immutable, locked historical daily scan snapshots for auditing and compliance.</p>
            </div>
            <a href="{{ route('admin.audit.logs') }}" class="mt-6 w-full bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white text-xs font-semibold py-2 px-3 rounded-lg text-center transition flex items-center justify-center gap-1.5">
                <span>View Audit Logs</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <!-- Delivery Notes / Waybills -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-blue-300 transition group">
            <div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Delivery Notes / Waybills</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Upload and review scanned waybills and proofs of dispatch for station packages.</p>
            </div>
            <a href="{{ route('admin.delivery-notes.index') }}" class="mt-6 w-full bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white text-xs font-semibold py-2 px-3 rounded-lg text-center transition flex items-center justify-center gap-1.5">
                <span>View Delivery Notes</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <!-- Customer SMS Notification History -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-amber-300 transition group">
            <div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg mb-4 group-hover:scale-110 transition">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">SMS Reminder Logs</h3>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">Track automated customer reminder messages, expiration alerts, and delivery statuses.</p>
            </div>
            <a href="{{ route('admin.sms.logs') }}" class="mt-6 w-full bg-amber-50 hover:bg-amber-500 text-amber-800 hover:text-white text-xs font-semibold py-2 px-3 rounded-lg text-center transition flex items-center justify-center gap-1.5">
                <span>View SMS History</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

    </div>

    <!-- ADMIN BATCH SAVE & PERMANENT ARCHIVE CARD -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-box-archive text-amber-600"></i> Admin Batch Save & Permanent Archive
            </h3>
            <p class="text-xs text-slate-500 mt-1">Save current active scans for future reference. Once saved, records become immutable and the active board resets to a blank page for your next import.</p>
        </div>
        <form action="{{ route('admin.batch.save') }}" method="POST" onsubmit="return confirm('Are you sure you want to save and lock this batch? This will clear active records from the PWA, archive the active master list, and lock records permanently.');">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-8">
                    <label class="block text-xs font-medium text-slate-700 uppercase tracking-wider mb-1.5">Select Station Batch to Archive & Reset</label>
                    <select name="station_id" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-hub-500 focus:border-hub-500 bg-white" required>
                        <option value="" disabled selected>Select station...</option>
                        @foreach($stations as $station)
                            <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-4 rounded-lg text-xs shadow-sm transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>Save Batch & Reset Page</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- MASTER LIST REAL-TIME MANAGEMENT & RESET CARD -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-hub-600"></i> Station Master List & Real-Time Reset
                </h3>
                <p class="text-xs text-slate-500 mt-1">Upload, view active expected packages in real-time, or clear/reset the master list once scans are completed.</p>
            </div>
            <div>
                <a href="{{ route('admin.scan.logs') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-2 rounded-lg transition">
                    <i class="fa-solid fa-scroll"></i> Retrieve Historical Scan Logs
                </a>
            </div>
        </div>

        <div id="masterListAlert" class="hidden rounded-xl p-3 text-xs mb-4"></div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Station Selector & Upload Box -->
            <div class="lg:col-span-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 uppercase tracking-wider mb-1.5">Select Station to Inspect</label>
                    <select id="masterStationSelect" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-hub-500 bg-white">
                        <option value="" disabled selected>Select station...</option>
                        @foreach($stations as $station)
                            <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                    <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-file-arrow-up text-hub-600"></i> Upload New Master List (CSV)
                    </h4>
                    <form id="masterUploadForm" action="{{ route('admin.master.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="hidden" name="station_id" id="formStationId">
                        <div>
                            <input type="file" name="file" class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-hub-50 file:text-hub-700 hover:file:bg-hub-100 cursor-pointer border border-slate-300 rounded-lg bg-white" accept=".csv,.txt" required>
                        </div>
                        <button type="submit" class="w-full bg-hub-600 hover:bg-hub-700 text-white font-medium py-2 px-3 rounded-lg text-xs shadow-sm transition">
                            Save & Import Master List
                        </button>
                    </form>

                    <button id="clearMasterBtn" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-700 font-medium py-2 px-3 rounded-lg text-xs border border-rose-200 transition flex items-center justify-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Clear / Reset Master List (Blank)</span>
                    </button>
                </div>
            </div>

            <!-- Real-Time Master List Preview Table -->
            <div class="lg:col-span-8 flex flex-col">
                <label class="block text-xs font-medium text-slate-700 uppercase tracking-wider mb-1.5">
                    Live Master List Preview (<span id="masterCount" class="font-bold text-hub-600">0</span> items loaded)
                </label>
                <div class="flex-1 border border-slate-200 rounded-xl bg-white overflow-hidden flex flex-col min-h-[250px]">
                    <div class="overflow-x-auto flex-1 max-h-[320px]">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider border-b border-slate-200 sticky top-0">
                                <tr>
                                    <th class="p-3">#</th>
                                    <th class="p-3">Tracking Number</th>
                                    <th class="p-3">Hub</th>
                                    <th class="p-3">Aging</th>
                                    <th class="p-3">Arrival Date</th>
                                </tr>
                            </thead>
                            <tbody id="masterTableBody" class="divide-y divide-slate-100 text-slate-700">
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400">Select a station above to view its live master list.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PUBLISH & LOCK AUDIT LOG CARD -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h3 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
            <i class="fa-solid fa-lock text-slate-700"></i> Publish & Lock Daily Audit Log
        </h3>
        <p class="text-xs text-slate-500 mb-4">Publishing locks the scan logs for this date, making them permanently read-only and preventing any modifications or deletions.</p>
        
        <form action="{{ route('admin.audit.publish') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-9">
                    <label class="block text-xs font-medium text-slate-700 uppercase tracking-wider mb-1.5">Select Audit Date to Lock</label>
                    <input type="date" name="audit_date" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-hub-500 bg-white" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-4 rounded-lg text-xs shadow-sm transition">
                        Lock & Publish Audit Log
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- STATION SECURITY & ACCESS CONTROL MANAGEMENT CARD -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <div class="mb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-shield-cat text-hub-600"></i> Station Security & Access Controls
            </h3>
            <p class="text-xs text-slate-500 mt-1">Configure restriction policies for scanner PWA operators across one, multiple, or all distribution stations.</p>
        </div>

        <div id="securityAlert" class="hidden rounded-xl p-3 text-xs mb-4"></div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
            <div class="md:col-span-5 space-y-2">
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-medium text-slate-700 uppercase tracking-wider">Select Target Station(s)</label>
                    <div class="flex items-center gap-1.5">
                        <input class="w-3.5 h-3.5 text-hub-600 rounded border-slate-300 focus:ring-hub-500 cursor-pointer" type="checkbox" id="selectAllStations">
                        <label for="selectAllStations" class="text-xs text-slate-500 cursor-pointer select-none">Select All</label>
                    </div>
                </div>
                <select id="securityStationSelect" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-hub-500 bg-white" multiple size="5">
                    @foreach($stations as $station)
                        <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400">Hold Ctrl (Windows) or Cmd (Mac) to select multiple stations.</p>
            </div>

            <div class="md:col-span-7 space-y-4">
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-4">
                    <div>
                        <h4 class="text-xs font-bold text-slate-800">Disable Manual Type-in for Normal Users</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Prevents station operators from typing tracking numbers manually. Forces physical barcode/QR scanning across selected hubs.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="disableManualToggle" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-hub-600"></div>
                    </label>
                </div>

                <button id="saveSecurityBtn" class="w-full bg-hub-600 hover:bg-hub-700 text-white font-medium py-2.5 px-4 rounded-lg text-xs shadow-sm transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save & Broadcast Security Changes</span>
                </button>
            </div>
        </div>
    </div>

</div> 

<script> 
document.addEventListener('DOMContentLoaded', () => { 
    const stationSelect = document.getElementById('masterStationSelect'); 
    const formStationId = document.getElementById('formStationId'); 
    const masterTableBody = document.getElementById('masterTableBody'); 
    const masterCount = document.getElementById('masterCount'); 
    const clearMasterBtn = document.getElementById('clearMasterBtn'); 
    const masterListAlert = document.getElementById('masterListAlert'); 

    function showMasterAlert(message, type = 'success') { 
        masterListAlert.className = type === 'success' 
            ? 'rounded-xl p-3 text-xs mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-2'
            : 'rounded-xl p-3 text-xs mb-4 bg-red-50 border border-red-200 text-red-800 flex items-center gap-2';
        masterListAlert.innerHTML = `<i class="fa-solid fa-circle-${type === 'success' ? 'check' : 'exclamation'} text-sm"></i> <span>${message}</span>`;
        masterListAlert.classList.remove('hidden'); 
        setTimeout(() => masterListAlert.classList.add('hidden'), 4000); 
    } 

    async function fetchMasterListDetails(stationId) { 
        formStationId.value = stationId; 
        clearMasterBtn.disabled = false; 
        try { 
            const res = await fetch(`/api/admin/stations/${stationId}/master-list/items`); 
            if (res.ok) { 
                const data = await res.json(); 
                const items = data.master_list || []; 
                masterCount.textContent = items.length; 
                if (items.length === 0) { 
                    masterTableBody.innerHTML = `<tr><td colspan="5" class="p-6 text-center text-slate-400">No master list items found. Station is currently blank.</td></tr>`; 
                    return; 
                } 
                masterTableBody.innerHTML = items.map((m, idx) => ` 
                    <tr class="hover:bg-slate-50 transition"> 
                        <td class="p-3 text-slate-500">${idx + 1}</td> 
                        <td class="p-3 font-mono font-bold text-slate-800">${m.tracking_number}</td> 
                        <td class="p-3 text-slate-600">${m.hub || 'KE-3PL-Flexcom'}</td> 
                        <td class="p-3 text-slate-600">${m.aging || 0} Days</td> 
                        <td class="p-3 text-slate-400">${m.arrival_date || '-'}</td> 
                    </tr> 
                `).join(''); 
            } 
        } catch (e) { 
            showMasterAlert('Failed to fetch real-time master list.', 'danger'); 
        } 
    } 

    stationSelect.addEventListener('change', (e) => { 
        fetchMasterListDetails(e.target.value); 
    }); 

    clearMasterBtn.addEventListener('click', async () => { 
        const stationId = stationSelect.value; 
        if (!stationId) return; 
        if (!confirm('Are you sure you want to clear and reset this station master list to a blank page? This cannot be undone.')) { 
            return; 
        } 
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; 
        clearMasterBtn.disabled = true; 
        clearMasterBtn.textContent = 'Clearing & Resetting...'; 
        try { 
            const res = await fetch(`/api/admin/stations/${stationId}/master-list/clear`, { 
                method: 'DELETE', 
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken 
                } 
            }); 
            if (res.ok) { 
                showMasterAlert('Master list successfully cleared and reset to blank.', 'success'); 
                fetchMasterListDetails(stationId); 
            } else { 
                throw new Error('Failed to reset'); 
            } 
        } catch (e) { 
            showMasterAlert('Error clearing master list.', 'danger'); 
        } finally { 
            clearMasterBtn.disabled = false; 
            clearMasterBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i> <span>Clear / Reset Master List (Blank)</span>'; 
        } 
    }); 

    const securityStationSelect = document.getElementById('securityStationSelect'); 
    const selectAllCheckbox = document.getElementById('selectAllStations'); 
    const disableManualToggle = document.getElementById('disableManualToggle'); 
    const saveSecurityBtn = document.getElementById('saveSecurityBtn'); 
    const securityAlert = document.getElementById('securityAlert'); 

    function showSecurityAlert(message, type = 'success') { 
        securityAlert.className = type === 'success' 
            ? 'rounded-xl p-3 text-xs mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-2'
            : 'rounded-xl p-3 text-xs mb-4 bg-red-50 border border-red-200 text-red-800 flex items-center gap-2';
        securityAlert.innerHTML = `<i class="fa-solid fa-circle-${type === 'success' ? 'check' : 'exclamation'} text-sm"></i> <span>${message}</span>`;
        securityAlert.classList.remove('hidden'); 
        setTimeout(() => securityAlert.classList.add('hidden'), 4000); 
    } 

    selectAllCheckbox.addEventListener('change', (e) => { 
        const isChecked = e.target.checked; 
        Array.from(securityStationSelect.options).forEach(opt => { 
            opt.selected = isChecked; 
        }); 
    }); 

    saveSecurityBtn.addEventListener('click', async () => { 
        const selectedOptions = Array.from(securityStationSelect.selectedOptions); 
        if (selectedOptions.length === 0 && !selectAllCheckbox.checked) { 
            showSecurityAlert('Please select at least one station or check "Select All".', 'danger'); 
            return; 
        } 
        const stationIds = selectAllCheckbox.checked ? 'all' : selectedOptions.map(opt => opt.value); 
        const isChecked = disableManualToggle.checked; 
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; 
        saveSecurityBtn.disabled = true; 
        saveSecurityBtn.textContent = 'Applying & Broadcasting...'; 
        try { 
            const res = await fetch(`/api/admin/stations/settings`, { 
                method: 'PUT', 
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken 
                }, 
                body: JSON.stringify({ station_ids: stationIds, disable_manual: isChecked }) 
            }); 
            if (res.ok) { 
                const data = await res.json(); 
                showSecurityAlert(data.message || 'Security settings successfully applied and broadcasted!', 'success'); 
            } else { 
                throw new Error('Server response error'); 
            } 
        } catch (e) { 
            showSecurityAlert('Failed to save security configuration to server.', 'danger'); 
        } finally { 
            saveSecurityBtn.disabled = false; 
            saveSecurityBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>Save & Broadcast Security Changes</span>'; 
        } 
    }); 
}); 
</script> 
@endsection