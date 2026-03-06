// src/functions/api/room.js

export async function apiGetRooms(params = {}) {
    // params: { q, per_page, page }
    return await window.axios.get(`${window.API_URL}/rooms`, { params });
}

export async function apiReadRoom(id) {
    return await window.axios.get(`${window.API_URL}/rooms/read/${id}`);
}

export async function apiCreateRoom(formData) {
    // multipart/form-data (thumbnail + images[])
    return await window.axios.post(`${window.API_URL}/rooms/create`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
    });
}

export async function apiUpdateRoom(id, formData) {
    // Your route uses POST /rooms/update/{room}
    return await window.axios.post(`${window.API_URL}/rooms/update/${id}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
    });
}

export async function apiDeleteRoom(id) {
    return await window.axios.delete(`${window.API_URL}/rooms/delete/${id}`);
}

export async function apiDeleteRoomImage(roomId, imageId) {
    // Route: DELETE /rooms/delete-image/{room}/{image}
    return await window.axios.delete(`${window.API_URL}/rooms/delete-image/${roomId}/${imageId}`);
}