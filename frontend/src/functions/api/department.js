// src/functions/api/department.js

export async function apiGetDepartments(params = {}) {
    // params: { q }
    return await window.axios.get(`${window.API_URL}/departments`, { params });
}

export async function apiCreateDepartment(payload) {
    return await window.axios.post(`${window.API_URL}/departments/create`, payload);
}

export async function apiReadDepartment(id) {
    return await window.axios.get(`${window.API_URL}/departments/read/${id}`);
}

export async function apiUpdateDepartment(id, payload) {
    return await window.axios.put(`${window.API_URL}/departments/update/${id}`, payload);
}

export async function apiDeleteDepartment(id) {
    return await window.axios.delete(`${window.API_URL}/departments/delete/${id}`);
}