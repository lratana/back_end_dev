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
              <li class="breadcrumb-item"><router-link :to="{ name: 'dashboard' }">{{ $t('home') }}</router-link></li>
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
                <p>{{ $t('dashboard.today') }}</p>
              </div>
              <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
              <div class="inner">
                <h3>{{ stats.upcoming }}</h3>
                <p>{{ $t('dashboard.upcoming') }}</p>
              </div>
              <div class="icon"><i class="fas fa-calendar-check"></i></div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h3>{{ stats.week }}</h3>
                <p>{{ $t('dashboard.this_week') }}</p>
              </div>
              <div class="icon"><i class="fas fa-calendar-week"></i></div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
              <div class="inner">
                <h3>{{ stats.cancelled }}</h3>
                <p>{{ $t('status_cancelled') }}</p>
              </div>
              <div class="icon"><i class="fas fa-times-circle"></i></div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <div class="d-flex flex-wrap align-items-center" style="gap:10px;">
              <input class="form-control form-control-sm" style="width:200px" :placeholder="$t('dashboard.search_room')"
                v-model="searchRoom" />

              <select class="form-control form-control-sm" style="width:180px" v-model="filterStatus">
                <option value="">{{ $t('dashboard.all_status') }}</option>
                <option value="pending">{{ $t('pending') }}</option>
                <option value="approved">{{ $t('approved') }}</option>
                <option value="rejected">{{ $t('rejected') }}</option>
                <option value="cancel_requested">{{ $t('cancel_requested') }}</option>
                <option value="cancelled">{{ $t('cancelled') }}</option>
                <option value="completed">{{ $t('completed') }}</option>
              </select>

              <button class="btn btn-sm btn-primary" @click="fetchDashboard" :disabled="loading">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i> {{ $t('dashboard.refresh') }}
              </button>

              <router-link class="btn btn-sm btn-success ml-auto" :to="{ name: 'bookings' }">{{
                $t('dashboard.new_booking') }}</router-link>
              <router-link class="btn btn-sm btn-outline-secondary" :to="{ name: 'bookingscalendar' }">{{
                $t('dashboard.calendar') }}</router-link>
            </div>
          </div>
        </div>

        <div v-if="error" class="alert alert-danger">{{ error }}</div>

        <div class="row">
          <div class="col-lg-6">
            <div class="card card-success">
              <div class="card-header">
                <h3 class="card-title">{{ $t('dashboard.upcoming_bookings') }}</h3>
              </div>
              <div class="card-body p-0">
                <div v-if="loading" class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> {{ $t('loading') }}
                </div>
                <div v-else>
                  <div v-if="filteredUpcoming.length === 0" class="p-3 text-muted">{{
                    $t('dashboard.no_upcoming_bookings') }}</div>
                  <div v-else class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                      <thead>
                        <tr>
                          <th>{{ $t('room') }}</th>
                          <th>{{ $t('dashboard.start') }}</th>
                          <th>{{ $t('dashboard.end') }}</th>
                          <th>{{ $t('status') }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="b in filteredUpcoming" :key="b.id">
                          <td><strong>{{ b.room?.name ?? 'N/A' }}</strong></td>
                          <td>{{ formatDateTime(b.start_datetime) }}</td>
                          <td>{{ formatDateTime(b.end_datetime) }}</td>
                          <td><span class="badge" :class="statusBadgeClass(b.status)">{{ b.status }}</span></td>
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
                <h3 class="card-title">{{ $t('dashboard.recent_bookings') }}</h3>
              </div>
              <div class="card-body p-0">
                <div v-if="loading" class="p-3 text-center"><i class="fas fa-spinner fa-spin"></i> {{ $t('loading') }}
                </div>
                <div v-else>
                  <div v-if="filteredRecent.length === 0" class="p-3 text-muted">{{ $t('dashboard.no_recent_bookings')
                    }}</div>
                  <div v-else class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                      <thead>
                        <tr>
                          <th>{{ $t('room') }}</th>
                          <th>{{ $t('dashboard.start') }}</th>
                          <th>{{ $t('status') }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="b in filteredRecent" :key="b.id">
                          <td><strong>{{ b.room?.name ?? 'N/A' }}</strong></td>
                          <td>{{ formatDateTime(b.start_datetime) }}</td>
                          <td><span class="badge" :class="statusBadgeClass(b.status)">{{ b.status }}</span></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
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
  cancelled: 0
});

const searchRoom = ref("");
const filterStatus = ref("");

function normalizeDt(dt) {
  return dt ? String(dt).replace(" ", "T") : null;
}

function formatDateTime(dt) {
  if (!dt) return "-";
  return formatFullDateTime(dt);
}

function statusBadgeClass(status) {
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

const filteredUpcoming = computed(() => {
  let list = [...upcoming.value];
  if (filterStatus.value) list = list.filter((b) => b.status === filterStatus.value);
  if (searchRoom.value) list = list.filter((b) => (b.room?.name ?? "").toLowerCase().includes(searchRoom.value.toLowerCase()));
  return list;
});

const filteredRecent = computed(() => {
  let list = [...recent.value];
  if (filterStatus.value) list = list.filter((b) => b.status === filterStatus.value);
  if (searchRoom.value) list = list.filter((b) => (b.room?.name ?? "").toLowerCase().includes(searchRoom.value.toLowerCase()));
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
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || $t('dashboard.failed_to_load_dashboard');
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
</style>