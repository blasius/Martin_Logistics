import { customerApi } from './axios'

export async function fetchOrders() {
    const res = await customerApi.get('/customer/orders')
    return res.data
}

export async function fetchOrder(id) {
    const res = await customerApi.get(`/customer/orders/${id}`)
    return res.data
}

export async function createOrder(data) {
    const res = await customerApi.post('/customer/orders', data)
    return res.data
}
