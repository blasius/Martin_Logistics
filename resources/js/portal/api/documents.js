import { api } from '../../plugins/axios';

export const documentsApi = {
    getAll(params) { return api.get('/portal/documents', { params }); },
    show(id) { return api.get(`/portal/documents/${id}`); },
    store(formData) { return api.post('/portal/documents', formData, { headers: { 'Content-Type': 'multipart/form-data' } }); },
    update(id, data) { return api.put(`/portal/documents/${id}`, data); },
    destroy(id) { return api.delete(`/portal/documents/${id}`); },
    download(id) { return api.get(`/portal/documents/${id}/download`, { responseType: 'blob' }); },
    stats() { return api.get('/portal/documents/stats'); },
    categories() { return api.get('/portal/documents/categories'); },
};

export const documentTemplatesApi = {
    getAll(params) { return api.get('/portal/document-templates', { params }); },
    show(id) { return api.get(`/portal/document-templates/${id}`); },
    store(data) { return api.post('/portal/document-templates', data); },
    update(id, data) { return api.put(`/portal/document-templates/${id}`, data); },
    destroy(id) { return api.delete(`/portal/document-templates/${id}`); },
    preview(id, data) { return api.post(`/portal/document-templates/${id}/preview`, { data }); },
};
