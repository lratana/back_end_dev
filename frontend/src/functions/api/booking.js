export async function apiGetBookings(params = {}) {
    return await window.axios.get(`${window.API_URL}/bookings`, { params });
}

export async function apiGetBooking(id) {
    return await window.axios.get(`${window.API_URL}/bookings/read/${id}`);
}

export async function apiCreateBooking(payload) {
    return await window.axios.post(`${window.API_URL}/bookings/create`, payload);
}

export async function apiUpdateBooking(id, payload) {
    return await window.axios.put(`${window.API_URL}/bookings/update/${id}`, payload);
}

export async function apiRequestCancelBooking(id) {
    return await window.axios.put(`${window.API_URL}/bookings/request-cancel/${id}`);
}

export async function apiApproveBooking(id) {
    return await window.axios.put(`${window.API_URL}/bookings/approve/${id}`);
}

export async function apiRejectBooking(id) {
    return await window.axios.put(`${window.API_URL}/bookings/reject/${id}`);
}

export async function apiConfirmCancelBooking(id) {
    return await window.axios.put(`${window.API_URL}/bookings/confirm-cancel/${id}`);
}

export async function apiAdminCancelBooking(id) {
    return await window.axios.put(`${window.API_URL}/bookings/admin-cancel/${id}`);
}

export async function apiDeleteBooking(id) {
    return await window.axios.delete(`${window.API_URL}/bookings/delete/${id}`);
}