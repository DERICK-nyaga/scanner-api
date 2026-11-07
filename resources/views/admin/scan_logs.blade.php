@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📜 Historical Scan Logs & Retrieval</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary btn-sm">⬅️ Back to Dashboard</a>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.scan.logs') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label class="form-label fw-bold">Filter by Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">🔍 Filter Logs</button>
                    <a href="{{ route('admin.scan.logs') }}" class="btn btn-light border">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Scan Logs Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 text-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Station</th>
                            <th>Tracking Code</th>
                            <th>Hub</th>
                            <th>Aging</th>
                            <th>Method</th>
                            <th>Match Status</th>
                            <th>Scanned At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scanLogs as $log)
                            <tr>
                                <td class="fw-bold">{{ $log->station->unique_identifier ?? 'N/A' }}</td>
                                <td class="font-mono text-teal-700 fw-bold">{{ $log->tracking_number ?? $log->code }}</td>
                                <td>{{ $log->hub }}</td>
                                <td>{{ $log->aging }} Days</td>
                                <td>
                                    <span class="badge bg-{{ $log->method == 'scanned' ? 'info' : 'warning' }}">
                                        {{ ucfirst($log->method) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->match_status == 'matched' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($log->match_status) }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $log->scanned_at ?? $log->created_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No historical scan logs found matching your criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            {{ $scanLogs->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection