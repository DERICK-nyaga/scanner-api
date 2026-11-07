@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 Historical Uploaded Master Lists</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.scan.logs') }}" class="btn btn-outline-success btn-sm">📜 View Scan Logs</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary btn-sm">⬅️ Back to Dashboard</a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.historical.master') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Filter by Station</label>
                    <select name="station_id" class="form-select">
                        <option value="">All Stations</option>
                        @foreach($stations as $station)
                            <option value="{{ $station->id }}" {{ $stationId == $station->id ? 'selected' : '' }}>
                                {{ $station->unique_identifier }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">🔍 Filter Records</button>
                    <a href="{{ route('admin.historical.master') }}" class="btn btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Historical Master Lists Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 ps-3">Station</th>
                            <th class="py-3">Tracking Number</th>
                            <th class="py-3">Hub</th>
                            <th class="py-3">Initial Aging</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 pe-3">Uploaded / Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($historicalMasterLists as $item)
                            <tr>
                                <td class="fw-bold ps-3">{{ $item->station->unique_identifier ?? 'N/A' }}</td>
                                <td class="font-mono text-teal-700 fw-bold">{{ $item->tracking_number }}</td>
                                <td>{{ $item->hub }}</td>
                                <td>{{ $item->aging }} Days</td>
                                <td>
                                    <span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">
                                        {{ $item->is_active ? 'Active Batch' : 'Archived' }}
                                    </span>
                                </td>
                                <td class="text-muted small pe-3">{{ $item->created_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No master list archives found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Enhanced Modern Bootstrap Pagination Footer -->
        <div class="card-footer bg-white py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="text-muted small">
                Showing 
                <span class="fw-semibold text-dark">{{ $historicalMasterLists->firstItem() ?? 0 }}</span> 
                to 
                <span class="fw-semibold text-dark">{{ $historicalMasterLists->lastItem() ?? 0 }}</span> 
                of 
                <span class="fw-semibold text-dark">{{ $historicalMasterLists->total() }}</span> entries
            </div>
            
            <div>
                {{ $historicalMasterLists->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection