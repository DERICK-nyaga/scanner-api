@extends('layouts.app')

@section('content')
<div class="card p-4">
    <h4 class="mb-3">📄 Station Delivery Notes & Waybills</h4>
    <form action="{{ route('admin.delivery-notes.store') }}" method="POST" enctype="multipart/form-data" class="mb-4">
        @csrf
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label">Select Station</label>
                <select name="station_id" class="form-select" required>
                    @foreach($stations as $station)
                        <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Upload Delivery Note / Waybill (PDF/Scan)</label>
                <input type="file" name="delivery_note" class="form-control" accept=".pdf,.jpg,.png,.jpeg" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Upload</button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Station</th>
                    <th>File Name</th>
                    <th>Uploaded At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveryNotes as $note)
                <tr>
                    <td>{{ $note->station->unique_identifier ?? 'N/A' }}</td>
                    <td>{{ $note->original_filename }}</td>
                    <td>{{ $note->created_at }}</td>
                    <td>
                        <a href="{{ Storage::url($note->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">View Document</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted">No delivery notes uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection