import { api } from '../../plugins/axios';

export const walletsApi = {
    index(params) {
        return api.get('/portal/wallets', { params });
    },
    show(id) {
        return api.get(`/portal/wallets/${id}`);
    },
    myWallet(params) {
        return api.get('/portal/wallets/my', { params });
    },
    myTransactions(params) {
        return api.get('/portal/wallets/my/transactions', { params });
    },
    transactions(id, params) {
        return api.get(`/portal/wallets/${id}/transactions`, { params });
    },
    storeTransaction(data) {
        return api.post('/portal/wallets/transaction', data);
    },
    acknowledge(transactionId) {
        return api.post(`/portal/wallets/transactions/${transactionId}/acknowledge`);
    },
    settle(id, data) {
        return api.post(`/portal/wallets/${id}/settle`, data);
    },
    settlements(id) {
        return api.get(`/portal/wallets/${id}/settlements`);
    },
};
