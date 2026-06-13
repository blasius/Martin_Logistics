import { defineStore } from 'pinia'
import { customerApi, ensureCsrfCookie } from '../api/axios'

export const useAuthStore = defineStore('customerAuth', {
    state: () => ({
        user: null,
        initialized: false,
    }),
    actions: {
        async signup(data) {
            await ensureCsrfCookie()
            const res = await customerApi.post('/customer/auth/signup', data)
            this.user = res.data.user
            this.initialized = true
            return res.data
        },
        async login(email, password) {
            await ensureCsrfCookie()
            const res = await customerApi.post('/customer/auth/login', { email, password })
            if (res.data.requires_email_verification) {
                this.initialized = true
                return res.data
            }
            this.user = res.data.user
            this.initialized = true
            return res.data
        },
        async verifyEmail(code) {
            const res = await customerApi.post('/customer/auth/verify-email', { code })
            await this.checkAuth()
            return res.data
        },
        async resendVerification() {
            return await customerApi.post('/customer/auth/resend-verification')
        },
        async checkAuth() {
            try {
                const res = await customerApi.get('/customer/auth/me')
                this.user = res.data
            } catch {
                this.user = null
            } finally {
                this.initialized = true
            }
        },
        async logout() {
            await customerApi.post('/customer/auth/logout')
            this.user = null
            window.location.href = '/customer/login'
        },
    },
})
