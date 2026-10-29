@extends('layouts.app')

@section('content')
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">🔒 Published & Locked Audit Logs</h4>
        <span class="badge bg-danger">Read-Only / Locked</span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Station ID</th>
                    <th>Audit Date</th>
                    <th>Total Scanned Snapshots</th>
                    <th>Published At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($publishedLogs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>Station #{{ $log->station_id }}</td>
                    <td><strong>{{ $log->audit_date }}</strong></td>
                    <td><span class="badge bg-secondary">{{ count($log->snapshot_data ?? []) }} items</span></td>
                    <td>{{ $log->created_at }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#snapshotModal{{ $log->id }}">
                            👁️ View Snapshot
                        </button>
                    </td>
                </tr>

                <!-- Snapshot Detail Modal -->
                <div class="modal fade" id="snapshotModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Audit Snapshot Details - {{ $log->audit_date }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small">This record is permanently locked and cannot be edited or deleted.</p>
                                <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><code>{{ json_encode($log->snapshot_data, JSON_PRETTY_PRINT) }}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No audit logs published and locked yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection