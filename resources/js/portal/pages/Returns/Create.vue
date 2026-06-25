<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import returnsApi from '../../api/returns'

const router = useRouter()

const form = ref({
    order_id: '',
    client_id: '',
    reason: '',
    notes: '',
    items: [{ description: '', quantity: 1, reason: '' }],
})

const orders = ref<any[]>([])
const clients = ref<any[]>([])
const submitting = ref(false)

async function loadReferences() {
    try {
        const [oRes, cRes] = await Promise.all([
            (await import('../../api/orders')).default.index({ per_page: 200 }),
            (await import('../../api/clients')).default.index({ per_page: 200 }),
        ])
        orders.value = oRes.data.data ?? oRes.data
        clients.value = cRes.data.data ?? cRes.data
    } catch {}
}

function addItem() {
    form.value.items.push({ description: '', quantity: 1, reason: '' })
}

function removeItem(i: number) {
    if (form.value.items.length > 1) form.value.items.splice(i, 1)
}

async function submit() {
    submitting.value = true
    try {
        const res = await returnsApi.store(form.value)
        router.push(`/returns/${res.data.id}`)
    } catch (e: any) {
        alert(e.response?.data?.message || 'Failed to create return request')
    } finally { submitting.value = false }
}

onMounted(loadReferences)
</script>

<template>
    <div>
        <div class="flex items-center gap-4 mb-6">
            <router-link to="/returns" class="text-slate-400 hover:text-slate-600">&larr; Back</router-link>
            <h1 class="text-2xl font-bold text-slate-800">New Return Request</h1>
        </div>

        <form @submit.prevent="submit" class="max-w-3xl space-y-6">
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-700">Return Details</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Client</label>
                        <select v-model="form.client_id" required
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                            <option value="">Select client...</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.user?.name ?? c.id }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Order</label>
                        <select v-model="form.order_id" required
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500">
                            <option value="">Select order...</option>
                            <option v-for="o in orders" :key="o.id" :value="o.id">{{ o.reference }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Reason for Return</label>
                    <textarea v-model="form.reason" required rows="2"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Notes (optional)</label>
                    <textarea v-model="form.notes" rows="2"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-700">Return Items</h2>
                    <button type="button" @click="addItem"
                        class="text-sm px-3 py-1.5 border border-emerald-200 text-emerald-600 rounded-lg hover:bg-emerald-50 transition-colors">
                        + Add Item
                    </button>
                </div>

                <div v-for="(item, i) in form.items" :key="i"
                    class="flex gap-3 items-start p-3 bg-slate-50 rounded-lg">
                    <div class="flex-1">
                        <input v-model="item.description" placeholder="Description" required
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div class="w-24">
                        <input v-model.number="item.quantity" type="number" min="1" placeholder="Qty" required
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div class="flex-1">
                        <input v-model="item.reason" placeholder="Reason (optional)"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <button type="button" @click="removeItem(i)"
                        class="px-2 py-2 text-red-400 hover:text-red-600 transition-colors text-lg leading-none">&times;</button>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <router-link to="/returns"
                    class="px-4 py-2 text-sm text-slate-600 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancel
                </router-link>
                <button type="submit" :disabled="submitting"
                    class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium disabled:opacity-50">
                    {{ submitting ? 'Submitting...' : 'Create Return Request' }}
                </button>
            </div>
        </form>
    </div>
</template>
