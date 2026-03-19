<template>
    <li class="nav-item dropdown">
        <a class="nav-link" href="#" role="button" @click.prevent="toggleDropdown">
            <i class="far fa-bell"></i>
            <span v-if="unreadCount > 0" class="badge badge-danger navbar-badge">
                {{ unreadCount > 99 ? "99+" : unreadCount }}
            </span>
        </a>

        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" :class="{ show: isOpen }"
            style="max-height: 420px; overflow-y: auto; min-width: 360px;">
            <span class="dropdown-item dropdown-header">
                {{ unreadCount }} Unread Notification{{ unreadCount === 1 ? "" : "s" }}
            </span>

            <div class="dropdown-divider"></div>

            <template v-if="loading">
                <div class="dropdown-item text-center py-3">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading...
                </div>
            </template>

            <template v-else-if="notifications.length === 0">
                <div class="dropdown-item text-center text-muted py-3">
                    No notifications found.
                </div>
            </template>

            <template v-else>
                <div v-for="item in notifications" :key="item.id" class="dropdown-item notification-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1 pr-2" style="cursor: pointer;" @click="handleOpenNotification(item)">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-circle text-danger mr-2" style="font-size: 8px;"
                                    v-if="!item.read_at"></i>
                                <strong class="text-sm">
                                    {{ item.data?.title || "Notification" }}
                                </strong>
                            </div>

                            <div class="text-sm text-muted mt-1">
                                {{ item.data?.message || "-" }}
                            </div>

                            <div class="text-xs text-muted mt-1">
                                <i class="far fa-clock mr-1"></i>
                                {{ formatDate(item.created_at) }}
                            </div>
                        </div>

                        <div class="btn-group btn-group-sm">
                            <button v-if="!item.read_at" type="button" class="btn btn-light btn-sm" title="Mark as read"
                                @click.stop="markAsRead(item)">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-light btn-sm text-danger" title="Delete"
                                @click.stop="removeNotification(item)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <div class="dropdown-divider"></div>

            <div class="dropdown-item d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-primary" @click="markAllAsRead"
                    :disabled="unreadCount === 0 || actionLoading">
                    Mark all as read
                </button>

                <button type="button" class="btn btn-sm btn-outline-secondary" @click="fetchNotifications"
                    :disabled="actionLoading">
                    Refresh
                </button>
            </div>
        </div>
    </li>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import {
    apiDeleteNotification,
    apiGetNotifications,
    apiGetUnreadNotifications,
    apiMarkAllNotificationsAsRead,
    apiMarkNotificationAsRead,
} from "@func/api/notification";

const router = useRouter();

const isOpen = ref(false);
const loading = ref(false);
const actionLoading = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);

let pollInterval = null;

function formatDate(value) {
    if (!value) return "-";
    const date = new Date(value);
    return date.toLocaleString();
}

async function fetchNotifications() {
    loading.value = true;
    try {
        const [allRes, unreadRes] = await Promise.all([
            apiGetNotifications({ per_page: 10 }),
            apiGetUnreadNotifications(),
        ]);

        notifications.value = allRes.data?.data || [];
        unreadCount.value = unreadRes.data?.count || 0;
    } catch (error) {
        console.error("Failed to fetch notifications:", error);
    } finally {
        loading.value = false;
    }
}

async function fetchUnreadCountOnly() {
    try {
        const unreadRes = await apiGetUnreadNotifications();
        unreadCount.value = unreadRes.data?.count || 0;
    } catch (error) {
        console.error("Failed to fetch unread count:", error);
    }
}

async function markAsRead(item) {
    if (!item?.id) return;

    actionLoading.value = true;
    try {
        await apiMarkNotificationAsRead(item.id);

        const target = notifications.value.find((n) => n.id === item.id);
        if (target) {
            target.read_at = new Date().toISOString();
        }

        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (error) {
        console.error("Failed to mark notification as read:", error);
    } finally {
        actionLoading.value = false;
    }
}

async function markAllAsRead() {
    if (unreadCount.value === 0) return;

    actionLoading.value = true;
    try {
        await apiMarkAllNotificationsAsRead();

        notifications.value = notifications.value.map((item) => ({
            ...item,
            read_at: item.read_at || new Date().toISOString(),
        }));

        unreadCount.value = 0;
    } catch (error) {
        console.error("Failed to mark all notifications as read:", error);
    } finally {
        actionLoading.value = false;
    }
}

async function removeNotification(item) {
    if (!item?.id) return;

    actionLoading.value = true;
    try {
        await apiDeleteNotification(item.id);

        if (!item.read_at) {
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }

        notifications.value = notifications.value.filter((n) => n.id !== item.id);
    } catch (error) {
        console.error("Failed to delete notification:", error);
    } finally {
        actionLoading.value = false;
    }
}

async function handleOpenNotification(item) {
    if (!item.read_at) {
        await markAsRead(item);
    }

    const bookingId = item.data?.booking_id;

    if (bookingId) {
        router.push({ name: "bookings" });
    }

    isOpen.value = false;
}

async function toggleDropdown() {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        await fetchNotifications();
    }
}

function handleClickOutside(event) {
    const dropdown = document.querySelector(".notification-item")?.closest(".nav-item.dropdown");
    if (!dropdown) return;

    if (!dropdown.contains(event.target)) {
        isOpen.value = false;
    }
}

onMounted(async () => {
    await fetchNotifications();

    pollInterval = setInterval(() => {
        fetchUnreadCountOnly();
    }, 15000);

    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }

    document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.dropdown-menu.show {
    display: block;
}

.notification-item {
    white-space: normal;
}

.notification-item:hover {
    background-color: #f4f6f9;
}
</style>