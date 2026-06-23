<template>
    <div>
        <div id="recaptcha-container"></div>
        <input v-model="fullNumber" placeholder="+250700123456" />
        <button @click="sendSms">Send SMS</button>

        <div v-if="showCode">
            <input v-model="smsCode" placeholder="123456" />
            <button @click="confirmCode">Verify</button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { auth, RecaptchaVerifier, signInWithPhoneNumber, initFirebase } from '@/plugins/firebase'
import { api } from '../../plugins/axios'

const fullNumber = ref('')
const smsCode = ref('')
const showCode = ref(false)
const firebaseReady = ref(false)
let confirmationResult = null

onMounted(async () => {
    await initFirebase()
    firebaseReady.value = !!auth
})

async function sendSms() {
    if (!auth) { alert('Firebase is not configured'); return }
    try {
        window.recaptchaVerifier = new RecaptchaVerifier('recaptcha-container', {
            size: 'invisible'
        }, auth)

        confirmationResult = await signInWithPhoneNumber(auth, fullNumber.value, window.recaptchaVerifier)
        showCode.value = true
        alert('SMS sent')
    } catch (e) {
        console.error(e)
        alert('Failed to send SMS')
    }
}

async function confirmCode() {
    try {
        const result = await confirmationResult.confirm(smsCode.value)
        const idToken = await result.user.getIdToken()

        // send idToken to server for verification and link contact
        const res = await api.post(`/api/contacts/${contactId}/verify-firebase`, { idToken })
        alert(res.data.message)
    } catch (e) {
        console.error(e)
        alert('Verification failed')
    }
}
</script>
