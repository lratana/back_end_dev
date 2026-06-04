<template>
    <div class="content-wrapper booking-report-page">
        <!-- Header -->
        <section class="content-header report-hero">
            <div class="container-fluid">
                <div class="report-hero-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 16px;">
                        <div>
                            <div class="report-eyebrow">Booking Analytics</div>
                            <h1 class="report-title">Booking Reports</h1>
                            <p class="report-subtitle mb-0">
                                {{ currentPeriodLabel }}
                            </p>
                        </div>

                        <div class="report-actions">
                            <button class="btn btn-light report-action-btn" :disabled="loading" @click="loadReport">
                                <i class="fas fa-sync-alt mr-1" :class="{ 'fa-spin': loading }"></i>
                                Refresh
                            </button>

                            <button class="btn btn-success report-action-btn" :disabled="loading || !hasReportData"
                                @click="exportExcel">
                                <i class="fas fa-file-excel mr-1"></i>
                                Excel
                            </button>

                            <button class="btn btn-danger report-action-btn" :disabled="loading || !hasReportData"
                                @click="exportPdf">
                                <i class="fas fa-file-pdf mr-1"></i>
                                PDF
                            </button>
                        </div>
                    </div>

                    <div class="report-breadcrumb mt-3">
                        <router-link :to="{ name: 'dashboard' }">Home</router-link>
                        <span>/</span>
                        <strong>Booking Reports</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="content report-content">
            <div class="container-fluid">
                <!-- Filters -->
                <div class="card report-filter-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3"
                            style="gap: 12px;">
                            <div>
                                <h3 class="section-title mb-1">Report Filters</h3>
                                <div class="section-subtitle">
                                    Choose period, status, and user to generate the report.
                                </div>
                            </div>

                            <div class="d-flex flex-wrap" style="gap: 8px;">
                                <button class="btn btn-primary px-4" :disabled="loading" @click="loadReport">
                                    <i class="fas fa-search mr-1" :class="{ 'fa-spin': loading }"></i>
                                    Generate
                                </button>

                                <button class="btn btn-outline-secondary px-4" :disabled="loading"
                                    @click="resetFilters">
                                    Reset
                                </button>
                            </div>
                        </div>

                        <div class="filter-grid">
                            <div class="filter-item">
                                <label>Report Type</label>
                                <select class="form-control" v-model="filters.type">
                                    <option value="daily">Daily</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>

                            <div class="filter-item" v-if="filters.type === 'daily'">
                                <label>Date</label>
                                <input type="date" class="form-control" v-model="filters.date" />
                            </div>

                            <div class="filter-item" v-if="filters.type === 'monthly'">
                                <label>Month</label>
                                <select class="form-control" v-model="filters.month">
                                    <option v-for="m in 12" :key="m" :value="m">
                                        {{ monthName(m) }}
                                    </option>
                                </select>
                            </div>

                            <div class="filter-item" v-if="filters.type === 'monthly' || filters.type === 'yearly'">
                                <label>Year</label>
                                <input type="number" class="form-control" v-model="filters.year" min="2000"
                                    max="2100" />
                            </div>

                            <div class="filter-item">
                                <label>Status</label>
                                <select class="form-control" v-model="filters.status">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="cancel_requested">Cancel Requested</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <div class="filter-item user-filter" v-if="isAdmin">
                                <label>User</label>

                                <div class="chip-select-box" @click="focusUserInput">
                                    <span v-if="selectedUserObject" class="chip-item">
                                        {{ selectedUserObject.name }} ({{ selectedUserObject.email }})
                                        <button type="button" class="chip-remove" @click.stop="clearSelectedUser">
                                            ×
                                        </button>
                                    </span>

                                    <input v-if="!selectedUserObject" ref="userInputRef" v-model="userKeyword"
                                        type="text" class="chip-input" placeholder="Search user..."
                                        @focus="showUserDropdown = true" @input="showUserDropdown = true"
                                        @keydown.esc="showUserDropdown = false" />
                                </div>

                                <div v-if="showUserDropdown && filteredUsers.length && !selectedUserObject"
                                    class="chip-dropdown">
                                    <button v-for="u in filteredUsers" :key="u.id" type="button"
                                        class="chip-dropdown-item" @click="selectUser(u)">
                                        <div class="font-weight-bold">{{ u.name }}</div>
                                        <small class="text-muted">{{ u.email }}</small>
                                    </button>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    វាយ search user រួចជ្រើសរើសពី list
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error -->
                <div v-if="error" class="alert alert-danger report-alert">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    {{ error }}
                </div>

                <!-- Meta -->
                <div v-if="report" class="report-meta-strip">
                    <div class="meta-pill">
                        <span>Type</span>
                        <strong>{{ reportLabel }}</strong>
                    </div>

                    <div v-if="report.type === 'daily'" class="meta-pill">
                        <span>Date</span>
                        <strong>{{ report.date || "-" }}</strong>
                    </div>

                    <div v-if="report.type === 'monthly'" class="meta-pill">
                        <span>Month</span>
                        <strong>{{ monthName(report.month) }}</strong>
                    </div>

                    <div v-if="report.type === 'monthly' || report.type === 'yearly'" class="meta-pill">
                        <span>Year</span>
                        <strong>{{ report.year || "-" }}</strong>
                    </div>

                    <div class="meta-pill">
                        <span>Status</span>
                        <strong>{{ filters.status || "All" }}</strong>
                    </div>

                    <div v-if="isAdmin" class="meta-pill meta-pill-wide">
                        <span>User</span>
                        <strong>{{ selectedUserLabel }}</strong>
                    </div>
                </div>

                <!-- Empty -->
                <div v-if="report && !loading && !hasReportData" class="empty-report-card">
                    <div class="empty-icon">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h5>No bookings found</h5>
                    <p>Try changing date, status, or user filter.</p>
                </div>

                <!-- KPI -->
                <div class="stats-grid" v-if="report">
                    <button v-for="card in statCards" :key="card.key" type="button" class="stat-card"
                        :class="{ active: filters.status === card.key || (card.key === 'total' && !filters.status) }"
                        @click="applyStatusFromCard(card.key)">
                        <div class="stat-icon">
                            <i :class="card.icon"></i>
                        </div>

                        <div class="stat-body">
                            <h3>{{ report.stats?.[card.key] ?? 0 }}</h3>
                            <p>{{ card.label }}</p>
                        </div>
                    </button>
                </div>

                <!-- Chart + Summary -->
                <div class="report-main-grid" v-if="report">
                    <div class="card report-card chart-card">
                        <div class="card-header report-card-header">
                            <div>
                                <h3>Summary Chart</h3>
                                <span>Overview by selected period</span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div v-if="hasSummaryData" class="chart-box">
                                <Bar :data="chartData" :options="chartOptions" />
                            </div>

                            <div v-else class="text-center text-muted py-5">
                                No summary data
                            </div>
                        </div>
                    </div>

                    <div class="card report-card summary-card">
                        <div class="card-header report-card-header">
                            <div>
                                <h3>Summary Details</h3>
                                <span>Total by label</span>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <table class="table table-hover mb-0 report-table">
                                <thead>
                                    <tr>
                                        <th style="width: 70px;">#</th>
                                        <th>Label</th>
                                        <th style="width: 110px;" class="text-right">Count</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-if="!report.summary?.length">
                                        <td colspan="3" class="text-center text-muted py-4">
                                            No summary data
                                        </td>
                                    </tr>

                                    <tr v-for="(item, index) in report.summary || []" :key="`${item.label}-${index}`">
                                        <td>{{ index + 1 }}</td>
                                        <td class="font-weight-semibold">{{ item.label }}</td>
                                        <td class="text-right">
                                            <span class="count-pill">{{ item.count }}</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="card report-card details-card" v-if="report">
                    <div class="card-header report-card-header details-header">
                        <div>
                            <h3>Booking Details</h3>
                            <span>{{ filteredReportData.length }} record(s)</span>
                        </div>

                        <div class="detail-search">
                            <i class="fas fa-search"></i>
                            <input type="text" v-model="detailSearch"
                                placeholder="Search room, user, title, chairman, status..." />
                        </div>
                    </div>

                    <div class="card-body">
                        <div v-if="hasReportData">
                            <CustomTable :title="'Report Bookings'" :data="filteredReportData" :columns="columns"
                                :pageSize="25" />
                        </div>

                        <div v-else class="text-center text-muted py-4">
                            No detailed bookings available.
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, h, nextTick, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { useStore } from "vuex";
import * as XLSX from "xlsx";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import { Bar } from "vue-chartjs";
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from "chart.js";
import CustomTable from "../includes/tables/CustomTable.vue";
import { LoadingModal, CloseModal, MessageModal } from "@func/swal";
import { formatFullDateTime } from "@func/datetime";
import { apiGetDetailUsers } from "@func/api/user";
import {
    apiGetDailyBookingReport,
    apiGetMonthlyBookingReport,
    apiGetYearlyBookingReport,
} from "@func/api/booking-report";

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const store = useStore();

