<template>
    <div class="content-wrapper" style="min-height: 1416px">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Bookings</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <router-link :to="{ name: 'dashboard' }">Home</router-link>
                            </li>
                            <li class="breadcrumb-item active">Bookings</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap:10px;">
                            <div class="d-flex flex-wrap align-items-end" style="gap:10px;">
                                <div>
                                    <label class="mb-1 small text-muted">Status</label>
                                    <select class="form-control form-control-sm" v-model="filters.status">
                                        <option value="">All</option>
                                        <option value="pending">pending</option>
                                        <option value="approved">approved</option>
                                        <option value="rejected">rejected</option>
                                        <option value="cancel_requested">cancel_requested</option>
                                        <option value="cancelled">cancelled</option>
                                        <option value="completed">completed</option>
                                    </select>
                                </div>

                                <div style="min-width:260px;">
                                    <label class="mb-1 small text-muted">Room</label>
                                    <select class="form-control form-control-sm" v-model="filters.room_id">
                                        <option value="">All rooms</option>
                                        <option v-for="r in roomOptions" :key="r.id" :value="String(r.id)">
                                            {{ r.name }} (ID: {{ r.id }})
                                        </option>
                                    </select>
                                </div>

                                <button type="button" class="btn btn-sm btn-outline-primary" :disabled="loading"
                                    @click="loadAll">
                                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                                    Reload
                                </button>
                            </div>

                            <button type="button" class="btn btn-sm btn-success" @click="openCreate">
                                <i class="fas fa-plus"></i> New Booking
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div v-if="error" class="alert alert-danger mb-3">{{ error }}</div>

                        <CustomTable :title="'Bookings'" :data="filteredBookings" :columns="columns" :pageSize="25" />
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" ref="bookingModal" aria-modal="true" role="dialog">
        <form @submit.prevent="saveBooking">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            {{ bookingObject.id ? "Edit Booking" : "Create Booking" }}
                        </h4>
                        <button type="button" class="close" @click="hideBookingModal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Room</label>
                                <select class="form-control" v-model="bookingObject.room_id"
                                    :class="{ 'is-invalid': bookingErr.room_id }">
                                    <option value="" disabled>Select a room...</option>
                                    <option v-for="r in roomOptions" :key="r.id" :value="String(r.id)">
                                        {{ r.name }} (Capacity: {{ r.capacity ?? "-" }})
                                    </option>
                                </select>
                                <div class="invalid-feedback">{{ bookingErr.room_id }}</div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Recurrence</label>
                                <select class="form-control" v-model="bookingObject.recurrence_type">
                                    <option value="none">none</option>
                                    <option value="daily">daily</option>
                                    <option value="weekly">weekly</option>
                                    <option value="monthly">monthly</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Start datetime</label>
                                <input type="datetime-local" class="form-control" v-model="bookingObject.start_datetime"
                                    :class="{ 'is-invalid': bookingErr.start_datetime }" />
                                <div class="invalid-feedback">{{ bookingErr.start_datetime }}</div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>End datetime</label>
                                <input type="datetime-local" class="form-control" v-model="bookingObject.end_datetime"
                                    :class="{ 'is-invalid': bookingErr.end_datetime }" />
                                <div class="invalid-feedback">{{ bookingErr.end_datetime }}</div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Recurrence period (optional)</label>
                                <input type="number" min="1" class="form-control"
                                    v-model="bookingObject.recurrence_period" />
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Recurrence until (optional)</label>
                                <input type="date" class="form-control" v-model="bookingObject.recurrence_until" />
                            </div>

                            <div class="col-md-12 form-group">
                                <label>Recurrence days (optional)</label>
                                <input type="text" class="form-control" v-model="bookingObject.recurrence_days"
                                    placeholder="Mon,Tue or Wed,Fri" />
                            </div>

                            <div class="col-12">
                                <small class="text-muted">
                                    * If conflict => backend returns 422 message: "Room is already booked for this time"
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" @click="hideBookingModal">
                            Close
                        </button>

                        <button type="submit" class="btn btn-primary" :disabled="saving || !canEditBookingForm">
                            {{ saving ? "Saving..." : "Save changes" }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" ref="detailModal" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h4 class="modal-title">Booking Detail</h4>
                    <button type="button" class="close text-white" @click="hideDetailModal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="detailError" class="alert alert-danger">{{ detailError }}</div>

                    <table v-if="selectedBooking" class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <th style="width: 180px;">ID</th>
                                <td>{{ selectedBooking.id }}</td>
                            </tr>
                            <tr>
                                <th>Room</th>
                                <td>{{ selectedBooking.room?.name ?? `Room #${selectedBooking.room_id}` }}</td>
                            </tr>
                            <tr>
                                <th>User</th>
                                <td>{{ selectedBooking.user?.name ?? "-" }}</td>
                            </tr>
                            <tr>
                                <th>Start</th>
                                <td>{{ fmt(selectedBooking.start_datetime) }}</td>
                            </tr>
                            <tr>
                                <th>End</th>
                                <td>{{ fmt(selectedBooking.end_datetime) }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span class="badge" :class="statusBadge(selectedBooking.status)">
                                        {{ selectedBooking.status }}
                                    </span>
                                    <span v-if="isPastBooking(selectedBooking)" class="badge badge-dark ml-2">
                                        expired
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Recurrence Type</th>
                                <td>{{ selectedBooking.recurrence_type ?? "none" }}</td>
                            </tr>
                            <tr>
                                <th>Recurrence Days</th>
                                <td>{{ formatRecurrenceDays(selectedBooking.recurrence_days) }}</td>
                            </tr>
                            <tr>
                                <th>Recurrence Period</th>
                                <td>{{ selectedBooking.recurrence_period ?? "-" }}</td>
                            </tr>
                            <tr>
                                <th>Recurrence Until</th>
                                <td>{{ selectedBooking.recurrence_until ?? "-" }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" @click="hideDetailModal">
                        Close
                    </button>

                    <div class="d-flex flex-wrap" style="gap:8px;">
                        <button v-if="selectedBooking && canOpenEdit(selectedBooking)" type="button"
                            class="btn btn-primary" @click="editFromDetail">
                            <i class="fa fa-pen mr-1"></i> Edit
                        </button>

                        <button v-if="selectedBooking && canRequestCancel(selectedBooking)" type="button"
                            class="btn btn-warning" :disabled="actionLoading"
                            @click="requestCancelBooking(selectedBooking.id)">
                            <i class="fa fa-paper-plane mr-1"></i> Request Cancel
                        </button>

                        <button v-if="selectedBooking && canApprove(selectedBooking)" type="button"
                            class="btn btn-success" :disabled="actionLoading"
                            @click="approveBooking(selectedBooking.id)">
                            <i class="fa fa-check mr-1"></i> Approve
                        </button>

                        <button v-if="selectedBooking && canReject(selectedBooking)" type="button"
                            class="btn btn-danger" :disabled="actionLoading" @click="rejectBooking(selectedBooking.id)">
                            <i class="fa fa-times mr-1"></i> Reject
                        </button>

                        <button v-if="selectedBooking && canConfirmCancel(selectedBooking)" type="button"
                            class="btn btn-warning" :disabled="actionLoading"
                            @click="confirmCancelBooking(selectedBooking.id)">
                            <i class="fa fa-ban mr-1"></i> Confirm Cancel
                        </button>

                        <button v-if="selectedBooking && canAdminDirectCancel(selectedBooking)" type="button"
                            class="btn btn-warning" :disabled="actionLoading"
                            @click="adminDirectCancelBooking(selectedBooking.id)">
                            <i class="fa fa-ban mr-1"></i> Cancel
                        </button>

                        <button v-if="selectedBooking && canDelete(selectedBooking)" type="button"
                            class="btn btn-outline-danger" :disabled="actionLoading"
                            @click="deleteBooking(selectedBooking.id)">
                            <i class="fa fa-trash mr-1"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, h, onMounted, reactive, ref } from "vue";
import { useStore } from "vuex";
import CustomTable from "../includes/tables/CustomTable.vue";
import { CloseModal, LoadingModal, MessageModal } from "@func/swal";

import {
    apiGetBookings,
    apiCreateBooking,
    apiUpdateBooking,
    apiGetBooking,
    apiRequestCancelBooking,
    apiApproveBooking,
    apiRejectBooking,
    apiConfirmCancelBooking,
    apiAdminCancelBooking,
    apiDeleteBooking,
} from "@func/api/booking";

import { apiGetRooms } from "@func/api/room";

const store = useStore();

const bookingModal = ref(null);
const detailModal = ref(null);

const loading = ref(false);
const saving = ref(false);
const actionLoading = ref(false);
const error = ref("");
const formError = ref("");
const detailError = ref("");

const bookings = ref([]);
const rooms = ref([]);
const selectedBooking = ref(null);

const filters = reactive({
    status: "",
    room_id: "",
});

const currentUser = computed(() => store.state.user || null);
const isAdmin = computed(() => currentUser.value?.level === "admin");

const roomOptions = computed(() => rooms.value ?? []);

const filteredBookings = computed(() => {
    let list = [...(bookings.value ?? [])];

    if (filters.status) {
        list = list.filter((b) => b.status === filters.status);
    }

    if (filters.room_id) {
        list = list.filter((b) => String(b.room_id) === String(filters.room_id));
    }

    return list;
});

function normalizeDt(dt) {
    if (!dt) return null;
    return String(dt).replace(" ", "T");
}

function fmt(dt) {
    if (!dt) return "-";
    const d = new Date(normalizeDt(dt));
    if (Number.isNaN(d.getTime())) return String(dt);
    return d.toLocaleString();
}

function toLocalInput(dt) {
    if (!dt) return "";
    const d = new Date(normalizeDt(dt));
    if (Number.isNaN(d.getTime())) return "";
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function toMysqlDatetime(dt) {
    if (!dt) return null;
    return String(dt).replace("T", " ");
}

function isPastBooking(booking) {
    if (!booking?.end_datetime) return false;
    const end = new Date(normalizeDt(booking.end_datetime));
    if (Number.isNaN(end.getTime())) return false;
    return end.getTime() < Date.now();
}

function formatRecurrenceDays(value) {
    if (!value) return "-";
    if (Array.isArray(value)) return value.join(", ");
    return value;
}

function statusBadge(status) {
    switch (status) {
        case "pending":
            return "badge-warning";
        case "approved":
            return "badge-success";
        case "rejected":
            return "badge-danger";
        case "cancel_requested":
            return "badge-info";
        case "cancelled":
            return "badge-secondary";
        case "completed":
            return "badge-primary";
        default:
            return "badge-dark";
    }
}

const bookingObject = reactive({
    id: null,
    room_id: "",
    start_datetime: "",
    end_datetime: "",
    recurrence_type: "none",
    recurrence_days: "",
    recurrence_period: "",
    recurrence_until: "",
    status: "",
});

const bookingErr = reactive({
    room_id: "",
    start_datetime: "",
    end_datetime: "",
});

const defaultBookingObject = {
    id: null,
    room_id: "",
    start_datetime: "",
    end_datetime: "",
    recurrence_type: "none",
    recurrence_days: "",
    recurrence_period: "",
    recurrence_until: "",
    status: "",
};

const defaultBookingErr = {
    room_id: "",
    start_datetime: "",
    end_datetime: "",
};

const canEditBookingForm = computed(() => {
    if (!bookingObject.id) return true;
    if (isAdmin.value) return !isPastBooking(bookingObject);
    return bookingObject.status === "pending" && !isPastBooking(bookingObject);
});

function resetData() {
    Object.assign(bookingObject, { ...defaultBookingObject });
    Object.assign(bookingErr, { ...defaultBookingErr });
    formError.value = "";
}

function resetDetail() {
    selectedBooking.value = null;
    detailError.value = "";
}

function showBookingModal() {
    if (!window.$ || !bookingModal.value) return;
    window.$(bookingModal.value).modal("show");
}

function hideBookingModal() {
    if (!window.$ || !bookingModal.value) return;
    window.$(bookingModal.value).modal("hide");
}

function showDetailModal() {
    if (!window.$ || !detailModal.value) return;
    window.$(detailModal.value).modal("show");
}

function hideDetailModal() {
    if (!window.$ || !detailModal.value) return;
    window.$(detailModal.value).modal("hide");
}

function canOpenEdit(booking) {
    if (!booking) return false;
    if (isPastBooking(booking)) return false;
    if (isAdmin.value) return true;
    return booking.status === "pending";
}

function canRequestCancel(booking) {
    if (!booking || isAdmin.value) return false;
    if (isPastBooking(booking)) return false;
    return ["pending", "approved"].includes(booking.status);
}

function canApprove(booking) {
    if (!booking || !isAdmin.value) return false;
    if (isPastBooking(booking)) return false;
    return booking.status === "pending";
}

function canReject(booking) {
    if (!booking || !isAdmin.value) return false;
    if (isPastBooking(booking)) return false;
    return booking.status === "pending";
}

function canConfirmCancel(booking) {
    if (!booking || !isAdmin.value) return false;
    if (isPastBooking(booking)) return false;
    return booking.status === "cancel_requested";
}

function canAdminDirectCancel(booking) {
    if (!booking || !isAdmin.value) return false;
    if (isPastBooking(booking)) return false;
    return ["pending", "approved"].includes(booking.status);
}

function canDelete(booking) {
    if (!booking || !isAdmin.value) return false;
    return true;
}

async function loadRooms() {
    try {
        const res = await apiGetRooms({ per_page: 200, page: 1 });
        rooms.value = res.data?.data ?? res.data ?? [];
    } catch (e) {
        console.log("loadRooms error", e);
    }
}

async function loadBookings() {
    loading.value = true;
    error.value = "";

    try {
        LoadingModal();
        const res = await apiGetBookings({ per_page: 250, page: 1 });
        bookings.value = res.data?.data ?? [];
        CloseModal();
    } catch (e) {
        CloseModal();
        error.value = e?.response?.data?.message || e?.message || "Failed to load bookings";
    } finally {
        loading.value = false;
    }
}

async function loadAll() {
    await loadRooms();
    await loadBookings();
}

function openCreate() {
    resetData();
    showBookingModal();
}

async function viewBooking(id) {
    try {
        LoadingModal();
        const response = await apiGetBooking(id);
        const booking = response.data?.data ?? response.data?.booking ?? response.data;

        selectedBooking.value = booking;
        showDetailModal();
        CloseModal();
    } catch (e) {
        CloseModal();
        return MessageModal("error", "Error", e?.response?.data?.message || e.message);
    }
}

function fillFormFromBooking(booking) {
    Object.assign(bookingObject, {
        id: booking.id,
        room_id: String(booking.room_id ?? ""),
        start_datetime: toLocalInput(booking.start_datetime),
        end_datetime: toLocalInput(booking.end_datetime),
        recurrence_type: booking.recurrence_type ?? "none",
        recurrence_days: Array.isArray(booking.recurrence_days)
            ? booking.recurrence_days.join(",")
            : (booking.recurrence_days ?? ""),
        recurrence_period: booking.recurrence_period ?? "",
        recurrence_until: booking.recurrence_until ?? "",
        status: booking.status ?? "",
    });
}

function editFromDetail() {
    if (!selectedBooking.value) return;
    fillFormFromBooking(selectedBooking.value);
    hideDetailModal();
    showBookingModal();
}

async function saveBooking() {
    saving.value = true;
    formError.value = "";
    Object.assign(bookingErr, { ...defaultBookingErr });

    try {
        LoadingModal();

        const payload = {
            room_id: bookingObject.room_id ? Number(bookingObject.room_id) : null,
            start_datetime: toMysqlDatetime(bookingObject.start_datetime),
            end_datetime: toMysqlDatetime(bookingObject.end_datetime),
            recurrence_type: bookingObject.recurrence_type || "none",
            recurrence_days: bookingObject.recurrence_days
                ? bookingObject.recurrence_days.split(",").map((x) => x.trim()).filter(Boolean)
                : null,
            recurrence_period: bookingObject.recurrence_period || null,
            recurrence_until: bookingObject.recurrence_until || null,
        };

        let response = null;

        if (bookingObject.id) {
            response = await apiUpdateBooking(bookingObject.id, payload);
            onBookingUpdate(response.data?.data ?? response.data?.booking ?? response.data);
        } else {
            response = await apiCreateBooking(payload);
            onBookingCreate(response.data?.data ?? response.data?.booking ?? response.data);
        }

        hideBookingModal();
        MessageModal("success", "Success", response.data?.message || "Saved");
    } catch (e) {
        if (!e.response) {
            CloseModal();
            saving.value = false;
            return MessageModal("error", "Error", e.message);
        }

        if (e.response.status === 422) {
            const errors = e.response.data.errors || {};
            Object.keys(bookingErr).forEach((k) => {
                bookingErr[k] = errors[k]?.[0] || "";
            });

            if (!Object.keys(errors).length && e.response.data.message) {
                formError.value = e.response.data.message;
            }

            CloseModal();
            saving.value = false;
            return;
        }

        CloseModal();
        saving.value = false;
        return MessageModal("error", "Error", e.response.data.message || "Save failed");
    } finally {
        CloseModal();
        saving.value = false;
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
        const item = response.data?.data ?? response.data;
        onBookingUpdate(item);
        selectedBooking.value = item;
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
        const item = response.data?.data ?? response.data;
        onBookingUpdate(item);
        selectedBooking.value = item;
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
        const item = response.data?.data ?? response.data;
        onBookingUpdate(item);
        selectedBooking.value = item;
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
        const item = response.data?.data ?? response.data;
        onBookingUpdate(item);
        selectedBooking.value = item;
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
        const item = response.data?.data ?? response.data;
        onBookingUpdate(item);
        selectedBooking.value = item;
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
        bookings.value = bookings.value.filter((x) => x.id !== id);
        hideDetailModal();
        CloseModal();
        MessageModal("success", "Success", response.data?.message || "Booking deleted");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e.message);
    } finally {
        actionLoading.value = false;
    }
}

function onBookingCreate(b) {
    bookings.value.unshift(b);
}

function onBookingUpdate(b) {
    bookings.value = bookings.value.map((x) => (x.id === b.id ? b : x));
}

onMounted(async () => {
    if (window.$ && bookingModal.value) {
        window.$(bookingModal.value).on("hide.bs.modal", () => resetData());
    }

    if (window.$ && detailModal.value) {
        window.$(detailModal.value).on("hide.bs.modal", () => resetDetail());
    }

    await loadAll();
});

const columns = [
    { header: "ID", accessorKey: "id" },
    {
        header: "Room",
        accessorFn: (row) => row.room?.name ?? `Room #${row.room_id}`,
    },
    {
        header: "User",
        accessorFn: (row) => row.user?.name ?? "-",
    },
    {
        header: "Start",
        accessorKey: "start_datetime",
        cell: ({ getValue }) => fmt(getValue()),
        meta: { align: "center" },
    },
    {
        header: "End",
        accessorKey: "end_datetime",
        cell: ({ getValue }) => fmt(getValue()),
        meta: { align: "center" },
    },
    {
        header: "Status",
        accessorKey: "status",
        cell: ({ getValue }) =>
            h("span", { class: ["badge", statusBadge(getValue())] }, getValue()),
        meta: { align: "center" },
    },
    {
        accessorKey: "action",
        header: () => [
            "Actions",
            h(
                "button",
                {
                    type: "button",
                    onClick: openCreate,
                    class: "btn btn-sm btn-success ml-3",
                },
                "Create"
            ),
        ],
        cell: ({ row: { original } }) => [
            h(
                "button",
                {
                    type: "button",
                    onClick: () => viewBooking(original.id),
                    class: "btn btn-sm btn-outline-secondary mx-1",
                },
                h("i", { class: "fa fa-eye" })
            ),
        ],
        enableSorting: false,
        meta: { align: "center" },
    },
];
</script>