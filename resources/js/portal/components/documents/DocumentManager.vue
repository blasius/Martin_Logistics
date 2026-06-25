<template>
    <div class="bg-white rounded-xl border border-slate-100">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <FileText class="w-5 h-5 text-slate-500" />
                <h3 class="font-semibold text-slate-700">Documents</h3>
                <span v-if="documents.length" class="text-xs text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">{{ documents.length }}</span>
            </div>
            <button @click="showUpload = true" class="flex items-center gap-1.5 text-sm text-orange-600 hover:text-orange-700 font-medium px-3 py-1.5 rounded-lg hover:bg-orange-50 transition-colors">
                <Upload class="w-4 h-4" /> Upload
            </button>
        </div>

        <div v-if="loading" class="p-5 space-y-3">
            <div v-for="i in 2" :key="i" class="flex items-center gap-3 animate-pulse">
                <div class="w-10 h-10 bg-slate-100 rounded-lg"></div>
                <div class="flex-1 space-y-1.5">
                    <div class="h-3 bg-slate-100 rounded w-3/4"></div>
                    <div class="h-2 bg-slate-50 rounded w-1/3"></div>
                </div>
            </div>
        </div>

        <div v-else-if="documents.length === 0" class="p-8 text-center">
            <FileText class="w-10 h-10 text-slate-200 mx-auto mb-2" />
            <p class="text-sm text-slate-500">No documents attached</p>
        </div>

        <div v-else class="divide-y divide-slate-50">
            <div v-for="doc in documents" :key="doc.id"
                class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors group">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                    :class="iconClass(doc.mime_type)">
                    <component :is="fileIcon(doc.mime_type)" class="w-4 h-4" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-medium text-slate-700 truncate">{{ doc.name }}</p>
                        <span v-if="doc.expires_at" class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                            :class="expiryClass(doc)">
                            {{ expiryLabel(doc) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">{{ formatSize(doc.size) }} &middot; {{ doc.category || 'Uncategorized' }}</p>
                </div>
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="download(doc)" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors" title="Download">
                        <Download class="w-4 h-4" />
                    </button>
                    <button @click="editDocument(doc)" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors" title="Edit">
                        <Pencil class="w-4 h-4" />
                    </button>
                    <button @click="confirmDelete(doc)" class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition-colors" title="Delete">
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <transition name="fade">
            <div v-if="showUpload" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-800">Upload Document</h3>
                        <button @click="showUpload = false" class="p-1 hover:bg-slate-100 rounded-lg transition-colors">
                            <X class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">File</label>
                            <input type="file" @change="onFileSelect" ref="fileInput"
                                class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 transition-colors" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Name (optional)</label>
                            <input v-model="uploadForm.name" type="text" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all" placeholder="Auto from filename" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                                <select v-model="uploadForm.category" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                                    <option value="">Select category</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Expires At</label>
                                <input v-model="uploadForm.expires_at" type="date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                            <textarea v-model="uploadForm.notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <div v-if="uploadProgress > 0" class="mt-4">
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-orange-500 rounded-full transition-all duration-300" :style="{ width: uploadProgress + '%' }"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1 text-right">{{ uploadProgress }}%</p>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="showUpload = false" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancel</button>
                        <button @click="submitUpload" :disabled="uploading || !selectedFile" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors disabled:opacity-50">
                            <span v-if="uploading">Uploading...</span>
                            <span v-else>Upload</span>
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Edit Modal -->
        <transition name="fade">
            <div v-if="editing" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-800">Edit Document</h3>
                        <button @click="editing = null" class="p-1 hover:bg-slate-100 rounded-lg transition-colors">
                            <X class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                            <input v-model="editForm.name" type="text" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                                <select v-model="editForm.category" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none">
                                    <option value="">Select</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Expires At</label>
                                <input v-model="editForm.expires_at" type="date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                            <textarea v-model="editForm.notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button @click="editing = null" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancel</button>
                        <button @click="submitEdit" :disabled="saving" class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors disabled:opacity-50">
                            {{ saving ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Delete Confirm -->
        <transition name="fade">
            <div v-if="deleting" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">
                    <AlertTriangle class="w-12 h-12 text-red-400 mx-auto mb-3" />
                    <h3 class="text-lg font-semibold text-slate-800 mb-1">Delete Document?</h3>
                    <p class="text-sm text-slate-500 mb-6">This action cannot be undone.</p>
                    <div class="flex justify-center gap-3">
                        <button @click="deleting = null" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancel</button>
                        <button @click="submitDelete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">Delete</button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { FileText, Upload, Download, Pencil, Trash2, X, AlertTriangle, File, Image, FileArchive } from 'lucide-vue-next';
import { documentsApi } from '../../api/documents';

const props = defineProps({
    documentableType: { type: String, required: true },
    documentableId: { type: [Number, String], required: true },
});

const emit = defineEmits(['updated']);

const documents = ref([]);
const categories = ref([]);
const loading = ref(false);
const showUpload = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const selectedFile = ref(null);
const fileInput = ref(null);
const editing = ref(null);
const deleting = ref(null);
const saving = ref(false);

const uploadForm = ref({ name: '', category: '', expires_at: '', notes: '' });
const editForm = ref({ name: '', category: '', expires_at: '', notes: '' });

function fileIcon(mime) {
    if (!mime) return File;
    if (mime.startsWith('image/')) return Image;
    if (mime.includes('pdf')) return FileText;
    if (mime.includes('zip') || mime.includes('rar')) return FileArchive;
    return File;
}

function iconClass(mime) {
    if (!mime) return 'bg-slate-100 text-slate-500';
    if (mime.startsWith('image/')) return 'bg-pink-100 text-pink-600';
    if (mime.includes('pdf')) return 'bg-red-100 text-red-600';
    if (mime.includes('zip') || mime.includes('rar')) return 'bg-amber-100 text-amber-600';
    return 'bg-slate-100 text-slate-500';
}

function formatSize(bytes) {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    let size = bytes;
    while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
    return `${size.toFixed(1)} ${units[i]}`;
}

function expiryClass(doc) {
    if (!doc.expires_at) return '';
    const days = doc.days_until_expiry;
    if (days < 0) return 'bg-red-100 text-red-700';
    if (days <= 14) return 'bg-orange-100 text-orange-700';
    return 'bg-green-100 text-green-700';
}

function expiryLabel(doc) {
    if (!doc.expires_at) return '';
    const days = doc.days_until_expiry;
    if (days < 0) return 'Expired';
    if (days === 0) return 'Expires today';
    if (days === 1) return 'Expires tomorrow';
    return `${days}d left`;
}

function onFileSelect(e) {
    selectedFile.value = e.target.files[0];
}

async function fetchDocuments() {
    loading.value = true;
    try {
        const res = await documentsApi.getAll({
            documentable_type: props.documentableType,
            documentable_id: props.documentableId,
            per_page: 100,
        });
        documents.value = res.data.data;
    } catch {} finally {
        loading.value = false;
    }
}

async function fetchCategories() {
    try {
        const res = await documentsApi.categories();
        categories.value = res.data;
    } catch {}
}

async function submitUpload() {
    if (!selectedFile.value) return;
    uploading.value = true;
    uploadProgress.value = 0;
    try {
        const fd = new FormData();
        fd.append('file', selectedFile.value);
        fd.append('documentable_type', props.documentableType);
        fd.append('documentable_id', props.documentableId);
        if (uploadForm.value.name) fd.append('name', uploadForm.value.name);
        if (uploadForm.value.category) fd.append('category', uploadForm.value.category);
        if (uploadForm.value.expires_at) fd.append('expires_at', uploadForm.value.expires_at);
        if (uploadForm.value.notes) fd.append('notes', uploadForm.value.notes);

        await documentsApi.store(fd);
        showUpload.value = false;
        uploadForm.value = { name: '', category: '', expires_at: '', notes: '' };
        selectedFile.value = null;
        uploadProgress.value = 0;
        await fetchDocuments();
        emit('updated');
    } catch {} finally {
        uploading.value = false;
    }
}

function editDocument(doc) {
    editing.value = doc;
    editForm.value = {
        name: doc.name,
        category: doc.category || '',
        expires_at: doc.expires_at || '',
        notes: doc.notes || '',
    };
}

async function submitEdit() {
    saving.value = true;
    try {
        await documentsApi.update(editing.value.id, editForm.value);
        editing.value = null;
        await fetchDocuments();
        emit('updated');
    } catch {} finally {
        saving.value = false;
    }
}

function confirmDelete(doc) {
    deleting.value = doc;
}

async function submitDelete() {
    try {
        await documentsApi.destroy(deleting.value.id);
        deleting.value = null;
        await fetchDocuments();
        emit('updated');
    } catch {}
}

async function download(doc) {
    try {
        const res = await documentsApi.download(doc.id);
        const url = window.URL.createObjectURL(new Blob([res.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', doc.name);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch {}
}

watch(() => props.documentableId, () => { fetchDocuments(); });

onMounted(() => {
    fetchDocuments();
    fetchCategories();
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
