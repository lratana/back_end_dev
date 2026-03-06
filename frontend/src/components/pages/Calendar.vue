<template>
    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">

                <div v-if="error" class="alert alert-danger">
                    {{ error }}
                </div>

                <div class="card">
                    <div class="card-body">

                        <div class="calendar-legend mb-3">
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

                            <div class="ml-auto d-flex align-items-center" style="gap:10px;">
                                <button class="btn btn-sm btn-outline-primary" :disabled="loading" @click="refetch">
                                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                                    Reload
                                </button>
                            </div>
                        </div>

                        <div class="gc-wrap">
                            <FullCalendar ref="calendarRef" :options="calendarOptions" />
                        </div>

                    </div>
                </div>

            </div>
        </section>
    </div>

    <div class="modal fade" ref="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Booking Detail</h5>
                    <button type="button" class="close" @click="hideModal">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="detailLoading" class="text-center py-4">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>

                    <div v-else-if="selected">
                        <div class="mb-2">
                            <strong>Room:</strong>
                            {{ selected.room?.name ?? ("Room #" + selected.room_id) }}
                        </div>

                        <div class="mb-2">
                            <strong>Start:</strong> {{ fmt(selected.start_datetime) }}
                        </div>

                        <div class="mb-2">
                            <strong>End:</strong> {{ fmt(selected.end_datetime) }}
                        </div>

                        <div class="mb-2">
                            <strong>Status:</strong>
                            <span class="badge" :class="statusBadge(selected.status)">
                                {{ selected.status }}
                            </span>

                            <span v-if="isPastBooking(selected)" class="badge badge-dark ml-2">
                                expired
                            </span>
                        </div>

                        <div v-if="selected.note" class="mb-2">
                            <strong>Note:</strong> {{ selected.note }}
                        </div>
                    </div>

                    <div v-else class="text-muted">
                        No data
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" @click="hideModal">Close</button>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import { apiGetCalendar } from "@func/api/calendar";

const calendarRef = ref(null);

const loading = ref(false);
const error = ref("");

const detailModal = ref(null);
const selected = ref(null);
const detailLoading = ref(false);

const lastRange = ref({ start: "", end: "" });

function showModal() {
    if (!window.$ || !detailModal.value) return;
    window.$(detailModal.value).modal("show");
}

function hideModal() {
    if (!window.$ || !detailModal.value) return;
    window.$(detailModal.value).modal("hide");
}

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
    if (status === "pending") return "ev-pending";
    if (status === "approved") return "ev-approved";
    if (status === "rejected") return "ev-rejected";
    if (status === "cancel_requested") return "ev-cancel-requested";
    if (status === "cancelled") return "ev-cancelled";
    if (status === "completed") return "ev-completed";
    return "ev-default";
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
            extendedProps: { booking: b },
            classNames: [
                statusClass(b.status),
                isPastBooking(b) ? "ev-expired" : "",
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
    selectable: false,
    editable: false,
    dayMaxEvents: true,
    eventDisplay: "block",
    eventTimeFormat: { hour: "2-digit", minute: "2-digit", hour12: false },
    events: fetchEvents,

    eventClick(info) {
        const booking = info.event.extendedProps?.booking ?? null;

        if (!booking) return;

        if (isPastBooking(booking)) {
            error.value = "This booking has already expired and cannot be opened.";
            return;
        }

        selected.value = booking;
        detailLoading.value = false;
        showModal();
    },
};
</script>

<style scoped>
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

.gc-wrap {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
}

:deep(.fc .fc-event) {
    border: 0 !important;
    border-radius: 8px !important;
    padding: 2px 6px !important;
    font-size: 12px !important;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
}

:deep(.fc .fc-day-today) {
    background: rgba(0, 123, 255, 0.08) !important;
}

:deep(.ev-pending) {
    background: #ffc107 !important;
    color: #212529 !important;
}

:deep(.ev-approved) {
    background: #28a745 !important;
    color: #fff !important;
}

:deep(.ev-rejected) {
    background: #dc3545 !important;
    color: #fff !important;
}

:deep(.ev-cancel-requested) {
    background: #17a2b8 !important;
    color: #fff !important;
}

:deep(.ev-cancelled) {
    background: #6c757d !important;
    color: #fff !important;
    text-decoration: line-through;
    opacity: 0.9;
}

:deep(.ev-completed) {
    background: #007bff !important;
    color: #fff !important;
}

:deep(.ev-default) {
    background: #343a40 !important;
    color: #fff !important;
}

:deep(.ev-expired) {
    opacity: 0.55 !important;
    cursor: not-allowed !important;
    filter: grayscale(20%);
}

:deep(.ev-expired .fc-event-title),
:deep(.ev-expired .fc-event-time) {
    text-decoration: line-through;
}
</style>