import { api } from '../../plugins/axios';

export const loadOptimizationApi = {
    vehicles(params = {}) {
        return api.get('/portal/load-optimization/vehicles', { params });
    },
    optimize(data) {
        return api.post('/portal/load-optimization/optimize', data);
    },
    suitability(vehicleId, weight, volume) {
        return api.get(`/portal/load-optimization/vehicles/${vehicleId}/suitability`, { params: { weight, volume } });
    },
};
