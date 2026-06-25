import { api } from '../../plugins/axios';

export const ratingsApi = {
    leaderboard(periodStart, periodEnd, limit = 20, sort = 'desc') {
        return api.get('/portal/ratings/leaderboard', {
            params: { period_start: periodStart, period_end: periodEnd, limit, sort },
        });
    },
    topDrivers(periodStart, periodEnd, limit = 10) {
        return api.get('/portal/ratings/top-drivers', {
            params: { period_start: periodStart, period_end: periodEnd, limit },
        });
    },
    bottomDrivers(periodStart, periodEnd, limit = 10) {
        return api.get('/portal/ratings/bottom-drivers', {
            params: { period_start: periodStart, period_end: periodEnd, limit },
        });
    },
    driverProfile(driverId, periodStart, periodEnd) {
        return api.get(`/portal/ratings/drivers/${driverId}`, {
            params: { period_start: periodStart, period_end: periodEnd },
        });
    },
    dispatcherProfile(userId, periodStart, periodEnd) {
        return api.get(`/portal/ratings/dispatchers/${userId}`, {
            params: { period_start: periodStart, period_end: periodEnd },
        });
    },
    submitRating(data) {
        return api.post('/portal/ratings/submit', data);
    },
    calculateScores(periodStart, periodEnd) {
        return api.post('/portal/ratings/calculate', {
            period_start: periodStart,
            period_end: periodEnd,
        });
    },
    calculateDriverScore(driverId, periodStart, periodEnd) {
        return api.post(`/portal/ratings/drivers/${driverId}/calculate`, {
            period_start: periodStart,
            period_end: periodEnd,
        });
    },
    calculateDispatcherScore(userId, periodStart, periodEnd) {
        return api.post(`/portal/ratings/dispatchers/${userId}/calculate`, {
            period_start: periodStart,
            period_end: periodEnd,
        });
    },
    availableDrivers() {
        return api.get('/portal/ratings/available-drivers');
    },
    availableDispatchers() {
        return api.get('/portal/ratings/available-dispatchers');
    },
};