const loading = ref(false);
const error = ref("");
const report = ref(null);
const users = ref([]);
const detailSearch = ref("");

const userInputRef = ref(null);
const userKeyword = ref("");
const showUserDropdown = ref(false);

const currentUser = computed(() => store.state.user || null);
const isAdmin = computed(() => currentUser.value?.level === "admin");

const hasReportData = computed(() => {
    return Array.isArray(report.value?.data) && report.value.data.length > 0;
});

const hasSummaryData = computed(() => {
    return Array.isArray(report.value?.summary) && report.value.summary.length > 0;
});

const reportLabel = computed(() => {
    if (!report.value?.type) return "-";
    if (report.value.type === "daily") return "Daily";
    if (report.value.type === "monthly") return "Monthly";
    if (report.value.type === "yearly") return "Yearly";
    return report.value.type;
});

const currentPeriodLabel = computed(() => {
    if (filters.type === "daily") {
        return `Daily Report - ${filters.date || "-"}`;
    }

    if (filters.type === "monthly") {
        return `Monthly Report - ${monthName(filters.month)} ${filters.year}`;
    }

    return `Yearly Report - ${filters.year}`;
});

const selectedUserObject = computed(() => {
    if (!filters.user_id) return null;
    return users.value.find((u) => String(u.id) === String(filters.user_id)) || null;
});

const selectedUserLabel = computed(() => {
    if (!filters.user_id) return "All Users";

    const found = users.value.find((u) => String(u.id) === String(filters.user_id));

    return found ? `${found.name} (${found.email})` : filters.user_id;
});

const filteredUsers = computed(() => {
    const keyword = String(userKeyword.value || "").trim().toLowerCase();
    const rows = users.value || [];

    if (!keyword) return rows.slice(0, 20);

    return rows
        .filter((u) => {
            const text = `${u.name || ""} ${u.email || ""}`.toLowerCase();
            return text.includes(keyword);
        })
        .slice(0, 20);
});

const today = new Date();
const currentMonth = today.getMonth() + 1;
const currentYear = today.getFullYear();
const yyyy = today.getFullYear();
const mm = String(today.getMonth() + 1).padStart(2, "0");
const dd = String(today.getDate()).padStart(2, "0");
const currentDate = `${yyyy}-${mm}-${dd}`;

const filters = reactive({
    type: "daily",
    date: currentDate,
    month: currentMonth,
    year: currentYear,
    user_id: "",
    status: "",
});

const statCards = [
    { key: "total", label: "Total", icon: "fas fa-calendar-alt text-info" },
    { key: "pending", label: "Pending", icon: "fas fa-clock text-warning" },
    { key: "approved", label: "Approved", icon: "fas fa-check text-success" },
    { key: "rejected", label: "Rejected", icon: "fas fa-times text-danger" },
    { key: "cancel_requested", label: "Cancel Requested", icon: "fas fa-paper-plane text-primary" },
    { key: "cancelled", label: "Cancelled", icon: "fas fa-ban text-secondary" },
    { key: "completed", label: "Completed", icon: "fas fa-flag-checkered text-dark" },
];

const chartData = computed(() => {
    const summary = report.value?.summary || [];

    return {
        labels: summary.map((item) => item.label),
        datasets: [
            {
                label: "Bookings",
                data: summary.map((item) => item.count),
                borderWidth: 1,
                backgroundColor: "#0d6efd",
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0,
            },
        },
    },
};

const filteredReportData = computed(() => {
    const keyword = String(detailSearch.value || "").trim().toLowerCase();
    const rows = report.value?.data || [];

    if (!keyword) return rows;

    return rows.filter((item) => {
        const text = [
            item.room?.name,
            item.user?.name,
            item.meeting_title,
            item.meeting_chairman,
            item.status,
        ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase();

        return text.includes(keyword);
    });
});

function fmt(dt) {
    if (!dt) return "-";
    return formatFullDateTime(dt);
}

function monthName(month) {
    const months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];

    const index = Number(month) - 1;

    return months[index] || month || "-";
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

function focusUserInput() {
    nextTick(() => {
        userInputRef.value?.focus();
    });
}

function selectUser(user) {
    filters.user_id = String(user.id);
    userKeyword.value = "";
    showUserDropdown.value = false;
}

function clearSelectedUser() {
    filters.user_id = "";
    userKeyword.value = "";
    showUserDropdown.value = false;
    focusUserInput();
}

function resetFilters() {
    filters.type = "daily";
    filters.date = currentDate;
    filters.month = currentMonth;
    filters.year = currentYear;
    filters.user_id = "";
    filters.status = "";
    userKeyword.value = "";
    showUserDropdown.value = false;
    detailSearch.value = "";
    report.value = null;
    error.value = "";
}

function applyStatusFromCard(key) {
    filters.status = key === "total" ? "" : key;
    loadReport();
}

function buildExtraFilters() {
    const extra = {};

    if (filters.status && String(filters.status).trim() !== "") {
        extra.status = filters.status;
    }

    if (isAdmin.value && filters.user_id && String(filters.user_id).trim() !== "") {
        extra.user_id = Number(filters.user_id);
    }

    return extra;
}

function getExportRows() {
    return (filteredReportData.value || []).map((item, index) => ({
        No: index + 1,
        Room: item.room?.name ?? `Room #${item.room_id}`,
        User: item.user?.name ?? "-",
        "Meeting Title": item.meeting_title ?? "-",
        Chairman: item.meeting_chairman ?? "-",
        Start: fmt(item.start_datetime),
        End: fmt(item.end_datetime),
        Status: item.status ?? "-",
    }));
}

function getReportFileName(ext = "xlsx") {
    const type = report.value?.type || filters.type || "report";

    if (type === "daily") {
        return `booking-report-daily-${report.value?.date || filters.date}.${ext}`;
    }

    if (type === "monthly") {
        return `booking-report-monthly-${report.value?.year || filters.year}-${String(report.value?.month || filters.month).padStart(2, "0")}.${ext}`;
    }

    return `booking-report-yearly-${report.value?.year || filters.year}.${ext}`;
}

function exportExcel() {
    try {
        const rows = getExportRows();

        if (!rows.length) {
            return MessageModal("warning", "No Data", "No bookings available to export.");
        }

        const worksheet = XLSX.utils.json_to_sheet(rows);
        const workbook = XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(workbook, worksheet, "Booking Reports");
        XLSX.writeFile(workbook, getReportFileName("xlsx"));
    } catch (e) {
        MessageModal("error", "Export Error", e?.message || "Failed to export Excel.");
    }
}

function exportPdf() {
    try {
        const rows = getExportRows();

        if (!rows.length) {
            return MessageModal("warning", "No Data", "No bookings available to export.");
        }

        const doc = new jsPDF("landscape");
        const title = `Booking Report - ${reportLabel.value}`;

        doc.setFontSize(14);
        doc.text(title, 14, 15);

        let subtitle = "";

        if (report.value?.type === "daily") {
            subtitle = `Date: ${report.value?.date || filters.date}`;
        } else if (report.value?.type === "monthly") {
            subtitle = `Month: ${monthName(report.value?.month || filters.month)} / Year: ${report.value?.year || filters.year}`;
        } else {
            subtitle = `Year: ${report.value?.year || filters.year}`;
        }

        doc.setFontSize(10);
        doc.text(subtitle, 14, 22);

        autoTable(doc, {
            startY: 28,
            head: [
                [
                    "No",
                    "Room",
                    "User",
                    "Meeting Title",
                    "Chairman",
                    "Start",
                    "End",
                    "Status",
                ],
            ],
            body: rows.map((row) => [
                row.No,
                row.Room,
                row.User,
                row["Meeting Title"],
                row.Chairman,
                row.Start,
                row.End,
                row.Status,
            ]),
            styles: {
                fontSize: 8,
                cellPadding: 2,
            },
            headStyles: {
                fillColor: [52, 58, 64],
            },
        });

        doc.save(getReportFileName("pdf"));
    } catch (e) {
        MessageModal("error", "Export Error", e?.message || "Failed to export PDF.");
    }
}

async function loadUsers() {
    if (!isAdmin.value) return;

    try {
        const res = await apiGetDetailUsers({
            per_page: 500,
            page: 1,
        });

        users.value = res.data?.data ?? [];
    } catch (e) {
        console.log("loadUsers error", e);
    }
}

async function loadReport() {
    loading.value = true;
    error.value = "";

    try {
        LoadingModal();

        const extra = buildExtraFilters();
        let response = null;

        if (filters.type === "daily") {
            response = await apiGetDailyBookingReport(filters.date || null, extra);
        } else if (filters.type === "monthly") {
            response = await apiGetMonthlyBookingReport(
                filters.month ? Number(filters.month) : null,
                filters.year ? Number(filters.year) : null,
                extra
            );
        } else {
            response = await apiGetYearlyBookingReport(
                filters.year ? Number(filters.year) : null,
                extra
            );
        }

        report.value = response.data;
        CloseModal();
    } catch (e) {
        CloseModal();
        error.value = e?.response?.data?.message || e?.message || "Failed to load report";
        MessageModal("error", "Error", error.value);
    } finally {
        loading.value = false;
    }
}

function handleDocumentClick(event) {
    const chipBox = document.querySelector(".chip-select-box");
    const dropdown = document.querySelector(".chip-dropdown");

    if (chipBox?.contains(event.target) || dropdown?.contains(event.target)) {
        return;
    }

    showUserDropdown.value = false;
}

onMounted(async () => {
    document.addEventListener("click", handleDocumentClick);
    await loadUsers();
    await loadReport();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleDocumentClick);
});

const columns = [
    {
        header: "No",
        cell: ({ row }) => row.index + 1,
    },
    {
        header: "Room",
        accessorFn: (row) => row.room?.name ?? `Room #${row.room_id}`,
    },
    {
        header: "User",
        accessorFn: (row) => row.user?.name ?? "-",
    },
    {
        header: "Meeting Title",
        accessorFn: (row) => row.meeting_title ?? "-",
    },
    {
        header: "Chairman",
        accessorFn: (row) => row.meeting_chairman ?? "-",
    },
    {
        header: "Start",
        accessorKey: "start_datetime",
        cell: ({ getValue }) => fmt(getValue()),
    },
    {
        header: "End",
        accessorKey: "end_datetime",
        cell: ({ getValue }) => fmt(getValue()),
    },
    {
        header: "Status",
        accessorKey: "status",
        cell: ({ getValue }) =>
            h("span", { class: ["badge", statusBadge(getValue())] }, getValue()),
        meta: {
            align: "center",
        },
    },
];
</script>

<style scoped>
.booking-report-page {
    background: #f4f7fb;
    min-height: 100vh;
}

.report-hero {
    padding: 18px 0 10px;
}

.report-hero-card {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 45%, #084298 100%);
    color: #fff;
    border-radius: 22px;
    padding: 28px;
    box-shadow: 0 16px 40px rgba(13, 110, 253, 0.22);
}

.report-eyebrow {
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-size: 12px;
    font-weight: 800;
    opacity: 0.85;
    margin-bottom: 6px;
}

.report-title {
    margin: 0;
    font-size: 34px;
    font-weight: 900;
}

.report-subtitle {
    opacity: 0.88;
    font-size: 15px;
}

.report-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.report-action-btn {
    border-radius: 999px;
    padding: 9px 16px;
    font-weight: 800;
    border: 0;
}

.report-breadcrumb {
    display: flex;
    gap: 8px;
    align-items: center;
    font-size: 13px;
    opacity: 0.9;
}

.report-breadcrumb a {
    color: #fff;
    font-weight: 700;
}

.report-content {
    padding-top: 8px;
    padding-bottom: 30px;
}

.report-filter-card,
.report-card,
.empty-report-card {
    border: 0;
    border-radius: 18px;
    box-shadow: 0 10px 26px rgba(16, 24, 40, 0.06);
}

.section-title {
    font-size: 20px;
    font-weight: 900;
    color: #101828;
}

.section-subtitle {
    font-size: 13px;
    color: #667085;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 16px;
}

.filter-item {
    grid-column: span 2;
    position: relative;
}

.filter-item.user-filter {
    grid-column: span 4;
}

.filter-item label {
    display: block;
    font-size: 13px;
    font-weight: 800;
    color: #344054;
    margin-bottom: 6px;
}

.filter-item .form-control {
    border-radius: 12px;
    min-height: 42px;
    border-color: #d0d5dd;
}

.report-alert {
    border: 0;
    border-radius: 14px;
    margin-top: 16px;
    box-shadow: 0 8px 20px rgba(220, 53, 69, 0.08);
}

.report-meta-strip {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 18px 0;
}

.meta-pill {
    background: #fff;
    border: 1px solid #e4e7ec;
    border-radius: 999px;
    padding: 9px 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(16, 24, 40, 0.04);
}

.meta-pill span {
    font-size: 12px;
    color: #667085;
    font-weight: 800;
    text-transform: uppercase;
}

.meta-pill strong {
    font-size: 13px;
    color: #101828;
}

.meta-pill-wide {
    max-width: 420px;
}

.empty-report-card {
    background: #fff;
    padding: 46px 20px;
    text-align: center;
    margin-top: 16px;
}

.empty-icon {
    width: 72px;
    height: 72px;
    margin: 0 auto 14px;
    border-radius: 22px;
    background: #f2f4f7;
    display: grid;
    place-items: center;
    color: #98a2b3;
    font-size: 30px;
}

.empty-report-card h5 {
    font-weight: 900;
    color: #101828;
}

.empty-report-card p {
    color: #667085;
    margin: 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}

.stat-card {
    background: #fff;
    border-radius: 18px;
    padding: 18px 14px;
    display: flex;
    gap: 12px;
    align-items: center;
    text-align: left;
    box-shadow: 0 10px 26px rgba(16, 24, 40, 0.06);
    border: 1px solid #eef2f6;
    transition: 0.2s ease;
    cursor: pointer;
    width: 100%;
}

.stat-card:hover {
    transform: translateY(-2px);
    border-color: #0d6efd;
}

.stat-card.active {
    border-color: #0d6efd;
    box-shadow: 0 12px 28px rgba(13, 110, 253, 0.16);
}

.stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #f8fafc;
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.stat-icon i {
    font-size: 18px;
}

.stat-body h3 {
    margin: 0;
    font-size: 25px;
    font-weight: 900;
    color: #101828;
}

.stat-body p {
    margin: 0;
    color: #667085;
    font-size: 12px;
    font-weight: 800;
}

.report-main-grid {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(340px, 1fr);
    gap: 18px;
    margin-bottom: 18px;
}

.report-card {
    overflow: hidden;
}

.report-card-header {
    background: #fff;
    border-bottom: 1px solid #eef2f6;
    padding: 18px 20px;
}

.report-card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 900;
    color: #101828;
}

