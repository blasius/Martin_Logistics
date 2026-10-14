<template>
    <div class="h-screen flex flex-col bg-slate-50 overflow-hidden relative">
        <Transition name="slide-fade">
            <div v-if="notification.show" class="fixed bottom-8 right-8 z-[130] flex items-center gap-3 px-6 py-3 bg-slate-900 text-white rounded-xl shadow-2xl border border-slate-700 no-print">
                <Check class="w-4 h-4 text-emerald-400" />
                <span class="text-sm font-bold">{{ notification.message }}</span>
            </div>
        </Transition>

        <header class="bg-white border-b border-slate-200 px-8 py-4 flex flex-wrap items-center justify-between gap-4 shadow-sm z-10 no-print">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-indigo-600 rounded-lg"><Wrench class="w-6 h-6 text-white" /></div>
                <div>
                    <h1 class="text-xl font-black text-slate-800 uppercase leading-none">Mechanic Performance</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1.5">Workshop completion, turnaround &amp; ratings</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Period Start</label>
                    <input v-model="periodStart" type="date" class="px-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[9px] font-black uppercase tracking-widest text-slate-400 mb-1">Period End</label>
                    <input v-model="periodEnd" type="date" class="px-3 py-2 bg-slate-100 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>
                <button @click="loadMechanics" class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-xs font-black rounded-lg hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                    <RefreshCw v-if="!loading" class="w-4 h-4" />
                    <span v-else class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    Refresh
                </button>
            </div>
        </header>

        <div class="px-8 py-3 bg-slate-800 text-white text-[10px] font-black uppercase tracking-widest grid grid-cols-12 gap-4 shadow-lg z-10 no-print">
            <div class="col-span-3">Mechanic</div>
            <div class="col-span-1 text-center">Overall</div>
            <div class="col-span-1 text-center">Automated</div>
            <div class="col-span-2 text-center">Human</div>
            <div class="col-span-1 text-center">Jobs</div>
            <div class="col-span-1 text-center">Done</div>
            <div class="col-span-2 text-center">Turnaround</div>
            <div class="col-span-1 text-right">Actions</div>
        </div>

        <div class="flex-1 overflow-y-auto px-8 py-4 space-y-2 custom-scrollbar">
            <div v-if="loading" class="flex flex-col items-center justify-center py-24 gap-3">
                <span class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></span>
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Loading mechanics...</p>
            </div>

            <template v-else>
                <div v-for="m in mechanics" :key="m.id"
                     class="bg-white border border-slate-200 rounded-lg p-3 grid grid-cols-12 gap-4 items-center transition-all hover:border-indigo-300">
                    <div class="col-span-3">
                        <div class="font-black text-slate-900 uppercase tracking-tighter">{{ m.name }}</div>
                        <div class="text-[10px] font-bold text-slate-400">{{ m.email }}</div>
                    </div>

                    <div class="col-span-1 text-center">
                        <span v-if="m.score" :class="scoreBadge(m.score.overall_score)" class="px-2 py-0.5 rounded text-xs font-black">
                            {{ m.score.overall_score }}
                        </span>
                        <span v-else class="text-[10px] font-bold text-slate-300 uppercase">No score</span>
                    </div>

                    <div class="col-span-1 text-center text-sm font-bold text-slate-600">{{ m.score?.automated_score ?? '-' }}</div>

                    <div class="col-span-2 text-center text-sm font-bold text-slate-600">
                        {{ m.score?.human_rating_avg ? m.score.human_rating_avg + '/5' : '-' }}
                        <span v-if="m.score?.human_rating_count" class="text-slate-400 text-xs">({{ m.score.human_rating_count }})</span>
                    </div>

                    <div class="col-span-1 text-center text-sm font-bold text-slate-600">{{ m.stats?.assignments_total ?? 0 }}</div>

                    <div class="col-span-1 text-center text-sm font-bold text-slate-600">
                        {{ m.stats?.completion_rate != null ? m.stats.completion_rate + '%' : '-' }}
                    </div>

                    <div class="col-span-2 text-center text-sm font-bold text-slate-600">
                        {{ m.stats?.avg_turnaround_minutes != null ? m.stats.avg_turnaround_minutes + 'm' : '-' }}
                        <span v-if="m.stats?.unresolved_releases" class="ml-2 text-[10px] font-black text-rose-500 uppercase">{{ m.stats.unresolved_releases }} open</span>
                    </div>

                    <div class="col-span-1 text-right">
                        <button @click="calculateMechanic(m.id)"
                                class="text-[10px] font-black uppercase bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200 transition-all">
                            Calculate
                        </button>
                    </div>
                </div>

                <div v-if="mechanics.length === 0" class="text-center py-24 text-slate-300 text-xs font-black uppercase tracking-widest">
                    No mechanics found.
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Check, RefreshCw, Wrench } from 'lucide-vue-next'
import { ratingsApi } from '../../api/ratings'

const mechanics = ref([])
const loading = ref(false)
const periodStart = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0])
const periodEnd = ref(new Date().toISOString().split('T')[0])
const notification = ref({ show: false, message: '' })

let notificationTimer = null

function notify(message) {
  notification.value = { show: true, message }
  clearTimeout(notificationTimer)
  notificationTimer = setTimeout(() => { notification.value.show = false }, 3000)
}

function scoreBadge(score) {
  if (score >= 80) return 'bg-green-100 text-green-800'
  if (score >= 60) return 'bg-yellow-100 text-yellow-800'
  return 'bg-red-100 text-red-800'
}

async function loadMechanics() {
  loading.value = true
  try {
    const res = await ratingsApi.availableMechanics()
    const users = res.data.data || []
    const profiles = await Promise.all(
      users.map(async (u) => {
        try {
          const p = await ratingsApi.mechanicProfile(u.id, periodStart.value, periodEnd.value)
          return p.data.data || { mechanic: u, score: null, stats: null, ratings: [] }
        } catch {
          return { mechanic: u, score: null, stats: null, ratings: [] }
        }
      })
    )
    mechanics.value = profiles.map((p) => ({
      id: p.mechanic.id,
      name: p.mechanic.name,
      email: p.mechanic.email,
      score: p.score,
      stats: p.stats,
    }))
  } catch (e) {
    console.error('Failed to load mechanics', e)
    notify('Failed to load mechanics.')
  } finally {
    loading.value = false
  }
}

async function calculateMechanic(userId) {
  try {
    await ratingsApi.calculateMechanicScore(userId, periodStart.value, periodEnd.value)
    notify('Mechanic score calculated.')
    await loadMechanics()
  } catch (e) {
    console.error('Failed to calculate', e)
    notify('Failed to calculate score.')
  }
}

onMounted(loadMechanics)
</script>
