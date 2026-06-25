import { api } from '../../plugins/axios';

export const chartOfAccountsApi = {
    getAll(params = {}) {
        return api.get('/portal/accounting/chart-of-accounts', { params });
    },
    show(id) {
        return api.get(`/portal/accounting/chart-of-accounts/${id}`);
    },
    create(data) {
        return api.post('/portal/accounting/chart-of-accounts', data);
    },
    update(id, data) {
        return api.put(`/portal/accounting/chart-of-accounts/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/accounting/chart-of-accounts/${id}`);
    },
};

export const fiscalYearsApi = {
    getAll(params = {}) {
        return api.get('/portal/accounting/fiscal-years', { params });
    },
    show(id) {
        return api.get(`/portal/accounting/fiscal-years/${id}`);
    },
    create(data) {
        return api.post('/portal/accounting/fiscal-years', data);
    },
    update(id, data) {
        return api.put(`/portal/accounting/fiscal-years/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/accounting/fiscal-years/${id}`);
    },
};

export const journalEntriesApi = {
    getAll(params = {}) {
        return api.get('/portal/accounting/journal-entries', { params });
    },
    show(id) {
        return api.get(`/portal/accounting/journal-entries/${id}`);
    },
    create(data) {
        return api.post('/portal/accounting/journal-entries', data);
    },
    update(id, data) {
        return api.put(`/portal/accounting/journal-entries/${id}`, data);
    },
    destroy(id) {
        return api.delete(`/portal/accounting/journal-entries/${id}`);
    },
    post(id) {
        return api.post(`/portal/accounting/journal-entries/${id}/post`);
    },
    reverse(id, data) {
        return api.post(`/portal/accounting/journal-entries/${id}/reverse`, data);
    },
};

export const accountingReportsApi = {
    trialBalance(params = {}) {
        return api.get('/portal/accounting/reports/trial-balance', { params });
    },
    profitLoss(params = {}) {
        return api.get('/portal/accounting/reports/profit-loss', { params });
    },
    balanceSheet(params = {}) {
        return api.get('/portal/accounting/reports/balance-sheet', { params });
    },
};