.report-card-header span {
    color: #667085;
    font-size: 13px;
    font-weight: 600;
}

.chart-box {
    height: 360px;
}

.report-table thead {
    background: #f8fafc;
}

.report-table th {
    border-top: 0;
    color: #344054;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.report-table td {
    vertical-align: middle;
}

.count-pill {
    display: inline-flex;
    justify-content: center;
    min-width: 38px;
    padding: 4px 10px;
    border-radius: 999px;
    background: #e9f3ff;
    color: #0b5ed7;
    font-weight: 900;
}

.details-card {
    margin-bottom: 30px;
}

.details-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
}

.detail-search {
    position: relative;
    width: min(100%, 420px);
}

.detail-search i {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: #98a2b3;
}

.detail-search input {
    width: 100%;
    border: 1px solid #d0d5dd;
    border-radius: 999px;
    padding: 10px 14px 10px 38px;
    outline: none;
    transition: 0.2s ease;
}

.detail-search input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}

.chip-select-box {
    min-height: 42px;
    border: 1px solid #d0d5dd;
    border-radius: 12px;
    padding: 5px 8px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    background: #fff;
    cursor: text;
}

.chip-select-box:focus-within {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}

.chip-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e9f3ff;
    color: #0b5ed7;
    border: 1px solid #b6d4fe;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 13px;
    line-height: 1.2;
}

