@extends('layouts.app') 

@section('content') 
<div class="container py-4"> 
    <div class="d-flex justify-content-between align-items-center mb-4"> 
        <h2>📊 Station Admin Dashboard</h2> 
        <div class="d-flex gap-2"> 
            <a href="/app/" target="_blank" class="btn btn-outline-primary">📦 Open Station PWA Scanner</a> 
            <a href="{{ route('admin.stations.index') }}" class="btn btn-primary">🏢 Manage Stations</a> 
        </div> 
    </div> 

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

    <!-- Master List & Audit Publication Section --> 
    <div class="row g-4 mb-4"> 
        <!-- Master List Upload Card --> 
        <div class="col-md-6"> 
            <div class="card shadow-sm border-0 h-100"> 
                <div class="card-body"> 
                    <h5 class="card-title mb-3">📥 Upload Master List (CSV)</h5> 
                    <form action="{{ route('admin.master.upload') }}" method="POST" enctype="multipart/form-data"> 
                        @csrf 
                        <div class="mb-3"> 
                            <label class="form-label">Select Station</label> 
                            <select name="station_id" class="form-select" required> 
                                @foreach($stations as $station) 
                                    <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option> 
                                @endforeach 
                            </select> 
                        </div> 
                        <div class="mb-3"> 
                            <label class="form-label">CSV File (tracking_number, name, phone, arrival_date)</label> 
                            <input type="file" name="file" class="form-control" accept=".csv,.txt" required> 
                        </div> 
                        <button type="submit" class="btn btn-primary w-100">Upload & Import Master List</button> 
                    </form> 
                </div> 
            </div> 
        </div> 

        <!-- Publish & Lock Audit Log Card --> 
        <div class="col-md-6"> 
            <div class="card shadow-sm border-0 h-100"> 
                <div class="card-body"> 
                    <h5 class="card-title mb-3">🔒 Publish & Lock Daily Audit Log</h5> 
                    <form action="{{ route('admin.audit.publish') }}" method="POST"> 
                        @csrf 
                        <div class="mb-3"> 
                            <label class="form-label">Select Audit Date to Lock</label> 
                            <input type="date" name="audit_date" class="form-control" value="{{ date('Y-m-d') }}" required> 
                        </div> 
                        <p class="text-muted small">Publishing locks the scan logs for this date, making them permanently read-only and preventing any modifications or deletions.</p> 
                        <button type="submit" class="btn btn-dark w-100">Lock & Publish Audit Log</button> 
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
                            <!-- Multi-select enabled size 5 dropdown box -->
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
                            
                            <!-- Explicit Save Button -->
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
    const stationSelect = document.getElementById('securityStationSelect');
    const selectAllCheckbox = document.getElementById('selectAllStations');
    const disableManualToggle = document.getElementById('disableManualToggle');
    const saveSecurityBtn = document.getElementById('saveSecurityBtn');
    const securityAlert = document.getElementById('securityAlert');

    function showAlert(message, type = 'success') {
        securityAlert.className = `alert alert-${type} py-2 mb-3`;
        securityAlert.textContent = message;
        securityAlert.classList.remove('d-none');
        setTimeout(() => securityAlert.classList.add('d-none'), 4000);
    }

    // Toggle all options when "Select All" is checked
    selectAllCheckbox.addEventListener('change', (e) => {
        const isChecked = e.target.checked;
        Array.from(stationSelect.options).forEach(opt => {
            opt.selected = isChecked;
        });
    });

    saveSecurityBtn.addEventListener('click', async () => {
        const selectedOptions = Array.from(stationSelect.selectedOptions);
        
        if (selectedOptions.length === 0 && !selectAllCheckbox.checked) {
            showAlert('Please select at least one station or check "Select All".', 'warning');
            return;
        }

        const stationIds = selectAllCheckbox.checked 
            ? 'all' 
            : selectedOptions.map(opt => opt.value);

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
                body: JSON.stringify({ 
                    station_ids: stationIds,
                    disable_manual: isChecked 
                })
            });

            if (res.ok) {
                const data = await res.json();
                showAlert(data.message || 'Security settings successfully applied and broadcasted!', 'success');
            } else {
                throw new Error('Server response error');
            }
        } catch (e) {
            showAlert('Failed to save security configuration to server.', 'danger');
        } finally {
            saveSecurityBtn.disabled = false;
            saveSecurityBtn.textContent = '💾 Save & Broadcast Security Changes';
        }
    });
});
</script>
@endsection