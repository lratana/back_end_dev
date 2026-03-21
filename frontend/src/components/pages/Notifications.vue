<template>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h1>Notifications</h1>

                    <div class="mt-2 mt-md-0">
                        <button class="btn btn-primary mr-2" @click="markAllAsRead"
                            :disabled="counts.unread === 0 || loading">
                            Mark All as Read
                        </button>
                        <button class="btn btn-outline-secondary" @click="fetchNotifications" :disabled="loading">
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="mb-3 d-flex flex-wrap align-items-center">
                    <button class="btn mr-2 mb-2" :class="activeFilter === 'all' ? 'btn-dark' : 'btn-outline-dark'"
                        @click="changeFilter('all')" :disabled="loading">
                        All
                        <span class="badge badge-light ml-2">{{ counts.all }}</span>
                    </button>

                    <button class="btn mr-2 mb-2"
                        :class="activeFilter === 'unread' ? 'btn-danger' : 'btn-outline-danger'"
                        @click="changeFilter('unread')" :disabled="loading">
                        Unread
                        <span class="badge badge-light ml-2">{{ counts.unread }}</span>
                    </button>

                    <button class="btn mb-2" :class="activeFilter === 'read' ? 'btn-success' : 'btn-outline-success'"
                        @click="changeFilter('read')" :disabled="loading">
                        Read
                        <span class="badge badge-light ml-2">{{ counts.read }}</span>
                    </button>
                </div>

                <div v-if="loading" class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading...
                    </div>
                </div>

                <div v-else>
                    <CustomTable title="Notifications" :data="tableData" :columns="columns" :pageSize="10">
                        <template #actions>
                            <div class="ml-2">
                                <span class="badge badge-secondary p-2">
                                    Total: {{ tableData.length }}
                                </span>
                            </div>
                        </template>
                    </CustomTable>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, h, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";
import CustomTable from "../includes/tables/CustomTable.vue";
import {
    apiDeleteNotification,
    apiGetNotifications,
    apiMarkAllNotificationsAsRead,
    apiMarkNotificationAsRead,
} from "@func/api/notification";

const router = useRouter();

const loading = ref(false);
const notifications = ref([]);
const activeFilter = ref("all");

const counts = ref({
    all: 0,
    unread: 0,
    read: 0,
});

function formatDate(value) {
    if (!value) return "-";
    return new Date(value).toLocaleString();
}

async function fetchNotifications() {
    loading.value = true;

    try {
        const res = await apiGetNotifications({
            filter: activeFilter.value,
            per_page: 1000,
        });

        notifications.value = res.data?.data || [];

        counts.value = {
            all: res.data?.counts?.all || 0,
            unread: res.data?.counts?.unread || 0,
            read: res.data?.counts?.read || 0,
        };
    } catch (error) {
        console.error("Failed to fetch notifications:", error);
        Swal.fire("Error", error.response?.data?.message || error.message, "error");
    } finally {
        loading.value = false;
    }
}

async function markAsRead(item) {
    try {
        await apiMarkNotificationAsRead(item.id);
        await fetchNotifications();
    } catch (error) {
        Swal.fire("Error", error.response?.data?.message || error.message, "error");
    }
}

async function markAllAsRead() {
    try {
        await apiMarkAllNotificationsAsRead();
        await fetchNotifications();
    } catch (error) {
        Swal.fire("Error", error.response?.data?.message || error.message, "error");
    }
}

async function removeNotification(item) {
    const result = await Swal.fire({
        title: "Delete notification?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it",
    });

    if (!result.isConfirmed) return;

    try {
        await apiDeleteNotification(item.id);
        await fetchNotifications();
    } catch (error) {
        Swal.fire("Error", error.response?.data?.message || error.message, "error");
    }
}

async function openNotification(item) {
    if (!item.read_at) {
        await markAsRead(item);
    }

    const bookingId = item.data?.booking_id;

    if (bookingId) {
        router.push({
            name: "notificationBookingDetail",
            params: { id: bookingId }
        });
    }
}

function changeFilter(filter) {
    if (activeFilter.value === filter || loading.value) return;
    activeFilter.value = filter;
    fetchNotifications();
}

const tableData = computed(() => {
    return notifications.value.map((item) => ({
        ...item,
        title: item.data?.title || "Notification",
        message: item.data?.message || "-",
        status: item.data?.status || "-",
        created_at_text: formatDate(item.created_at),
        is_read_text: item.read_at ? "Read" : "Unread",
    }));
});

const columns = computed(() => [
    {
        accessorKey: "id",
        header: "#",
        cell: ({ row }) => row.index + 1,
        meta: { align: "center" },
    },
    {
        accessorKey: "title",
        header: "Title",
        cell: ({ row }) => {
            const item = row.original;
            return h(
                "div",
                { class: "d-flex align-items-center" },
                [
                    !item.read_at
                        ? h("i", {
                            class: "fas fa-circle text-danger mr-2",
                            style: "font-size: 8px;",
                        })
                        : null,
                    h("strong", item.title),
                ]
            );
        },
        meta: { align: "left" },
    },
    {
        accessorKey: "message",
        header: "Message",
        cell: ({ row }) =>
            h(
                "div",
                {
                    class: "text-wrap",
                    style: "white-space: normal; min-width: 260px;",
                },
                row.original.message
            ),
        meta: { align: "left" },
    },
    {
        accessorKey: "status",
        header: "Status",
        cell: ({ row }) =>
            h("span", { class: "badge badge-info" }, row.original.status),
        meta: { align: "center" },
    },
    {
        accessorKey: "is_read_text",
        header: "Read",
        cell: ({ row }) =>
            row.original.read_at
                ? h("span", { class: "badge badge-success" }, "Read")
                : h("span", { class: "badge badge-warning" }, "Unread"),
        meta: { align: "center" },
    },
    {
        accessorKey: "created_at_text",
        header: "Created At",
        cell: ({ row }) => row.original.created_at_text,
        meta: { align: "center" },
    },
    {
        id: "actions",
        header: "Actions",
        cell: ({ row }) => {
            const item = row.original;

            const buttons = [];

            if (!item.read_at) {
                buttons.push(
                    h(
                        "button",
                        {
                            class: "btn btn-sm btn-success mr-2",
                            onClick: () => markAsRead(item),
                        },
                        "Read"
                    )
                );
            }

            buttons.push(
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-primary mr-2",
                        onClick: () => openNotification(item),
                    },
                    "Open"
                )
            );

            buttons.push(
                h(
                    "button",
                    {
                        class: "btn btn-sm btn-danger",
                        onClick: () => removeNotification(item),
                    },
                    "Delete"
                )
            );

            return h("div", { class: "text-nowrap" }, buttons);
        },
        meta: { align: "center" },
    },
]);

onMounted(() => {
    fetchNotifications();
});
</script>