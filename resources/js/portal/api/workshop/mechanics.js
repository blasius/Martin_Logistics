import { api } from '../../../plugins/axios';

export const mechanicsApi = {
    index() {
        return api.get('/portal/workshop/mechanics');
    },
    store(data) {
        return api.post('/portal/workshop/mechanics', data);
    },
};
