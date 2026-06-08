export function apiGetUsers(params = {}) {
    return axios.get(window.API_URL + '/users', { params });
}

export function apiGetDetailUsers(params = {}) {
    return axios.get(window.API_URL + `/manage/users`, { params });
}
// export function apiGetDetailUser
export function apiReadDetailUser(id) {
    return axios.get(window.API_URL + `/manage/users/read/${id}`);
}

export function apiCreateUser(data) {
    return axios.post(window.API_URL + `/manage/users/create`, data, {
        headers: {
            "Content-Type": "multipart/form-data",
        },
    });
}

export function apiUpdateUser(id, data) {
    return axios.post(window.API_URL + `/manage/users/update/${id}`, data, {
        headers: {
            "Content-Type": "multipart/form-data",
        },
    });
}
// Update user level (admin/user)
export function apiUpdateUserLevel(id, level) {
    return axios.put(window.API_URL + `/manage/users/${id}/level`, {
        level,
    });
}

export function apiDeleteUser(id) {
    return axios.delete(window.API_URL + `/manage/users/delete/${id}`);
}