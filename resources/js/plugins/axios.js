// resources/js/portal/plugins/axios.js
import axios from "axios";

// Base URL — use environment variable or default to your backend
const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || "https://martin_logistics.test",
    withCredentials: true, // critical for Sanctum cookie auth
    headers: {
        "Accept": "application/json",
    },
});

// --- 🛡 CSRF Handling (for Sanctum SPA mode) ---
async function ensureCsrfCookie() {
    try {
        await api.get("/sanctum/csrf-cookie");
    } catch (error) {
        console.error("Failed to get CSRF cookie:", error);
    }
}

// --- 🔐 Token Handling (for mobile/driver mode) ---
function setAuthToken(token) {
    if (token) {
        api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
    } else {
        delete api.defaults.headers.common["Authorization"];
    }
}

// --- 🧩 Auto Retry Logic for Expired Tokens ---
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        if (error.response?.status === 401) {
            console.warn("Unauthorized: clearing token");
            localStorage.removeItem("token");
        }
        return Promise.reject(error);
    }
);

export { api, ensureCsrfCookie, setAuthToken };
