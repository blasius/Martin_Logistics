<template>
    <Transition name="fade">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
            <Transition name="pop">
                <div v-if="show" class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                            <LogOut class="w-8 h-8" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Confirm Logout</h3>
                        <p class="text-gray-500 mb-6">Are you sure you want to end your session? You will need to log in again to access the portal.</p>

                        <div class="flex gap-3">
                            <button @click="$emit('close')" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button @click="$emit('confirm')" class="flex-1 px-4 py-2.5 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 shadow-lg shadow-red-200 transition-all">
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<script setup>
import { LogOut } from 'lucide-vue-next';
defineProps(['show']);
defineEmits(['close', 'confirm']);
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.pop-enter-active { transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.pop-leave-active { transition: transform 0.2s ease-in; }
.pop-enter-from, .pop-leave-to { transform: scale(0.9); }
</style>
