import api from './index';

export default {
    stats() { return api.get('/portal/hr/stats'); },
    // Departments
    departments: {
        index() { return api.get('/portal/hr/departments'); },
        store(data) { return api.post('/portal/hr/departments', data); },
        update(id, data) { return api.put(`/portal/hr/departments/${id}`, data); },
        destroy(id) { return api.delete(`/portal/hr/departments/${id}`); },
    },
    // Positions
    positions: {
        index(params) { return api.get('/portal/hr/positions', { params }); },
        store(data) { return api.post('/portal/hr/positions', data); },
        update(id, data) { return api.put(`/portal/hr/positions/${id}`, data); },
        destroy(id) { return api.delete(`/portal/hr/positions/${id}`); },
    },
    // Employees
    employees: {
        index(params) { return api.get('/portal/hr/employees', { params }); },
        store(data) { return api.post('/portal/hr/employees', data); },
        show(id) { return api.get(`/portal/hr/employees/${id}`); },
        update(id, data) { return api.put(`/portal/hr/employees/${id}`, data); },
    },
    // Attendance
    attendance: {
        index(params) { return api.get('/portal/hr/attendance', { params }); },
        clockIn(data) { return api.post('/portal/hr/attendance/clock-in', data); },
        clockOut(data) { return api.post('/portal/hr/attendance/clock-out', data); },
    },
    // Leave
    leaveTypes: {
        index() { return api.get('/portal/hr/leave-types'); },
        store(data) { return api.post('/portal/hr/leave-types', data); },
    },
    leaveRequests: {
        index(params) { return api.get('/portal/hr/leave-requests', { params }); },
        store(data) { return api.post('/portal/hr/leave-requests', data); },
        approve(id) { return api.post(`/portal/hr/leave-requests/${id}/approve`); },
        reject(id, reason) { return api.post(`/portal/hr/leave-requests/${id}/reject`, { reason }); },
        cancel(id) { return api.post(`/portal/hr/leave-requests/${id}/cancel`); },
    },
    leaveBalances(params) { return api.get('/portal/hr/leave-balances', { params }); },
    // Payroll
    payPeriods: {
        index() { return api.get('/portal/hr/pay-periods'); },
        store(data) { return api.post('/portal/hr/pay-periods', data); },
        close(id) { return api.post(`/portal/hr/pay-periods/${id}/close`); },
    },
    payslips: {
        index(params) { return api.get('/portal/hr/payslips', { params }); },
        generate(data) { return api.post('/portal/hr/payslips/generate', data); },
        approve(id, data) { return api.post(`/portal/hr/payslips/${id}/approve`, data); },
        markPaid(id) { return api.post(`/portal/hr/payslips/${id}/mark-paid`); },
    },
};
