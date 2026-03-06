<template>
    <div class="content-wrapper">
        <section class="content pt-3">
            <div class="container-fluid">
                <div v-if="error" class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" @click="error = ''">
                        <span>&times;</span>
                    </button>
                    {{ error }}
                </div>

                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Booking Calendar
                        </h3>

                        <div class="card-tools d-flex align-items-center" style="gap:8px;">
                            <div class="calendar-legend mr-2">
                                <div class="legend-item">
                                    <span class="legend-color pending"></span>
                                    Pending
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color approved"></span>
                                    Approved
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color rejected"></span>
                                    Rejected
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color cancel_requested"></span>
                                    Cancel Request
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color cancelled"></span>
                                    Cancelled
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color completed"></span>
                                    Completed
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color expired"></span>
                                    Expired
                                </div>
                            </div>

                            <button class="btn btn-sm btn-outline-primary" :disabled="loading" @click="refetch">
                                <i class="fas fa-sync-alt mr-1" :class="{ 'fa-spin': loading }"></i>
                                Reload
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="gc-wrap">
                            <FullCalendar ref="calendarRef" :options="calendarOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Create Booking Modal -->
    <div class="modal fade" ref="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Create Booking
                    </h5>
                    <button type="button" class="close text-white" @click="hideCreateModal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="formError" class="alert alert-danger">
                        {{ formError }}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Room <span class="text-danger">*</span></label>
                            <select v-model="bookingForm.room_id" class="form-control">
                                <option value="">-- Select Room --</option>
                                <option v-for="room in rooms" :key="room.id" :value="String(room.id)">
                                    {{ room.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Recurrence Type</label>
                            <select v-model="bookingForm.recurrence_type" class="form-control">
                                <option value="none">none</option>
                                <option value="daily">daily</option>
                                <option value="weekly">weekly</option>
                                <option value="monthly">monthly</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Start <span class="text-danger">*</span></label>
                            <input v-model="bookingForm.start_datetime" type="datetime-local" class="form-control" />
                        </div>

                        <div class="form-group col-md-6">
                            <label>End <span class="text-danger">*</span></label>
                            <input v-model="bookingForm.end_datetime" type="datetime-local" class="form-control" />
                        </div>

                        <div class="form-group col-md-6">
                            <label>Recurrence Period</label>
                            <input v-model="bookingForm.recurrence_period" type="number" min="1" class="form-control" />
                        </div>

                        <div class="form-group col-md-6">
                            <label>Recurrence Until</label>
                            <input v-model="bookingForm.recurrence_until" type="date" class="form-control" />
                        </div>

                        <div class="form-group col-md-12">
                            <label>Recurrence Days</label>
                            <input v-model="bookingForm.recurrence_days" type="text" class="form-control"
                                placeholder="mon,tue or wed,fri" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button class="btn btn-default" type="button" @click="hideCreateModal">
                        <i class="fas fa-times mr-1"></i> Close
                    </button>

                    <button class="btn btn-primary" type="button" :disabled="saving" @click="submitCreateBooking">
                        <i class="fas fa-save mr-1" :class="{ 'fa-spin': saving }"></i>
                        {{ saving ? 'Saving...' : 'Create Booking' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Detail Modal -->
    <div class="modal fade" ref="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle mr-2"></i>
                        Booking Detail
                    </h5>
                    <button type="button" class="close text-white" @click="hideDetailModal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="detailLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Loading...
                    </div>

                    <div v-else-if="selected">
                        <table class="table table-sm table-bordered">
                            <tbody>
                                <tr>
                                    <th style="width: 170px;">Room</th>
                                    <td>{{ selected.room?.name ?? ("Room #" + selected.room_id) }}</td>
                                </tr>
                                <tr>
                                    <th>Start</th>
                                    <td>{{ fmt(selected.start_datetime) }}</td>
                                </tr>
                                <tr>
                                    <th>End</th>
                                    <td>{{ fmt(selected.end_datetime) }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge" :class="statusBadge(selected.status)">
                                            {{ selected.status }}
                                        </span>
                                        <span v-if="isPastBooking(selected)" class="badge badge-dark ml-2">
                                            expired
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="selected.user?.name">
                                    <th>User</th>
                                    <td>{{ selected.user.name }}</td>
                                </tr>
                                <tr v-if="selected.recurrence_type">
                                    <th>Recurrence Type</th>
                                    <td>{{ selected.recurrence_type }}</td>
                                </tr>
                                <tr v-if="selected.recurrence_days">
                                    <th>Recurrence Days</th>
                                    <td>{{ formatRecurrenceDays(selected.recurrence_days) }}</td>
                                </tr>
                                <tr v-if="selected.recurrence_period">
                                    <th>Recurrence Period</th>
                                    <td>{{ selected.recurrence_period }}</td>
                                </tr>
                                <tr v-if="selected.recurrence_until">
                                    <th>Recurrence Until</th>
                                    <td>{{ selected.recurrence_until }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-muted">
                        No data
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button class="btn btn-default" type="button" @click="hideDetailModal">
                        <i class="fas fa-times mr-1"></i> Close
                    </button>

                    <div class="d-flex" style="gap: 8px; flex-wrap: wrap;">
                        <button v-if="selected && canRequestCancel(selected)" class="btn btn-warning" type="button"
                            :disabled="actionLoading" @click="onRequestCancel(selected)">
                            <i class="fas fa-paper-plane mr-1" :class="{ 'fa-spin': actionLoading }"></i>
                            Request Cancel
                        </button>

                        <button v-if="selected && canApprove(selected)" class="btn btn-success" type="button"
                            :disabled="actionLoading" @click="onApproveBooking(selected)">
                            <i class="fas fa-check mr-1" :class="{ 'fa-spin': actionLoading }"></i>
                            Approve
                        </button>

                        <button v-if="selected && canReject(selected)" class="btn btn-danger" type="button"
                            :disabled="actionLoading" @click="onRejectBooking(selected)">
                            <i class="fas fa-times-circle mr-1" :class="{ 'fa-spin': actionLoading }"></i>
                            Reject
                        </button>

                        <button v-if="selected && canConfirmCancel(selected)" class="btn btn-warning" type="button"
                            :disabled="actionLoading" @click="onConfirmCancel(selected)">
                            <i class="fas fa-ban mr-1" :class="{ 'fa-spin': actionLoading }"></i>
                            Confirm Cancel
                        </button>

                        <button v-if="selected && canAdminDirectCancel(selected)" class="btn btn-warning" type="button"
                            :disabled="actionLoading" @click="onAdminDirectCancel(selected)">
                            <i class="fas fa-ban mr-1" :class="{ 'fa-spin': actionLoading }"></i>
                            Cancel
                        </button>

                        <button v-if="selected && canDelete(selected)" class="btn btn-outline-danger" type="button"
                            :disabled="actionLoading" @click="onDeleteBooking(selected)">
                            <i class="fas fa-trash mr-1" :class="{ 'fa-spin': actionLoading }"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";

import {
    apiGetCalendar,
    apiCreateBooking,
    apiUpdateBooking,
    apiGetBooking,
    apiRequestCancelBooking,
    apiApproveBooking,
    apiRejectBooking,
    apiConfirmCancelBooking,
    apiAdminCancelBooking,
    apiDeleteBooking,
} from "@func/api/calendar";

import { apiGetRooms } from "@func/api/room";

const calendarRef = ref(null);
const createModal = ref(null);
const detailModal = ref(null);

const loading = ref(false);
const saving = ref(false);
const actionLoading = ref(false);
const detailLoading = ref(false);

const error = ref("");
const formError = ref("");

const selected = ref(null);
const rooms = ref([]);
const lastRange = ref({ start: "", end: "" });

const bookingForm = reactive({
    room_id: "",
    start_datetime: "",
    end_datetime: "",
    recurrence_type: "none",
    recurrence_days: "",
    recurrence_period: "",
    recurrence_until: "",
});

const currentUser = computed(() => {
    try {
        return JSON.parse(localStorage.getItem("user") || "null");
    } catch {
        return null;
    }
});

const isAdmin = computed(() => currentUser.value?.level === "admin");

function showCreateModal() {
    if (!window.$ || !createModal.value) return;
    window.$(createModal.value).modal("show");
}

function hideCreateModal() {
    if (!window.$ || !createModal.value) return;
    window.$(createModal.value).modal("hide");
}

function showDetailModal() {
    if (!window.$ || !detailModal.value) return;
    window.$(detailModal.value).modal("show");
}

function hideDetailModal() {
    if (!window.$ || !detailModal.value) return;
    window.$(detailModal.value).modal("hide");
}

function normalizeDt(dt) {
    if (!dt) return null;
    return String(dt).replace(" ", "T");
}

function toMysqlDatetime(value) {
    if (!value) return null;

    if (value instanceof Date) {
        const yyyy = value.getFullYear();
        const mm = String(value.getMonth() + 1).padStart(2, "0");
        const dd = String(value.getDate()).padStart(2, "0");
        const hh = String(value.getHours()).padStart(2, "0");
        const ii = String(value.getMinutes()).padStart(2, "0");
        const ss = String(value.getSeconds()).padStart(2, "0");
        return `${yyyy}-${mm}-${dd} ${hh}:${ii}:${ss}`;
    }

    return String(value).replace("T", " ");
}

function fmt(dt) {
    if (!dt) return "-";
    const d = new Date(normalizeDt(dt));
    if (Number.isNaN(d.getTime())) return String(dt);
    return d.toLocaleString();
}

function formatForInput(dt) {
    if (!dt) return "";
    const d = new Date(dt);
    if (Number.isNaN(d.getTime())) return "";
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    const hh = String(d.getHours()).padStart(2, "0");
    const ii = String(d.getMinutes()).padStart(2, "0");
    return `${yyyy}-${mm}-${dd}T${hh}:${ii}`;
}

function parseRecurrenceDays(value) {
    if (!value) return null;
    if (Array.isArray(value)) return value;
    return String(value)
        .split(",")
        .map((x) => x.trim().toLowerCase())
        .filter(Boolean);
}

function formatRecurrenceDays(value) {
    if (!value) return "-";
    if (Array.isArray(value)) return value.join(", ");
    return value;
}

function isPastBooking(booking) {
    if (!booking?.end_datetime) return false;
    const end = new Date(normalizeDt(booking.end_datetime));
    if (Number.isNaN(end.getTime())) return false;
    return end.getTime() < Date.now();
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

function statusClass(status) {
    if (status === "pending") return "fc-event-pending";
    if (status === "approved") return "fc-event-approved";
    if (status === "rejected") return "fc-event-rejected";
    if (status === "cancel_requested") return "fc-event-cancel-requested";
    if (status === "cancelled") return "fc-event-cancelled";
    if (status === "completed") return "fc-event-completed";
    return "fc-event-default";
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
        const res = await apiGetRooms({ per_page: 100 });
        rooms.value = res?.data?.data ?? res?.data ?? [];
    } catch (e) {
        console.error("Failed to load rooms", e);
    }
}

function resetForm() {
    bookingForm.room_id = "";
    bookingForm.start_datetime = "";
    bookingForm.end_datetime = "";
    bookingForm.recurrence_type = "none";
    bookingForm.recurrence_days = "";
    bookingForm.recurrence_period = "";
    bookingForm.recurrence_until = "";
    formError.value = "";
}

async function fetchEvents(info, successCallback, failureCallback) {
    const startStr = info.startStr;
    const endStr = info.endStr;

    if (lastRange.value.start === startStr && lastRange.value.end === endStr) {
        return;
    }

    lastRange.value = { start: startStr, end: endStr };
    loading.value = true;
    error.value = "";

    try {
        const res = await apiGetCalendar({ start: startStr, end: endStr });
        const bookings = res.data ?? [];

        const events = bookings.map((b) => ({
            id: String(b.id),
            title: b.room?.name ?? `Room ${b.room_id}`,
            start: normalizeDt(b.start_datetime),
            end: normalizeDt(b.end_datetime),
            editable: !isPastBooking(b) && (isAdmin.value ? true : b.status === "pending"),
            extendedProps: { booking: b },
            classNames: [
                statusClass(b.status),
                isPastBooking(b) ? "fc-event-expired" : "",
            ].filter(Boolean),
        }));

        successCallback(events);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to load calendar";
        failureCallback(e);
    } finally {
        loading.value = false;
    }
}

function refetch() {
    const api = calendarRef.value?.getApi?.();
    if (!api) return;
    lastRange.value = { start: "", end: "" };
    api.refetchEvents();
}

async function onDateSelect(selectInfo) {
    resetForm();
    await loadRooms();

    bookingForm.start_datetime = formatForInput(selectInfo.start);
    bookingForm.end_datetime = formatForInput(selectInfo.end);

    showCreateModal();
}

async function submitCreateBooking() {
    formError.value = "";

    if (!bookingForm.room_id) {
        formError.value = "Please select room";
        return;
    }

    if (!bookingForm.start_datetime || !bookingForm.end_datetime) {
        formError.value = "Please select start and end datetime";
        return;
    }

    saving.value = true;

    try {
        await apiCreateBooking({
            room_id: Number(bookingForm.room_id),
            start_datetime: toMysqlDatetime(bookingForm.start_datetime),
            end_datetime: toMysqlDatetime(bookingForm.end_datetime),
            recurrence_type: bookingForm.recurrence_type || "none",
            recurrence_days: parseRecurrenceDays(bookingForm.recurrence_days),
            recurrence_period: bookingForm.recurrence_period ? Number(bookingForm.recurrence_period) : null,
            recurrence_until: bookingForm.recurrence_until || null,
        });

        hideCreateModal();
        resetForm();
        refetch();
    } catch (e) {
        formError.value = e?.response?.data?.message || e?.message || "Failed to create booking";
    } finally {
        saving.value = false;
    }
}

function eventClick(info) {
    const booking = info.event.extendedProps?.booking ?? null;

    if (!booking) return;

    if (isPastBooking(booking)) {
        error.value = "This booking has already expired and cannot be opened.";
        return;
    }

    selected.value = booking;
    detailLoading.value = false;
    showDetailModal();
}

async function eventDrop(info) {
    const booking = info.event.extendedProps?.booking;
    if (!booking?.id) {
        info.revert();
        return;
    }

    if (isPastBooking(booking)) {
        info.revert();
        error.value = "Past bookings cannot be moved.";
        return;
    }

    if (!isAdmin.value && booking.status !== "pending") {
        info.revert();
        error.value = "You can only move pending bookings";
        return;
    }

    try {
        await apiUpdateBooking(booking.id, {
            room_id: booking.room_id,
            start_datetime: toMysqlDatetime(info.event.start),
            end_datetime: toMysqlDatetime(info.event.end),
            recurrence_type: booking.recurrence_type ?? "none",
            recurrence_days: booking.recurrence_days ?? null,
            recurrence_period: booking.recurrence_period ?? null,
            recurrence_until: booking.recurrence_until ?? null,
        });

        await reloadBookingAfterAction(booking.id);
    } catch (e) {
        info.revert();
        error.value = e?.response?.data?.message || e?.message || "Failed to update booking";
    }
}

async function eventResize(info) {
    const booking = info.event.extendedProps?.booking;
    if (!booking?.id) {
        info.revert();
        return;
    }

    if (isPastBooking(booking)) {
        info.revert();
        error.value = "Past bookings cannot be resized.";
        return;
    }

    if (!isAdmin.value && booking.status !== "pending") {
        info.revert();
        error.value = "You can only resize pending bookings";
        return;
    }

    try {
        await apiUpdateBooking(booking.id, {
            room_id: booking.room_id,
            start_datetime: toMysqlDatetime(info.event.start),
            end_datetime: toMysqlDatetime(info.event.end),
            recurrence_type: booking.recurrence_type ?? "none",
            recurrence_days: booking.recurrence_days ?? null,
            recurrence_period: booking.recurrence_period ?? null,
            recurrence_until: booking.recurrence_until ?? null,
        });

        await reloadBookingAfterAction(booking.id);
    } catch (e) {
        info.revert();
        error.value = e?.response?.data?.message || e?.message || "Failed to resize booking";
    }
}

async function reloadBookingAfterAction(id) {
    try {
        const res = await apiGetBooking(id);
        selected.value = res.data?.data ?? res.data?.booking ?? res.data;
    } catch (e) {
        console.error(e);
    }

    refetch();
}

async function onRequestCancel(booking) {
    if (!booking?.id) return;
    if (isPastBooking(booking)) {
        error.value = "Past bookings cannot request cancellation.";
        return;
    }

    if (!window.confirm("Do you want to request cancellation for this booking?")) {
        return;
    }

    actionLoading.value = true;

    try {
        await apiRequestCancelBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to request cancel";
    } finally {
        actionLoading.value = false;
    }
}

async function onApproveBooking(booking) {
    if (!booking?.id) return;
    if (isPastBooking(booking)) {
        error.value = "Past bookings cannot be approved.";
        return;
    }

    if (!window.confirm("Approve this booking?")) {
        return;
    }

    actionLoading.value = true;

    try {
        await apiApproveBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to approve booking";
    } finally {
        actionLoading.value = false;
    }
}

async function onRejectBooking(booking) {
    if (!booking?.id) return;
    if (isPastBooking(booking)) {
        error.value = "Past bookings cannot be rejected.";
        return;
    }

    if (!window.confirm("Reject this booking?")) {
        return;
    }

    actionLoading.value = true;

    try {
        await apiRejectBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to reject booking";
    } finally {
        actionLoading.value = false;
    }
}

async function onConfirmCancel(booking) {
    if (!booking?.id) return;
    if (isPastBooking(booking)) {
        error.value = "Past bookings cannot confirm cancellation.";
        return;
    }

    if (!window.confirm("Confirm cancel for this booking?")) {
        return;
    }

    actionLoading.value = true;

    try {
        await apiConfirmCancelBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to confirm cancel";
    } finally {
        actionLoading.value = false;
    }
}

async function onAdminDirectCancel(booking) {
    if (!booking?.id) return;
    if (isPastBooking(booking)) {
        error.value = "Past bookings cannot be cancelled directly.";
        return;
    }

    if (!window.confirm("Cancel this booking directly as admin?")) {
        return;
    }

    actionLoading.value = true;

    try {
        await apiAdminCancelBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to cancel booking";
    } finally {
        actionLoading.value = false;
    }
}

async function onDeleteBooking(booking) {
    if (!booking?.id) return;

    if (!window.confirm("Delete this booking permanently?")) {
        return;
    }

    actionLoading.value = true;

    try {
        await apiDeleteBooking(booking.id);
        hideDetailModal();
        refetch();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to delete booking";
    } finally {
        actionLoading.value = false;
    }
}

onMounted(async () => {
    await loadRooms();
});

const calendarOptions = {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
    },
    height: "auto",
    expandRows: true,
    stickyHeaderDates: true,
    nowIndicator: true,
    selectable: true,
    selectMirror: true,
    editable: true,
    eventResizableFromStart: true,
    dayMaxEvents: true,
    eventDisplay: "block",
    eventTimeFormat: {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    },
    events: fetchEvents,
    select: onDateSelect,
    eventClick,
    eventDrop,
    eventResize,
};
</script>

<style>
.gc-wrap {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
}

.calendar-legend {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    font-size: 13px;
    color: #495057;
    gap: 6px;
}

.legend-color {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    display: inline-block;
}

.legend-color.pending {
    background: #ffc107;
}

.legend-color.approved {
    background: #28a745;
}

.legend-color.rejected {
    background: #dc3545;
}

.legend-color.cancel_requested {
    background: #17a2b8;
}

.legend-color.cancelled {
    background: #6c757d;
}

.legend-color.completed {
    background: #007bff;
}

.legend-color.expired {
    background: #343a40;
}

.fc .fc-toolbar-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #343a40;
}

.fc .fc-button {
    text-transform: capitalize;
}

.fc .fc-col-header-cell-cushion,
.fc .fc-daygrid-day-number,
.fc .fc-timegrid-axis-cushion,
.fc .fc-timegrid-slot-label-cushion {
    color: #495057;
    text-decoration: none;
}

.fc .fc-day-today {
    background: rgba(0, 123, 255, 0.08) !important;
}

.fc .fc-event {
    border: 0 !important;
    border-radius: 8px !important;
    padding: 2px 6px !important;
    font-size: 12px !important;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
}

.fc-event-pending {
    background: #ffc107 !important;
    color: #212529 !important;
}

.fc-event-approved {
    background: #28a745 !important;
    color: #fff !important;
}

.fc-event-rejected {
    background: #dc3545 !important;
    color: #fff !important;
}

.fc-event-cancel-requested {
    background: #17a2b8 !important;
    color: #fff !important;
}

.fc-event-cancelled {
    background: #6c757d !important;
    color: #fff !important;
    text-decoration: line-through;
    opacity: 0.9;
}

.fc-event-completed {
    background: #007bff !important;
    color: #fff !important;
}

.fc-event-default {
    background: #343a40 !important;
    color: #fff !important;
}

.fc-event-expired {
    opacity: 0.55 !important;
    cursor: not-allowed !important;
    filter: grayscale(20%);
}

.fc-event-expired .fc-event-title,
.fc-event-expired .fc-event-time {
    text-decoration: line-through;
}

.modal-header.bg-primary,
.modal-header.bg-info {
    border-bottom: 0;
}

.table th {
    background: #f8f9fa;
}
</style>