<template>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Booking Detail</h1>

                    <div>
                        <router-link :to="{ name: 'notifications' }" class="btn btn-secondary mr-2">
                            Back to Notifications
                        </router-link>
                        <router-link :to="{ name: 'bookings' }" class="btn btn-outline-primary">
                            Go to Bookings
                        </router-link>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div v-if="error" class="alert alert-danger">
                    {{ error }}
                </div>

                <div v-if="loading" class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Loading...
                    </div>
                </div>

                <div v-else-if="booking" class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">
                            Booking #{{ booking.id }}
                        </h3>

                        <div class="d-flex flex-wrap" style="gap:8px;">
                            <button v-if="canRequestCancel(booking)" type="button" class="btn btn-warning"
                                :disabled="actionLoading" @click="requestCancelBooking(booking.id)">
                                Request Cancel
                            </button>

                            <button v-if="canApprove(booking)" type="button" class="btn btn-success"
                                :disabled="actionLoading" @click="approveBooking(booking.id)">
                                Approve
                            </button>

                            <button v-if="canReject(booking)" type="button" class="btn btn-danger"
                                :disabled="actionLoading" @click="rejectBooking(booking.id)">
                                Reject
                            </button>

                            <button v-if="canConfirmCancel(booking)" type="button" class="btn btn-warning"
                                :disabled="actionLoading" @click="confirmCancelBooking(booking.id)">
                                Confirm Cancel
                            </button>

                            <button v-if="canAdminDirectCancel(booking)" type="button" class="btn btn-warning"
                                :disabled="actionLoading" @click="adminDirectCancelBooking(booking.id)">
                                Cancel
                            </button>

                            <button v-if="canDelete(booking)" type="button" class="btn btn-outline-danger"
                                :disabled="actionLoading" @click="deleteBooking(booking.id)">
                                Delete
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered table-sm">
                            <tbody>
                                <tr>
                                    <th style="width: 200px;">ID</th>
                                    <td>{{ booking.id }}</td>
                                </tr>
                                <tr>
                                    <th>Room</th>
                                    <td>{{ booking.room?.name ?? `Room #${booking.room_id}` }}</td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td>{{ booking.user?.name ?? "-" }}</td>
                                </tr>
                                <tr>
                                    <th>Start</th>
                                    <td>{{ fmt(booking.start_datetime) }}</td>
                                </tr>
                                <tr>
                                    <th>End</th>
                                    <td>{{ fmt(booking.end_datetime) }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge" :class="statusBadge(booking.status)">
                                            {{ booking.status }}
                                        </span>
                                        <span v-if="isPastBooking(booking)" class="badge badge-dark ml-2">
                                            expired
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Meeting Title</th>
                                    <td>{{ booking.meeting_title || "-" }}</td>
                                </tr>
                                <tr>
                                    <th>Meeting Chairman</th>
                                    <td>{{ booking.meeting_chairman || "-" }}</td>
                                </tr>
                                <tr>
                                    <th>Snack Required</th>
                                    <td>{{ booking.snack_required ? "Yes" : "No" }}</td>
                                </tr>
                                <tr>
                                    <th>Snack Note</th>
                                    <td>{{ booking.snack_note || "-" }}</td>
                                </tr>
                                <tr>
                                    <th>Recurrence Type</th>
                                    <td>{{ booking.recurrence_type ?? "none" }}</td>
                                </tr>
                                <tr>
                                    <th>Recurrence Days</th>
                                    <td>{{ formatRecurrenceDays(booking.recurrence_days) }}</td>
                                </tr>
                                <tr>
                                    <th>Recurrence Period</th>
                                    <td>{{ booking.recurrence_period ?? "-" }}</td>
                                </tr>
                                <tr>
                                    <th>Recurrence Until</th>
                                    <td>{{ fmtDateOnly(booking.recurrence_until) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="alert alert-warning">
                    Booking not found.
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useStore } from "vuex";
import Swal from "sweetalert2";
import { formatFullDateTime } from "@func/datetime";
import { LoadingModal, CloseModal, MessageModal } from "@func/swal";
import {
    apiGetBooking,
    apiRequestCancelBooking,
    apiApproveBooking,
    apiRejectBooking,
    apiConfirmCancelBooking,
    apiAdminCancelBooking,
    apiDeleteBooking,
} from "@func/api/booking";

const route = useRoute();
const router = useRouter();
const store = useStore();

const loading = ref(false);
const actionLoading = ref(false);
const error = ref("");
const booking = ref(null);

const currentUser = computed(() => store.state.user || null);
const isAdmin = computed(() => currentUser.value?.level === "admin");

function normalizeDt(dt) {
    if (!dt) return null;
    return String(dt).replace(" ", "T");
}

function fmt(dt) {
    if (!dt) return "-";
    return formatFullDateTime(dt);
}

function fmtDateOnly(dt) {
    if (!dt) return "-";
    return formatFullDateTime(dt).split(",")[0];
}

function formatRecurrenceDays(value) {
    if (!value) return "-";
    if (Array.isArray(value)) return value.join(", ");
    return String(value);
}

function isPastBooking(item) {
    if (!item?.end_datetime) return false;
    const end = new Date(normalizeDt(item.end_datetime));
    if (Number.isNaN(end.getTime())) return false;
    return end.getTime() < Date.now();
}

function statusBadge(status) {
    switch (status) {
        case "pending": return "badge-warning";
        case "approved": return "badge-success";
        case "rejected": return "badge-danger";
        case "cancel_requested": return "badge-info";
        case "cancelled": return "badge-secondary";
        case "completed": return "badge-primary";
        default: return "badge-dark";
    }
}

function canRequestCancel(item) {
    if (!item || isAdmin.value) return false;
    if (isPastBooking(item)) return false;
    return ["pending", "approved"].includes(item.status);
}

function canApprove(item) {
    if (!item || !isAdmin.value) return false;
    if (isPastBooking(item)) return false;
    return item.status === "pending";
}

function canReject(item) {
    if (!item || !isAdmin.value) return false;
    if (isPastBooking(item)) return false;
    return item.status === "pending";
}

function canConfirmCancel(item) {
    if (!item || !isAdmin.value) return false;
    if (isPastBooking(item)) return false;
    return item.status === "cancel_requested";
}

function canAdminDirectCancel(item) {
    if (!item || !isAdmin.value) return false;
    if (isPastBooking(item)) return false;
    return ["pending", "approved", "cancel_requested"].includes(item.status);
}

function canDelete(item) {
    return !!item && isAdmin.value;
}

async function loadBooking() {
    loading.value = true;
    error.value = "";

    try {
        LoadingModal();
        const id = route.params.id;
        const response = await apiGetBooking(id);
        booking.value = response.data?.data ?? response.data?.booking ?? response.data;
        CloseModal();
    } catch (e) {
        CloseModal();
        error.value = e?.response?.data?.message || e.message || "Failed to load booking";
    } finally {
        loading.value = false;
    }
}

async function requestCancelBooking(id) {
    const result = await Swal.fire({
        icon: "warning",
        title: "Request Cancel",
        text: "Do you want to request cancellation for this booking?",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Yes, request",
    });

    if (!result.isConfirmed) return;

    try {
        actionLoading.value = true;
        LoadingModal();
        const response = await apiRequestCancelBooking(id);
        booking.value = response.data?.data ?? response.data;
        CloseModal();
        MessageModal("success", "Success", response.data?.message || "Cancel request submitted");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e.message);
    } finally {
        actionLoading.value = false;
    }
}

async function approveBooking(id) {
    const result = await Swal.fire({
        icon: "question",
        title: "Approve Booking",
        text: "Are you sure you want to approve this booking?",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        confirmButtonText: "Yes, approve",
    });

    if (!result.isConfirmed) return;

    try {
        actionLoading.value = true;
        LoadingModal();
        const response = await apiApproveBooking(id);
        booking.value = response.data?.data ?? response.data;
        CloseModal();
        MessageModal("success", "Success", response.data?.message || "Booking approved");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e.message);
    } finally {
        actionLoading.value = false;
    }
}

async function rejectBooking(id) {
    const result = await Swal.fire({
        icon: "warning",
        title: "Reject Booking",
        text: "Are you sure you want to reject this booking?",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, reject",
    });

    if (!result.isConfirmed) return;

    try {
        actionLoading.value = true;
        LoadingModal();
        const response = await apiRejectBooking(id);
        booking.value = response.data?.data ?? response.data;
        CloseModal();
        MessageModal("success", "Success", response.data?.message || "Booking rejected");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e.message);
    } finally {
        actionLoading.value = false;
    }
}

async function confirmCancelBooking(id) {
    const result = await Swal.fire({
        icon: "warning",
        title: "Confirm Cancel",
        text: "Are you sure you want to confirm this cancellation?",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Yes, confirm",
    });

    if (!result.isConfirmed) return;

    try {
        actionLoading.value = true;
        LoadingModal();
        const response = await apiConfirmCancelBooking(id);
        booking.value = response.data?.data ?? response.data;
        CloseModal();
        MessageModal("success", "Success", response.data?.message || "Booking cancelled");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e.message);
    } finally {
        actionLoading.value = false;
    }
}

async function adminDirectCancelBooking(id) {
    const result = await Swal.fire({
        icon: "warning",
        title: "Cancel Booking",
        text: "Cancel this booking directly as admin?",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Yes, cancel",
    });

    if (!result.isConfirmed) return;

    try {
        actionLoading.value = true;
        LoadingModal();
        const response = await apiAdminCancelBooking(id);
        booking.value = response.data?.data ?? response.data;
        CloseModal();
        MessageModal("success", "Success", response.data?.message || "Booking cancelled");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e.message);
    } finally {
        actionLoading.value = false;
    }
}

async function deleteBooking(id) {
    const result = await Swal.fire({
        icon: "warning",
        title: "Delete Booking",
        text: "Delete this booking permanently?",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete",
    });

    if (!result.isConfirmed) return;

    try {
        actionLoading.value = true;
        LoadingModal();
        const response = await apiDeleteBooking(id);
        CloseModal();
        MessageModal("success", "Success", response.data?.message || "Booking deleted");
        router.push({ name: "notifications" });
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e.message);
    } finally {
        actionLoading.value = false;
    }
}

onMounted(() => {
    loadBooking();
});
</script>