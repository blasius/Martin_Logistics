<template>
    <div id="recaptcha-container"></div>
</template>

<script setup>
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from "firebase/auth";
import { initializeApp } from "firebase/app";

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);

const sendFirebaseCode = async (phoneNumber) => {
    const verifier = new RecaptchaVerifier('recaptcha-container', {}, auth);
    const confirmation = await signInWithPhoneNumber(auth, phoneNumber, verifier);
    window.confirmationResult = confirmation;
}

const verifyFirebaseCode = async (code) => {
    await window.confirmationResult.confirm(code);
    alert("Phone verified successfully!");
}
</script>
