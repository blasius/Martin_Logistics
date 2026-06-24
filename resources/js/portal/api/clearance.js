import { api } from '../../plugins/axios';

export const clearanceApi = {
    check(params) {
        return api.post('/portal/clearance/check', params);
    },
    requestBypass(data) {
        return api.post('/portal/clearance/request-bypass', data);
    },
    bypassRequests(params) {
        return api.get('/portal/clearance/bypass-requests', { params });
    },
    approveBypass(id, data) {
        return api.post(`/portal/clearance/approve-bypass/${id}`, data);
    },
    rejectBypass(id, data) {
        return api.post(`/portal/clearance/reject-bypass/${id}`, data);
    },
};
