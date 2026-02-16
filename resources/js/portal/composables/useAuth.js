// resources/js/portal/composables/useAuth.js
import { ref } from "vue";
import { api, ensureCsrfCookie, setAuthToken } from "@/plugins/axios.js";

const user = ref(null);
const token = ref(localStorage.getItem("token") || null);
const loading = ref(false);
const error = ref(null);

// ✅ initialize token if present
if (token.value) {
    setAuthToken(token.value);
    fetchUser().catch(() => logout());
}

async function fetchUser() {
    try {
        const { data } = await api.get("/user");
        user.value = data;
    } catch (err) {
        console.error("Failed to fetch user:", err);
        throw err;
    }
}

async function login(identifier, password) {
    loading.value = true;
    error.value = null;

    try {
        // For Sanctum: ensure CSRF cookie exists first
        await ensureCsrfCookie();

        const { data } = await api.post("/login", { identifier, password });
        user.value = data.user;

        if (data.token) {
            token.value = data.token;
            localStorage.setItem("token", data.token);
            setAuthToken(data.token);
        }

        return true;
    } catch (err) {
        error.value = err.response?.data?.message || "Login failed";
        return false;
    } finally {
        loading.value = false;
    }
}

async function logout() {
    try {
        await api.post("/logout");
    } catch (e) {
        console.warn("Logout error (ignored):", e);
    }
    user.value = null;
    token.value = null;
    localStorage.removeItem("token");
    setAuthToken(null);
}

async function register(payload) {
    loading.value = true;
    error.value = null;

    try {
        await ensureCsrfCookie();
        const { data } = await api.post("/register", payload);
        user.value = data.user;
        return data;
    } catch (err) {
        error.value = err.response?.data?.message || "Registration failed";
        throw err;
    } finally {
        loading.value = false;
    }
}

export function useAuth() {
    return {
        user,
        token,
        loading,
        error,
        login,
        logout,
        register,
        fetchUser,
    };
}
