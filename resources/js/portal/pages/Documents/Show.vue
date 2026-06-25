<template>
    <div class="p-6 bg-slate-50 min-h-screen">
        <div v-if="loading" class="text-center py-20"><p class="text-slate-400 font-bold text-xs uppercase">Loading...</p></div>
        <div v-else-if="!doc" class="text-center py-20"><p class="text-slate-400 font-bold text-xs uppercase">Document not found</p></div>
        <div v-else class="max-w-3xl mx-auto space-y-6">
            <button @click="$router.push('/documents')" class="flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-indigo-600">
                <ArrowLeft class="w-4 h-4" /> Back to Documents
            </button>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-xl font-black text-slate-800 uppercase">{{ doc.name }}</h1>
                        <p class="text-xs text-slate-400 mt-1">{{ doc.mime_type }} &middot; {{ formatSize(doc.size) }}</p>
                    </div>
                    <div class="flex gap-2">
                        <a :href="downloadUrl" class="p-2.5 bg-indigo-600 text-white rounded-xl" title="Download">
                            <Download class="w-4 h-4" />
                        </a>
                        <button @click="confirmDelete" class="p-2.5 bg-rose-100 text-rose-600 rounded-xl" title="Delete">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4 mt-6 text-xs">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase">Category</p>
                        <p class="font-bold text-slate-700 mt-1">{{ doc.category || 'Uncategorized' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase">Expires</p>
                        <p v-if="doc.is_expired" class="font-bold text-rose-600 mt-1">Expired {{ formatDate(doc.expires_at) }}</p>
                        <p v-else-if="doc.expires_at" class="font-bold text-slate-700 mt-1">{{ formatDate(doc.expires_at) }}</p>
                        <p v-else class="text-slate-400 mt-1">No expiry</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase">Uploaded By</p>
                        <p class="font-bold text-slate-700 mt-1">{{ doc.uploader?.name || '—' }}</p>
                    </div>
                </div>

                <div v-if="doc.notes" class="mt-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Notes</p>
                    <p class="text-xs text-slate-600 mt-1">{{ doc.notes }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-black text-slate-800 uppercase text-sm mb-4">Update Metadata</h3>
                <form @submit.prevent="update" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Name</label>
                            <input v-model="editForm.name" type="text" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Category</label>
                            <select v-model="editForm.category" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Uncategorized</option>
                                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Expires At</label>
                            <input v-model="editForm.expires_at" type="date" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Notes</label>
                        <textarea v-model="editForm.notes" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase">Save</button>
                </form>
            </div>
        </div>

        <div v-if="showDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showDelete = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-slate-200 p-6 text-center">
                <AlertTriangle class="w-10 h-10 mx-auto text-rose-500 mb-4" />
                <h3 class="font-black text-slate-800 uppercase text-sm mb-2">Delete Document?</h3>
                <p class="text-xs text-slate-500 mb-6">This cannot be undone.</p>
                <div class="flex gap-3 justify-center">
                    <button @click="showDelete = false" class="px-4 py-2 bg-slate-100 rounded-xl text-xs font-black text-slate-600 uppercase">Cancel</button>
                    <button @click="doDelete" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-black uppercase">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { documentsApi } from '../../api/documents';
import { ArrowLeft, Download, Trash2, AlertTriangle } from 'lucide-vue-next';

const route = useRoute();
const doc = ref(null);
const categories = ref([]);
const loading = ref(true);
const showDelete = ref(false);
const editForm = ref({ name: '', category: '', expires_at: '', notes: '' });

const downloadUrl = computed(() => {
    if (!doc.value?.id) return '#';
    return `${import.meta.env.VITE_API_BASE_URL || '/api'}/portal/documents/${doc.value.id}/download`;
});

async function load() {
    loading.value = true;
    try {
        const [docRes, catRes] = await Promise.all([
            documentsApi.show(route.params.id),
            documentsApi.categories(),
        ]);
        doc.value = docRes.data;
        categories.value = catRes.data || [];
        editForm.value = {
            name: doc.value.name || '',
            category: doc.value.category || '',
            expires_at: doc.value.expires_at ? doc.value.expires_at.slice(0, 10) : '',
            notes: doc.value.notes || '',
        };
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

async function update() {
    try {
        await documentsApi.update(doc.value.id, editForm.value);
        await load();
    } catch (e) { console.error(e); }
}

function confirmDelete() { showDelete.value = true; }

async function doDelete() {
    try {
        await documentsApi.destroy(doc.value.id);
        showDelete.value = false;
        window.location.href = '/portal/documents';
    } catch (e) { console.error(e); }
}

function formatSize(bytes) {
    if (!bytes) return '—';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function formatDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(load);
</script>
