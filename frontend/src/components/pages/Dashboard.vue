<template>
  <div class="content-wrapper">
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>{{ $t('dashboard_main') }}</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">{{ $t('home') }}</router-link>
              </li>
              <li class="breadcrumb-item active">{{ $t('dashboard_main') }}</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
              <div class="inner">
                <h3>{{ stats.today }}</h3>
                <p>Today</p>
              </div>
              <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
              <div class="inner">
                <h3>{{ stats.upcoming }}</h3>
                <p>Upcoming This Week</p>
              </div>
              <div class="icon"><i class="fas fa-calendar-check"></i></div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h3>{{ stats.week }}</h3>
                <p>Completed This Week</p>
              </div>
              <div class="icon"><i class="fas fa-calendar-week"></i></div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
              <div class="inner">
                <h3>{{ stats.cancelled }}</h3>
                <p>Cancelled This Week</p>
              </div>
              <div class="icon"><i class="fas fa-times-circle"></i></div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
              <input class="form-control form-control-sm" style="width:200px" placeholder="Search room"
                v-model="searchRoom" />

              <select class="form-control form-control-sm" style="width:180px" v-model="filterStatus">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancel_requested">Cancel Requested</option>
                <option value="cancelled">Cancelled</option>
                <option value="completed">Completed</option>
              </select>

              <button class="btn btn-sm btn-primary" @click="fetchDashboard" :disabled="loading">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                Refresh
              </button>

              <router-link class="btn btn-sm btn-success ml-auto" :to="{ name: 'bookings' }">
                New Booking
              </router-link>

              <router-link class="btn btn-sm btn-outline-secondary" :to="{ name: 'bookingscalendar' }">
                Weekly Calendar
              </router-link>
            </div>
          </div>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div class="row">
          <div class="col-lg-6">
            <div class="card card-success">
              <div class="card-header">
                <h3 class="card-title">Upcoming Bookings</h3>
              </div>

              <div class="card-body p-0">
                <div v-if="loading" class="p-3 text-center">
                  <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>

                <div v-else>
                  <div v-if="filteredUpcoming.length === 0" class="p-3 text-muted">
                    No upcoming bookings
                  </div>

                  <div v-else class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                      <thead>
                        <tr>
                          <th>Room</th>
                          <th>Start</th>
                          <th>End</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="b in filteredUpcoming" :key="b.id">
                          <td><strong>{{ b.room?.name ?? 'N/A' }}</strong></td>
                          <td>{{ formatDateTime(b.start_datetime) }}</td>
                          <td>{{ formatDateTime(b.end_datetime) }}</td>
                          <td>
                            <span class="badge" :class="statusBadgeClass(b.status)">
                              {{ statusLabel(b.status) }}
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Recent Bookings</h3>
              </div>

              <div class="card-body p-0">
                <div v-if="loading" class="p-3 text-center">
                  <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>

                <div v-else>
                  <div v-if="filteredRecent.length === 0" class="p-3 text-muted">
                    No recent bookings
                  </div>

                  <div v-else class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                      <thead>
                        <tr>
                          <th>Room</th>
                          <th>Start</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="b in filteredRecent" :key="b.id">
                          <td><strong>{{ b.room?.name ?? 'N/A' }}</strong></td>
                          <td>{{ formatDateTime(b.start_datetime) }}</td>
                          <td>
                            <span class="badge" :class="statusBadgeClass(b.status)">
                              {{ statusLabel(b.status) }}
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Optional weekly summary -->
        <div class="row">
          <div class="col-lg-12">
            <div class="card card-outline card-secondary">
              <div class="card-header">
                <h3 class="card-title">Weekly Booking Summary</h3>
              </div>
              <div class="card-body">
                <div class="row text-center">
                  <div class="col-md-2 col-6 mb-3">
                    <div class="summary-box">
                      <div class="summary-number text-warning">{{ stats.pending }}</div>
                      <div class="summary-label">Pending</div>
                    </div>
                  </div>

                  <div class="col-md-2 col-6 mb-3">
                    <div class="summary-box">
                      <div class="summary-number text-success">{{ stats.approved }}</div>
                      <div class="summary-label">Approved</div>
                    </div>
                  </div>

                  <div class="col-md-2 col-6 mb-3">
                    <div class="summary-box">
                      <div class="summary-number text-danger">{{ stats.rejected }}</div>
                      <div class="summary-label">Rejected</div>
                    </div>
                  </div>

                  <div class="col-md-2 col-6 mb-3">
                    <div class="summary-box">
                      <div class="summary-number text-info">{{ stats.cancel_requested }}</div>
                      <div class="summary-label">Cancel Requested</div>
                    </div>
                  </div>

                  <div class="col-md-2 col-6 mb-3">
                    <div class="summary-box">
                      <div class="summary-number text-secondary">{{ stats.cancelled }}</div>
                      <div class="summary-label">Cancelled</div>
                    </div>
                  </div>

                  <div class="col-md-2 col-6 mb-3">
                    <div class="summary-box">
                      <div class="summary-number text-primary">{{ stats.completed }}</div>
                      <div class="summary-label">Completed</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- /Optional weekly summary -->

      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { formatFullDateTime } from "@func/datetime";

