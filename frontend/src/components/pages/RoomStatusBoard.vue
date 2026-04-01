<template>
    <div class="content-wrapper tv-wrapper">
        <section class="content-header p-0">
            <div class="container-fluid p-0">
                <div class="tv-dashboard">
                    <div class="tv-header">
                        <div class="header-left">
                            <div class="header-kicker">
                                <i class="fas fa-display"></i>
                                <span>Live Monitor</span>
                            </div>

                            <h1>Meeting Room Status Board</h1>
                            <p>{{ nowText }}</p>
                        </div>

                        <button class="btn btn-light btn-sm refresh-btn" @click="loadBoard" :disabled="loading">
                            <i class="fas fa-sync-alt mr-1" :class="{ 'fa-spin': loading }"></i>
                            Refresh
                        </button>
                    </div>

                    <div v-if="error" class="alert alert-danger mx-3 mt-3">
                        {{ error }}
                    </div>

                    <div class="tv-grid">
                        <div v-for="room in rooms" :key="room.room_id" class="room-card"
                            :class="statusCardClass(room.status)">
                            <div class="room-card-glow"></div>

                            <div class="room-top">
                                <div class="room-title-wrap">
                                    <div class="room-name-line">
                                        <i class="fas fa-door-open room-icon"></i>
                                        <h3>{{ room.room_name }}</h3>
                                    </div>

                                    <p>
                                        {{ room.department || "No Department" }}
                                        <span v-if="room.location">• {{ room.location }}</span>
                                    </p>
                                </div>

                                <div class="status-badge" :class="statusBadgeClass(room.status)">
                                    <i :class="statusIconClass(room.status)" class="mr-1"></i>
                                    {{ room.status }}
                                </div>
                            </div>

                            <div class="room-body">
                                <template v-if="room.status === 'occupied'">
                                    <div class="label-text">
                                        <i class="fas fa-circle mr-1"></i>
                                        Now Running
                                    </div>

                                    <div class="main-title">
                                        <i class="fas fa-people-group title-icon"></i>
                                        <span>{{ room.meeting_title || "Meeting in progress" }}</span>
                                    </div>

                                    <div class="info-block">
                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="sub-label">Chairman</div>
                                                <div class="sub-value">{{ room.meeting_chairman || "-" }}</div>
                                            </div>
                                        </div>

                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="sub-label">Time</div>
                                                <div class="sub-value">
                                                    {{ fmt(room.start_datetime) }} - {{ fmt(room.end_datetime) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="countdown-box danger">
                                        <div class="countdown-head">
                                            <i class="fas fa-hourglass-end"></i>
                                            <span>Ends In</span>
                                        </div>
                                        <div class="countdown-text">
                                            {{ formatCountdown(room.live_countdown_seconds) }}
                                        </div>
                                    </div>
                                </template>

                                <template v-else-if="room.status === 'upcoming'">
                                    <div class="label-text">
                                        <i class="fas fa-bell mr-1"></i>
                                        Coming Soon
                                    </div>

                                    <div class="main-title">
                                        <i class="fas fa-calendar-check title-icon"></i>
                                        <span>{{ room.meeting_title || "Upcoming meeting" }}</span>
                                    </div>

                                    <div class="info-block">
                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="sub-label">Chairman</div>
                                                <div class="sub-value">{{ room.meeting_chairman || "-" }}</div>
                                            </div>
                                        </div>

                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="sub-label">Starts</div>
                                                <div class="sub-value">{{ fmt(room.start_datetime) }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="countdown-box warning">
                                        <div class="countdown-head">
                                            <i class="fas fa-hourglass-start"></i>
                                            <span>Starts In</span>
                                        </div>
                                        <div class="countdown-text">
                                            {{ formatCountdown(room.live_countdown_seconds) }}
                                        </div>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="label-text">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Ready
                                    </div>

                                    <div class="main-title available-title">
                                        <i class="fas fa-circle-check title-icon"></i>
                                        <span>Available Now</span>
                                    </div>

                                    <div class="info-block">
                                        <div class="info-row">
                                            <div class="info-icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="info-content">
                                                <div class="sub-label">Capacity</div>
                                                <div class="sub-value">{{ room.capacity || "-" }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="countdown-box success">
                                        <div class="countdown-head">
                                            <i class="fas fa-bolt"></i>
                                            <span>Status</span>
                                        </div>
                                        <div class="countdown-text small-text">
                                            Ready for booking
                                        </div>
                                    </div>
                                </template>
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

const nowText = computed(() =>
    now.value.toLocaleString([], {
        year: "numeric",
        month: "short",
        day: "numeric",
        weekday: "long",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false,
    })
);

function normalizeDt(dt) {
    return dt ? String(dt).replace(" ", "T") : null;
}

function fmt(dt) {
    if (!dt) return "-";

    const d = new Date(normalizeDt(dt));
    if (Number.isNaN(d.getTime())) return String(dt);

    const nowDate = new Date();

    const startOfToday = new Date(
        nowDate.getFullYear(),
        nowDate.getMonth(),
        nowDate.getDate()
    );
    const startOfTomorrow = new Date(
        nowDate.getFullYear(),
        nowDate.getMonth(),
        nowDate.getDate() + 1
    );
    const startOfDayAfterTomorrow = new Date(
        nowDate.getFullYear(),
        nowDate.getMonth(),
        nowDate.getDate() + 2
    );

    const timeText = d.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });

    if (d >= startOfToday && d < startOfTomorrow) {
        return `Today • ${timeText}`;
    }

    if (d >= startOfTomorrow && d < startOfDayAfterTomorrow) {
        return `Tomorrow • ${timeText}`;
    }

    const dateText = d.toLocaleDateString([], {
        year: "numeric",
        month: "short",
        day: "numeric",
    });

    return `${dateText} • ${timeText}`;
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

function statusIconClass(status) {
    if (status === "occupied") return "fas fa-play-circle";
    if (status === "upcoming") return "fas fa-clock";
    return "fas fa-check-circle";
}

function formatCountdown(seconds) {
    if (seconds == null || seconds < 0) return "--:--:--";

    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hrs >= 24) {
        const days = Math.floor(hrs / 24);
        const remainHours = hrs % 24;
        return `${String(days).padStart(2, "0")}d ${String(remainHours).padStart(2, "0")}h ${String(mins).padStart(2, "0")}m`;
    }

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
        rooms.value = Array.isArray(res.data)
            ? res.data.map((room) => ({
                ...room,
                live_countdown_seconds: room.countdown_seconds,
            }))
            : [];

        updateLiveCountdown();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to load room status board";
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await loadBoard();

    refreshTimer = setInterval(loadBoard, 30000);
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
.tv-wrapper {
    background: #020617;
}

.tv-dashboard {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 30%),
        radial-gradient(circle at top right, rgba(16, 185, 129, 0.12), transparent 28%),
        linear-gradient(135deg, #0f172a, #111827 45%, #1e293b);
    color: #fff;
    padding: 24px;
}

.tv-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 22px;
    padding: 4px 8px 0;
}

.header-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.08);
    color: #cbd5e1;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
}

