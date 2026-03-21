<template>
    <li class="nav-item dropdown notification-root">
        <a class="nav-link notification-bell" href="#" role="button" @click.prevent="toggleDropdown"
            :class="{ ringing: isBellRinging }">
            <i class="far fa-bell"></i>
            <span v-if="unreadCount > 0" class="badge badge-danger navbar-badge">
                {{ unreadCount > 99 ? "99+" : unreadCount }}
            </span>
        </a>

        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" :class="{ show: isOpen }"
            style="max-height: 420px; overflow-y: auto; min-width: 380px;">
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
                                <i v-if="!item.read_at" class="fas fa-circle text-danger mr-2"
                                    style="font-size: 8px;"></i>
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

                <div class="d-flex">
                    <button type="button" class="btn btn-sm btn-outline-secondary mr-2" @click="fetchNotifications"
                        :disabled="actionLoading">
                        Refresh
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-info" @click="goToAllNotifications">
                        View All
                    </button>
                </div>
            </div>
        </div>
    </li>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { useRouter } from "vue-router";
import { useStore } from "vuex";
import {
    apiDeleteNotification,
    apiGetNotifications,
    apiGetUnreadNotifications,
    apiMarkAllNotificationsAsRead,
    apiMarkNotificationAsRead,
} from "@func/api/notification";

const router = useRouter();
const store = useStore();

const isOpen = ref(false);
const loading = ref(false);
const actionLoading = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const isBellRinging = ref(false);

let pollInterval = null;
let bellTimeout = null;
let subscribedUserId = null;

function formatDate(value) {
    if (!value) return "-";
    const date = new Date(value);
    return date.toLocaleString();
}

function ringBell() {
    isBellRinging.value = true;

    if (bellTimeout) clearTimeout(bellTimeout);

    bellTimeout = setTimeout(() => {
        isBellRinging.value = false;
    }, 1800);
}

async function fetchNotifications() {
    loading.value = true;
    try {
        const [allRes, unreadRes] = await Promise.all([
            apiGetNotifications({ per_page: 5 }),
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
        if (target && !target.read_at) {
            target.read_at = new Date().toISOString();
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        }
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
        router.push({
            name: "notificationBookingDetail",
            params: { id: bookingId }
        });
    } else {
        router.push({ name: "notifications" });
    }

    isOpen.value = false;
}

function goToAllNotifications() {
    isOpen.value = false;
    router.push({ name: "notifications" });
}

async function toggleDropdown() {
    isOpen.value = !isOpen.value;

    if (isOpen.value) {
        await fetchNotifications();
    }
}

function handleClickOutside(event) {
    const root = document.querySelector(".notification-root");
    if (!root) return;

    if (!root.contains(event.target)) {
        isOpen.value = false;
    }
}

function subscribeToNotificationChannel(userId) {
    if (!userId || !window.Echo) return;

    if (subscribedUserId && subscribedUserId !== userId) {
        window.Echo.leave(`App.Models.User.${subscribedUserId}`);
    }

    subscribedUserId = userId;

    window.Echo.private(`App.Models.User.${userId}`)
        .notification(async () => {
            unreadCount.value += 1;
            ringBell();
            await fetchNotifications();
        });
}

function leaveNotificationChannel() {
    if (subscribedUserId && window.Echo) {
        window.Echo.leave(`App.Models.User.${subscribedUserId}`);
        subscribedUserId = null;
    }
}

onMounted(async () => {
    await fetchNotifications();

    const userId = store.state.user?.id;
    if (userId) {
        subscribeToNotificationChannel(userId);
    }

    pollInterval = setInterval(() => {
        fetchUnreadCountOnly();
    }, 15000);

    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    if (pollInterval) clearInterval(pollInterval);
    if (bellTimeout) clearTimeout(bellTimeout);

    leaveNotificationChannel();
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

.notification-bell {
    position: relative;
}

.notification-bell.ringing i {
    animation: bell-ring 0.9s ease-in-out 2;
    color: #dc3545;
}

@keyframes bell-ring {
    0% {
        transform: rotate(0deg);
    }

    10% {
        transform: rotate(18deg);
    }

    20% {
        transform: rotate(-16deg);
    }

    30% {
        transform: rotate(14deg);
    }

    40% {
        transform: rotate(-12deg);
    }

    50% {
        transform: rotate(10deg);
    }

    60% {
        transform: rotate(-8deg);
    }

    70% {
        transform: rotate(6deg);
    }

    80% {
        transform: rotate(-4deg);
    }

    90% {
        transform: rotate(2deg);
    }

    100% {
        transform: rotate(0deg);
    }
}
</style>