const loading = ref(false);
const error = ref("");
const upcoming = ref([]);
const recent = ref([]);

const stats = ref({
  today: 0,
  upcoming: 0,
  week: 0,
  cancelled: 0,
  pending: 0,
  approved: 0,
  rejected: 0,
  cancel_requested: 0,
  completed: 0
});

const searchRoom = ref("");
const filterStatus = ref("");

function formatDateTime(dt) {
  if (!dt) return "-";
  return formatFullDateTime(dt);
}

function statusBadgeClass(status) {
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

function statusLabel(status) {
  switch (status) {
    case "pending":
      return "Pending";
    case "approved":
      return "Approved";
    case "rejected":
      return "Rejected";
    case "cancel_requested":
      return "Cancel Requested";
    case "cancelled":
      return "Cancelled";
    case "completed":
      return "Completed";
    default:
      return status;
  }
}

const filteredUpcoming = computed(() => {
  let list = [...upcoming.value];

  if (filterStatus.value) {
    list = list.filter((b) => b.status === filterStatus.value);
  }

  if (searchRoom.value) {
    list = list.filter((b) =>
      (b.room?.name ?? "").toLowerCase().includes(searchRoom.value.toLowerCase())
    );
  }

  return list;
});

const filteredRecent = computed(() => {
  let list = [...recent.value];

  if (filterStatus.value) {
    list = list.filter((b) => b.status === filterStatus.value);
  }

  if (searchRoom.value) {
    list = list.filter((b) =>
      (b.room?.name ?? "").toLowerCase().includes(searchRoom.value.toLowerCase())
    );
  }

  return list;
});

async function fetchDashboard() {
  loading.value = true;
  error.value = "";

  try {
    const res = await window.axios.get(`${window.API_URL}/dashboard`);

    upcoming.value = res.data?.upcoming ?? [];
    recent.value = res.data?.recent ?? [];

    stats.value.today = res.data?.today ?? 0;
    stats.value.upcoming = res.data?.upcoming_count ?? 0;
    stats.value.week = res.data?.week ?? 0;
    stats.value.cancelled = res.data?.cancelled ?? 0;
    stats.value.pending = res.data?.pending ?? 0;
    stats.value.approved = res.data?.approved ?? 0;
    stats.value.rejected = res.data?.rejected ?? 0;
    stats.value.cancel_requested = res.data?.cancel_requested ?? 0;
    stats.value.completed = res.data?.completed ?? 0;
  } catch (e) {
    error.value =
      e?.response?.data?.message ||
      e?.message ||
      "Failed to load weekly dashboard";
  } finally {
    loading.value = false;
  }
}

onMounted(fetchDashboard);
</script>

<style scoped>
.table td,
.table th {
  vertical-align: middle;
}

.small-box,
.card,
.summary-box {
  border-radius: 10px;
}

.summary-box {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  padding: 14px 10px;
  height: 100%;
}

.summary-number {
  font-size: 24px;
  font-weight: 700;
  line-height: 1.2;
}

.summary-label {
  font-size: 13px;
  color: #6c757d;
  margin-top: 4px;
}
</style>