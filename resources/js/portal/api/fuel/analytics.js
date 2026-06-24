import { api } from '../../../plugins/axios';

export const fuelAnalyticsApi = {
    consumptionReport(params) {
        return api.get('/portal/fuel/analytics/consumption', { params });
    },
    analyseTrip(data) {
        return api.post('/portal/fuel/analytics/analyse-trip', data);
    },
    rateDriver(data) {
        return api.post('/portal/fuel/analytics/rate-driver', data);
    },
    driverRankings(params) {
        return api.get('/portal/fuel/analytics/driver-rankings', { params });
    },
    pumpToTankVariance(params) {
        return api.get('/portal/fuel/analytics/pump-to-tank-variance', { params });
    },
};
