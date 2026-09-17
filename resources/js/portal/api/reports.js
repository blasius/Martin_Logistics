import { api } from '../../plugins/axios';

export const reportsApi = {
    profitability(params = {}) {
        return api.get('/portal/reports/profitability', { params });
    },
    costing(params = {}) {
        return api.get('/portal/reports/costing', { params });
    },
    stops(params = {}) {
        return api.get('/portal/reports/stops', { params });
    },
    stopsOptions() {
        return api.get('/portal/reports/stops/options');
    },
    options() {
        return api.get('/portal/reports/profitability/options');
    },
};