.header-left h1 {
    margin: 0;
    font-size: 40px;
    font-weight: 800;
    letter-spacing: 0.3px;
    line-height: 1.1;
}

.header-left p {
    margin: 8px 0 0;
    color: #cbd5e1;
    font-size: 17px;
    font-weight: 500;
}

.refresh-btn {
    min-width: 110px;
    font-weight: 700;
    border-radius: 10px;
}

.tv-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 22px;
}

.room-card {
    position: relative;
    overflow: hidden;
    border-radius: 24px;
    padding: 24px;
    min-height: 320px;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
    border: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(8px);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.room-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 22px 48px rgba(0, 0, 0, 0.34);
}

.room-card-glow {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(135deg,
            rgba(255, 255, 255, 0.12),
            rgba(255, 255, 255, 0.02) 40%,
            transparent 70%);
}

.card-available {
    background: linear-gradient(135deg, #065f46, #059669 60%, #10b981);
}

.card-occupied {
    background: linear-gradient(135deg, #991b1b, #dc2626 60%, #ef4444);
}

.card-upcoming {
    background: linear-gradient(135deg, #92400e, #d97706 60%, #f59e0b);
}

.room-top {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 22px;
}

.room-name-line {
    display: flex;
    align-items: center;
    gap: 10px;
}

.room-icon {
    font-size: 22px;
    opacity: 0.9;
}

.room-title-wrap h3 {
    margin: 0;
    font-size: 30px;
    font-weight: 800;
    line-height: 1.1;
}

.room-title-wrap p {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, 0.88);
    font-size: 15px;
    font-weight: 500;
    line-height: 1.4;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 10px 14px;
    border-radius: 999px;
    text-transform: uppercase;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.8px;
    white-space: nowrap;
    background: rgba(255, 255, 255, 0.18);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.14);
    color: #fff;
}

.room-body {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 14px;
    height: calc(100% - 88px);
}

.label-text {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 1px;
    text-transform: uppercase;
    opacity: 0.95;
}

.main-title {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 30px;
    font-weight: 800;
    line-height: 1.25;
    letter-spacing: 0.2px;
    word-break: break-word;
}

.title-icon {
    margin-top: 4px;
    font-size: 22px;
    opacity: 0.95;
}

.available-title {
    font-size: 34px;
}

.info-block {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 2px;
}

.info-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.10);
}

.info-icon {
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.14);
    font-size: 15px;
    flex-shrink: 0;
}

.info-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.sub-label {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: rgba(255, 255, 255, 0.78);
}

.sub-value {
    font-size: 18px;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.96);
    line-height: 1.35;
    word-break: break-word;
}

.countdown-box {
    margin-top: auto;
    border-radius: 18px;
    padding: 16px 18px;
    background: rgba(255, 255, 255, 0.14);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
}

.countdown-box.danger {
    background: rgba(127, 29, 29, 0.30);
}

.countdown-box.warning {
    background: rgba(120, 53, 15, 0.28);
}

.countdown-box.success {
    background: rgba(6, 78, 59, 0.28);
}

.countdown-head {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.82);
}

.countdown-text {
    font-size: 36px;
    font-weight: 900;
    letter-spacing: 1.2px;
    line-height: 1.1;
}

.countdown-text.small-text {
    font-size: 24px;
    letter-spacing: 0.4px;
}

@media (max-width: 1200px) {
    .header-left h1 {
        font-size: 34px;
    }

    .main-title,
    .available-title {
        font-size: 28px;
    }

    .countdown-text {
        font-size: 30px;
    }
}

@media (max-width: 768px) {
    .tv-dashboard {
        padding: 16px;
    }

    .tv-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .tv-grid {
        grid-template-columns: 1fr;
    }

    .room-card {
        min-height: 290px;
        padding: 20px;
    }

    .room-title-wrap h3 {
        font-size: 26px;
    }

    .main-title,
    .available-title {
        font-size: 24px;
    }

    .countdown-text {
        font-size: 26px;
    }

    .sub-value {
        font-size: 16px;
    }
}
</style>