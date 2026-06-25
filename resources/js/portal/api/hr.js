import api from './index';

export default {
    stats() { return api.get('/hr/stats'); },
    // Departments
    departments: {
        index() { return api.get('/hr/departments'); },
        store(data) { return api.post('/hr/departments', data); },
        update(id, data) { return api.put(`/hr/departments/${id}`, data); },
        destroy(id) { return api.delete(`/hr/departments/${id}`); },
    },
    // Positions
    positions: {
        index(params) { return api.get('/hr/positions', { params }); },
        store(data) { return api.post('/hr/positions', data); },
        update(id, data) { return api.put(`/hr/positions/${id}`, data); },
        destroy(id) { return api.delete(`/hr/positions/${id}`); },
    },
    // Employees
    employees: {
        index(params) { return api.get('/hr/employees', { params }); },
        store(data) { return api.post('/hr/employees', data); },
        show(id) { return api.get(`/hr/employees/${id}`); },
        update(id, data) { return api.put(`/hr/employees/${id}`, data); },
    },
    // Attendance
    attendance: {
        index(params) { return api.get('/hr/attendance', { params }); },
        clockIn(data) { return api.post('/hr/attendance/clock-in', data); },
        clockOut(data) { return api.post('/hr/attendance/clock-out', data); },
    },
    // Leave
    leaveTypes: {
        index() { return api.get('/hr/leave-types'); },
        store(data) { return api.post('/hr/leave-types', data); },
    },
    leaveRequests: {
        index(params) { return api.get('/hr/leave-requests', { params }); },
        store(data) { return api.post('/hr/leave-requests', data); },
        approve(id) { return api.post(`/hr/leave-requests/${id}/approve`); },
        reject(id, reason) { return api.post(`/hr/leave-requests/${id}/reject`, { reason }); },
        cancel(id) { return api.post(`/hr/leave-requests/${id}/cancel`); },
    },
    leaveBalances(params) { return api.get('/hr/leave-balances', { params }); },
    // Payroll
    payPeriods: {
        index() { return api.get('/hr/pay-periods'); },
        store(data) { return api.post('/hr/pay-periods', data); },
        close(id) { return api.post(`/hr/pay-periods/${id}/close`); },
    },
    payslips: {
        index(params) { return api.get('/hr/payslips', { params }); },
        generate(data) { return api.post('/hr/payslips/generate', data); },
        approve(id, data) { return api.post(`/hr/payslips/${id}/approve`, data); },
        markPaid(id) { return api.post(`/hr/payslips/${id}/mark-paid`); },
    },
};
