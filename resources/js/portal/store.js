import { defineStore } from 'pinia'
import axios from './api'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: JSON.parse(localStorage.getItem('user') || 'null'),
    }),
    getters: {
        isAuthenticated: (s) => !!s.token,
    },
    actions: {
        async login(identifier, password) {
            const { data } = await axios.post('/login', { identifier, password })
            this.token = data.token
            this.user = data.user
            localStorage.setItem('token', this.token)
            localStorage.setItem('user', JSON.stringify(this.user))
            return data
        },
        logout() {
            this.token = null
            this.user = null
            localStorage.clear()
        },
    },
})
