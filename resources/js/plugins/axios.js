import axios from "axios";

// 1. Export the 'api' instance
export const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || "https://martin-logistics.test/api",
    withCredentials: true,
    withXSRFToken: true, // <--- This is the crucial modern Axios setting
    xsrfCookieName: "XSRF-TOKEN",
    xsrfHeaderName: "X-XSRF-TOKEN",
    headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

// 2. Export the CSRF function
export async function ensureCsrfCookie() {
    const baseUrl = (import.meta.env.VITE_API_BASE_URL || "https://martin-logistics.test").replace('/api', '');
    return axios.get(`${baseUrl}/sanctum/csrf-cookie`, { withCredentials: true });
}

// 3. Export the missing setAuthToken function
export function setAuthToken(token) {
    if (token) {
        api.defaults.headers.common["Authorization"] = `Bearer ${token}`;
    } else {
        delete api.defaults.headers.common["Authorization"];
    }
}

// Interceptor logic
api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;
        const configUrl = error.config?.url; // Define the URL from the config
        const isLoginPage = window.location.pathname.includes('portal/login');

        // 1. If it's the 'user' check failing on boot, don't redirect.
        // Let authStore.checkAuth handle it silently.
        if (status === 401 && configUrl === 'user') {
            return Promise.reject(error);
        }

        // 2. For any other 401, redirect to login if not already there.
        if (status === 401 && !isLoginPage) {
            window.location.href = '/portal/login';
        }

        return Promise.reject(error);
    }
);

export default api;
