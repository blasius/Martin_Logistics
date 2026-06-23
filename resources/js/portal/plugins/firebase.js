import { initializeApp, getApps } from "firebase/app";
import { getAuth, RecaptchaVerifier, signInWithPhoneNumber } from "firebase/auth";

let auth = null;

export async function initFirebase() {
    if (getApps().length > 0) {
        auth = getAuth(getApps()[0]);
        return;
    }

    let config;
    try {
        const { api } = await import("./axios");
        const { data } = await api.get("/portal/settings/firebase");
        config = data;
    } catch {
        console.warn("Firebase: failed to fetch config, skipping initialization");
        return;
    }

    if (!config.configured) {
        console.warn("Firebase: not configured in settings, skipping initialization");
        return;
    }

    const app = initializeApp({
        apiKey: config.apiKey,
        authDomain: config.authDomain,
        projectId: config.projectId,
    });

    auth = getAuth(app);
}

export { auth, RecaptchaVerifier, signInWithPhoneNumber };