.chip-remove {
    border: 0;
    background: transparent;
    color: #0b5ed7;
    cursor: pointer;
    font-size: 15px;
    line-height: 1;
    padding: 0;
}

.chip-input {
    border: 0;
    outline: none;
    flex: 1;
    min-width: 160px;
    padding: 4px 2px;
    font-size: 14px;
    background: transparent;
}

.chip-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    box-shadow: 0 12px 28px rgba(16, 24, 40, 0.12);
    z-index: 40;
    max-height: 240px;
    overflow-y: auto;
}

.chip-dropdown-item {
    width: 100%;
    border: 0;
    background: #fff;
    text-align: left;
    padding: 10px 14px;
    cursor: pointer;
    font-size: 14px;
}

.chip-dropdown-item:hover {
    background: #f8fafc;
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .report-main-grid {
        grid-template-columns: 1fr;
    }

    .filter-item {
        grid-column: span 3;
    }

    .filter-item.user-filter {
        grid-column: span 6;
    }
}

@media (max-width: 768px) {
    .report-hero-card {
        padding: 22px;
        border-radius: 18px;
    }

    .report-title {
        font-size: 28px;
    }

    .filter-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .filter-item,
    .filter-item.user-filter {
        grid-column: span 2;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .details-header {
        align-items: stretch;
    }

    .detail-search {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .report-actions {
        width: 100%;
    }

    .report-action-btn {
        flex: 1;
    }
}
</style>