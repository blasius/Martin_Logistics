<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Document Templates</h1>
                <p class="text-sm text-slate-500 mt-1">Manage print and export templates</p>
            </div>
            <router-link to="/documents/templates/create" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors">
                <Plus class="w-4 h-4" /> New Template
            </router-link>
        </div>

        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="bg-white rounded-xl border border-slate-100 p-5 animate-pulse">
                <div class="h-4 bg-slate-100 rounded w-3/4 mb-3"></div>
                <div class="h-3 bg-slate-50 rounded w-1/2 mb-4"></div>
                <div class="h-8 bg-slate-50 rounded w-full"></div>
            </div>
        </div>

        <div v-else-if="templates.length === 0" class="bg-white rounded-2xl border border-slate-100 p-12 text-center">
            <FileText class="w-16 h-16 text-slate-200 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-slate-700 mb-1">No templates yet</h3>
            <p class="text-sm text-slate-500 mb-6">Create your first document template for printing and exports.</p>
            <router-link to="/documents/templates/create" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors">
                <Plus class="w-4 h-4" /> Create Template
            </router-link>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="t in templates" :key="t.id" class="bg-white rounded-xl border border-slate-100 p-5 hover:shadow-sm transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center">
                            <FileText class="w-4 h-4 text-orange-600" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm">{{ t.name }}</h3>
                            <p class="text-xs text-slate-400">{{ t.category || 'Uncategorized' }}</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full" :class="t.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'">
                        {{ t.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <p v-if="t.description" class="text-xs text-slate-500 mb-4 line-clamp-2">{{ t.description }}</p>
                <div class="flex items-center gap-2">
                    <button @click="openPreview(t)" class="text-xs text-orange-600 hover:text-orange-700 font-medium">Preview</button>
                    <button @click="editTemplate(t)" class="text-xs text-slate-500 hover:text-slate-700 font-medium">Edit</button>
                    <button @click="confirmDelete(t)" class="text-xs text-red-500 hover:text-red-700 font-medium ml-auto">Delete</button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <transition name="fade">
            <div v-if="preview" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-6 max-h-[80vh] flex flex-col" @click.stop>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-800">{{ preview.name }}</h3>
                        <button @click="preview = null" class="p-1 hover:bg-slate-100 rounded-lg transition-colors">
                            <X class="w-5 h-5 text-slate-400" />
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto bg-slate-50 rounded-lg p-6">
                        <pre class="text-xs text-slate-600 whitespace-pre-wrap font-mono">{{ preview.content }}</pre>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Delete Confirm -->
        <transition name="fade">
            <div v-if="deleting" class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center" @click.stop>
                    <AlertTriangle class="w-12 h-12 text-red-400 mx-auto mb-3" />
                    <h3 class="text-lg font-semibold text-slate-800 mb-1">Delete Template?</h3>
                    <p class="text-sm text-slate-500 mb-6">This cannot be undone.</p>
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { FileText, Plus, X, AlertTriangle } from 'lucide-vue-next';
import { documentTemplatesApi } from '../../../api/documents';

const router = useRouter();
const templates = ref([]);
const loading = ref(true);
const preview = ref(null);
const deleting = ref(null);

async function fetchTemplates() {
    loading.value = true;
    try {
        const res = await documentTemplatesApi.getAll({ per_page: 50 });
        templates.value = res.data.data;
    } catch {} finally {
        loading.value = false;
    }
}

function openPreview(t) {
    preview.value = t;
}

function editTemplate(t) {
    router.push(`/documents/templates/${t.id}/edit`);
}

function confirmDelete(t) {
    deleting.value = t;
}

async function submitDelete() {
    try {
        await documentTemplatesApi.destroy(deleting.value.id);
        templates.value = templates.value.filter(t => t.id !== deleting.value.id);
        deleting.value = null;
    } catch {}
}

onMounted(fetchTemplates);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
