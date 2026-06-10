<template>
    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-6">
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 w-full max-w-md overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h1 class="text-xl font-black text-slate-800 tracking-tight uppercase italic">Reset Password</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Choose a new password for your account</p>
            </div>

            <div v-if="success" class="p-8 text-center">
                <div class="mx-auto w-14 h-14 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-2">Password Changed</h2>
                <p class="text-xs font-bold text-slate-500 mb-6">Your password has been reset successfully.</p>
                <router-link to="/login"
                             class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-10 py-3.5 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 uppercase tracking-widest">
                    Go to Login
                </router-link>
            </div>

            <div v-else class="p-6 space-y-5">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">New Password</label>
                    <input v-model="form.password" type="password" placeholder="Min 8 characters"
                           class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 block mb-2 tracking-wide">Confirm Password</label>
                    <input v-model="form.password_confirmation" type="password" placeholder="Repeat new password"
                           class="border-slate-200 px-4 py-3.5 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none bg-slate-50 w-full transition-all" />
                </div>

                <p v-if="error" class="text-[9px] font-bold text-red-500 text-center">{{ error }}</p>

                <button @click="submitReset"
                        :disabled="submitting"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl text-xs font-black transition-all shadow-lg shadow-blue-200 active:scale-95 disabled:opacity-40 disabled:shadow-none uppercase tracking-widest">
                    {{ submitting ? 'Resetting...' : 'Reset Password' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { profileApi } from '../../api/profile';

const route = useRoute();
const form = ref({ password: '', password_confirmation: '' });
const submitting = ref(false);
const error = ref('');
const success = ref(false);

const email = ref('');
const token = ref('');

onMounted(() => {
    email.value = route.query.email || '';
    token.value = route.query.token || '';
    if (!email.value || !token.value) {
        error.value = 'Invalid or expired reset link.';
    }
});

const submitReset = async () => {
    if (form.value.password.length < 8) {
        error.value = 'Password must be at least 8 characters.';
        return;
    }
    if (form.value.password !== form.value.password_confirmation) {
        error.value = 'Passwords do not match.';
        return;
    }

    submitting.value = true;
    error.value = '';
    try {
        await profileApi.resetPassword({
            email: email.value,
            token: token.value,
            password: form.value.password,
            password_confirmation: form.value.password_confirmation,
        });
        success.value = true;
    } catch (e) {
        if (e.response?.data?.errors?.email) {
            error.value = e.response.data.errors.email[0];
        } else {
            error.value = 'Failed to reset password. The link may have expired.';
        }
    } finally {
        submitting.value = false;
    }
};
</script>
