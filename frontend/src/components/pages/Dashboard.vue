<template>
  <div class="content-wrapper">

    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">

          <div class="col-sm-6">
            <h1>Dashboard</h1>
          </div>

          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item">
                <router-link :to="{ name: 'dashboard' }">Home</router-link>
              </li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>

        </div>
      </div>
    </section>


    <section class="content">
      <div class="container-fluid">


        <!-- ================= Summary ================= -->

        <div class="row">

          <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
              <div class="inner">
                <h3>{{ stats.today }}</h3>
                <p>Today</p>
              </div>
              <div class="icon">
                <i class="fas fa-calendar-day"></i>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
              <div class="inner">
                <h3>{{ stats.upcoming }}</h3>
                <p>Upcoming</p>
              </div>
              <div class="icon">
                <i class="fas fa-calendar-check"></i>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
              <div class="inner">
                <h3>{{ stats.week }}</h3>
                <p>This Week</p>
              </div>
              <div class="icon">
                <i class="fas fa-calendar-week"></i>
              </div>
            </div>
          </div>

          <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
              <div class="inner">
                <h3>{{ stats.cancelled }}</h3>
                <p>Cancelled</p>
              </div>
              <div class="icon">
                <i class="fas fa-times-circle"></i>
              </div>
            </div>
          </div>

        </div>


        <!-- ================= Filters ================= -->

        <div class="card">

          <div class="card-header">

            <div class="d-flex flex-wrap align-items-center" style="gap:10px;">

              <input class="form-control form-control-sm" style="width:200px" placeholder="Search room..."
                v-model="searchRoom" />

              <select class="form-control form-control-sm" style="width:160px" v-model="filterStatus">
                <option value="">All status</option>
                <option value="scheduled">scheduled</option>
                <option value="completed">completed</option>
                <option value="cancelled">cancelled</option>
              </select>


              <button class="btn btn-sm btn-primary" @click="fetchDashboard" :disabled="loading">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                Refresh
              </button>


              <router-link class="btn btn-sm btn-success ml-auto" :to="{ name: 'bookings' }">
                New Booking
              </router-link>


              <router-link class="btn btn-sm btn-outline-secondary" :to="{ name: 'calendar' }">
                Calendar
              </router-link>

            </div>

          </div>

        </div>



        <!-- ================= Alerts ================= -->

        <div v-if="error" class="alert alert-danger">
          {{ error }}
        </div>



        <div class="row">


          <!-- ================= Upcoming ================= -->

          <div class="col-lg-6">

            <div class="card card-success">

              <div class="card-header">
                <h3 class="card-title">
                  Upcoming Bookings
                </h3>
              </div>


              <div class="card-body p-0">

                <div v-if="loading" class="p-3 text-center">
                  <i class="fas fa-spinner fa-spin"></i>
                  Loading...
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

                          <td>
                            <strong>{{ b.room?.name ?? 'N/A' }}</strong>
                          </td>

                          <td>
                            {{ formatDateTime(b.start_datetime) }}
                          </td>

                          <td>
                            {{ formatDateTime(b.end_datetime) }}
                          </td>

                          <td>
                            <span class="badge badge-success">
                              {{ b.status }}
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



          <!-- ================= Recent ================= -->

          <div class="col-lg-6">

            <div class="card card-info">

              <div class="card-header">
                <h3 class="card-title">
                  Recent Bookings
                </h3>
              </div>


              <div class="card-body p-0">

                <div v-if="loading" class="p-3 text-center">
                  <i class="fas fa-spinner fa-spin"></i>
                  Loading...
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

                          <td>
                            <strong>{{ b.room?.name ?? 'N/A' }}</strong>
                          </td>

                          <td>
                            {{ formatDateTime(b.start_datetime) }}
                          </td>

                          <td>
                            <span class="badge" :class="statusBadgeClass(b.status)">
                              {{ b.status }}
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


      </div>
    </section>
  </div>
</template>



<script setup>

import { ref, computed, onMounted } from "vue"

const loading = ref(false)
const error = ref("")

const upcoming = ref([])
const recent = ref([])

const stats = ref({
  today: 0,
  upcoming: 0,
  week: 0,
  cancelled: 0
})


const searchRoom = ref("")
const filterStatus = ref("")



function normalizeDt(dt) {
  if (!dt) return null
  return String(dt).replace(" ", "T")
}


function formatDateTime(dt) {

  if (!dt) return "-"

  const d = new Date(normalizeDt(dt))

  if (Number.isNaN(d.getTime()))
    return String(dt)

  return d.toLocaleString()

}



function statusBadgeClass(status) {

  switch (status) {

    case "scheduled":
      return "badge-success"

    case "cancelled":
      return "badge-danger"

    case "completed":
      return "badge-secondary"

    default:
      return "badge-primary"

  }

}



const filteredUpcoming = computed(() => {

  let list = [...upcoming.value]

  if (filterStatus.value)
    list = list.filter(b => b.status === filterStatus.value)

  if (searchRoom.value)
    list = list.filter(b =>
      (b.room?.name ?? "")
        .toLowerCase()
        .includes(searchRoom.value.toLowerCase())
    )

  return list

})



const filteredRecent = computed(() => {

  let list = [...recent.value]

  if (filterStatus.value)
    list = list.filter(b => b.status === filterStatus.value)

  if (searchRoom.value)
    list = list.filter(b =>
      (b.room?.name ?? "")
        .toLowerCase()
        .includes(searchRoom.value.toLowerCase())
    )

  return list

})



async function fetchDashboard() {

  loading.value = true
  error.value = ""

  try {

    const res = await window.axios.get(`${window.API_URL}/dashboard`)

    upcoming.value = res.data?.upcoming ?? []
    recent.value = res.data?.recent ?? []

    stats.value.today = res.data?.today ?? 0
    stats.value.upcoming = res.data?.upcoming_count ?? upcoming.value.length
    stats.value.week = res.data?.week ?? 0
    stats.value.cancelled = res.data?.cancelled ?? 0
    console.log("Error or not" + res.data);
  }
  catch (e) {

    error.value =
      e?.response?.data?.message ||
      e?.message ||
      "Failed to load dashboard"

  }
  finally {

    loading.value = false

  }

}



onMounted(() => {

  fetchDashboard()

})

</script>



<style scoped>
.table td,
.table th {
  vertical-align: middle;
}
</style>