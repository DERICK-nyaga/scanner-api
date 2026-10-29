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
                <div class="card-body">
                    <h5 class="card-title text-danger">🔄 Returns & OTP Portal</h5>
                    <p class="card-text text-muted small">Manage packages returning to Jumia network, assign pickup agents, and verify OTP codes.</p>
                    <a href="{{ route('admin.returns') }}" class="btn btn-sm btn-danger w-100">Open Returns Portal</a>
                </div>
            </div>
        </div>

        <!-- Published & Locked Audit Logs -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 bg-white">
                <div class="card-body">
                    <h5 class="card-title text-success">🔒 Published Audit Logs</h5>
                    <p class="card-text text-muted small">View immutable, locked historical daily scan snapshots for auditing and compliance.</p>
                    <a href="{{ route('admin.audit.logs') }}" class="btn btn-sm btn-success w-100">View Audit Logs</a>
                </div>
            </div>
        </div>

        <!-- Delivery Notes / Waybills -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 bg-white">
                <div class="card-body">
                    <h5 class="card-title text-primary">📄 Delivery Notes / Waybills</h5>
                    <p class="card-text text-muted small">Upload and review scanned waybills and proofs of dispatch for station packages.</p>
                    <a href="{{ route('admin.delivery-notes.index') }}" class="btn btn-sm btn-primary w-100">View Delivery Notes</a>
                </div>
            </div>
        </div>

        <!-- Customer SMS Notification History -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100 bg-white">
                <div class="card-body">
                    <h5 class="card-title text-warning text-dark">💬 SMS Reminder Logs</h5>
                    <p class="card-text text-muted small">Track automated customer reminder messages, expiration alerts, and delivery statuses.</p>
                    <a href="{{ route('admin.sms.logs') }}" class="btn btn-sm btn-warning w-100 text-dark">View SMS History</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Master List & Audit Publication Forms Section -->
    <div class="row g-4">
        <!-- Master List Upload Card -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
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
            <div class="card shadow-sm border-0">
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
</div>
@endsection