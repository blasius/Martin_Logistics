import { api } from '../../plugins/axios';

export const rolesApi = {
    getAll() {
        return api.get('/portal/roles/manage');
    },

    get(id) {
        return api.get(`/portal/roles/manage/${id}`);
    },

    create(data) {
        return api.post('/portal/roles/manage', data);
    },

    update(id, data) {
        return api.put(`/portal/roles/manage/${id}`, data);
    },

    destroy(id) {
        return api.delete(`/portal/roles/manage/${id}`);
    },

    permissionsList() {
        return api.get('/portal/roles/manage/permissions-list');
    },

    permissionsGrouped() {
        return api.get('/portal/roles/manage/permissions-grouped');
    },

    assignToUser(data) {
        return api.post('/portal/roles/manage/assign-to-user', data);
    },

    auditLog() {
        return api.get('/portal/roles/manage/audit-log');
    },
};
