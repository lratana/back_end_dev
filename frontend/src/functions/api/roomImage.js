// src/functions/api/roomImage.js

export async function apiUploadRoomImage(roomId, formData) {
    // Route: POST /room-images/upload/{roomId}
    // formData: image(file), is_primary(optional)
    return await window.axios.post(`${window.API_URL}/room-images/upload/${roomId}`, formData, {
        headers: { "Content-Type": "multipart/form-data" },
    });
}

export async function apiDeleteImageById(imageId) {
    // Route: DELETE /room-images/delete/{imageId}
    return await window.axios.delete(`${window.API_URL}/room-images/delete/${imageId}`);
}