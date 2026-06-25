<template>
    <div class="p-6 bg-slate-50 min-h-screen space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Documents</h1>
                <p class="text-xs font-bold text-slate-400">Upload &amp; manage all fleet documents</p>
            </div>
            <div class="flex gap-2">
                <button @click="showUploadModal = true" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl shadow-lg font-black text-xs flex items-center gap-2 uppercase">
                    <Upload class="w-4 h-4" /> Upload Document
                </button>
                <button @click="load" class="p-2.5 bg-white rounded-xl border border-slate-200 hover:bg-slate-50">
                    <RefreshCw class="w-4 h-4 text-slate-500" />
                </button>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase">Total</p>
                <p class="text-2xl font-black text-slate-800">{{ stats.total }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase">Expired</p>
                <p class="text-2xl font-black text-rose-600">{{ stats.expired }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase">Expiring Soon</p>
                <p class="text-2xl font-black text-amber-600">{{ stats.expiring_soon }}</p>
            </div>
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <p class="text-[10px] font-black text-slate-400 uppercase">Categories</p>
                <p class="text-2xl font-black text-slate-800">{{ Object.keys(stats.by_category || {}).length }}</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-wrap gap-4 items-center">
            <input v-model="filters.search" type="text" placeholder="Search name..." @input="debouncedLoad" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none w-48">
            <select v-model="filters.category" @change="load" class="bg-slate-50 border-none rounded-xl text-xs font-bold px-3 py-2 outline-none">
                <option value="">All Categories</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="flex items-center gap-2 text-xs font-bold text-slate-500">
                <input v-model="filters.expired" type="checkbox" @change="load" class="rounded">
                Show expired only
            </label>
        </div>

        <div v-if="loading" class="text-center py-20"><p class="text-slate-400 font-bold text-xs uppercase">Loading...</p></div>

        <div v-else-if="!documents.length" class="text-center py-20">
            <FolderOpen class="w-12 h-12 mx-auto text-slate-300 mb-4" />
            <p class="text-slate-400 font-bold text-xs uppercase">No documents found</p>
        </div>

        <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50/50 text-[10px] font-black text-slate-400 uppercase border-b">
                    <tr>
                        <th class="p-4">Name</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Size</th>
                        <th class="p-4">Expires</th>
                        <th class="p-4">Uploaded By</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="d in documents" :key="d.id" class="border-b border-slate-50 hover:bg-slate-50/50 text-xs">
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <FileText class="w-4 h-4 text-indigo-500 shrink-0" />
                                <span class="font-bold text-slate-700">{{ d.name }}</span>
                            </div>
                        </td>
                        <td class="p-4"><span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-1 rounded-lg">{{ d.category || '—' }}</span></td>
                        <td class="p-4 text-slate-400 text-[10px] font-mono">{{ d.mime_type?.split('/').pop() || '—' }}</td>
                        <td class="p-4 text-slate-500">{{ formatSize(d.size) }}</td>
                        <td class="p-4">
                            <span v-if="d.is_expired" class="text-[10px] font-black text-rose-600 bg-rose-50 px-2 py-1 rounded-lg">Expired</span>
                            <span v-else-if="d.days_until_expiry !== null && d.days_until_expiry <= 30" class="text-[10px] font-black text-amber-600 bg-amber-50 px-2 py-1 rounded-lg">{{ d.days_until_expiry }} days</span>
                            <span v-else-if="d.expires_at" class="text-slate-400">{{ formatDate(d.expires_at) }}</span>
                            <span v-else class="text-slate-300">—</span>
                        </td>
                        <td class="p-4 text-slate-600">{{ d.uploader?.name || '—' }}</td>
                        <td class="p-4 text-right">
                            <div class="flex gap-1 justify-end">
                                <button @click="download(d.id)" class="p-1.5 text-slate-400 hover:text-indigo-600" title="Download">
                                    <Download class="w-4 h-4" />
                                </button>
                                <button @click="confirmDelete(d)" class="p-1.5 text-slate-400 hover:text-rose-600" title="Delete">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showUploadModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200 max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-black text-slate-800 uppercase text-sm">Upload Document</h3>
                    <button @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600"><X class="w-5 h-5" /></button>
                </div>
                <form @submit.prevent="upload" class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">File *</label>
                        <input ref="fileInput" type="file" @change="onFileChange" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-black file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Document Name</label>
                            <input v-model="form.name" type="text" required class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Category</label>
                            <select v-model="form.category" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">Uncategorized</option>
                                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Entity Type</label>
                            <select v-model="form.documentable_type" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                                <option value="">General (no link)</option>
                                <option value="App\Models\Vehicle">Vehicle</option>
                                <option value="App\Models\Driver">Driver</option>
                                <option value="App\Models\Client">Client</option>
                                <option value="App\Models\Trip">Trip</option>
                                <option value="App\Models\User">User</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Entity ID</label>
                            <input v-model.number="form.documentable_id" type="number" min="0" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase">Expires At</label>
                            <input v-model="form.expires_at" type="date" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase">Notes</label>
                        <textarea v-model="form.notes" class="w-full mt-1 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold"></textarea>
                    </div>
                    <button type="submit" :disabled="uploading" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-xs uppercase disabled:opacity-50">
                        {{ uploading ? 'Uploading...' : 'Upload Document' }}
                    </button>
                </form>
            </div>
        </div>

        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="deleteTarget = null">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full border border-slate-200 p-6 text-center">
                <AlertTriangle class="w-10 h-10 mx-auto text-rose-500 mb-4" />
                <h3 class="font-black text-slate-800 uppercase text-sm mb-2">Delete Document?</h3>
                <p class="text-xs text-slate-500 mb-6">{{ deleteTarget.name }}</p>
                <div class="flex gap-3 justify-center">
                    <button @click="deleteTarget = null" class="px-4 py-2 bg-slate-100 rounded-xl text-xs font-black text-slate-600 uppercase">Cancel</button>
                    <button @click="doDelete" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-black uppercase">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { documentsApi } from '../../api/documents';
import { Upload, RefreshCw, FileText, Download, Trash2, FolderOpen, X, AlertTriangle } from 'lucide-vue-next';

const documents = ref([]);
const categories = ref([]);
const loading = ref(true);
const uploading = ref(false);
const showUploadModal = ref(false);
const deleteTarget = ref(null);
const fileInput = ref(null);
const stats = ref({ total: 0, expired: 0, expiring_soon: 0, by_category: {} });
const filters = ref({ search: '', category: '', expired: false });
let debounceTimer = null;

const form = ref({
    name: '', category: '', documentable_type: '', documentable_id: null,
    expires_at: '', notes: '',
});

async function load() {
    loading.value = true;
    try {
        const params = {};
        if (filters.value.search) params.name = filters.value.search;
        if (filters.value.category) params.category = filters.value.category;
        if (filters.value.expired) params.expired = true;
        const [docRes, statsRes, catRes] = await Promise.all([
            documentsApi.getAll(params),
            documentsApi.stats(),
            documentsApi.categories(),
        ]);
        documents.value = docRes.data.data || docRes.data || [];
        stats.value = statsRes.data;
        categories.value = catRes.data || [];
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
}

function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(load, 300);
}

function onFileChange(e) {
    const file = e.target.files[0];
    if (file && !form.value.name) {
        form.value.name = file.name.replace(/\.[^/.]+$/, '');
    }
}

async function upload() {
    uploading.value = true;
    try {
        const fd = new FormData();
        fd.append('file', fileInput.value.files[0]);
        fd.append('name', form.value.name || fileInput.value.files[0]?.name);
        if (form.value.category) fd.append('category', form.value.category);
        if (form.value.documentable_type) fd.append('documentable_type', form.value.documentable_type);
        if (form.value.documentable_id) fd.append('documentable_id', form.value.documentable_id);
        if (form.value.expires_at) fd.append('expires_at', form.value.expires_at);
        if (form.value.notes) fd.append('notes', form.value.notes);
        await documentsApi.store(fd);
        showUploadModal.value = false;
        form.value = { name: '', category: '', documentable_type: '', documentable_id: null, expires_at: '', notes: '' };
        await load();
    } catch (e) { console.error(e); }
    finally { uploading.value = false; }
}

async function download(id) {
    try {
        const res = await documentsApi.download(id);
        const url = window.URL.createObjectURL(new Blob([res.data]));
        const link = document.createElement('a');
        link.href = url;
        const disposition = res.headers['content-disposition'];
        const match = disposition?.match(/filename="?(.+?)"?$/);
        link.download = match?.[1] || 'document';
        link.click();
        window.URL.revokeObjectURL(url);
    } catch (e) { console.error(e); }
}

function confirmDelete(doc) { deleteTarget.value = doc; }

async function doDelete() {
    if (!deleteTarget.value) return;
    try {
        await documentsApi.destroy(deleteTarget.value.id);
        deleteTarget.value = null;
        await load();
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
