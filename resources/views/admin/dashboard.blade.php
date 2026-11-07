@extends('layouts.app') 

@section('content') 
<div class="container py-4"> 
    <div class="d-flex justify-content-between align-items-center mb-4"> 
        <h2>📊 Station Admin Dashboard</h2> 
        <div class="d-flex gap-2"> 
            <a href="/app/" target="_blank" class="btn btn-outline-primary">📦 Open Station PWA Scanner</a> 
            <a href="{{ route('admin.historical.master') }}" class="btn btn-outline-secondary">📋 Historical Master Lists</a> 
            <a href="{{ route('admin.stations.index') }}" class="btn btn-primary">🏢 Manage Stations</a> 
        </div> 
    </div> 

    <!-- Session Feedback Alerts --> 
    @if(session('success')) 
        <div class="alert alert-success py-2 mb-4">{{ session('success') }}</div> 
    @endif 
    @if(session('error')) 
        <div class="alert alert-danger py-2 mb-4">{{ session('error') }}</div> 
    @endif 

    <!-- Quick Access Metric / Navigation Cards --> 
    <div class="row g-4 mb-5"> 
        <!-- Returns & Jumia Reverse Logistics Portal --> 
        <div class="col-md-3"> 
            <div class="card shadow-sm border-0 h-100 bg-white"> 
                <div class="card-body flex-column d-flex justify-content-between"> 
                    <div> 
                        <h5 class="card-title text-danger">🔄 Returns & OTP Portal</h5> 
                        <p class="card-text text-muted small">Manage packages returning to Jumia network, assign pickup agents, and verify OTP codes.</p> 
                    </div> 
                    <a href="{{ route('admin.returns') }}" class="btn btn-sm btn-danger w-100 mt-2">Open Returns Portal</a> 
                </div> 
            </div> 
        </div> 

        <!-- Published & Locked Audit Logs --> 
        <div class="col-md-3"> 
            <div class="card shadow-sm border-0 h-100 bg-white"> 
                <div class="card-body flex-column d-flex justify-content-between"> 
                    <div> 
                        <h5 class="card-title text-success">🔒 Published Audit Logs</h5> 
                        <p class="card-text text-muted small">View immutable, locked historical daily scan snapshots for auditing and compliance.</p> 
                    </div> 
                    <a href="{{ route('admin.audit.logs') }}" class="btn btn-sm btn-success w-100 mt-2">View Audit Logs</a> 
                </div> 
            </div> 
        </div> 

        <!-- Delivery Notes / Waybills --> 
        <div class="col-md-3"> 
            <div class="card shadow-sm border-0 h-100 bg-white"> 
                <div class="card-body flex-column d-flex justify-content-between"> 
                    <div> 
                        <h5 class="card-title text-primary">📄 Delivery Notes / Waybills</h5> 
                        <p class="card-text text-muted small">Upload and review scanned waybills and proofs of dispatch for station packages.</p> 
                    </div> 
                    <a href="{{ route('admin.delivery-notes.index') }}" class="btn btn-sm btn-primary w-100 mt-2">View Delivery Notes</a> 
                </div> 
            </div> 
        </div> 

        <!-- Customer SMS Notification History --> 
        <div class="col-md-3"> 
            <div class="card shadow-sm border-0 h-100 bg-white"> 
                <div class="card-body flex-column d-flex justify-content-between"> 
                    <div> 
                        <h5 class="card-title text-warning text-dark">💬 SMS Reminder Logs</h5> 
                        <p class="card-text text-muted small">Track automated customer reminder messages, expiration alerts, and delivery statuses.</p> 
                    </div> 
                    <a href="{{ route('admin.sms.logs') }}" class="btn btn-sm btn-warning w-100 text-dark mt-2">View SMS History</a> 
                </div> 
            </div> 
        </div> 
    </div> 

    <!-- Admin Batch Save & Permanent Archive Card --> 
    <div class="row g-4 mb-4"> 
        <div class="col-12"> 
            <div class="card shadow-sm border-0"> 
                <div class="card-body"> 
                    <div class="d-flex justify-content-between align-items-center mb-3"> 
                        <div> 
                            <h5 class="card-title text-slate-800 mb-1">🔒 Admin Batch Save & Permanent Archive</h5> 
                            <p class="text-muted small mb-0">Save current active scans for future reference. Once saved, records become immutable and the active board resets to a blank page for your next import.</p> 
                        </div> 
                    </div> 
                    <form action="{{ route('admin.batch.save') }}" method="POST" onsubmit="return confirm('Are you sure you want to save and lock this batch? This will clear active records from the PWA, archive the active master list, and lock records permanently.');"> 
                        @csrf 
                        <div class="row align-items-end g-3"> 
                            <div class="col-md-8"> 
                                <label class="form-label fw-bold">Select Station Batch to Archive & Reset</label> 
                                <select name="station_id" class="form-select" required> 
                                    <option value="" disabled selected>Select station...</option> 
                                    @foreach($stations as $station) 
                                        <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option> 
                                    @endforeach 
                                </select> 
                            </div> 
                            <div class="col-md-4"> 
                                <button type="submit" class="btn btn-dark w-100"> 
                                    💾 Save Batch & Reset Page 
                                </button> 
                            </div> 
                        </div> 
                    </form> 
                </div> 
            </div> 
        </div> 
    </div> 

    <!-- Master List Real-Time Management & Reset Card --> 
    <div class="row g-4 mb-4"> 
        <div class="col-12"> 
            <div class="card shadow-sm border-0"> 
                <div class="card-body"> 
                    <div class="d-flex justify-content-between align-items-center mb-3"> 
                        <div> 
                            <h5 class="card-title text-slate-800 mb-1">📋 Station Master List & Real-Time Reset</h5> 
                            <p class="text-muted small mb-0">Upload, view active expected packages in real-time, or clear/reset the master list once scans are completed.</p> 
                        </div> 
                        <div> 
                            <a href="{{ route('admin.scan.logs') }}" class="btn btn-outline-secondary btn-sm">📜 Retrieve Historical Scan Logs</a> 
                        </div> 
                    </div> 
                    
                    <div id="masterListAlert" class="alert d-none py-2" role="alert"></div> 
                    
                    <div class="row g-3"> 
                        <!-- Station Selector for Master List --> 
                        <div class="col-md-4"> 
                            <label class="form-label fw-bold">Select Station to Inspect</label> 
                            <select id="masterStationSelect" class="form-select"> 
                                <option value="" disabled selected>Select station...</option> 
                                @foreach($stations as $station) 
                                    <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option> 
                                @endforeach 
                            </select> 
                            <div class="mt-3 p-3 bg-light rounded border"> 
                                <h6 class="fw-bold mb-2">📥 Upload New Master List (CSV)</h6> 
                                <form id="masterUploadForm" action="{{ route('admin.master.upload') }}" method="POST" enctype="multipart/form-data"> 
                                    @csrf 
                                    <input type="hidden" name="station_id" id="formStationId"> 
                                    <div class="mb-2"> 
                                        <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt" required> 
                                    </div> 
                                    <button type="submit" class="btn btn-primary btn-sm w-100" id="uploadMasterBtn">Save & Import Master List</button> 
                                </form> 
                                <!-- Clear / Reset Button --> 
                                <button id="clearMasterBtn" class="btn btn-danger btn-sm w-100 mt-2" disabled> 
                                    🗑️ Clear / Reset Master List (Blank Page) 
                                </button> 
                            </div> 
                        </div> 
                        
                        <!-- Real-Time Master List Preview Table --> 
                        <div class="col-md-8"> 
                            <label class="form-label fw-bold">Live Master List Preview (<span id="masterCount">0</span> items loaded)</label> 
                            <div class="table-responsive border rounded bg-white" style="max-height: 300px; overflow-y: auto;"> 
                                <table class="table table-sm table-striped mb-0 text-xs"> 
                                    <thead class="table-light sticky-top"> 
                                        <tr> 
                                            <th>#</th> 
                                            <th>Tracking Number</th> 
                                            <th>Hub</th> <th>Aging</th> 
                                            <th>Arrival Date</th> 
                                        </tr> 
                                    </thead> 
                                    <tbody id="masterTableBody"> 
                                        <tr> 
                                            <td colspan="5" class="text-center text-muted py-4">Select a station above to view its live master list.</td> 
                                        </tr> 
                                    </tbody> 
                                </table> 
                            </div> 
                        </div> 
                    </div> 
                </div> 
            </div> 
        </div> 
    </div> 

    <!-- Publish & Lock Audit Log Card --> 
    <div class="row g-4 mb-4"> 
        <div class="col-12"> 
            <div class="card shadow-sm border-0"> 
                <div class="card-body"> 
                    <h5 class="card-title mb-3">🔒 Publish & Lock Daily Audit Log</h5> 
                    <form action="{{ route('admin.audit.publish') }}" method="POST"> 
                        @csrf 
                        <div class="row align-items-end"> 
                            <div class="col-md-9 mb-3 mb-md-0"> 
                                <label class="form-label">Select Audit Date to Lock</label> 
                                <input type="date" name="audit_date" class="form-control" value="{{ date('Y-m-d') }}" required> 
                                <p class="text-muted small mb-0 mt-1">Publishing locks the scan logs for this date, making them permanently read-only and preventing any modifications or deletions.</p> 
                            </div> 
                            <div class="col-md-3"> 
                                <button type="submit" class="btn btn-dark w-100">Lock & Publish Audit Log</button> 
                            </div> 
                        </div> 
                    </form> 
                </div> 
            </div> 
        </div> 
    </div> 

    <!-- Station Security & Access Control Management Card --> 
    <div class="row g-4"> 
        <div class="col-12"> 
            <div class="card shadow-sm border-0"> 
                <div class="card-body"> 
                    <h5 class="card-title mb-3 text-slate-800">🛡️ Station Security & Access Controls</h5> 
                    <p class="text-muted small">Configure restriction policies for scanner PWA operators across one, multiple, or all distribution stations.</p> 
                    <div id="securityAlert" class="alert d-none py-2" role="alert"></div> 
                    <div class="row align-items-center"> 
                        <div class="col-md-5 mb-3 mb-md-0"> 
                            <div class="d-flex justify-content-between align-items-center mb-1"> 
                                <label class="form-label fw-bold mb-0">Select Target Station(s)</label> 
                                <div class="form-check"> 
                                    <input class="form-check-input cursor-pointer" type="checkbox" id="selectAllStations"> 
                                    <label class="form-check-label small text-muted cursor-pointer" for="selectAllStations">Select All</label> 
                                </div> 
                            </div> 
                            <select id="securityStationSelect" class="form-select" multiple size="5"> 
                                @foreach($stations as $station) 
                                    <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option> 
                                @endforeach 
                            </select> 
                            <small class="text-muted d-block mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple individual stations.</small> 
                        </div> 
                        <div class="col-md-7"> 
                            <div class="p-3 bg-light border rounded d-flex align-items-center justify-content-between mb-3"> 
                                <div> 
                                    <h6 class="mb-1 font-semibold">Disable Manual Type-in for Normal Users</h6> 
                                    <p class="text-muted small mb-0">Prevents station operators from typing tracking numbers manually. Forces physical barcode/QR scanning across selected hubs.</p> 
                                </div> 
                                <div class="form-check form-switch fs-4 ms-3"> 
                                    <input class="form-check-input cursor-pointer" type="checkbox" id="disableManualToggle"> 
                                </div> 
                            </div> 
                            <button id="saveSecurityBtn" class="btn btn-primary w-100"> 
                                💾 Save & Broadcast Security Changes 
                            </button> 
                        </div> 
                    </div> 
                </div> 
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
        masterListAlert.className = `alert alert-${type} py-2 mb-3`; 
        masterListAlert.textContent = message; 
        masterListAlert.classList.remove('d-none'); 
        setTimeout(() => masterListAlert.classList.add('d-none'), 4000); 
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
                    masterTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No master list items found. Station is currently blank.</td></tr>`; 
                    return; 
                } 
                masterTableBody.innerHTML = items.map((m, idx) => ` 
                    <tr> 
                        <td>${idx + 1}</td> 
                        <td class="font-mono fw-bold">${m.tracking_number}</td> 
                        <td>${m.hub || 'KE-3PL-Flexcom'}</td> 
                        <td>${m.aging || 0} Days</td> 
                        <td>${m.arrival_date || '-'}</td> 
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
            clearMasterBtn.textContent = '🗑️ Clear / Reset Master List (Blank Page)'; 
        } 
    }); 

    const securityStationSelect = document.getElementById('securityStationSelect'); 
    const selectAllCheckbox = document.getElementById('selectAllStations'); 
    const disableManualToggle = document.getElementById('disableManualToggle'); 
    const saveSecurityBtn = document.getElementById('saveSecurityBtn'); 
    const securityAlert = document.getElementById('securityAlert'); 

    function showSecurityAlert(message, type = 'success') { 
        securityAlert.className = `alert alert-${type} py-2 mb-3`; 
        securityAlert.textContent = message; 
        securityAlert.classList.remove('d-none'); 
        setTimeout(() => securityAlert.classList.add('d-none'), 4000); 
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
            showSecurityAlert('Please select at least one station or check "Select All".', 'warning'); 
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
            saveSecurityBtn.textContent = '💾 Save & Broadcast Security Changes'; 
        } 
    }); 
}); 
</script> 
@endsection