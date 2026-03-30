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
                                <div class="legend-item"><span class="legend-color pending"></span>Pending</div>
                                <div class="legend-item"><span class="legend-color approved"></span>Approved</div>
                                <div class="legend-item"><span class="legend-color rejected"></span>Rejected</div>
                                <div class="legend-item"><span class="legend-color cancel_requested"></span>Cancel
                                    Request</div>
                                <div class="legend-item"><span class="legend-color cancelled"></span>Cancelled</div>
                                <div class="legend-item"><span class="legend-color completed"></span>Completed</div>
                                <div class="legend-item"><span class="legend-color expired"></span>Expired</div>
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

    <!-- Create / Edit Booking Modal -->
    <div class="modal fade" ref="createModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle mr-2"></i>
                        {{ bookingForm.id ? "Edit Booking" : "Create Booking" }}
                    </h5>
                    <button type="button" class="close text-white" @click="hideCreateModal">
                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div v-if="formError" class="alert alert-danger">
                        {{ formError }}
                    </div>

                    <div v-if="availabilityMessage" class="alert" :class="availabilityAlertClass">
                        {{ availabilityMessage }}
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Start <span class="text-danger">*</span></label>
                            <input v-model="bookingForm.start_datetime" type="datetime-local" class="form-control" />
                        </div>

                        <div class="form-group col-md-6">
                            <label>End <span class="text-danger">*</span></label>
                            <input v-model="bookingForm.end_datetime" type="datetime-local" class="form-control" />
                        </div>

                        <div class="col-md-12 mb-2 d-flex flex-wrap align-items-center" style="gap:8px;">
                            <button type="button" class="btn btn-outline-info btn-sm"
                                :disabled="checkingAvailability || !canCheckAvailability" @click="loadAvailableRooms">
                                <i class="fas fa-search mr-1" :class="{ 'fa-spin': checkingAvailability }"></i>
                                {{ checkingAvailability ? "Checking..." : "Refresh Available Rooms" }}
                            </button>

                            <small class="text-muted">
                                ជ្រើសថ្ងៃ/ម៉ោងរួច ប្រព័ន្ធនឹងស្វែងរកបន្ទប់ទំនេរ auto
                            </small>
                        </div>

                        <div class="form-group col-md-6">
                            <label>Room <span class="text-danger">*</span></label>
                            <select class="form-control" v-model="bookingForm.room_id"
                                :disabled="!canSelectRoom || checkingAvailability">
                                <option value="" disabled>
                                    {{
                                        checkingAvailability
                                            ? "Loading available rooms..."
                                            : availableRooms.length
                                                ? "Select available room..."
                                                : "No available room"
                                    }}
                                </option>
                                <option v-for="r in availableRooms" :key="r.id" :value="String(r.id)">
                                    {{ r.name }} (Capacity: {{ r.capacity ?? "-" }})
                                </option>
                            </select>
                            <small class="text-muted">
                                បង្ហាញតែបន្ទប់ទំនេរ តាមថ្ងៃ/ម៉ោងដែលបានជ្រើស
                            </small>
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
                            <label>Meeting Title</label>
                            <input v-model="bookingForm.meeting_title" type="text" class="form-control"
                                placeholder="Meeting title" />
                        </div>

                        <div class="form-group col-md-6">
                            <label>Meeting Chairman</label>
                            <input v-model="bookingForm.meeting_chairman" type="text" class="form-control"
                                placeholder="Chairman name" />
                        </div>

                        <div class="form-group col-md-6">
                            <label>Recurrence Period</label>
                            <input v-model="bookingForm.recurrence_period" type="number" min="1" class="form-control"
                                :disabled="isRecurrenceNone" />
                        </div>

                        <div class="form-group col-md-6">
                            <label>Recurrence Until</label>
                            <input v-model="bookingForm.recurrence_until" type="date" class="form-control"
                                :disabled="isRecurrenceNone" />
                        </div>

                        <div class="form-group col-md-12">
                            <label>Recurrence Days</label>
                            <input v-model="bookingForm.recurrence_days" type="text" class="form-control"
                                placeholder="mon,tue or wed,fri" :disabled="isRecurrenceNone" />
                            <small class="text-muted">Use: mon, tue, wed, thu, fri, sat, sun</small>
                        </div>

                        <div class="form-group col-md-4">
                            <label>Snack Required</label>
                            <select v-model="bookingForm.snack_required" class="form-control">
                                <option :value="false">No</option>
                                <option :value="true">Yes</option>
                            </select>
                        </div>

                        <div class="form-group col-md-8">
                            <label>Snack Note</label>
                            <input v-model="bookingForm.snack_note" type="text" class="form-control"
                                placeholder="Snack detail" :disabled="!bookingForm.snack_required" />
                        </div>

                        <div class="form-group col-md-4">
                            <label>Technician Required</label>
                            <select v-model="bookingForm.technician_required" class="form-control">
                                <option :value="false">No</option>
                                <option :value="true">Yes</option>
                            </select>
                        </div>

                        <div class="form-group col-md-8">
                            <label>Technician Note</label>
                            <input v-model="bookingForm.technician_note" type="text" class="form-control"
                                placeholder="Technician detail" :disabled="!bookingForm.technician_required" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button class="btn btn-default" type="button" @click="hideCreateModal">Close</button>
                    <button class="btn btn-primary" type="button" :disabled="saving" @click="saveBooking">
                        {{ saving ? "Saving..." : bookingForm.id ? "Update Booking" : "Create Booking" }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" ref="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
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
                                    <th style="width:170px;">Room</th>
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
                                        <div v-if="statusHelpText(selected.status)" class="small text-muted mt-1">
                                            {{ statusHelpText(selected.status) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="selected.user?.name">
                                    <th>User</th>
                                    <td>{{ selected.user.name }}</td>
                                </tr>
                                <tr v-if="selected.meeting_title">
                                    <th>Meeting Title</th>
                                    <td>{{ selected.meeting_title }}</td>
                                </tr>
                                <tr v-if="selected.meeting_chairman">
                                    <th>Meeting Chairman</th>
                                    <td>{{ selected.meeting_chairman }}</td>
                                </tr>
                                <tr>
                                    <th>Snack Required</th>
                                    <td>{{ selected.snack_required ? "Yes" : "No" }}</td>
                                </tr>
                                <tr v-if="selected.snack_note">
                                    <th>Snack Note</th>
                                    <td>{{ selected.snack_note }}</td>
                                </tr>
                                <tr>
                                    <th>Technician Required</th>
                                    <td>{{ selected.technician_required ? "Yes" : "No" }}</td>
                                </tr>
                                <tr v-if="selected.technician_note">
                                    <th>Technician Note</th>
                                    <td>{{ selected.technician_note }}</td>
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
                                    <td>{{ fmtDateOnly(selected.recurrence_until) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-muted">No data</div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button class="btn btn-default" type="button" @click="hideDetailModal">Close</button>

                    <div class="d-flex flex-wrap" style="gap:8px;">
                        <button v-if="selected && canEdit(selected)" class="btn btn-primary" type="button"
                            @click="editFromDetail">
                            Edit
                        </button>

                        <button v-if="selected && canApprove(selected)" class="btn btn-success" type="button"
                            :disabled="actionLoading" @click="onApproveBooking(selected)">
                            Approve
                        </button>

                        <button v-if="selected && canReject(selected)" class="btn btn-danger" type="button"
                            :disabled="actionLoading" @click="onRejectBooking(selected)">
                            Reject
                        </button>

                        <button v-if="selected && canRequestCancel(selected)" class="btn btn-warning" type="button"
                            :disabled="actionLoading" @click="onRequestCancel(selected)">
                            Request Cancel
                        </button>

                        <button v-if="selected && canConfirmCancel(selected)" class="btn btn-warning" type="button"
                            :disabled="actionLoading" @click="onConfirmCancel(selected)">
                            Confirm Cancel
                        </button>

                        <button v-if="selected && canAdminDirectCancel(selected)" class="btn btn-warning" type="button"
                            :disabled="actionLoading" @click="onAdminDirectCancel(selected)">
                            Force Cancel
                        </button>

                        <button v-if="selected && canDelete(selected)" class="btn btn-outline-danger" type="button"
                            :disabled="actionLoading" @click="onDeleteBooking(selected)">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useStore } from "vuex";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import Swal from "sweetalert2";
import { CloseModal, LoadingModal, MessageModal } from "@func/swal";
import { formatFullDateTime } from "@func/datetime";
import { apiGetCalendar } from "@/functions/api/calendar";
import {
    apiCreateBooking,
    apiUpdateBooking,
    apiGetBooking,
    apiRequestCancelBooking,
    apiApproveBooking,
    apiRejectBooking,
    apiConfirmCancelBooking,
    apiAdminCancelBooking,
    apiDeleteBooking,
    apiGetAvailableRooms,
} from "@func/api/booking";

const store = useStore();

const calendarRef = ref(null);
const createModal = ref(null);
const detailModal = ref(null);

const loading = ref(false);
const saving = ref(false);
const actionLoading = ref(false);
const detailLoading = ref(false);
const checkingAvailability = ref(false);

const error = ref("");
const formError = ref("");
const availabilityMessage = ref("");

const selected = ref(null);
const availableRooms = ref([]);
const lastRange = ref({ start: "", end: "" });

let availabilityTimer = null;

const bookingForm = reactive({
    id: null,
    status: "",
    room_id: "",
    start_datetime: "",
    end_datetime: "",
    recurrence_type: "none",
    recurrence_days: "",
    recurrence_period: "",
    recurrence_until: "",
    meeting_title: "",
    meeting_chairman: "",
    snack_required: false,
    snack_note: "",
    technician_required: false,
    technician_note: "",
});

const currentUser = computed(() => store.state.user || null);

const isAdmin = computed(() =>
    ["super_admin", "admin"].includes(currentUser.value?.level)
);

const isRecurrenceNone = computed(() => bookingForm.recurrence_type === "none");

const canCheckAvailability = computed(() => {
    return !!bookingForm.start_datetime && !!bookingForm.end_datetime;
});

const canSelectRoom = computed(() => {
    return canCheckAvailability.value && availableRooms.value.length > 0;
});

const availabilityAlertClass = computed(() => {
    if (availableRooms.value.length > 0) return "alert-info";
    if (availabilityMessage.value) return "alert-warning";
    return "alert-info";
});

watch(
    () => bookingForm.recurrence_type,
    (value) => {
        if (value === "none") {
            bookingForm.recurrence_days = "";
            bookingForm.recurrence_period = "";
            bookingForm.recurrence_until = "";
        }
    }
);

watch(
    () => bookingForm.snack_required,
    (value) => {
        if (!value) bookingForm.snack_note = "";
    }
);

watch(
    () => bookingForm.technician_required,
    (value) => {
        if (!value) bookingForm.technician_note = "";
    }
);

watch(
    () => [bookingForm.start_datetime, bookingForm.end_datetime],
    ([newStart, newEnd], [oldStart, oldEnd]) => {
        if (newStart !== oldStart || newEnd !== oldEnd) {
            const currentRoomId = bookingForm.room_id;
            availableRooms.value = [];
            availabilityMessage.value = "";

            if (availabilityTimer) clearTimeout(availabilityTimer);

            if (newStart && newEnd) {
                availabilityTimer = setTimeout(() => {
                    loadAvailableRooms(true, currentRoomId);
                }, 500);
            }
        }
    }
);

function showCreateModal() {
    if (window.$ && createModal.value) window.$(createModal.value).modal("show");
}

function hideCreateModal() {
    if (window.$ && createModal.value) window.$(createModal.value).modal("hide");
    resetForm();
}

function showDetailModal() {
    if (window.$ && detailModal.value) window.$(detailModal.value).modal("show");
}

function hideDetailModal() {
    if (window.$ && detailModal.value) window.$(detailModal.value).modal("hide");
    setTimeout(() => {
        selected.value = null;
    }, 200);
}

function normalizeDt(dt) {
    if (!dt) return null;

    if (dt instanceof Date) {
        const yyyy = dt.getFullYear();
        const mm = String(dt.getMonth() + 1).padStart(2, "0");
        const dd = String(dt.getDate()).padStart(2, "0");
        const hh = String(dt.getHours()).padStart(2, "0");
        const ii = String(dt.getMinutes()).padStart(2, "0");
        const ss = String(dt.getSeconds()).padStart(2, "0");
        return `${yyyy}-${mm}-${dd}T${hh}:${ii}:${ss}`;
    }

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
    return formatFullDateTime(dt);
}

function fmtDateOnly(dt) {
    if (!dt) return "-";
    return formatFullDateTime(dt).split(",")[0];
}

function formatForInput(dt) {
    if (!dt) return "";
    const d = new Date(normalizeDt(dt));
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

    return String(value)
        .split(",")
        .map((x) => x.trim().toLowerCase())
        .filter(Boolean);
}

function formatRecurrenceDays(value) {
    if (!value) return "-";
    if (Array.isArray(value)) return value.join(", ");
    return String(value);
}

function isPastBooking(booking) {
    if (!booking?.end_datetime) return false;
    const end = new Date(normalizeDt(booking.end_datetime));
    return !Number.isNaN(end.getTime()) && end.getTime() < Date.now();
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

function statusHelpText(status) {
    switch (status) {
        case "pending":
            return "Waiting for admin review";
        case "approved":
            return "Approved and active";
        case "rejected":
            return "Rejected by admin";
        case "cancel_requested":
            return "Waiting for admin cancellation approval";
        case "cancelled":
            return "Booking has been cancelled";
        case "completed":
            return "Booking completed";
        default:
            return "";
    }
}

function ownerIdOf(booking) {
    return Number(
        booking?.user_id ??
        booking?.user?.id ??
        selected.value?.user_id ??
        selected.value?.user?.id ??
        0
    );
}

function canEdit(booking) {
    if (!booking || isPastBooking(booking)) return false;

    if (isAdmin.value) {
        return booking.status === "pending";
    }

    const currentUserId = Number(currentUser.value?.id ?? 0);
    return ownerIdOf(booking) === currentUserId && booking.status === "pending";
}

function canRequestCancel(booking) {
    if (!booking || isPastBooking(booking) || isAdmin.value) return false;

    const currentUserId = Number(currentUser.value?.id ?? 0);
    return ownerIdOf(booking) === currentUserId && booking.status === "approved";
}

function canApprove(booking) {
    return !!booking && isAdmin.value && !isPastBooking(booking) && booking.status === "pending";
}

function canReject(booking) {
    return !!booking && isAdmin.value && !isPastBooking(booking) && booking.status === "pending";
}

function canConfirmCancel(booking) {
    return !!booking && isAdmin.value && !isPastBooking(booking) && booking.status === "cancel_requested";
}

function canAdminDirectCancel(booking) {
    return !!booking && isAdmin.value && !isPastBooking(booking) && booking.status === "approved";
}

function canDelete(booking) {
    if (!booking) return false;

    if (isAdmin.value) {
        return ["pending", "rejected", "cancelled"].includes(booking.status);
    }

    if (isPastBooking(booking)) return false;

    const currentUserId = Number(currentUser.value?.id ?? 0);
    const ownerId = ownerIdOf(booking);

    return !!currentUserId && !!ownerId && ownerId === currentUserId && booking.status === "pending";
}

function resetForm() {
    bookingForm.id = null;
    bookingForm.status = "";
    bookingForm.room_id = "";
    bookingForm.start_datetime = "";
    bookingForm.end_datetime = "";
    bookingForm.recurrence_type = "none";
    bookingForm.recurrence_days = "";
    bookingForm.recurrence_period = "";
    bookingForm.recurrence_until = "";
    bookingForm.meeting_title = "";
    bookingForm.meeting_chairman = "";
    bookingForm.snack_required = false;
    bookingForm.snack_note = "";
    bookingForm.technician_required = false;
    bookingForm.technician_note = "";
    formError.value = "";
    availableRooms.value = [];
    availabilityMessage.value = "";
}

async function fillFormFromBooking(booking) {
    if (!booking) return;

    bookingForm.id = booking.id;
    bookingForm.status = booking.status ?? "";
    bookingForm.room_id = String(booking.room_id ?? "");
    bookingForm.start_datetime = formatForInput(booking.start_datetime);
    bookingForm.end_datetime = formatForInput(booking.end_datetime);
    bookingForm.recurrence_type = booking.recurrence_type ?? "none";
    bookingForm.recurrence_days = Array.isArray(booking.recurrence_days)
        ? booking.recurrence_days.join(",")
        : (booking.recurrence_days ?? "");
    bookingForm.recurrence_period = booking.recurrence_period ?? "";
    bookingForm.recurrence_until = booking.recurrence_until ? String(booking.recurrence_until).slice(0, 10) : "";
    bookingForm.meeting_title = booking.meeting_title ?? "";
    bookingForm.meeting_chairman = booking.meeting_chairman ?? "";
    bookingForm.snack_required = !!booking.snack_required;
    bookingForm.snack_note = booking.snack_note ?? "";
    bookingForm.technician_required = !!booking.technician_required;
    bookingForm.technician_note = booking.technician_note ?? "";

    availableRooms.value = [];
    availabilityMessage.value = "កំពុងស្វែងរកបន្ទប់ទំនេរ...";

    await loadAvailableRooms(true, booking.room_id);
}

async function editFromDetail() {
    if (!selected.value) return;

    const booking = JSON.parse(JSON.stringify(selected.value));

    hideDetailModal();

    setTimeout(async () => {
        await fillFormFromBooking(booking);
        showCreateModal();
    }, 300);
}

async function loadAvailableRooms(silent = false, preferredRoomId = null) {
    if (!silent) {
        formError.value = "";
    }

    availabilityMessage.value = "";
    availableRooms.value = [];

    const currentRoomId = bookingForm.room_id;

    if (!bookingForm.start_datetime || !bookingForm.end_datetime) {
        if (!silent) {
            formError.value = "Please select start datetime and end datetime first.";
        }
        return;
    }

    const start = new Date(normalizeDt(bookingForm.start_datetime));
    const end = new Date(normalizeDt(bookingForm.end_datetime));

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        if (!silent) {
            formError.value = "Invalid start or end datetime.";
        }
        return;
    }

    if (end <= start) {
        if (!silent) {
            formError.value = "End datetime must be after start datetime.";
        }
        return;
    }

    checkingAvailability.value = true;

    try {
        const res = await apiGetAvailableRooms({
            start_datetime: toMysqlDatetime(bookingForm.start_datetime),
            end_datetime: toMysqlDatetime(bookingForm.end_datetime),
            ignore_id: bookingForm.id || null,
        });

        availableRooms.value = res.data?.data ?? [];

        const targetRoomId = preferredRoomId ?? currentRoomId;

        if (availableRooms.value.length) {
            availabilityMessage.value = `មានបន្ទប់ទំនេរ ${availableRooms.value.length} បន្ទប់។ សូមជ្រើសរើសបន្ទប់។`;

            if (targetRoomId) {
                const matched = availableRooms.value.find(
                    (r) => Number(r.id) === Number(targetRoomId)
                );

                if (matched) {
                    bookingForm.room_id = String(matched.id);
                } else if (currentRoomId) {
                    bookingForm.room_id = String(currentRoomId);
                }
            }
        } else {
            availabilityMessage.value = "មិនមានបន្ទប់ទំនេរ សម្រាប់ថ្ងៃ និងម៉ោងដែលបានជ្រើសទេ។";
            bookingForm.room_id = currentRoomId || "";
        }
    } catch (e) {
        bookingForm.room_id = currentRoomId || "";

        if (!silent) {
            formError.value =
                e?.response?.data?.message ||
                e?.message ||
                "Failed to check available rooms.";
        }
    } finally {
        checkingAvailability.value = false;
    }
}

async function fetchEvents(info, successCallback, failureCallback) {
    const startStr = info.startStr;
    const endStr = info.endStr;

    lastRange.value = { start: startStr, end: endStr };
    loading.value = true;
    error.value = "";

    try {
        const res = await apiGetCalendar({ start: startStr, end: endStr });
        const bookings = res.data ?? [];

        successCallback(
            bookings.map((b) => ({
                id: String(b.id),
                title: b.meeting_title
                    ? `${b.room?.name ?? `Room ${b.room_id}`} - ${b.meeting_title}`
                    : (b.room?.name ?? `Room ${b.room_id}`),
                start: normalizeDt(b.start_datetime),
                end: normalizeDt(b.end_datetime),
                editable: !isPastBooking(b) && b.status === "pending",
                extendedProps: { booking: b },
                classNames: [
                    statusClass(b.status),
                    isPastBooking(b) ? "fc-event-expired" : "",
                ].filter(Boolean),
            }))
        );
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
    api.refetchEvents();
}

async function onDateSelect(selectInfo) {
    if (selectInfo.start < new Date()) {
        error.value = "You cannot create a booking in the past.";
        return;
    }

    resetForm();

    bookingForm.start_datetime = formatForInput(selectInfo.start);
    bookingForm.end_datetime = formatForInput(selectInfo.end);

    showCreateModal();
}

async function onDateClick(info) {
    const clickedDate = new Date(info.date);

    if (Number.isNaN(clickedDate.getTime())) {
        error.value = "Invalid selected date.";
        return;
    }

    if (clickedDate < new Date()) {
        error.value = "You cannot create a booking in the past.";
        return;
    }

    resetForm();

    const start = new Date(clickedDate);
    const end = new Date(clickedDate);

    if (info.view.type === "dayGridMonth" || info.allDay) {
        start.setHours(8, 0, 0, 0);
        end.setHours(9, 0, 0, 0);
    } else {
        end.setHours(end.getHours() + 1);
    }

    bookingForm.start_datetime = formatForInput(start);
    bookingForm.end_datetime = formatForInput(end);

    showCreateModal();
}

async function saveBooking() {
    formError.value = "";

    if (!bookingForm.room_id) {
        formError.value = "Please select available room";
        return;
    }

    if (!bookingForm.start_datetime || !bookingForm.end_datetime) {
        formError.value = "Please select start and end datetime";
        return;
    }

    const start = new Date(normalizeDt(bookingForm.start_datetime));
    const end = new Date(normalizeDt(bookingForm.end_datetime));

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        formError.value = "Invalid datetime";
        return;
    }

    if (end <= start) {
        formError.value = "End datetime must be after start datetime";
        return;
    }

    const recurrenceDays = parseRecurrenceDays(bookingForm.recurrence_days);
    const recurrencePeriod = bookingForm.recurrence_period ? Number(bookingForm.recurrence_period) : null;

    if (bookingForm.recurrence_type === "weekly" && (!recurrenceDays || recurrenceDays.length === 0)) {
        formError.value = "Weekly recurrence requires recurrence days.";
        return;
    }

    if (bookingForm.recurrence_type !== "none" && !recurrencePeriod) {
        formError.value = "Recurrence period is required.";
        return;
    }

    if (bookingForm.recurrence_type !== "none" && !bookingForm.recurrence_until) {
        formError.value = "Recurrence until is required.";
        return;
    }

    if (bookingForm.snack_required && !String(bookingForm.snack_note || "").trim()) {
        formError.value = "Snack note is required.";
        return;
    }

    if (bookingForm.technician_required && !String(bookingForm.technician_note || "").trim()) {
        formError.value = "Technician note is required.";
        return;
    }

    saving.value = true;

    try {
        LoadingModal();

        const payload = {
            room_id: Number(bookingForm.room_id),
            start_datetime: toMysqlDatetime(bookingForm.start_datetime),
            end_datetime: toMysqlDatetime(bookingForm.end_datetime),
            recurrence_type: bookingForm.recurrence_type || "none",
            recurrence_days: recurrenceDays,
            recurrence_period: recurrencePeriod,
            recurrence_until: bookingForm.recurrence_until || null,
            meeting_title: bookingForm.meeting_title || null,
            meeting_chairman: bookingForm.meeting_chairman || null,
            snack_required: !!bookingForm.snack_required,
            snack_note: bookingForm.snack_required ? (bookingForm.snack_note || null) : null,
            technician_required: !!bookingForm.technician_required,
            technician_note: bookingForm.technician_required ? (bookingForm.technician_note || null) : null,
        };

        let response = null;

        if (bookingForm.id) {
            response = await apiUpdateBooking(bookingForm.id, payload);
        } else {
            response = await apiCreateBooking(payload);
        }

        hideCreateModal();
        CloseModal();
        refetch();

        MessageModal(
            "success",
            "Success",
            response?.data?.message || (bookingForm.id ? "Booking updated successfully" : "Booking created successfully")
        );
    } catch (e) {
        CloseModal();

        if (e?.response?.status === 422) {
            formError.value = e?.response?.data?.message || "Validation failed.";
            return;
        }

        MessageModal(
            "error",
            "Error",
            e?.response?.data?.message || e?.message || "Failed to save booking"
        );
    } finally {
        CloseModal();
        saving.value = false;
    }
}

async function eventClick(info) {
    const booking = info.event.extendedProps?.booking ?? null;
    if (!booking?.id) return;

    if (isPastBooking(booking)) {
        return MessageModal("warning", "Expired", "This booking has already expired and cannot be opened.");
    }

    detailLoading.value = true;
    selected.value = null;
    showDetailModal();

    try {
        LoadingModal();
        const res = await apiGetBooking(booking.id);
        selected.value = res.data?.data ?? res.data?.booking ?? res.data;
        CloseModal();
    } catch (e) {
        CloseModal();
        hideDetailModal();
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Failed to load booking detail");
    } finally {
        detailLoading.value = false;
    }
}

async function eventDrop(info) {
    const booking = info.event.extendedProps?.booking;
    if (!booking?.id) return info.revert();

    if (isPastBooking(booking)) {
        info.revert();
        error.value = "Past bookings cannot be moved.";
        return;
    }

    if (booking.status !== "pending") {
        info.revert();
        error.value = "Only pending bookings can be modified.";
        return;
    }

    try {
        await apiUpdateBooking(booking.id, {
            room_id: booking.room_id,
            start_datetime: toMysqlDatetime(info.event.start),
            end_datetime: toMysqlDatetime(info.event.end),
            recurrence_type: booking.recurrence_type ?? "none",
            recurrence_days: Array.isArray(booking.recurrence_days)
                ? booking.recurrence_days
                : parseRecurrenceDays(booking.recurrence_days),
            recurrence_period: booking.recurrence_period ?? null,
            recurrence_until: booking.recurrence_until ?? null,
            meeting_title: booking.meeting_title ?? null,
            meeting_chairman: booking.meeting_chairman ?? null,
            snack_required: !!booking.snack_required,
            snack_note: booking.snack_note ?? null,
            technician_required: !!booking.technician_required,
            technician_note: booking.technician_note ?? null,
        });

        await reloadBookingAfterAction(booking.id);
    } catch (e) {
        info.revert();
        error.value = e?.response?.data?.message || e?.message || "Failed to update booking";
    }
}

async function eventResize(info) {
    const booking = info.event.extendedProps?.booking;
    if (!booking?.id) return info.revert();

    if (isPastBooking(booking)) {
        info.revert();
        error.value = "Past bookings cannot be resized.";
        return;
    }

    if (booking.status !== "pending") {
        info.revert();
        error.value = "Only pending bookings can be modified.";
        return;
    }

    try {
        await apiUpdateBooking(booking.id, {
            room_id: booking.room_id,
            start_datetime: toMysqlDatetime(info.event.start),
            end_datetime: toMysqlDatetime(info.event.end),
            recurrence_type: booking.recurrence_type ?? "none",
            recurrence_days: Array.isArray(booking.recurrence_days)
                ? booking.recurrence_days
                : parseRecurrenceDays(booking.recurrence_days),
            recurrence_period: booking.recurrence_period ?? null,
            recurrence_until: booking.recurrence_until ?? null,
            meeting_title: booking.meeting_title ?? null,
            meeting_chairman: booking.meeting_chairman ?? null,
            snack_required: !!booking.snack_required,
            snack_note: booking.snack_note ?? null,
            technician_required: !!booking.technician_required,
            technician_note: booking.technician_note ?? null,
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

    const result = await Swal.fire({
        icon: "warning",
        title: "Request Cancel",
        text: "Do you want to request cancellation for this approved booking?",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Yes, request",
    });

    if (!result.isConfirmed) return;

    actionLoading.value = true;
    try {
        LoadingModal();
        const response = await apiRequestCancelBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
        CloseModal();
        MessageModal("success", "Success", response?.data?.message || "Cancel request submitted");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Failed to request cancel");
    } finally {
        actionLoading.value = false;
    }
}

async function onApproveBooking(booking) {
    if (!booking?.id) return;

    const result = await Swal.fire({
        icon: "question",
        title: "Approve Booking",
        text: "Are you sure you want to approve this booking?",
        showCancelButton: true,
        confirmButtonColor: "#28a745",
        confirmButtonText: "Yes, approve",
    });

    if (!result.isConfirmed) return;

    actionLoading.value = true;
    try {
        LoadingModal();
        const response = await apiApproveBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
        CloseModal();
        MessageModal("success", "Success", response?.data?.message || "Booking approved");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Failed to approve booking");
    } finally {
        actionLoading.value = false;
    }
}

async function onRejectBooking(booking) {
    if (!booking?.id) return;

    const result = await Swal.fire({
        icon: "warning",
        title: "Reject Booking",
        text: "Are you sure you want to reject this booking?",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, reject",
    });

    if (!result.isConfirmed) return;

    actionLoading.value = true;
    try {
        LoadingModal();
        const response = await apiRejectBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
        CloseModal();
        MessageModal("success", "Success", response?.data?.message || "Booking rejected");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Failed to reject booking");
    } finally {
        actionLoading.value = false;
    }
}

async function onConfirmCancel(booking) {
    if (!booking?.id) return;

    const result = await Swal.fire({
        icon: "warning",
        title: "Confirm Cancel",
        text: "Are you sure you want to confirm this cancellation?",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Yes, confirm",
    });

    if (!result.isConfirmed) return;

    actionLoading.value = true;
    try {
        LoadingModal();
        const response = await apiConfirmCancelBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
        CloseModal();
        MessageModal("success", "Success", response?.data?.message || "Booking cancelled");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Failed to confirm cancel");
    } finally {
        actionLoading.value = false;
    }
}

async function onAdminDirectCancel(booking) {
    if (!booking?.id) return;

    const result = await Swal.fire({
        icon: "warning",
        title: "Force Cancel Booking",
        text: "This booking is already approved. Do you want to force cancel it as admin?",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Yes, force cancel",
    });

    if (!result.isConfirmed) return;

    actionLoading.value = true;
    try {
        LoadingModal();
        const response = await apiAdminCancelBooking(booking.id);
        await reloadBookingAfterAction(booking.id);
        CloseModal();
        MessageModal("success", "Success", response?.data?.message || "Booking cancelled");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Failed to force cancel booking");
    } finally {
        actionLoading.value = false;
    }
}

async function onDeleteBooking(booking) {
    if (!booking?.id) return;

    const result = await Swal.fire({
        icon: "warning",
        title: "Delete Booking",
        text: "Delete this booking permanently?",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete",
    });

    if (!result.isConfirmed) return;

    actionLoading.value = true;
    try {
        LoadingModal();
        const response = await apiDeleteBooking(booking.id);
        hideDetailModal();
        refetch();
        CloseModal();
        MessageModal("success", "Success", response?.data?.message || "Booking deleted");
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Failed to delete booking");
    } finally {
        actionLoading.value = false;
    }
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
    selectable: true,
    selectMirror: true,
    editable: true,
    eventResizableFromStart: true,
    dayMaxEvents: true,
    eventDisplay: "block",
    eventTimeFormat: { hour: "2-digit", minute: "2-digit", hour12: false },
    events: fetchEvents,
    select: onDateSelect,
    dateClick: onDateClick,
    eventClick,
    eventDrop,
    eventResize,
};
</script>

<style scoped>
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

:deep(.fc .fc-event) {
    border: 0 !important;
    border-radius: 8px !important;
    padding: 2px 6px !important;
    font-size: 12px !important;
    font-weight: 600;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .08);
}

:deep(.fc .fc-day-today) {
    background: rgba(0, 123, 255, .08) !important;
}

:deep(.fc-event-pending) {
    background: #ffc107 !important;
    color: #212529 !important;
}

:deep(.fc-event-approved) {
    background: #28a745 !important;
    color: #fff !important;
}

:deep(.fc-event-rejected) {
    background: #dc3545 !important;
    color: #fff !important;
}

:deep(.fc-event-cancel-requested) {
    background: #17a2b8 !important;
    color: #fff !important;
}

:deep(.fc-event-cancelled) {
    background: #6c757d !important;
    color: #fff !important;
    text-decoration: line-through;
    opacity: .9;
}

:deep(.fc-event-completed) {
    background: #007bff !important;
    color: #fff !important;
}

:deep(.fc-event-default) {
    background: #343a40 !important;
    color: #fff !important;
}

:deep(.fc-event-expired) {
    opacity: .55 !important;
    cursor: not-allowed !important;
    filter: grayscale(20%);
}

:deep(.fc-event-expired .fc-event-title),
:deep(.fc-event-expired .fc-event-time) {
    text-decoration: line-through;
}

.modal .modal-body {
    max-height: calc(100vh - 210px);
    overflow-y: auto;
}
</style>