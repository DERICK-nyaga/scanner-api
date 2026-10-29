@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-between align-items-center mb-4">
        <h2>🏢 Station Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStationModal">
            ➕ Add New Station
        </button>
    </div>

    <!-- Stations Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Station Identifier</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Master Items</th>
                            <th>Scan Logs</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stations as $station)
                        <tr>
                            <td>{{ $station->id }}</td>
                            <td><code>{{ $station->unique_identifier }}</code></td>
                            <td class="fw-semibold">{{ $station->name }}</td>
                            <td>{{ $station->email }}</td>
                            <td>
                                <span class="badge {{ $station->role === 'admin' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ ucfirst($station->role) }}
                                </span>
                            </td>
                            <td>{{ $station->master_lists_count }}</td>
                            <td>{{ $station->scan_logs_count }}</td>
                            <td class="text-end">
                                <!-- Edit Modal Trigger -->
                                <button class="btn btn-sm btn-outline-secondary me-1" data-bs-toggle="modal" data-bs-target="#editStationModal{{ $station->id }}">
                                    ✏️ Edit
                                </button>
                                
                                <!-- Delete Form -->
                                <form action="{{ route('admin.stations.destroy', $station->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this station?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ Delete</button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editStationModal{{ $station->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.stations.update', $station->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Station</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3 text-start">
                                                <label class="form-label">Station Identifier</label>
                                                <input type="text" name="unique_identifier" class="form-control" value="{{ $station->unique_identifier }}" required>
                                            </div>
                                            <div class="mb-3 text-start">
                                                <label class="form-label">Station Name</label>
                                                <input type="text" name="name" class="form-control" value="{{ $station->name }}" required>
                                            </div>
                                            <div class="mb-3 text-start">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" value="{{ $station->email }}" required>
                                            </div>
                                            <div class="mb-3 text-start">
                                                <label class="form-label">New Password (leave blank to keep current)</label>
                                                <input type="password" name="password" class="form-control">
                                            </div>
                                            <div class="mb-3 text-start">
                                                <label class="form-label">Role</label>
                                                <select name="role" class="form-select" required>
                                                    <option value="user" {{ $station->role === 'user' ? 'selected' : '' }}>User</option>
                                                    <option value="admin" {{ $station->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                                    <option value="superadmin" {{ $station->role === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update Station</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No stations created yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createStationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.stations.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create Station Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Unique Identifier</label>
                        <input type="text" name="unique_identifier" class="form-control" placeholder="KE-3PL-Flexcom Cheptiret-Station" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Station Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Cheptiret Station" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="cheptiret@station.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Station</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection