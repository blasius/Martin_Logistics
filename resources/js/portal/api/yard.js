import { api } from '../../plugins/axios';

export const yardApi = {
    dashboard() {
        return api.get('/portal/workshop/yard/dashboard');
    },
    dockDoors() {
        return api.get('/portal/workshop/yard/dock-doors');
    },
    storeDockDoor(data) {
        return api.post('/portal/workshop/yard/dock-doors', data);
    },
    updateDockDoor(id, data) {
        return api.put(`/portal/workshop/yard/dock-doors/${id}`, data);
    },
    deleteDockDoor(id) {
        return api.delete(`/portal/workshop/yard/dock-doors/${id}`);
    },
    assignDockDoor(queueId, dockDoorId) {
        return api.post('/portal/workshop/yard/assign-dock-door', { queue_id: queueId, dock_door_id: dockDoorId });
    },
    releaseDockDoor(id) {
        return api.post(`/portal/workshop/yard/dock-doors/${id}/release`);
    },
    yardEntries(params) {
        return api.get('/portal/workshop/yard/entries', { params });
    },
    checkIn(data) {
        return api.post('/portal/workshop/yard/check-in', data);
    },
    checkOut(id) {
        return api.post(`/portal/workshop/yard/entries/${id}/check-out`);
    },
    queueByType(serviceType, params) {
        return api.get(`/portal/workshop/yard/queue/${serviceType}`, { params });
    },
    waitTime(serviceType) {
        return api.get(`/portal/workshop/yard/wait-time/${serviceType}`);
    },
};
