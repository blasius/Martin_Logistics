import { api } from '../../plugins/axios';

export const tripsApi = {
    searchOrders(q) {
        return api.get('/portal/orders/search', { params: { q } });
    },
    searchAssignments(q) {
        return api.get('/portal/trips/search-assignments', { params: { q } });
    },
    getAllRoutes() {
        return api.get('/portal/routes'); // Re-use the existing routes endpoint
    },
    createTrip(data) {
        return api.post('/portal/trips', data);
    }
};
