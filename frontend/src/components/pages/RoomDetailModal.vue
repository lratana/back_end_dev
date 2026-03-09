<template>
    <div class="modal fade" ref="modalEl" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <!-- header -->
                <div class="modal-header border-0">
                    <h4 class="modal-title font-weight-bold">
                        {{ room?.name || "Room" }}
                    </h4>

                    <button type="button" class="close" aria-label="Close" @click="close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <!-- body -->
                <div class="modal-body pt-0">
                    <div v-if="error" class="alert alert-danger">{{ error }}</div>

                    <div v-if="loading" class="text-center py-5">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>

                    <div v-else-if="room" class="row">
                        <!-- LEFT: images carousel -->
                        <div class="col-md-6 mb-3">
                            <div v-if="imageList.length" :id="carouselId" class="carousel slide" data-ride="carousel">
                                <!-- indicators -->
                                <ol class="carousel-indicators">
                                    <li v-for="(img, idx) in imageList" :key="idx" :data-target="'#' + carouselId"
                                        :data-slide-to="idx" :class="{ active: idx === 0 }" />
                                </ol>

                                <!-- slides -->
                                <div class="carousel-inner rounded">
                                    <div v-for="(img, idx) in imageList" :key="img.id || idx" class="carousel-item"
                                        :class="{ active: idx === 0 }">
                                        <img class="d-block w-100" :src="img.url" :alt="room.name"
                                            style="height: 520px; object-fit: cover;" />
                                    </div>
                                </div>

                                <!-- controls -->
                                <a class="carousel-control-prev" :href="'#' + carouselId" role="button"
                                    data-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="sr-only">Previous</span>
                                </a>

                                <a class="carousel-control-next" :href="'#' + carouselId" role="button"
                                    data-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="sr-only">Next</span>
                                </a>
                            </div>

                            <div v-else class="border rounded p-4 text-center text-muted" style="height:520px;">
                                No images
                            </div>
                        </div>

                        <!-- RIGHT: details -->
                        <div class="col-md-6">
                            <h2 class="font-weight-bold mb-3">{{ room.name }}</h2>

                            <div class="mb-3">
                                <div class="font-weight-bold">Description</div>
                                <div class="text-muted" style="white-space: pre-line;">
                                    {{ room.description || "-" }}
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-3 text-center h-100">
                                        <div class="mb-2">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div class="font-weight-bold">Location</div>
                                        <div class="small text-muted mt-1">
                                            {{ room.location || "-" }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-3 text-center h-100">
                                        <div class="mb-2">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div class="font-weight-bold">Capacity</div>
                                        <div class="small text-muted mt-1">
                                            {{ room.capacity ?? "-" }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="border rounded p-3 text-center h-100">
                                        <div class="mb-2">
                                            <i class="fas fa-tools"></i>
                                        </div>
                                        <div class="font-weight-bold">Equipment</div>
                                        <div class="small text-muted mt-1">
                                            {{ equipmentText }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right mt-4">
                                <button class="btn btn-secondary" type="button" @click="close">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-muted text-center py-5">
                        No data
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { apiReadRoom } from "@func/api/room";

/**
 * Usage:
 * const roomDetail = ref(null)
 * <RoomDetailModal ref="roomDetail" />
 * roomDetail.value.open(roomId)
 */

const modalEl = ref(null);
const loading = ref(false);
const error = ref("");
const room = ref(null);

// unique carousel id (avoid conflict if multiple modals exist)
const carouselId = `roomCarousel_${Math.random().toString(16).slice(2)}`;

function storageBase() {
    // window.API_URL = http://localhost:8000/api
    // storage url = http://localhost:8000/storage/...
    return window.API_URL.replace(/\/api\/?$/, "");
}

function imageUrlFrom(img) {
    const p = img?.image_path || img?.path || img?.thumbnail_path;
    if (!p) return "";
    return `${storageBase()}/storage/${p}`;
}

const imageList = computed(() => {
    // prefer images; if none, fallback thumbnail
    const imgs = room.value?.images ?? [];
    const list = imgs
        .map((x) => ({ ...x, url: imageUrlFrom(x) }))
        .filter((x) => !!x.url);

    if (list.length) return list;

    const thumb = room.value?.thumbnail_path;
    if (thumb) {
        return [{ id: "thumb", url: `${storageBase()}/storage/${thumb}` }];
    }
    return [];
});

const equipmentText = computed(() => {
    const eq = room.value?.equipment ?? [];
    if (!eq.length) return "None";
    // eq might be [{name}] or ["TV"] depending on API
    if (typeof eq[0] === "string") return eq.join(", ");
    return eq.map((x) => x.name).filter(Boolean).join(", ") || "None";
});

function show() {
    if (!window.$ || !modalEl.value) return;
    window.$(modalEl.value).modal("show");
}

function close() {
    if (!window.$ || !modalEl.value) return;
    window.$(modalEl.value).modal("hide");
}

/** Public method: open(roomId) */
async function open(roomId) {
    loading.value = true;
    error.value = "";
    room.value = null;

    show();

    try {
        const res = await apiReadRoom(roomId);
        room.value = res.data; // controller returns room object
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to load room detail";
    } finally {
        loading.value = false;

        // reset carousel to first slide after DOM updates (bootstrap)
        setTimeout(() => {
            try {
                if (window.$) window.$(`#${carouselId}`).carousel(0);
            } catch { }
        }, 0);
    }
}

defineExpose({ open, close });
</script>

<style scoped>
/* make modal look like your screenshot (wide, clean) */
.modal-content {
    border-radius: 10px;
}
</style>