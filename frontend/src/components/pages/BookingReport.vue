<template>
    <div class="content-wrapper" style="min-height: 1416px">
        <section class="content-header pb-2">
            <div class="container-fluid">
                <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap: 12px;">
                    <div>
                        <h1 class="mb-1 font-weight-bold">Booking Reports</h1>
                        <div class="text-muted">
                            {{ currentPeriodLabel }}
                        </div>
                    </div>

                    <div class="d-flex flex-wrap" style="gap: 8px;">
                        <button class="btn btn-outline-primary" :disabled="loading" @click="loadReport">
                            <i class="fas fa-sync-alt mr-1" :class="{ 'fa-spin': loading }"></i>
                            Refresh
                        </button>

                        <button class="btn btn-success" :disabled="loading || !hasReportData" @click="exportExcel">
                            <i class="fas fa-file-excel mr-1"></i>
                            Excel
                        </button>

                        <button class="btn btn-danger" :disabled="loading || !hasReportData" @click="exportPdf">
                            <i class="fas fa-file-pdf mr-1"></i>
                            PDF
                        </button>
                    </div>
                </div>

                <ol class="breadcrumb float-sm-right mt-2 mb-0">
                    <li class="breadcrumb-item">
                        <router-link :to="{ name: 'dashboard' }">Home</router-link>
                    </li>
                    <li class="breadcrumb-item active">Booking Reports</li>
                </ol>
            </div>
        </section>

        <section class="content pt-2">
            <div class="container-fluid">
                <!-- Filter Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h3 class="card-title mb-0 font-weight-bold">Filters</h3>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-2 col-md-3 form-group">
                                <label class="font-weight-semibold">Report Type</label>
                                <select class="form-control" v-model="filters.type">
                                    <option value="daily">Daily</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-3 form-group" v-if="filters.type === 'daily'">
                                <label class="font-weight-semibold">Date</label>
                                <input type="date" class="form-control" v-model="filters.date" />
                            </div>

                            <div class="col-lg-2 col-md-3 form-group" v-if="filters.type === 'monthly'">
                                <label class="font-weight-semibold">Month</label>
                                <select class="form-control" v-model="filters.month">
                                    <option v-for="m in 12" :key="m" :value="m">
                                        {{ monthName(m) }}
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-2 col-md-3 form-group"
                                v-if="filters.type === 'monthly' || filters.type === 'yearly'">
                                <label class="font-weight-semibold">Year</label>
                                <input type="number" class="form-control" v-model="filters.year" min="2000"
                                    max="2100" />
                            </div>

                            <div class="col-lg-2 col-md-3 form-group">
                                <label class="font-weight-semibold">Status</label>
                                <select class="form-control" v-model="filters.status">
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="cancel_requested">Cancel Requested</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>

                            <div class="col-lg-4 col-md-6 form-group" v-if="isAdmin">
                                <label class="font-weight-semibold">User</label>
                                <select class="form-control" v-model="filters.user_id">
                                    <option value="">All Users</option>
                                    <option v-for="u in users" :key="u.id" :value="String(u.id)">
                                        {{ u.name }} ({{ u.email }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap" style="gap: 10px;">
                            <button class="btn btn-primary px-4" :disabled="loading" @click="loadReport">
                                <i class="fas fa-search mr-1" :class="{ 'fa-spin': loading }"></i>
                                Generate Report
                            </button>

                            <button class="btn btn-light border" :disabled="loading" @click="resetFilters">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="error" class="alert alert-danger mt-3 shadow-sm">
                    {{ error }}
                </div>

                <!-- Meta Strip -->
                <div v-if="report" class="card mt-3 shadow-sm border-0">
                    <div class="card-body py-3">
                        <div class="d-flex flex-wrap align-items-center" style="gap: 10px;">
                            <span class="badge badge-light border px-3 py-2">
                                <strong>Type:</strong> {{ reportLabel }}
                            </span>

                            <span v-if="report.type === 'daily'" class="badge badge-light border px-3 py-2">
                                <strong>Date:</strong> {{ report.date || "-" }}
                            </span>

                            <span v-if="report.type === 'monthly'" class="badge badge-light border px-3 py-2">
                                <strong>Month:</strong> {{ monthName(report.month) }}
                            </span>

                            <span v-if="report.type === 'monthly' || report.type === 'yearly'"
                                class="badge badge-light border px-3 py-2">
                                <strong>Year:</strong> {{ report.year || "-" }}
                            </span>

                            <span class="badge badge-light border px-3 py-2">
                                <strong>Status:</strong> {{ filters.status || "All" }}
                            </span>

                            <span v-if="isAdmin" class="badge badge-light border px-3 py-2">
                                <strong>User:</strong> {{ selectedUserLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="report && !loading && !hasReportData" class="card mt-3 shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-folder-open fa-3x text-muted"></i>
                        </div>
                        <h5 class="mb-2">No bookings found</h5>
                        <div class="text-muted">
                            Try changing date, status, or user filter.
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="row mt-3" v-if="report">
                    <div class="col-xl col-lg-3 col-md-4 col-sm-6 mb-3" v-for="card in statCards" :key="card.key">
                        <div class="card border-0 shadow-sm h-100 report-stat-card">
                            <div class="card-body text-center py-4">
                                <div class="report-stat-icon mb-2">
                                    <i :class="card.icon"></i>
                                </div>
                                <h3 class="mb-1 font-weight-bold">{{ report.stats?.[card.key] ?? 0 }}</h3>
                                <div class="text-muted small">{{ card.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="card mt-2 shadow-sm border-0" v-if="report">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0 font-weight-bold">Summary Chart</h3>
                        <span class="text-muted small">Overview by selected period</span>
                    </div>
                    <div class="card-body">
                        <div v-if="hasSummaryData" style="height: 360px;">
                            <Bar :data="chartData" :options="chartOptions" />
                        </div>
                        <div v-else class="text-center text-muted py-5">
                            No summary data
                        </div>
                    </div>
                </div>

                <!-- Summary Table -->
                <div class="card mt-3 shadow-sm border-0" v-if="report">
                    <div class="card-header bg-white border-bottom">
                        <h3 class="card-title mb-0 font-weight-bold">Summary Details</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-bordered table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 70px;">#</th>
                                    <th>Label</th>
                                    <th style="width: 140px;">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!report.summary?.length">
                                    <td colspan="3" class="text-center text-muted py-3">No summary data</td>
                                </tr>
                                <tr v-for="(item, index) in report.summary || []" :key="`${item.label}-${index}`">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ item.label }}</td>
                                    <td>{{ item.count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Details -->
                <div class="card mt-3 shadow-sm border-0" v-if="report">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px;">
                            <h3 class="card-title mb-0 font-weight-bold">Booking Details</h3>

                            <div style="min-width: 280px;">
                                <input type="text" class="form-control form-control-sm" v-model="detailSearch"
                                    placeholder="Search room, user, title, chairman, status..." />
                            </div>
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
import { computed, h, onMounted, reactive, ref } from "vue";
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

const selectedUserLabel = computed(() => {
    if (!filters.user_id) return "All Users";
    const found = users.value.find((u) => String(u.id) === String(filters.user_id));
    return found ? `${found.name} (${found.email})` : filters.user_id;
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
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December",
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

function resetFilters() {
    filters.type = "daily";
    filters.date = currentDate;
    filters.month = currentMonth;
    filters.year = currentYear;
    filters.user_id = "";
    filters.status = "";
    detailSearch.value = "";
    report.value = null;
    error.value = "";
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
            head: [[
                "No",
                "Room",
                "User",
                "Meeting Title",
                "Chairman",
                "Start",
                "End",
                "Status",
            ]],
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
        const res = await apiGetDetailUsers({ per_page: 500, page: 1 });
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

onMounted(async () => {
    await loadUsers();
    await loadReport();
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
        meta: { align: "center" },
    },
];
</script>

<style scoped>
.report-stat-card {
    transition: all 0.2s ease;
    border-radius: 14px;
}

.report-stat-card:hover {
    transform: translateY(-2px);
}

.report-stat-icon i {
    font-size: 22px;
}
</style>