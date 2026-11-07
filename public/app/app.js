const { createApp, ref, computed, watch, nextTick, onMounted, onUnmounted } = Vue
const pad = (n) => String(n).padStart(2, '0')
const now = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}` }
const uid = () => (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random())

// Web Audio Beep Synthesizer
let audioCtx = null
function playAudioSignal(type) {
  try {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)()
    if (audioCtx.state === 'suspended') audioCtx.resume()
    if (type === 'success') {
      const osc = audioCtx.createOscillator()
      const gain = audioCtx.createGain()
      osc.type = 'sine'
      osc.frequency.setValueAtTime(880, audioCtx.currentTime)
      gain.gain.setValueAtTime(0.15, audioCtx.currentTime)
      gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15)
      osc.connect(gain)
      gain.connect(audioCtx.destination)
      osc.start()
      osc.stop(audioCtx.currentTime + 0.15)
    } else if (type === 'error') {
      [0, 0.12].forEach((delay) => {
        const osc = audioCtx.createOscillator()
        const gain = audioCtx.createGain()
        osc.type = 'sawtooth'
        osc.frequency.setValueAtTime(220, audioCtx.currentTime + delay)
        gain.gain.setValueAtTime(0.2, audioCtx.currentTime + delay)
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + delay + 0.1)
        osc.connect(gain)
        gain.connect(audioCtx.destination)
        osc.start(audioCtx.currentTime + delay)
        osc.stop(audioCtx.currentTime + delay + 0.1)
      })
    }
  } catch (e) {}
}

const OUTBOX_KEY = 'scanner-offline-outbox-v1'
const MASTER_KEY = 'scanner-master-list-v4'
const SETTINGS_KEY = 'scanner-settings'

let savedMaster = []
try { savedMaster = JSON.parse(localStorage.getItem(MASTER_KEY)) || [] } catch {}

let savedSettings = { disableManual: false }
try { savedSettings = JSON.parse(localStorage.getItem(SETTINGS_KEY)) || { disableManual: false } } catch {}

createApp({
  setup() {
    const rows = ref([])
    const masterList = ref(savedMaster)
    const settings = ref(savedSettings)
    const currentStationId = ref(localStorage.getItem('active_station_id') || '')
    const stationsList = ref([])
    const mode = ref(null)
    const typed = ref('')
    const lastScan = ref('')
    const sessionCount = ref(0)
    const statusMsg = ref({ text: '', type: '' })
    const masterInputText = ref('')
    const isOnline = ref(navigator.onLine)
    const isSyncing = ref(false)
    let scanner = null
    let lastCode = '', lastTime = 0
    let pollInterval = null

    window.addEventListener('online', () => {
      isOnline.value = true
      setStatus('Connection re-established. Flushing offline queue...', 'success')
      flushOutbox()
    })
    window.addEventListener('offline', () => {
      isOnline.value = false
      setStatus('Network offline. Scans will be saved locally and synced when online.', 'error')
    })

    async function fetchStations() {
      try {
        const res = await fetch('/api/admin/stations')
        if (res.ok) {
          const data = await res.json()
          stationsList.value = data
          if (currentStationId.value) {
            const exists = data.some(s => String(s.id) === String(currentStationId.value))
            if (exists) {
              await fetchServerScans()
              await loadStationDetails(currentStationId.value)
              flushOutbox()
            } else {
              currentStationId.value = ''
            }
          }
        }
      } catch (e) {
        console.error('Could not load stations from API', e)
      }
    }

    function clearStationOutbox() {
      try {
        const outbox = getOutbox()
        const filtered = outbox.filter(item => String(item.station_id) !== String(currentStationId.value))
        saveOutbox(filtered)
      } catch {}
    }

    async function fetchServerScans() {
      if (!currentStationId.value) return
      try {
        const stationRes = await fetch(`/api/admin/stations/${currentStationId.value}/details`).catch(() => null)
        if (stationRes && stationRes.ok) {
          const stationData = await stationRes.json()
          
          if (stationData.station && typeof stationData.station.disable_manual !== 'undefined') {
            settings.value.disableManual = Boolean(stationData.station.disable_manual)
          }

          const activeMaster = (stationData.master_list || []).map(m => {
            const initialAging = Number(m.aging || 0)
            return { ...m, arrival_date: m.arrival_date || new Date(Date.now() - initialAging * 86400000).toISOString() }
          })
          masterList.value = activeMaster
          try { localStorage.setItem(MASTER_KEY, JSON.stringify(activeMaster)) } catch {}
        }

        const res = await fetch(`/api/scans?station_id=${currentStationId.value}`)
        if (res.ok) {
          const data = await res.json()
          const scanItems = Array.isArray(data) ? data : (data.data || [])
          
          if (scanItems.length === 0) {
            rows.value = []
            clearStationOutbox()
          } else {
            const serverRowsMap = new Map()
            scanItems.forEach(item => {
              const code = String(item.code || item.tracking_number || '').trim().toUpperCase()
              if (code) {
                serverRowsMap.set(code, {
                  id: item.client_id || item.id || uid(),
                  code: code,
                  hub: item.hub || 'KE-3PL-Flexcom',
                  aging: item.aging || 0,
                  arrival_date: item.arrival_date,
                  ts: item.scanned_at || item.created_at || now(),
                  method: item.method || 'scanned',
                  matchStatus: item.match_status || 'matched',
                  note: item.note || ''
                })
              }
            })
            rows.value = Array.from(serverRowsMap.values())
          }
        }
      } catch (e) {
        console.error('Failed to sync station scans from server', e)
      }
    }

    function getOutbox() {
      try { return JSON.parse(localStorage.getItem(OUTBOX_KEY)) || [] } catch { return [] }
    }
    function saveOutbox(queue) {
      try { localStorage.setItem(OUTBOX_KEY, JSON.stringify(queue)) } catch {}
    }

    async function syncToServer(payloadRows) {
      if (!currentStationId.value) return
      const outbox = getOutbox()
      payloadRows.forEach(r => {
        if (!outbox.some(item => item.id === r.id)) {
          outbox.push({ station_id: currentStationId.value, ...r, synced: false })
        }
      })
      saveOutbox(outbox)
      if (!navigator.onLine) return
      await flushOutbox()
    }

    async function flushOutbox() {
      if (!navigator.onLine || isSyncing.value) return
      const outbox = getOutbox()
      const pending = outbox.filter(item => !item.synced && String(item.station_id) === String(currentStationId.value))
      if (pending.length === 0) return
      isSyncing.value = true
      try {
        const res = await fetch('/api/scans/bulk', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({
            station_id: currentStationId.value,
            scans: pending.map(r => ({
              client_id: r.id,
              code: r.code,
              scanned_at: r.ts,
              method: r.method,
              note: r.note
            }))
          })
        })
        if (res.ok) {
          const remaining = getOutbox().map(item => {
            if (pending.some(p => p.id === item.id)) {
              return { ...item, synced: true }
            }
            return item
          }).filter(item => !item.synced)
          saveOutbox(remaining)
          await fetchServerScans()
        }
      } catch (e) {
        console.error('Outbox flush failed', e)
      } finally {
        isSyncing.value = false
      }
    }

    onMounted(() => {
      fetchStations()
      pollInterval = setInterval(() => {
        if (currentStationId.value) {
          fetchServerScans()
          flushOutbox()
        }
      }, 5000)
    })

    onUnmounted(() => {
      if (pollInterval) clearInterval(pollInterval)
    })

    async function loadStationDetails(id) {
      try {
        const res = await fetch(`/api/admin/stations/${id}/details`)
        if (res.ok) {
          const data = await res.json()
          const freshMaster = (data.master_list || []).map(m => {
            const initialAging = Number(m.aging || 0)
            return { ...m, arrival_date: m.arrival_date || new Date(Date.now() - initialAging * 86400000).toISOString() }
          })
          
          masterList.value = freshMaster
          try { localStorage.setItem(MASTER_KEY, JSON.stringify(freshMaster)) } catch {}

          if (data.station && typeof data.station.disable_manual !== 'undefined') {
            settings.value.disableManual = Boolean(data.station.disable_manual)
          }
        }
      } catch (e) {
        console.error('Failed to load station details', e)
      }
    }

    async function switchStation(id) {
      if (!id) return
      currentStationId.value = id
      localStorage.setItem('active_station_id', id)
      rows.value = []
      masterList.value = []
      try { localStorage.removeItem(MASTER_KEY) } catch {}

      await loadStationDetails(id)
      await fetchServerScans()
      flushOutbox()
      setStatus(`Switched to station context successfully.`, 'success')
    }

    watch(masterList, () => {
      try { localStorage.setItem(MASTER_KEY, JSON.stringify(masterList.value)) } catch {}
    }, { deep: true })

    watch(settings, () => {
      try { localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings.value)) } catch {}
    }, { deep: true })

    function setStatus(text, type = 'error') {
      statusMsg.value = { text, type }
    }

    const scannedCodesMap = computed(() => {
      const map = {}
      rows.value.forEach(r => {
        const c = String(r.code || '').trim().toUpperCase()
        if (c) map[c] = (map[c] || 0) + 1
      })
      getOutbox().forEach(r => {
        if (String(r.station_id) === String(currentStationId.value)) {
          const c = String(r.code || '').trim().toUpperCase()
          if (c) map[c] = (map[c] || 0) + 1
        }
      })
      return map
    })

    const missingItems = computed(() => {
      const scanned = scannedCodesMap.value
      return masterList.value.filter(item => {
        const code = String(item.tracking_number || item).trim().toUpperCase()
        return !scanned[code]
      })
    })

    function computeLiveAging(arrivalDateStr, baseAging = 0) {
      if (!arrivalDateStr) return Number(baseAging)
      const start = new Date(arrivalDateStr)
      const nowTime = new Date()
      const diffDays = Math.floor((nowTime - start) / (1000 * 60 * 60 * 24))
      return Math.max(0, Number(baseAging) + Math.max(0, diffDays))
    }

    const unifiedRows = computed(() => {
      const localPending = getOutbox().filter(item => String(item.station_id) === String(currentStationId.value))
      const combinedMap = new Map()
      rows.value.forEach(r => combinedMap.set(r.code, r))
      localPending.forEach(r => combinedMap.set(r.code, { ...r, matchStatus: 'untracked' }))
      
      const list = Array.from(combinedMap.values()).map(r => {
        const cleanCode = String(r.code || '').trim().toUpperCase()
        let matchStatus = r.matchStatus || 'untracked'
        let matchedMasterItem = null
        if (masterList.value.length > 0) {
          matchedMasterItem = masterList.value.find(m => {
            const mCode = String(m.tracking_number || m).trim().toUpperCase()
            return mCode === cleanCode
          })
          if (matchedMasterItem && matchStatus === 'untracked') {
            matchStatus = 'matched'
          } else if (!matchedMasterItem && matchStatus === 'untracked') {
            matchStatus = 'unexpected'
          }
        }
        const arrivalAnchor = matchedMasterItem?.arrival_date || r.arrival_date || new Date().toISOString()
        const baseCsvAging = matchedMasterItem?.aging ?? r.aging ?? 0
        const liveAging = computeLiveAging(arrivalAnchor, baseCsvAging)
        const hubValue = matchedMasterItem?.hub || r.hub || 'KE-3PL-Flexcom'
        let expiryStatus = 'normal'
        if (liveAging >= 8 && liveAging <= 9) {
          expiryStatus = 'urgent-red'
        } else if (liveAging >= 10) {
          expiryStatus = 'expired-return'
        }
        return { ...r, hub: hubValue, aging: liveAging, source: 'physical', matchStatus, expiryStatus }
      })

      if (masterList.value.length > 0) {
        missingItems.value.forEach(m => {
          const mCode = m.tracking_number || m
          const mHub = m.hub || 'KE-3PL-Flexcom'
          const liveAging = computeLiveAging(m.arrival_date, m.aging || 0)
          let expiryStatus = 'normal'
          if (liveAging >= 8 && liveAging <= 9) expiryStatus = 'urgent-red'
          else if (liveAging >= 10) expiryStatus = 'expired-return'
          
          list.push({
            id: 'sys-' + mCode,
            code: mCode,
            hub: mHub,
            aging: liveAging,
            ts: '-',
            method: 'system',
            note: 'Awaiting physical scan',
            source: 'system-only',
            matchStatus: 'missing',
            expiryStatus
          })
        })
      }
      return list
    })

    function addRow(code, method) {
      if (!currentStationId.value) {
        setStatus('Please select a station from the top dropdown first!', 'error')
        playAudioSignal('error')
        return false
      }
      const trimmed = code.trim()
      if (!trimmed) {
        setStatus('Please enter a valid tracking code.', 'error')
        playAudioSignal('error')
        return false
      }
      if (scannedCodesMap.value[trimmed.toUpperCase()]) {
        setStatus(`Error: Item "${trimmed}" has already been scanned!`, 'error')
        playAudioSignal('error')
        return false
      }
      const matchedMaster = masterList.value.find(m => String(m.tracking_number || m).trim().toUpperCase() === trimmed.toUpperCase())
      const csvAging = matchedMaster?.aging ?? 0
      const arrivalAnchor = matchedMaster?.arrival_date || new Date(Date.now() - csvAging * 86400000).toISOString()
      
      const newRow = {
        id: uid(),
        code: trimmed,
        hub: matchedMaster?.hub || 'KE-3PL-Flexcom',
        aging: csvAging,
        arrival_date: arrivalAnchor,
        ts: now(),
        method,
        matchStatus: matchedMaster ? 'matched' : 'unexpected',
        note: ''
      }
      syncToServer([newRow])
      setStatus(`Saved & Verified: ${trimmed}`, 'success')
      playAudioSignal('success')
      return true
    }

    async function openMode(m) {
      if (!currentStationId.value) {
        setStatus('Please select a station before scanning.', 'error')
        playAudioSignal('error')
        return
      }
      if (m === 'keyboard' && settings.value.disableManual) {
        setStatus('Manual typing has been disabled by administrator.', 'error')
        playAudioSignal('error')
        return
      }
      if (mode.value === m) return closeMode()
      await stopCamera()
      statusMsg.value = { text: '', type: '' }
      mode.value = m
      if (m === 'camera') {
        await nextTick()
        startCamera()
      }
    }

    async function closeMode() {
      await stopCamera()
      mode.value = null
      statusMsg.value = { text: '', type: '' }
    }

    function saveTyped() {
      if (settings.value.disableManual) {
        setStatus('Manual typing is currently disabled.', 'error')
        playAudioSignal('error')
        return
      }
      if (!typed.value.trim()) {
        playAudioSignal('error')
        return
      }
      const success = addRow(typed.value, 'typed')
      if (success) typed.value = ''
    }

    function loadMasterListFromText() {
      if (!currentStationId.value) {
        setStatus('Please select a station first.', 'error')
        return
      }
      const lines = masterInputText.value
        .split('\n')
        .map(l => l.trim())
        .filter(l => l.length > 0)
      masterList.value = lines.map(line => {
        const parts = line.split(',')
        const trNum = parts[0]?.trim() || line
        const hubVal = parts[1]?.trim() || 'KE-3PL-Flexcom'
        const agingVal = Number(parts[2]?.trim() || 0)
        const arrivalIso = new Date(Date.now() - agingVal * 86400000).toISOString()
        return { tracking_number: trNum, hub: hubVal, aging: agingVal, arrival_date: arrivalIso }
      })
      masterInputText.value = ''
      setStatus(`Master list loaded with ${masterList.value.length} items.`, 'success')
      playAudioSignal('success')
    }

    function clearMasterList() {
      if (confirm('Are you sure you want to clear the system master list?')) {
        masterList.value = []
        setStatus('Master list cleared.', 'success')
      }
    }

    async function startCamera() {
      if (!window.Html5Qrcode) {
        setStatus('Scanner library missing or blocked', 'error')
        return
      }
      sessionCount.value = 0
      scanner = new Html5Qrcode('reader')
      try {
        await scanner.start(
          { facingMode: 'environment' },
          { fps: 10, qrbox: { width: 250, height: 160 } },
          onDecoded,
          () => {}
        )
      } catch (e) {
        setStatus('Camera error: ' + (e.message || e), 'error')
      }
    }

    function onDecoded(text) {
      const t = Date.now()
      if (text === lastCode && t - lastTime < 2500) return
      lastCode = text
      lastTime = t
      const success = addRow(text, 'scanned')
      if (success) {
        lastScan.value = text
        sessionCount.value++
        navigator.vibrate && navigator.vibrate(60)
      }
    }

    async function stopCamera() {
      if (!scanner) return
      try {
        await scanner.stop()
        scanner.clear()
      } catch {}
      scanner = null
    }

    return {
      currentStationId, stationsList, switchStation,
      rows, masterList, settings, unifiedRows, missingItems,
      mode, openMode, closeMode, typed, saveTyped,
      lastScan, sessionCount, statusMsg,
      masterInputText, loadMasterListFromText, clearMasterList,
      isOnline, isSyncing
    }
  },
  template: `
    <div class="min-h-screen bg-slate-50 text-slate-900 font-sans antialiased pb-12">
      <header class="bg-teal-700 text-white shadow-md">
        <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3">
          <div class="flex items-center gap-2">
            <h1 class="text-lg font-bold tracking-tight">📦 Station Portal</h1>
            <span :class="isOnline ? 'bg-emerald-500' : 'bg-amber-500'" class="w-2.5 h-2.5 rounded-full inline-block" :title="isOnline ? 'Online' : 'Offline Mode'"></span>
          </div>

          <div class="w-full sm:w-auto">
            <select v-model="currentStationId" @change="switchStation(currentStationId)" class="w-full sm:w-64 bg-teal-800 text-white border border-teal-600 rounded-lg px-3 py-1.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-white transition cursor-pointer">
              <option value="" disabled>Select Station</option>
              <option v-for="s in stationsList" :value="s.id" :key="s.id">
                {{ s.unique_identifier }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <span class="bg-teal-800/80 text-teal-100 px-3 py-1 rounded-full text-xs font-semibold">Scanned: {{ unifiedRows.filter(r => r.source === 'physical').length }}</span>
            <span v-if="masterList.length" class="bg-teal-800/80 text-teal-100 px-3 py-1 rounded-full text-xs font-semibold">Expected: {{ masterList.length }}</span>
          </div>
        </div>
      </header>

      <main class="max-w-6xl mx-auto px-4 mt-6">
        <div class="grid grid-cols-2 gap-4 mb-6">
          <button @click="openMode('camera')" :class="mode === 'camera' ? 'border-teal-600 bg-teal-50 ring-2 ring-teal-500/20' : 'border-slate-200 bg-white hover:border-teal-400 hover:bg-slate-50'" class="flex flex-col items-center justify-center p-4 border rounded-xl shadow-sm transition cursor-pointer">
            <span class="text-2xl mb-1">📷</span>
            <span class="text-xs font-semibold text-slate-700">Camera</span>
          </button>
          
          <button @click="openMode('keyboard')" :class="[
            mode === 'keyboard' ? 'border-teal-600 bg-teal-50 ring-2 ring-teal-500/20' : 'border-slate-200 bg-white hover:border-teal-400 hover:bg-slate-50',
            settings.disableManual ? 'opacity-40 cursor-not-allowed bg-slate-100' : ''
          ]" class="flex flex-col items-center justify-center p-4 border rounded-xl shadow-sm transition cursor-pointer">
            <span class="text-2xl mb-1">⌨️</span>
            <span class="text-xs font-semibold text-slate-700">Manual</span>
          </button>
        </div>

        <div v-if="statusMsg.text" :class="statusMsg.type === 'error' ? 'bg-red-50 text-red-800 border-red-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'" class="p-3 mb-6 border rounded-lg text-sm font-medium shadow-sm transition">
          {{ statusMsg.text }}
        </div>

        <section v-if="mode === 'camera'" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
          <div id="reader" class="w-full h-64 bg-slate-900 rounded-lg overflow-hidden flex items-center justify-center text-slate-400 text-sm">Initializing camera stream...</div>
          <div class="flex justify-between items-center mt-4">
            <p class="text-xs text-slate-600">Session count: <b class="text-slate-900">{{ sessionCount }}</b> <span v-if="lastScan">| Last: <code class="bg-slate-100 px-1 py-0.5 rounded text-teal-700">{{ lastScan }}</code></span></p>
            <button @click="closeMode" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition cursor-pointer">Close Camera</button>
          </div>
        </section>

        <section v-if="mode === 'keyboard'" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
          <div class="flex gap-2">
            <input v-model="typed" placeholder="Enter tracking number..." @keydown.enter.prevent="saveTyped" autofocus class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500" />
            <button @click="saveTyped" :disabled="!typed.trim()" class="px-4 py-2 bg-teal-700 hover:bg-teal-800 disabled:opacity-50 text-white text-sm font-medium rounded-lg shadow-sm transition cursor-pointer">Save</button>
            <button @click="closeMode" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition cursor-pointer">Close</button>
          </div>
        </section>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                  <th class="p-3 text-center w-12">#</th>
                  <th class="p-3">Tracking Number</th>
                  <th class="p-3">Hub</th>
                  <th class="p-3">Aging</th>
                  <th class="p-3">Status / Verification</th>
                  <th class="p-3">Timestamp</th>
                  <th class="p-3">Method</th>
                  <th class="p-3">Notes</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-sm">
                <tr v-for="(r, i) in unifiedRows" :key="r.id" :class="{
                  'bg-rose-100 border-l-4 border-rose-600 text-rose-900 font-semibold animate-pulse': r.aging >= 8 && r.aging <= 9,
                  'bg-red-200 border-l-4 border-red-700 font-bold': r.aging >= 10,
                  'bg-emerald-50/50': r.matchStatus === 'matched' && r.aging < 8,
                  'bg-amber-50/50': r.matchStatus === 'unexpected' && r.aging < 8,
                  'bg-rose-50/60': r.matchStatus === 'missing' && r.aging < 8
                }" class="transition hover:bg-slate-50/80">
                  <td class="p-3 text-center text-xs font-semibold text-slate-400">{{ unifiedRows.length - i }}</td>
                  <td class="p-3 font-mono">
                    <span class="text-slate-800 font-semibold">{{ r.code }}</span>
                  </td>
                  <td class="p-3 text-xs font-medium text-slate-600">{{ r.hub }}</td>
                  <td class="p-3">
                    <span class="px-2.5 py-1 rounded text-xs font-bold inline-block" :class="{
                      'bg-rose-200 text-rose-900 animate-pulse': r.aging >= 8 && r.aging <= 9,
                      'bg-red-300 text-red-950': r.aging >= 10,
                      'bg-slate-200 text-slate-700': r.aging < 8
                    }">{{ r.aging }} Days</span>
                  </td>
                  <td class="p-3">
                    <span :class="{
                      'bg-emerald-100 text-emerald-800': r.matchStatus === 'matched',
                      'bg-amber-100 text-amber-800': r.matchStatus === 'unexpected',
                      'bg-rose-100 text-rose-800': r.matchStatus === 'missing',
                      'bg-slate-100 text-slate-600': r.matchStatus === 'untracked'
                    }" class="px-2.5 py-1 rounded-full text-xs font-bold inline-block">
                      {{ r.matchStatus === 'matched' ? '✓ Matched' : r.matchStatus === 'missing' ? '❌ Missing' : r.matchStatus === 'unexpected' ? '⚠️ Unexpected' : 'Unchecked' }}
                    </span>
                  </td>
                  <td class="p-3 text-xs text-slate-500">{{ r.ts }}</td>
                  <td class="p-3">
                    <span :class="r.method === 'scanned' ? 'bg-sky-100 text-sky-800' : r.method === 'typed' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'" class="px-2 py-0.5 rounded-full text-[11px] font-semibold capitalize">
                      {{ r.method }}
                    </span>
                  </td>
                  <td class="p-3">
                    <span class="text-xs text-slate-600">{{ r.note || '-' }}</span>
                  </td>
                </tr>
                <tr v-if="!currentStationId">
                  <td colspan="8" class="p-8 text-center text-amber-600 text-sm font-medium">⚠️ Please select a station from the dropdown above to view records and start scanning.</td>
                </tr>
                <tr v-else-if="!unifiedRows.length">
                  <td colspan="8" class="p-8 text-center text-slate-400 text-sm">No packages recorded or loaded in master list for this station. Use 📷 Camera or ⌨️ Manual.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  `
}).mount('#app')