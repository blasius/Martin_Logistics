<template>
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <router-link to="/documents/templates" class="p-2 rounded-lg hover:bg-slate-100 transition-colors">
                <ArrowLeft class="w-5 h-5 text-slate-500" />
            </router-link>
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ isEdit ? 'Edit Template' : 'New Template' }}</h1>
                <p class="text-sm text-slate-500 mt-1">{{ isEdit ? 'Update the document template' : 'Create a new document print template' }}</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-100 p-6 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                        <input v-model="form.name" type="text" required
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
                            placeholder="e.g., Delivery Note" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Slug</label>
                        <input v-model="form.slug" type="text"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all"
                            placeholder="Auto-generated" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
                    <select v-model="form.category"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all">
                        <option value="">Uncategorized</option>
                        <option value="print">Print</option>
                        <option value="export">Export</option>
                        <option value="report">Report</option>
                        <option value="letter">Letter</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea v-model="form.description" rows="2"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all resize-none"></textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-slate-700">Template Content</label>
                        <span class="text-xs text-slate-400">Use {{ variable }} placeholders</span>
                    </div>
                    <textarea v-model="form.content" rows="15" required
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 outline-none transition-all font-mono"
                        placeholder="<h1>Delivery Note</h1><p>Order: {{order_reference}}</p>"></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" type="checkbox" v-model="form.is_active"
                        class="rounded border-slate-300 text-orange-600 focus:ring-orange-500/20" />
                    <label for="is_active" class="text-sm text-slate-700">Active</label>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <router-link to="/documents/templates" class="px-4 py-2 text-sm text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">Cancel</router-link>
                <button type="submit" :disabled="saving"
                    class="px-4 py-2 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 rounded-lg transition-colors disabled:opacity-50">
                    {{ saving ? 'Saving...' : (isEdit ? 'Update Template' : 'Create Template') }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { ArrowLeft } from 'lucide-vue-next';
import { documentTemplatesApi } from '../../../api/documents';

const router = useRouter();
const route = useRoute();

const isEdit = computed(() => !!route.params.id);
const form = ref({ name: '', slug: '', description: '', content: '', category: '', is_active: true });
const saving = ref(false);

async function fetchTemplate() {
    if (!isEdit.value) return;
    try {
        const res = await documentTemplatesApi.show(route.params.id);
        const t = res.data;
        form.value = {
            name: t.name,
            slug: t.slug,
            description: t.description || '',
            content: t.content,
            category: t.category || '',
            is_active: t.is_active,
        };
    } catch {
        router.push('/documents/templates');
    }
}

async function submit() {
    saving.value = true;
    try {
        if (isEdit.value) {
            await documentTemplatesApi.update(route.params.id, form.value);
        } else {
            await documentTemplatesApi.store(form.value);
        }
        router.push('/documents/templates');
    } catch {} finally {
        saving.value = false;
    }
}

onMounted(fetchTemplate);
</script>
