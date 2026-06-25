import { api } from '../../plugins/axios';

export const apiKeysApi = {
    index() {
        return api.get('/portal/api-keys');
    },
    store(data) {
        return api.post('/portal/api-keys', data);
    },
    destroy(id) {
        return api.delete(`/portal/api-keys/${id}`);
    },
};
