import axios from "axios";

export function apiGetNotifications(params = {}) {
    return axios.get(`${window.API_URL}/notifications`, {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("token") || ""}`,
        },
        params,
    });
}

export function apiGetUnreadNotifications() {
    return axios.get(`${window.API_URL}/notifications/unread`, {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("token") || ""}`,
        },
    });
}

export function apiMarkNotificationAsRead(id) {
    return axios.post(
        `${window.API_URL}/notifications/${id}/read`,
        {},
        {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token") || ""}`,
            },
        }
    );
}

export function apiMarkAllNotificationsAsRead() {
    return axios.post(
        `${window.API_URL}/notifications/read-all`,
        {},
        {
            headers: {
                Authorization: `Bearer ${localStorage.getItem("token") || ""}`,
            },
        }
    );
}

export function apiDeleteNotification(id) {
    return axios.delete(`${window.API_URL}/notifications/${id}`, {
        headers: {
            Authorization: `Bearer ${localStorage.getItem("token") || ""}`,
        },
    });
}