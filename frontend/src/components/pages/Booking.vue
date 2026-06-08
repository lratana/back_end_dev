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
                        <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px">
                            <div class="d-flex flex-wrap align-items-end" style="gap: 10px">
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

                                <div style="min-width: 260px">
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
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                        <div v-if="availabilityMessage" class="alert" :class="availabilityAlertClass">
                            {{ availabilityMessage }}
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Start datetime</label>
                                <input type="datetime-local" class="form-control" v-model="bookingObject.start_datetime"
                                    :disabled="!isFieldEditable('start_datetime')"
                                    :class="{ 'is-invalid': bookingErr.start_datetime }" />
                                <div class="invalid-feedback">{{ bookingErr.start_datetime }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>End datetime</label>
                                <input type="datetime-local" class="form-control" v-model="bookingObject.end_datetime"
                                    :disabled="!isFieldEditable('end_datetime')"
                                    :class="{ 'is-invalid': bookingErr.end_datetime }" />
                                <div class="invalid-feedback">{{ bookingErr.end_datetime }}</div>
                            </div>

                            <div class="col-12 mb-3 d-flex align-items-center" style="gap: 10px">
                                <button type="button" class="btn btn-outline-info btn-sm" @click="loadAvailableRooms">
                                    <i class="fas fa-search mr-1" :class="{ 'fa-spin': checkingAvailability }"></i>
                                    {{ checkingAvailability ? "Checking..." : "Refresh Available Rooms" }}
                                </button>
                                <small class="text-muted">After selecting the date/time, the system will automatically
                                    search for
                                    available rooms.</small>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Room</label>
                                <!-- Room Selection -->
                                <select class="form-control" v-model="bookingObject.room_id"
                                    :disabled="!isFieldEditable('room_id') || !canSelectRoom">
                                    <option value="" disabled>Select available room</option>
                                    <option v-for="r in availableRooms" :key="r.id" :value="String(r.id)">
                                        {{ r.name }} (Capacity: {{ r.capacity ?? "-" }})
                                    </option>
                                </select>
                                <div class="invalid-feedback">{{ bookingErr.room_id }}</div>
                                <small class="text-muted">
                                    Show only available rooms according to the selected date/time.
                                </small>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Recurrence</label>
                                <select v-model="bookingObject.recurrence_type" class="form-control"
                                    :disabled="!isFieldEditable('recurrence_type')">
                                    <option value="none">none</option>
                                    <option value="daily">daily</option>
                                    <option value="weekly">weekly</option>
                                    <option value="monthly">monthly</option>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Meeting Title</label>
                                <!-- Meeting Title -->
                                <input type="text" class="form-control" v-model="bookingObject.meeting_title"
                                    :disabled="!isFieldEditable('meeting_title')" />
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Meeting Chairman</label>
                                <!-- Meeting Chairman -->
                                <input type="text" class="form-control" v-model="bookingObject.meeting_chairman"
                                    :disabled="!isFieldEditable('meeting_chairman')" />
                            </div>

                            <!-- Recurrence Section -->
                            <div v-if="!isRecurrenceNone" class="col-md-12 form-group">
                                <!-- <h6 class="text-primary mb-2">
                                    <i class="fas fa-repeat mr-1"></i> Recurrence Settings
                                </h6> -->

                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Recurrence Period</label>
                                        <input v-model="bookingObject.recurrence_period" type="number" min="1"
                                            class="form-control" />
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label>Recurrence Until</label>
                                        <input v-model="bookingObject.recurrence_until" type="date"
                                            class="form-control" />
                                    </div>

                                    <div class="form-group col-md-12">
                                        <label>Recurrence Days</label>
                                        <input v-model="bookingObject.recurrence_days" type="text" class="form-control"
                                            placeholder="mon,tue or wed,fri" />
                                        <small class="text-muted">
                                            Use: mon, tue, wed, thu, fri, sat, sun
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Snack Required</label>
                                <select class="form-control" v-model="bookingObject.snack_required"
                                    :disabled="!isFieldEditable('snack_required')">
                                    <option :value="false">No</option>
                                    <option :value="true">Yes</option>
                                </select>
                            </div>

                            <div class="col-md-8 form-group">
                                <label>Snack Note</label>
                                <!-- Snack Note -->
                                <input type="text" class="form-control" v-model="bookingObject.snack_note" :disabled="!isFieldEditable('snack_note') || !bookingObject.snack_required
                                    " />
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Technician Required</label>
                                <select class="form-control" v-model="bookingObject.technician_required"
                                    :disabled="!isFieldEditable('technician_required')">
                                    <option :value="false">No</option>
                                    <option :value="true">Yes</option>
                                </select>
                            </div>

                            <div class="col-md-8 form-group">
                                <label>Technician Note</label>
                                <!-- Technician Note -->
                                <input type="text" class="form-control" v-model="bookingObject.technician_note"
                                    placeholder="Technician detail" :disabled="!isFieldEditable('technician_note') ||
                                        !bookingObject.technician_required
                                        " />
                            </div>

                            <div class="col-12">
                                <small class="text-muted">
                                    * If conflict => backend returns 422 message: "Room is already booked
                                    for this time"
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
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
                                <th style="width: 180px">ID</th>
                                <td>{{ selectedBooking.id }}</td>
                            </tr>
                            <tr>
                                <th>Room</th>
                                <td>
                                    {{ selectedBooking.room?.name ?? `Room #${selectedBooking.room_id}` }}
                                </td>
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

                                    <div v-if="statusHelpText(selectedBooking.status)" class="small text-muted mt-1">
                                        {{ statusHelpText(selectedBooking.status) }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Meeting Title</th>
                                <td>{{ selectedBooking.meeting_title || "-" }}</td>
                            </tr>
                            <tr>
                                <th>Meeting Chairman</th>
                                <td>{{ selectedBooking.meeting_chairman || "-" }}</td>
                            </tr>
                            <tr>
                                <th>Snack Required</th>
                                <td>{{ selectedBooking.snack_required ? "Yes" : "No" }}</td>
                            </tr>
                            <tr>
                                <th>Snack Note</th>
                                <td>{{ selectedBooking.snack_note || "-" }}</td>
                            </tr>
                            <tr>
                                <th>Technician Required</th>
                                <td>{{ selectedBooking.technician_required ? "Yes" : "No" }}</td>
                            </tr>
                            <tr>
                                <th>Technician Note</th>
                                <td>{{ selectedBooking.technician_note || "-" }}</td>
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
                                <td>{{ fmtDateOnly(selectedBooking.recurrence_until) }}</td>
                            </tr>
                            <tr v-if="selectedBooking.cancel_reason">
                                <th>Cancel Reason</th>
                                <td>{{ selectedBooking.cancel_reason }}</td>
                            </tr>
                            <tr v-if="selectedBooking.reject_reason">
                                <th>Reject Reason</th>
                                <td>{{ selectedBooking.reject_reason }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer justify-content">
                    <!-- <button type="button" class="btn btn-default" @click="hideDetailModal">
                        Close
                    </button> -->

                    <div class="d-flex flex-wrap" style="gap: 8px">
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
                            <i class="fa fa-ban mr-1"></i> Force Cancel
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
import { computed, h, onMounted, reactive, ref, watch } from "vue";
import { useStore } from "vuex";
import { useRoute, useRouter } from "vue-router";
import Swal from "sweetalert2";
import CustomTable from "../includes/tables/CustomTable.vue";
import { CloseModal, LoadingModal, MessageModal } from "@func/swal";
import {
    parseBookingLocalDatetime,
    parseCambodiaFormDatetime,
    formatBookingDateTime,
    formatBookingDate,
    formatBookingTime,
    formatDateOnly,
    toDatetimeLocalInput,
    toMysqlDatetime,
    isPastBookingEnd,
    isPastCambodiaFormEnd,
} from "@func/bookingTime";
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
    apiGetAvailableRooms,
} from "@func/api/booking";
import { apiGetRooms } from "@func/api/room";
import { formatSystemDateTime } from "@/functions/bookingTime";

if (window.$?.fn?.modal?.Constructor?.prototype) {
    window.$.fn.modal.Constructor.prototype._enforceFocus = function () { };
}

const store = useStore();
const route = useRoute();
const router = useRouter();

const bookingModal = ref(null);
const detailModal = ref(null);

const loading = ref(false);
const saving = ref(false);
const actionLoading = ref(false);
const checkingAvailability = ref(false);

const error = ref("");
const formError = ref("");
const detailError = ref("");
const availabilityMessage = ref("");

const bookings = ref([]);
const rooms = ref([]);
const availableRooms = ref([]);
const selectedBooking = ref(null);

let availabilityTimer = null;

const filters = reactive({
    status: "",
    room_id: "",
});

const currentUser = computed(() => store.state.user || null);
const isAdmin = computed(() =>
    ["super_admin", "admin"].includes(currentUser.value?.level)
);
const roomOptions = computed(() => rooms.value ?? []);
const isRecurrenceNone = computed(() => bookingObject.recurrence_type === "none");

const canCheckAvailability = computed(() => {
    return !!bookingObject.start_datetime && !!bookingObject.end_datetime;
});

const canSelectRoom = computed(() => {
    return canCheckAvailability.value && availableRooms.value.length > 0;
});

const availabilityAlertClass = computed(() => {
    if (availableRooms.value.length > 0) return "alert-info";
    if (availabilityMessage.value) return "alert-warning";
    return "alert-info";
});
const filteredBookings = computed(() => {
    let list = [...(bookings.value ?? [])];

    if (filters.status) {
        list = list.filter((booking) => booking.status === filters.status);
    }

    if (filters.room_id) {
        list = list.filter((booking) => String(booking.room_id) === String(filters.room_id));
    }

    list.sort((a, b) => {
        if (isAdmin.value) {
            const aCreated =
                parseApiUtcDatetime(a.created_at || a.start_datetime)?.getTime() ?? 0;

            const bCreated =
                parseApiUtcDatetime(b.created_at || b.start_datetime)?.getTime() ?? 0;

            return bCreated - aCreated;
        }

        const aStart = parseApiUtcDatetime(a.start_datetime)?.getTime() ?? 0;

        const bStart = parseApiUtcDatetime(b.start_datetime)?.getTime() ?? 0;

        return aStart - bStart;
    });

    return list;
});

// function normalizeDt(dt) {
//     if (!dt) return null;

//     if (dt instanceof Date) {
//         const yyyy = dt.getFullYear();
//         const mm = String(dt.getMonth() + 1).padStart(2, "0");
//         const dd = String(dt.getDate()).padStart(2, "0");
//         const hh = String(dt.getHours()).padStart(2, "0");
//         const ii = String(dt.getMinutes()).padStart(2, "0");
//         const ss = String(dt.getSeconds()).padStart(2, "0");
//         return `${yyyy}-${mm}-${dd}T${hh}:${ii}:${ss}`;
//     }

//     return String(dt).replace(" ", "T");
// }

// function fmt(dt) {
//     if (!dt) return "-";
//     return formatFullDateTime(dt);
// }

// function fmtDateOnly(dt) {
//     if (!dt) return "-";
//     return formatFullDateTime(dt).split(",")[0];
// }

// function toLocalInput(dt) {
//     if (!dt) return "";

//     // Expecting dt in "YYYY-MM-DD HH:MM:SS" or ISO format from backend
//     const dateTimeStr = String(dt).replace(" ", "T").slice(0, 16);
//     // slice(0,16) ensures "YYYY-MM-DDTHH:MM" format required by datetime-local
//     return dateTimeStr;
// }

// const startDateTimeOnlyTime = computed(() => {
//     if (!bookingObject.start_datetime) return "";
//     const [date, time] = bookingObject.start_datetime.split("T");
//     return `${date}T${time}`; // keeps original date
// });

// const endDateTimeOnlyTime = computed(() => {
//     if (!bookingObject.end_datetime) return "";
//     const [date, time] = bookingObject.end_datetime.split("T");
//     return `${date}T${time}`; // keeps original date
// });
// // When user changes time, keep the original date and only update the time part
// function updateStartTime(value) {
//     if (!bookingObject.start_datetime) return;
//     const [date] = bookingObject.start_datetime.split("T");
//     bookingObject.start_datetime = `${date}T${value.split("T")[1]}`;
// }
// // When user changes time, keep the original date and only update the time part
// function updateEndTime(value) {
//     if (!bookingObject.end_datetime) return;
//     const [date] = bookingObject.end_datetime.split("T");
//     bookingObject.end_datetime = `${date}T${value.split("T")[1]}`;
// }

// function toMysqlDatetime(value) {
//     if (!value) return null;

//     if (value instanceof Date) {
//         const yyyy = value.getFullYear();
//         const mm = String(value.getMonth() + 1).padStart(2, "0");
//         const dd = String(value.getDate()).padStart(2, "0");
//         const hh = String(value.getHours()).padStart(2, "0");
//         const ii = String(value.getMinutes()).padStart(2, "0");
//         const ss = String(value.getSeconds()).padStart(2, "0");
//         return `${yyyy}-${mm}-${dd} ${hh}:${ii}:${ss}`;
//     }

//     return String(value).replace("T", " ");
// }
function parseApiUtcDatetime(value) {
    return parseBookingLocalDatetime(value);
}

function parseLocalInput(value) {
    return parseCambodiaFormDatetime(value);
}

function fmt(value) {
    return formatSystemDateTime(value);
}

function fmtDateOnly(value) {
    return formatDateOnly(value);
}

function toLocalInput(value) {
    return toDatetimeLocalInput(value);
}

function toMysqlDatetimeWrapper(value) {
    return toMysqlDatetime(value);
}

function formatScheduleDate(value) {
    return formatBookingDate(value);
}

function formatScheduleTime(value) {
    return formatBookingTime(value);
}

function isPastBooking(booking) {
    return isPastBookingEnd(booking.end_datetime);
}

function isPastBookingForm() {
    return isPastCambodiaFormEnd(bookingObject.end_datetime);
}
function formatRecurrenceDays(value) {
    if (!value) return "-";
    if (Array.isArray(value)) return value.join(", ");
    return String(value);
}

function normalizeRecurrenceUntil(dt) {
    if (!dt) return "";
    return String(dt).slice(0, 10);
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

const bookingObject = reactive({
    id: null,
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
    meeting_title: "",
    meeting_chairman: "",
    snack_required: false,
    snack_note: "",
    technician_required: false,
    technician_note: "",
    status: "",
};

const defaultBookingErr = {
    room_id: "",
    start_datetime: "",
    end_datetime: "",
};

watch(
    () => bookingObject.recurrence_type,
    (value) => {
        if (value === "none") {
            bookingObject.recurrence_period = "";
            bookingObject.recurrence_until = "";
            bookingObject.recurrence_days = "";
        }
    }
);

watch(
    () => bookingObject.technician_required,
    (value) => {
        if (!value) bookingObject.technician_note = "";
    }
);

watch(
    () => bookingObject.snack_required,
    (value) => {
        if (!value) bookingObject.snack_note = "";
    }
);

watch(
    () => [bookingObject.start_datetime, bookingObject.end_datetime],
    ([newStart, newEnd], [oldStart, oldEnd]) => {
        if (newStart !== oldStart || newEnd !== oldEnd) {
            const currentSelectedRoomId = bookingObject.room_id;
            availableRooms.value = [];
            availabilityMessage.value = "";

            if (availabilityTimer) clearTimeout(availabilityTimer);

            if (newStart && newEnd) {
                availabilityTimer = setTimeout(() => {
                    loadAvailableRooms(true, currentSelectedRoomId);
                }, 500);
            }
        }
    }
);

const isApprovedBooking = computed(() => bookingObject.status === "approved");

// Fields editable only for pending bookings
const isFieldEditable = (fieldName) => {
    if (isApprovedBooking.value) {
        return fieldName === "start_datetime" || fieldName === "end_datetime";
    }
    return true; // All fields editable if not approved
};
const canEditBookingForm = computed(() => {
    if (!bookingObject.id) {
        return true;
    }

    if (isApprovedBooking.value) {
        return !isPastBookingForm();
    }

    return bookingObject.status === "pending" && !isPastBookingForm();
});

function resetData() {
    Object.assign(bookingObject, { ...defaultBookingObject });
    Object.assign(bookingErr, { ...defaultBookingErr });
    availableRooms.value = [];
    availabilityMessage.value = "";
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

    setTimeout(() => {
        selectedBooking.value = null;
    }, 200);

    if (route.query.bookingId) {
        const query = { ...route.query };
        delete query.bookingId;
        router.replace({ query });
    }
}

function canOpenEdit(booking) {
    if (!booking) return false;
    if (isPastBooking(booking)) return false;
    return booking.status === "pending" || booking.status === "approved";
}

function canRequestCancel(booking) {
    if (!booking || isAdmin.value) return false;
    if (isPastBooking(booking)) return false;
    return booking.status === "approved";
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
    return booking.status === "approved";
}

function canDelete(booking) {
    if (!booking) return false;

    if (isAdmin.value) {
        return ["pending", "rejected", "cancelled"].includes(booking.status);
    }

    if (isPastBooking(booking)) return false;

    const currentUserId = currentUser.value?.id;
    if (!currentUserId || Number(booking.user_id) !== Number(currentUserId)) {
        return false;
    }

    return booking.status === "pending";
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

async function openCreate() {
    resetData();

    // Preload all rooms first
    await loadRooms();

    // Set default start/end datetime
    const now = new Date();
    bookingObject.start_datetime = toLocalInput(new Date(now.getTime() + 15 * 60000)); // +15 min
    bookingObject.end_datetime = toLocalInput(new Date(now.getTime() + 75 * 60000)); // +1 hr

    // Load available rooms for default datetime
    await loadAvailableRooms(true);

    showBookingModal();
}

async function viewBooking(id) {
    try {
        detailError.value = "";
        LoadingModal();
        const response = await apiGetBooking(id);
        const booking = response.data?.data ?? response.data?.booking ?? response.data;

        console.log("========== API BOOKING DATETIME ==========");
        console.log("Raw API start:", booking.start_datetime);
        console.log("Raw API end:", booking.end_datetime);
        console.log("Cambodia display start:", fmt(booking.start_datetime));
        console.log("Cambodia display end:", fmt(booking.end_datetime));

        selectedBooking.value = booking;
        showDetailModal();
        CloseModal();
    } catch (e) {
        CloseModal();
        detailError.value = e?.response?.data?.message || e.message;
        return MessageModal("error", "Error", e?.response?.data?.message || e.message);
    }
}

async function openBookingDetailById(id) {
    if (!id) return;
    await viewBooking(id);
}

async function fillFormFromBooking(booking) {
    if (!booking) return;

    Object.assign(bookingObject, {
        id: booking.id,
        room_id: String(booking.room_id ?? ""),
        start_datetime: toLocalInput(booking.start_datetime),
        end_datetime: toLocalInput(booking.end_datetime),
        recurrence_type: booking.recurrence_type ?? "none",
        recurrence_days: Array.isArray(booking.recurrence_days)
            ? booking.recurrence_days.join(",")
            : booking.recurrence_days ?? "",
        recurrence_period: booking.recurrence_period ?? "",
        recurrence_until: normalizeRecurrenceUntil(booking.recurrence_until),
        meeting_title: booking.meeting_title ?? "",
        meeting_chairman: booking.meeting_chairman ?? "",
        snack_required: !!booking.snack_required,
        snack_note: booking.snack_note ?? "",
        technician_required: !!booking.technician_required,
        technician_note: booking.technician_note ?? "",
        status: booking.status ?? "",
    });

    availableRooms.value = [];
    availabilityMessage.value = "កំពុងស្វែងរកបន្ទប់ទំនេរ...";

    // Preload available rooms for the selected room
    await loadAvailableRooms(true, booking.room_id);
}
async function editFromDetail() {
    if (!selectedBooking.value) return;

    const booking = JSON.parse(JSON.stringify(selectedBooking.value));

    hideDetailModal();

    setTimeout(async () => {
        await fillFormFromBooking(booking);
        showBookingModal();
    }, 300);
}
function validateBookingForm() {
    formError.value = "";
    Object.assign(bookingErr, { ...defaultBookingErr });

    if (!bookingObject.start_datetime) {
        bookingErr.start_datetime = "Start datetime is required";
        return false;
    }

    if (!bookingObject.end_datetime) {
        bookingErr.end_datetime = "End datetime is required";
        return false;
    }

    if (!bookingObject.room_id) {
        bookingErr.room_id = "Room is required";
        return false;
    }

    const start = parseCambodiaFormDatetime(bookingObject.start_datetime);
    const end = parseCambodiaFormDatetime(bookingObject.end_datetime);

    if (!start) {
        bookingErr.start_datetime = "Invalid start datetime";
        return false;
    }

    if (!end) {
        bookingErr.end_datetime = "Invalid end datetime";
        return false;
    }

    if (end.getTime() <= start.getTime()) {
        bookingErr.end_datetime = "End datetime must be after start";
        return false;
    }

    return true;
}
async function loadAvailableRooms(silent = false, preferredRoomId = null) {
    if (!silent) {
        formError.value = "";
    }

    availabilityMessage.value = "";
    bookingErr.room_id = "";

    const currentSelectedRoomId = bookingObject.room_id;

    if (!bookingObject.start_datetime || !bookingObject.end_datetime) {
        if (!silent) {
            formError.value = "Please select start datetime and end datetime first.";
        }
        return;
    }
    const start = parseLocalInput(bookingObject.start_datetime);
    const end = parseLocalInput(bookingObject.end_datetime);

    if (!start || !end || end.getTime() <= start.getTime()) {
        formError.value = "Invalid datetime range";
        return;
    }

    if (end.getTime() <= start.getTime()) {
        if (!silent) {
            formError.value = "End datetime must be after start datetime.";
        }
        return;
    }

    checkingAvailability.value = true;

    try {
        const res = await apiGetAvailableRooms({
            start_datetime: toMysqlDatetime(bookingObject.start_datetime),
            end_datetime: toMysqlDatetime(bookingObject.end_datetime),
            ignore_id: bookingObject.id || null,
        });

        availableRooms.value = res.data?.data ?? [];

        const preferredId = preferredRoomId ?? currentSelectedRoomId;

        if (availableRooms.value.length) {
            availabilityMessage.value = `There are ${availableRooms.value.length} available rooms. Please select a room.`;

            if (preferredId) {
                const matched = availableRooms.value.find(
                    (r) => Number(r.id) === Number(preferredId)
                );
                if (matched) {
                    bookingObject.room_id = String(matched.id);
                } else if (currentSelectedRoomId) {
                    bookingObject.room_id = String(currentSelectedRoomId);
                }
            }
        } else {
            availabilityMessage.value = "No available rooms for the selected date and time.";
            bookingObject.room_id = currentSelectedRoomId || "";
        }
    } catch (e) {
        bookingObject.room_id = currentSelectedRoomId || "";

        if (!silent) {
            formError.value =
                e?.response?.data?.message || e?.message || "Failed to check available rooms.";
        }
    } finally {
        checkingAvailability.value = false;
    }
}

async function saveExtraTime() {
    formError.value = "";

    if (!bookingForm.id) {
        formError.value = "Please select a booking to extend.";
        return;
    }

    if (!bookingForm.start_datetime || !bookingForm.end_datetime) {
        formError.value = "Please select start and end datetime.";
        return;
    }

    const start = new Date(normalizeDt(bookingForm.start_datetime));
    const end = new Date(normalizeDt(bookingForm.end_datetime));

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        formError.value = "Invalid datetime format.";
        return;
    }

    if (start < new Date()) {
        formError.value = "Cannot select past time.";
        return;
    }

    if (end <= start) {
        formError.value = "End datetime must be after start datetime.";
        return;
    }

    // Check room availability first
    checkingAvailability.value = true;
    try {
        const res = await apiGetAvailableRooms({
            start_datetime: toMysqlDatetime(bookingForm.start_datetime),
            end_datetime: toMysqlDatetime(bookingForm.end_datetime),
            ignore_id: bookingForm.id,
        });

        if (!res.data?.data?.length) {
            formError.value = "No available room at this time.";
            return;
        }
    } catch (e) {
        formError.value = "Failed to check room availability.";
        return;
    } finally {
        checkingAvailability.value = false;
    }

    saving.value = true;

    try {
        LoadingModal();

        const payload = {
            end_datetime: toMysqlDatetime(bookingForm.end_datetime), // optional, backend +1h if not set
        };

        const response = await apiAddExtraTime(bookingForm.id, payload);

        hideCreateModal();
        CloseModal();
        refetch();

        MessageModal(
            "success",
            "Success",
            response?.data?.message || "Booking time extended. Admins have been notified."
        );
    } catch (e) {
        CloseModal();
        MessageModal(
            "error",
            "Error",
            e?.response?.data?.message || e?.message || "Failed to extend booking."
        );
    } finally {
        saving.value = false;
    }
}

async function saveBooking() {
    if (!validateBookingForm()) return;

    saving.value = true;
    formError.value = "";

    try {
        const payload = {
            room_id: Number(bookingObject.room_id),
            tart_datetime: toMysqlDatetimeWrapper(bookingForm.start_datetime),
            end_datetime: toMysqlDatetimeWrapper(bookingForm.end_datetime),
            meeting_title: bookingObject.meeting_title || null,
            meeting_chairman: bookingObject.meeting_chairman || null,
            snack_required: bookingObject.snack_required,
            snack_note: bookingObject.snack_required ? bookingObject.snack_note : null,
            technician_required: bookingObject.technician_required,
            technician_note: bookingObject.technician_required
                ? bookingObject.technician_note
                : null,
        };

        console.log("========== SAVE BOOKING DATETIME ==========");
        console.log("Form start Cambodia:", bookingObject.start_datetime);
        console.log("Form end Cambodia:", bookingObject.end_datetime);
        console.log("Payload start:", payload.start_datetime);
        console.log("Payload end:", payload.end_datetime);

        const response = bookingObject.id
            ? await apiUpdateBooking(bookingObject.id, payload)
            : await apiCreateBooking(payload);

        onBookingCreate(response.data?.data ?? response.data);
        hideBookingModal();
        Swal.fire({
            icon: "success",
            title: "Booking saved successfully",
            toast: true,
            position: "top-end",
            timer: 2000,
        });
    } catch (e) {
        formError.value = e?.response?.data?.message || "Failed to save booking";
    } finally {
        saving.value = false;
    }
}

async function requestCancelBooking(id) {
    const result = await Swal.fire({
        icon: "warning",
        title: "Request Cancel",
        input: "textarea",
        inputLabel: "Reason",
        inputPlaceholder: "Enter cancel reason...",
        inputAttributes: {
            "aria-label": "Enter cancel reason",
        },
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Yes, request",
        cancelButtonText: "Close",
        allowOutsideClick: false,
        didOpen: () => {
            const input = Swal.getInput();
            if (input) {
                input.removeAttribute("readonly");
                input.focus();
            }
        },
        inputValidator: (value) => {
            if (!String(value || "").trim()) {
                return "Cancel reason is required";
            }
        },
    });

    if (!result.isConfirmed) return;

    try {
        actionLoading.value = true;
        LoadingModal();
        const response = await apiRequestCancelBooking(id, {
            reason: String(result.value || "").trim(),
        });
        const item = response.data?.data ?? response.data;
        onBookingUpdate(item);
        selectedBooking.value = item;
        CloseModal();
        MessageModal(
            "success",
            "Success",
            response.data?.message || "Cancel request submitted"
        );
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
        input: "textarea",
        inputLabel: "Reason",
        inputPlaceholder: "Enter reject reason...",
        inputAttributes: {
            "aria-label": "Enter reject reason",
        },
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, reject",
        cancelButtonText: "Close",
        allowOutsideClick: false,
        didOpen: () => {
            const input = Swal.getInput();
            if (input) {
                input.removeAttribute("readonly");
                input.focus();
            }
        },
        inputValidator: (value) => {
            if (!String(value || "").trim()) {
                return "Reject reason is required";
            }
        },
    });

    if (!result.isConfirmed) return;

    try {
        actionLoading.value = true;
        LoadingModal();
        const response = await apiRejectBooking(id, {
            reason: String(result.value || "").trim(),
        });
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
        title: "Force Cancel Booking",
        text: "This booking is already approved. Do you want to force cancel it as admin?",
        showCancelButton: true,
        confirmButtonColor: "#f39c12",
        confirmButtonText: "Yes, force cancel",
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
    const exists = bookings.value.some((x) => x.id === b.id);
    if (exists) {
        bookings.value = bookings.value.map((x) => (x.id === b.id ? b : x));
    } else {
        bookings.value = [...bookings.value, b];
    }
}

function onBookingUpdate(b) {
    const exists = bookings.value.some((x) => x.id === b.id);
    if (exists) {
        bookings.value = bookings.value.map((x) => (x.id === b.id ? b : x));
    } else {
        bookings.value = [...bookings.value, b];
    }

    if (selectedBooking.value?.id === b.id) {
        selectedBooking.value = b;
    }
}

watch(
    () => route.query.bookingId,
    async (bookingId) => {
        if (bookingId) {
            await openBookingDetailById(bookingId);
        }
    },
    { immediate: true }
);

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
    { header: "No", cell: ({ row }) => row.index + 1, meta: { width: "50px" } },
    {
        header: "Room Selection",
        accessorFn: (row) => {
            const room = row.room?.name ?? `Room #${row.room_id}`;
            const floor = row.room?.floor ? `Floor ${row.room.floor}` : "";
            const capacity = row.room?.capacity ? `• ${row.room.capacity} Seats` : "";
            return `${room}\n${floor} ${capacity}`;
        },
    },
    {
        header: "User",
        accessorFn: (row) => {
            const name = row.user?.name ?? "-";
            const dept = row.user?.department ? `\n${row.user.department}` : "";
            return `${name}${dept}`;
        },
    },
    {
        header: "Schedule",
        cell: ({ row }) => {
            const start = row.original.start_datetime;
            const end = row.original.end_datetime;

            return `${formatScheduleDate(start)}\n${formatScheduleTime(
                start
            )} - ${formatScheduleTime(end)}`;
        },
        meta: { align: "center" },
    },
    {
        header: "Booked At",
        accessorKey: "created_at",
        cell: ({ getValue }) => fmt(getValue()),
        meta: { align: "center" },
    },
    {
        header: "Updated At",
        accessorKey: "updated_at",
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
        header: "Actions",
        cell: ({ row: { original } }) => [
            h(
                "button",
                {
                    type: "button",
                    class: "btn btn-sm btn-outline-secondary",
                    onClick: () => viewBooking(original.id),
                },
                h("i", { class: "fa fa-eye" })
            ),
        ],
        enableSorting: false,
        meta: { align: "center" },
    },
];
</script>

<style scoped>
.modal .modal-body {
    max-height: calc(100vh - 210px);
    overflow-y: auto;
}

/* Table clean modern look */
.table td,
.table th {
    vertical-align: middle;
    padding: 12px 8px;
    font-size: 0.9rem;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.badge {
    padding: 0.35em 0.6em;
    font-size: 0.8rem;
    border-radius: 0.25rem;
}

.badge-warning {
    background-color: #ffe8a1;
    color: #856404;
}

.badge-success {
    background-color: #d4edda;
    color: #155724;
}

.badge-danger {
    background-color: #f8d7da;
    color: #721c24;
}

.badge-info {
    background-color: #d1ecf1;
    color: #0c5460;
}

.badge-secondary {
    background-color: #e2e3e5;
    color: #6c757d;
}

.badge-primary {
    background-color: #cce5ff;
    color: #004085;
}

.badge-dark {
    background-color: #d6d8d9;
    color: #1b1e21;
}

/* Multi-line cell formatting */
.table td pre {
    margin: 0;
    font-family: inherit;
    white-space: pre-line;
}

/* Action button spacing */
.btn-outline-secondary {
    margin-right: 4px;
}
</style>
