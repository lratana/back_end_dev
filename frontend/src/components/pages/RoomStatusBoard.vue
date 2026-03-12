<template>
    <div class="content-wrapper" style="min-height: 1416px">
        <section class="content-header">
            <div class="container-fluid">
                <div class="tv-dashboard">
                    <div class="tv-header">
                        <div>
                            <h1>Meeting Room Status Board</h1>
                            <p>{{ nowText }}</p>
                        </div>

                        <button class="btn btn-light btn-sm" @click="loadBoard" :disabled="loading">
                            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                            Refresh
                        </button>
                    </div>

                    <div v-if="error" class="alert alert-danger mx-3 mt-3">
                        {{ error }}
                    </div>

                    <div class="tv-grid">
                        <div v-for="room in rooms" :key="room.room_id" class="room-card"
                            :class="statusCardClass(room.status)">
                            <div class="room-top">
                                <div>
                                    <h3>{{ room.room_name }}</h3>
                                    <p>
                                        {{ room.department || "No Department" }}
                                        <span v-if="room.location">• {{ room.location }}</span>
                                    </p>
                                </div>

                                <div class="status-badge" :class="statusBadgeClass(room.status)">
                                    {{ room.status }}
                                </div>
                            </div>

                            <div class="room-body">
                                <template v-if="room.status === 'occupied'">
                                    <div class="main-title">
                                        {{ room.meeting_title || "Meeting in progress" }}
                                    </div>
                                    <div class="sub-line">
                                        Chairman: {{ room.meeting_chairman || "-" }}
                                    </div>
                                    <div class="sub-line">
                                        Time: {{ fmt(room.start_datetime) }} - {{ fmt(room.end_datetime) }}
                                    </div>
                                    <div class="countdown danger">
                                        Ends in {{ formatCountdown(room.live_countdown_seconds) }}
                                    </div>
                                </template>

                                <template v-else-if="room.status === 'upcoming'">
                                    <div class="main-title">
                                        {{ room.meeting_title || "Upcoming meeting" }}
                                    </div>
                                    <div class="sub-line">
                                        Chairman: {{ room.meeting_chairman || "-" }}
                                    </div>
                                    <div class="sub-line">
                                        Starts: {{ fmt(room.start_datetime) }}
                                    </div>
                                    <div class="countdown warning">
                                        Starts in {{ formatCountdown(room.live_countdown_seconds) }}
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="main-title">Available Now</div>
                                    <div class="sub-line">Capacity: {{ room.capacity || "-" }}</div>
                                    <div class="countdown success">Ready for booking</div>
                                </template>

                                <div class="snack-box" v-if="room.snack_required">
                                    <strong>Snack:</strong> Yes
                                    <span v-if="room.snack_note"> — {{ room.snack_note }}</span>
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
import { onMounted, onBeforeUnmount, ref, computed } from "vue";
import { apiGetRoomStatusBoard } from "@func/api/room";

const rooms = ref([]);
const loading = ref(false);
const error = ref("");
const now = ref(new Date());

let refreshTimer = null;
let clockTimer = null;

const nowText = computed(() => now.value.toLocaleString());

function normalizeDt(dt) {
    return dt ? String(dt).replace(" ", "T") : null;
}

function fmt(dt) {
    if (!dt) return "-";
    const d = new Date(normalizeDt(dt));
    return Number.isNaN(d.getTime()) ? String(dt) : d.toLocaleTimeString();
}

function statusCardClass(status) {
    if (status === "occupied") return "card-occupied";
    if (status === "upcoming") return "card-upcoming";
    return "card-available";
}

function statusBadgeClass(status) {
    if (status === "occupied") return "badge-occupied";
    if (status === "upcoming") return "badge-upcoming";
    return "badge-available";
}

function formatCountdown(seconds) {
    if (seconds == null || seconds < 0) return "--:--:--";
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return [
        String(hrs).padStart(2, "0"),
        String(mins).padStart(2, "0"),
        String(secs).padStart(2, "0"),
    ].join(":");
}

function updateLiveCountdown() {
    const nowSec = Math.floor(Date.now() / 1000);

    rooms.value = rooms.value.map((room) => {
        let target = null;

        if (room.status === "occupied" && room.end_datetime) {
            target = Math.floor(new Date(normalizeDt(room.end_datetime)).getTime() / 1000);
        } else if (room.status === "upcoming" && room.start_datetime) {
            target = Math.floor(new Date(normalizeDt(room.start_datetime)).getTime() / 1000);
        }

        return {
            ...room,
            live_countdown_seconds: target ? Math.max(0, target - nowSec) : null,
        };
    });
}

async function loadBoard() {
    loading.value = true;
    error.value = "";

    try {
        const res = await apiGetRoomStatusBoard();
        rooms.value = (res.data || []).map((room) => ({
            ...room,
            live_countdown_seconds: room.countdown_seconds,
        }));
        updateLiveCountdown();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to load room status board";
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await loadBoard();

    refreshTimer = setInterval(loadBoard, 30000); // auto refresh 30s
    clockTimer = setInterval(() => {
        now.value = new Date();
        updateLiveCountdown();
    }, 1000);
});

onBeforeUnmount(() => {
    if (refreshTimer) clearInterval(refreshTimer);
    if (clockTimer) clearInterval(clockTimer);
});
</script>

<style scoped>
.tv-dashboard {
    min-height: 100vh;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #fff;
    padding: 20px;
}

.tv-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 0 8px;
}

.tv-header h1 {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
}

.tv-header p {
    margin: 4px 0 0;
    color: #cbd5e1;
}

.tv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 18px;
}

.room-card {
    border-radius: 20px;
    padding: 20px;
    min-height: 240px;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
    transition: transform 0.2s ease;
}

.room-card:hover {
    transform: translateY(-2px);
}

.card-available {
    background: linear-gradient(135deg, #065f46, #10b981);
}

.card-occupied {
    background: linear-gradient(135deg, #991b1b, #ef4444);
}

.card-upcoming {
    background: linear-gradient(135deg, #92400e, #f59e0b);
}

.room-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 20px;
}

.room-top h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.room-top p {
    margin: 6px 0 0;
    color: rgba(255, 255, 255, 0.85);
}

.status-badge {
    padding: 8px 12px;
    border-radius: 999px;
    text-transform: uppercase;
    font-size: 12px;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.18);
}

.room-body {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.main-title {
    font-size: 24px;
    font-weight: 700;
    line-height: 1.3;
}

.sub-line {
    font-size: 16px;
    color: rgba(255, 255, 255, 0.92);
}

.countdown {
    margin-top: 8px;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: 1px;
}

.snack-box {
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.14);
    font-size: 14px;
}
</style>