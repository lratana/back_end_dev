export async function apiGetRooms(params = {}) {
    return await window.axios.get(`${window.API_URL}/rooms`, { params });
}

export async function apiReadRoom(id) {
    return await window.axios.get(`${window.API_URL}/rooms/read/${id}`);
}

export async function apiCreateRoom(payload) {
    return await window.axios.post(`${window.API_URL}/rooms/create`, payload, {
        headers: { "Content-Type": "multipart/form-data" },
    });
}

export async function apiUpdateRoom(id, payload) {
    return await window.axios.post(`${window.API_URL}/rooms/update/${id}`, payload, {
        headers: { "Content-Type": "multipart/form-data" },
    });
}

export async function apiDeleteRoom(id) {
    return await window.axios.delete(`${window.API_URL}/rooms/delete/${id}`);
}

export async function apiDeleteRoomImage(roomId, imageId) {
    return await window.axios.delete(`${window.API_URL}/rooms/delete-image/${roomId}/${imageId}`);
}