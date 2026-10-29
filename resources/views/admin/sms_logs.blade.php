@extends('layouts.app')

@section('content')
<div class="card p-4">
    <h4 class="mb-3">💬 Customer SMS Notification Audit Log</h4>
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Station</th>
                    <th>Recipient Phone</th>
                    <th>Message Content</th>
                    <th>Status</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @forelse($smsLogs as $sms)
                <tr>
                    <td>{{ $sms->station->unique_identifier ?? 'N/A' }}</td>
                    <td><code>{{ $sms->recipient_phone }}</code></td>
                    <td>{{ $sms->message_content }}</td>
                    <td><span class="badge bg-success">{{ $sms->status }}</span></td>
                    <td>{{ $sms->created_at }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No SMS reminders sent yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection