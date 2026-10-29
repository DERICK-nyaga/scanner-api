@extends('layouts.app')

@section('content')
<div class="card p-4">
    <h4 class="mb-3">🔄 Jumia Reverse Logistics & Returned Packages</h4>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tracking #</th>
                    <th>Return Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Agent Pickup Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $ret)
                <tr>
                    <td><code>{{ $ret->tracking_number }}</code></td>
                    <td>{{ $ret->return_date }}</td>
                    <td>{{ $ret->reason }}</td>
                    <td><span class="badge bg-warning text-dark">{{ $ret->status }}</span></td>
                    <td>
                        @if($ret->pickup)
                            {{ $ret->pickup->agent_name }} ({{ $ret->pickup->agent_phone }})
                        @else
                            <span class="text-muted">Pending Handover</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-success">Verify OTP</button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No reverse packages logged.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection