const { createApp, ref, computed, watch, nextTick, onMounted } = Vue

const pad = (n) => String(n).padStart(2, '0')
const now = () => {
  const d = new Date()
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}
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

const ROWS_KEY = 'scanner-rows-v4'
const MASTER_KEY = 'scanner-master-list-v4'
const SETTINGS_KEY = 'scanner-settings'

let savedRows = []
try { savedRows = JSON.parse(localStorage.getItem(ROWS_KEY)) || [] } catch {}

let savedMaster = []
try { savedMaster = JSON.parse(localStorage.getItem(MASTER_KEY)) || [] } catch {}

let savedSettings = { disableManual: false }
try { savedSettings = JSON.parse(localStorage.getItem(SETTINGS_KEY)) || { disableManual: false } } catch {}

createApp({
  setup() {
    const rows = ref(savedRows)
    const masterList = ref(savedMaster) // Stores full objects: { tracking_number, hub, aging, arrival_date }
    const settings = ref(savedSettings)
    
    // Station Switcher State
    const currentStationId = ref(localStorage.getItem('active_station_id') || '1')
    const stationsList = ref([])

    const mode = ref(null) // 'camera' | 'keyboard' | 'admin'
    const typed = ref('')
    const lastScan = ref('')
    const sessionCount = ref(0)
    const statusMsg = ref({ text: '', type: '' })
    const masterInputText = ref('')

    let scanner = null
    let lastCode = '', lastTime = 0

    async function fetchStations() {
      try {
        const res = await fetch('/api/admin/stations')
        if (res.ok) {
          const data = await res.json()
          stationsList.value = data
          
          if (data.length > 0) {
            const exists = data.some(s => String(s.id) === String(currentStationId.value))
            if (!exists) {
              currentStationId.value = data[0].id
              localStorage.setItem('active_station_id', data[0].id)
            }
          }
        }
      } catch (e) {
        console.error('Could not load stations from API', e)
        stationsList.value = [{ id: 1, unique_identifier: 'KE-3PL-Flexcom Cheptiret-Station' }]
      }
    }

    onMounted(() => {
      fetchStations()
    })

    async function switchStation(id) {
      currentStationId.value = id
      localStorage.setItem('active_station_id', id)
      
      try {
        const res = await fetch(`/api/admin/stations/${id}/details`)
        if (res.ok) {
          const data = await res.json()
          masterList.value = (data.master_list || []).map(m => {
            const initialAging = Number(m.aging || 0)
            return {
              ...m,
              arrival_date: m.arrival_date || new Date(Date.now() - initialAging * 86400000).toISOString()
            }
          })
          setStatus(`Switched to station context successfully.`, 'success')
        }
      } catch (e) {
        console.error('Failed to sync station details', e)
      }
    }

    watch(rows, () => {
      try { localStorage.setItem(ROWS_KEY, JSON.stringify(rows.value)) } catch {}
    }, { deep: true })

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
        const c = r.code.trim().toUpperCase()
        if (c) map[c] = (map[c] || 0) + 1
      })
      return map
    })

    const missingItems = computed(() => {
      const scanned = scannedCodesMap.value
      return masterList.value.filter(item => {
        const code = (item.tracking_number || item).trim().toUpperCase()
        return !scanned[code]
      })
    })

    // Dynamic Live Aging Calculator
    function computeLiveAging(arrivalDateStr, baseAging = 0) {
      if (!arrivalDateStr) return Number(baseAging)
      const start = new Date(arrivalDateStr)
      const nowTime = new Date()
      const diffDays = Math.floor((nowTime - start) / (1000 * 60 * 60 * 24))
      return Math.max(0, Number(baseAging) + Math.max(0, diffDays))
    }

    const unifiedRows = computed(() => {
      const list = rows.value.map(r => {
        const cleanCode = r.code.trim().toUpperCase()
        let matchStatus = 'untracked'
        let matchedMasterItem = null

        if (masterList.value.length > 0) {
          matchedMasterItem = masterList.value.find(m => {
            const mCode = (m.tracking_number || m).trim().toUpperCase()
            return mCode === cleanCode
          })
          matchStatus = matchedMasterItem ? 'matched' : 'unexpected'
        }

        // Calculate live continuous aging
        const arrivalAnchor = matchedMasterItem?.arrival_date || r.arrival_date || new Date().toISOString()
        const baseCsvAging = matchedMasterItem?.aging ?? r.aging ?? 0
        const liveAging = computeLiveAging(arrivalAnchor, baseCsvAging)

        const hubValue = matchedMasterItem?.hub || r.hub || 'KE-3PL-Flexcom'

        let expiryStatus = 'normal'
        if (liveAging >= 8 && liveAging <= 9) {
          expiryStatus = 'urgent-red' // Days 8 & 9 highlight
        } else if (liveAging >= 10) {
          expiryStatus = 'expired-return' // Day 10+ (Reverse to Jumia)
        }

        return { 
          ...r, 
          hub: hubValue,
          aging: liveAging,
          source: 'physical', 
          matchStatus, 
          expiryStatus 
        }
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
      const trimmed = code.trim()
      if (!trimmed) {
        setStatus('Please enter a valid tracking code.', 'error')
        playAudioSignal('error')
        return false
      }

      // Duplicate Check across physical scanned rows
      if (scannedCodesMap.value[trimmed.toUpperCase()]) {
        setStatus(`Error: Item "${trimmed}" has already been scanned!`, 'error')
        playAudioSignal('error')
        return false
      }

      // Inherit master attributes if available
      const matchedMaster = masterList.value.find(m => (m.tracking_number || m).trim().toUpperCase() === trimmed.toUpperCase())
      const csvAging = matchedMaster?.aging ?? 0
      const arrivalAnchor = matchedMaster?.arrival_date || new Date(Date.now() - csvAging * 86400000).toISOString()

      rows.value.unshift({
        id: uid(),
        code: trimmed,
        hub: matchedMaster?.hub || 'KE-3PL-Flexcom',
        aging: csvAging,
        arrival_date: arrivalAnchor,
        ts: now(),
        method,
        note: ''
      })

      setStatus(`Saved: ${trimmed}`, 'success')
      playAudioSignal('success')
      return true
    }

    const removeRow = (id) => {
      rows.value = rows.value.filter((r) => r.id !== id)
    }

    async function openMode(m) {
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
      const lines = masterInputText.value
        .split('\n')
        .map(l => l.trim())
        .filter(l => l.length > 0)
      
      masterList.value = lines.map(line => {
        const parts = line.split(',')
        const trNum = parts[0]?.trim() || line
        const hubVal = parts[1]?.trim() || 'KE-3PL-Flexcom'
        const agingVal = Number(parts[2]?.trim() || 0)
        // Set arrival anchor backward by CSV aging days so counting continues automatically
        const arrivalIso = new Date(Date.now() - agingVal * 86400000).toISOString()

        return {
          tracking_number: trNum,
          hub: hubVal,
          aging: agingVal,
          arrival_date: arrivalIso
        }
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
      currentStationId,
      stationsList,
      switchStation,
      rows,
      masterList,
      settings,
      unifiedRows,
      missingItems,
      removeRow,
      mode,
      openMode,
      closeMode,
      typed,
      saveTyped,
      lastScan,
      sessionCount,
      statusMsg,
      masterInputText,
      loadMasterListFromText,
      clearMasterList
    }
  },
  template: `
    <div class="min-h-screen bg-slate-50 text-slate-900 font-sans antialiased pb-12">
      <!-- Top Header -->
      <header class="bg-teal-700 text-white shadow-md">
        <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col sm:flex-row justify-between items-center gap-3">
          <h1 class="text-lg font-bold tracking-tight">📦 Station Portal</h1>
          
          <!-- Station Switcher Dropdown -->
          <div class="w-full sm:w-auto">
            <select 
              v-model="currentStationId" 
              @change="switchStation(currentStationId)" 
              class="w-full sm:w-64 bg-teal-800 text-white border border-teal-600 rounded-lg px-3 py-1.5 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-white transition cursor-pointer"
            >
              <option v-for="s in stationsList" :value="s.id" :key="s.id">
                {{ s.unique_identifier }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <span class="bg-teal-800/80 text-teal-100 px-3 py-1 rounded-full text-xs font-semibold">Scanned: {{ rows.length }}</span>
            <span v-if="masterList.length" class="bg-teal-800/80 text-teal-100 px-3 py-1 rounded-full text-xs font-semibold">Expected: {{ masterList.length }}</span>
          </div>
        </div>
      </header>

      <main class="max-w-6xl mx-auto px-4 mt-6">
        <!-- Tools Action Bar -->
        <div class="grid grid-cols-3 gap-3 mb-6">
          <button 
            @click="openMode('camera')" 
            :class="mode === 'camera' ? 'border-teal-600 bg-teal-50 ring-2 ring-teal-500/20' : 'border-slate-200 bg-white hover:border-teal-400 hover:bg-slate-50'"
            class="flex flex-col items-center justify-center p-3 border rounded-xl shadow-sm transition cursor-pointer"
          >
            <span class="text-2xl mb-1">📷</span>
            <span class="text-xs font-semibold text-slate-700">Camera</span>
          </button>

          <button 
            @click="openMode('keyboard')" 
            :class="[
              mode === 'keyboard' ? 'border-teal-600 bg-teal-50 ring-2 ring-teal-500/20' : 'border-slate-200 bg-white hover:border-teal-400 hover:bg-slate-50',
              settings.disableManual ? 'opacity-40 cursor-not-allowed bg-slate-100' : ''
            ]"
            class="flex flex-col items-center justify-center p-3 border rounded-xl shadow-sm transition cursor-pointer"
          >
            <span class="text-2xl mb-1">⌨️</span>
            <span class="text-xs font-semibold text-slate-700">Manual</span>
          </button>

          <button 
            @click="openMode('admin')" 
            :class="mode === 'admin' ? 'border-teal-600 bg-teal-50 ring-2 ring-teal-500/20' : 'border-slate-200 bg-white hover:border-teal-400 hover:bg-slate-50'"
            class="flex flex-col items-center justify-center p-3 border rounded-xl shadow-sm transition cursor-pointer"
          >
            <span class="text-2xl mb-1">⚙️</span>
            <span class="text-xs font-semibold text-slate-700">Nyaga</span>
          </button>
        </div>

        <!-- Notification Banner -->
        <div v-if="statusMsg.text" :class="statusMsg.type === 'error' ? 'bg-red-50 text-red-800 border-red-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'" class="p-3 mb-6 border rounded-lg text-sm font-medium shadow-sm transition">
          {{ statusMsg.text }}
        </div>

        <!-- Camera Panel -->
        <section v-if="mode === 'camera'" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
          <div id="reader" class="w-full h-64 bg-slate-900 rounded-lg overflow-hidden flex items-center justify-center text-slate-400 text-sm">Initializing camera stream...</div>
          <div class="flex justify-between items-center mt-4">
            <p class="text-xs text-slate-600">Session count: <b class="text-slate-900">{{ sessionCount }}</b> <span v-if="lastScan">| Last: <code class="bg-slate-100 px-1 py-0.5 rounded text-teal-700">{{ lastScan }}</code></span></p>
            <button @click="closeMode" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition cursor-pointer">Close Camera</button>
          </div>
        </section>

        <!-- Keyboard Panel -->
        <section v-if="mode === 'keyboard'" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
          <div class="flex gap-2">
            <input 
              v-model="typed" 
              placeholder="Enter tracking number..." 
              @keydown.enter.prevent="saveTyped"
              autofocus
              class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
            />
            <button @click="saveTyped" :disabled="!typed.trim()" class="px-4 py-2 bg-teal-700 hover:bg-teal-800 disabled:opacity-50 text-white text-sm font-medium rounded-lg shadow-sm transition cursor-pointer">Save</button>
            <button @click="closeMode" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition cursor-pointer">Close</button>
          </div>
        </section>

        <!-- Admin Panel -->
        <section v-if="mode === 'admin'" class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6 space-y-4">
          <h3 class="font-bold text-slate-800 text-base">Admin System Controls</h3>
          
          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="settings.disableManual" id="lockManual" class="rounded text-teal-600 focus:ring-teal-500 w-4 h-4 cursor-pointer" />
            <label for="lockManual" class="text-sm font-medium text-slate-700 cursor-pointer">Disable Manual Type-in for Normal Users</label>
          </div>

          <div class="space-y-2">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Paste Master List (Format: tracking_number, hub, aging)</label>
            <textarea v-model="masterInputText" rows="4" placeholder="JE-VAR-366, KE-3PL-Flexcom, 4" class="w-full p-3 font-mono text-xs border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
            <div class="flex gap-2 pt-1">
              <button @click="loadMasterListFromText" :disabled="!masterInputText.trim()" class="px-4 py-2 bg-teal-700 hover:bg-teal-800 disabled:opacity-50 text-white text-sm font-medium rounded-lg shadow-sm transition cursor-pointer">📥 Import Master List</button>
              <button @click="clearMasterList" v-if="masterList.length" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium rounded-lg transition border border-red-200 cursor-pointer">🗑️ Clear System List</button>
              <button @click="closeMode" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition cursor-pointer">Close Admin</button>
            </div>
          </div>
          <p class="text-xs text-slate-500">System Master List contains <b class="text-slate-800">{{ masterList.length }}</b> expected packages.</p>
        </section>

        <!-- Reconciliation Data Table Card -->
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
                  <th class="p-3 text-center w-12">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-sm">
                <tr 
                  v-for="(r, i) in unifiedRows" 
                  :key="r.id"
                  :class="{
                    'bg-rose-100 border-l-4 border-rose-600 text-rose-900 font-semibold animate-pulse': r.aging >= 8 && r.aging <= 9,
                    'bg-red-200 border-l-4 border-red-700 font-bold': r.aging >= 10,
                    'bg-emerald-50/50': r.matchStatus === 'matched' && r.aging < 8,
                    'bg-amber-50/50': r.matchStatus === 'unexpected' && r.aging < 8,
                    'bg-rose-50/60': r.matchStatus === 'missing' && r.aging < 8
                  }"
                  class="transition hover:bg-slate-50/80"
                >
                  <td class="p-3 text-center text-xs font-semibold text-slate-400">{{ unifiedRows.length - i }}</td>
                  
                  <td class="p-3 font-mono">
                    <template v-if="r.source === 'physical'">
                      <input v-model="r.code" class="w-full bg-transparent border-0 focus:outline-none focus:ring-1 focus:ring-teal-500 rounded px-1" />
                    </template>
                    <template v-else>
                      <span class="text-slate-600">{{ r.code }}</span>
                    </template>
                  </td>

                  <td class="p-3 text-xs font-medium text-slate-600">
                    {{ r.hub }}
                  </td>

                  <td class="p-3">
                    <span class="px-2.5 py-1 rounded text-xs font-bold inline-block" :class="{
                      'bg-rose-200 text-rose-900 animate-pulse': r.aging >= 8 && r.aging <= 9,
                      'bg-red-300 text-red-950': r.aging >= 10,
                      'bg-slate-200 text-slate-700': r.aging < 8
                    }">
                      {{ r.aging }} Days
                    </span>
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
                    <template v-if="r.source === 'physical'">
                      <input v-model="r.note" placeholder="Add note..." class="w-full bg-transparent border-0 focus:outline-none focus:ring-1 focus:ring-teal-500 rounded px-1 text-slate-600 text-xs" />
                    </template>
                    <template v-else>
                      <span class="text-xs text-slate-400 italic">{{ r.note }}</span>
                    </template>
                  </td>

                  <td class="p-3 text-center">
                    <button v-if="r.source === 'physical'" @click="removeRow(r.id)" class="text-slate-400 hover:text-rose-600 font-bold transition cursor-pointer" title="Remove row">✕</button>
                  </td>
                </tr>

                <tr v-if="!unifiedRows.length">
                  <td colspan="9" class="p-8 text-center text-slate-400 text-sm">No packages recorded or loaded in master list. Use ⚙️ Admin Panel or 📷 Camera.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  `
}).mount('#app')