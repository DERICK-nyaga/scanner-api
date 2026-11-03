@extends('layouts.admin') {{-- Use your existing admin layout --}}

@section('content')
<div class="max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-md mt-6">
    <h2 class="text-xl font-bold text-slate-800 mb-4">🛡️ Station Security & Access Controls</h2>

    <div class="mb-4">
        <label class="block text-sm font-medium text-slate-700 mb-1">Select Station to Configure:</label>
        <select id="adminStationSelect" class="w-full p-2 border border-slate-300 rounded-lg">
            @foreach($stations as $station)
                <option value="{{ $station->id }}">{{ $station->unique_identifier }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-center gap-3 p-4 bg-slate-50 border border-slate-200 rounded-lg">
        <input type="checkbox" id="adminDisableManual" class="w-5 h-5 text-teal-600 rounded cursor-pointer" />
        <div>
            <label for="adminDisableManual" class="text-sm font-bold text-slate-800 cursor-pointer">Disable Manual Type-in for Normal Users</label>
            <p class="text-xs text-slate-500">When enabled, regular scanning operators cannot use the keyboard tab to type in codes manually.</p>
        </div>
    </div>

    <button id="saveSettingsBtn" class="mt-4 px-5 py-2 bg-teal-700 text-white font-medium rounded-lg hover:bg-teal-800 transition cursor-pointer">
        Save Security Settings
    </button>
</div>

<script>
    const stationSelect = document.getElementById('adminStationSelect');
    const disableManualCheckbox = document.getElementById('adminDisableManual');
    const saveBtn = document.getElementById('saveSettingsBtn');

    async function loadSettings(stationId) {
        try {
            const res = await fetch(`/api/admin/stations/${stationId}/details`);
            if (res.ok) {
                const data = await res.json();
                disableManualCheckbox.checked = Boolean(data.station?.disable_manual);
            }
        } catch (e) {
            console.error('Error fetching station settings', e);
        }
    }

    stationSelect.addEventListener('change', (e) => loadSettings(e.target.value));
    
    // Load initial selection
    if (stationSelect.value) loadSettings(stationSelect.value);

    saveBtn.addEventListener('click', async () => {
        const stationId = stationSelect.value;
        const disableManual = disableManualCheckbox.checked;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        try {
            const res = await fetch(`/api/admin/stations/${stationId}/settings`, {
                method: 'PUT',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ disable_manual: disableManual })
            });

            if (res.ok) {
                alert('Station security settings updated successfully!');
            } else {
                alert('Failed to update settings.');
            }
        } catch (e) {
            console.error('Save failed', e);
        }
    });
</script>
@endsection