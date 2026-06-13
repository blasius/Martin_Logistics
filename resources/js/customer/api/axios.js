import axios from 'axios'

export const customerApi = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || window.location.origin + '/api',
    withCredentials: true,
    withXSRFToken: true,
    xsrfCookieName: 'XSRF-TOKEN',
    xsrfHeaderName: 'X-XSRF-TOKEN',
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

customerApi.interceptors.response.use(
    (res) => res,
    (error) => {
        const status = error.response?.status
        const isLoginPage = window.location.pathname.includes('/customer/login')
        if ((status === 401 || status === 419) && !isLoginPage) {
            window.location.href = '/customer/login'
        }
        return Promise.reject(error)
    }
)

export async function ensureCsrfCookie() {
    return axios.get(window.location.origin + '/sanctum/csrf-cookie', { withCredentials: true })
}

export default customerApi
