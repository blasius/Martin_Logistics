import { api } from '../../plugins/axios';

export const reportsApi = {
    profitability(params = {}) {
        return api.get('/portal/reports/profitability', { params });
    },
    costing(params = {}) {
        return api.get('/portal/reports/costing', { params });
    },
    options() {
        return api.get('/portal/reports/profitability/options');
    },
};
