import { api } from '../../plugins/axios';

export const podApi = {
    getAll(params = {}) {
        return api.get('/portal/proofs-of-delivery', { params });
    },

    show(id) {
        return api.get(`/portal/proofs-of-delivery/${id}`);
    },

    create(data) {
        return api.post('/portal/proofs-of-delivery', data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
    },

    update(id, data) {
        return api.post(`/portal/proofs-of-delivery/${id}`, data, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
    },

    confirm(id) {
        return api.post(`/portal/proofs-of-delivery/${id}/confirm`);
    },

    reject(id, reason) {
        return api.post(`/portal/proofs-of-delivery/${id}/reject`, { reason });
    },

    downloadPdf(id) {
        return api.get(`/portal/proofs-of-delivery/${id}/pdf`, {
            responseType: 'blob',
        });
    },
};
