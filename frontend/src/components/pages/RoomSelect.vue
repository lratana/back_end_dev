<template>
    <div>
        <label v-if="label" class="mb-1">{{ label }}</label>

        <!-- Search -->
        <div class="input-group input-group-sm mb-2">
            <input class="form-control" :placeholder="searchPlaceholder" v-model="q" @input="debouncedLoad" />
            <div class="input-group-append">
                <button type="button" class="btn btn-default" @click="load">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <!-- Select -->
        <select class="form-control" :class="{ 'is-invalid': !!error }" :value="modelValue ?? ''" @change="onChange">
            <option value="" disabled>Select room...</option>
            <option v-for="r in rooms" :key="r.id" :value="r.id">
                {{ formatRoom(r) }}
            </option>
        </select>

        <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>

        <small v-if="hint" class="text-muted d-block mt-1">{{ hint }}</small>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { apiGetRooms } from "@func/api/room";

const props = defineProps({
    modelValue: { type: [Number, String, null], default: null },
    label: { type: String, default: "Room" },
    error: { type: String, default: "" },
    hint: { type: String, default: "Search by name then choose room" },
    searchPlaceholder: { type: String, default: "Search room name..." },
    perPage: { type: Number, default: 50 },
});

const emit = defineEmits(["update:modelValue", "selected"]);

const q = ref("");
const rooms = ref([]);
const loading = ref(false);

function storageBase() {
    // window.API_URL = http://localhost:8000/api
    return (window.API_URL || "").replace(/\/api\/?$/, "");
}

function formatRoom(r) {
    const name = r?.name ?? `Room #${r?.id ?? "-"}`;
    const loc = r?.location ? ` • ${r.location}` : "";
    const cap = r?.capacity != null ? ` • Cap:${r.capacity}` : "";
    return `${name}${loc}${cap}`;
}

async function load() {
    loading.value = true;
    try {
        const res = await apiGetRooms({
            q: q.value,
            per_page: props.perPage,
            page: 1,
        });

        rooms.value = res.data?.data ?? res.data ?? [];

        // if current selected room not in list (when editing), try keep it
        const current = props.modelValue != null ? Number(props.modelValue) : null;
        if (current && !rooms.value.some((x) => Number(x.id) === current)) {
            // try fetch without q to include it
            const res2 = await apiGetRooms({ q: "", per_page: props.perPage, page: 1 });
            const list2 = res2.data?.data ?? res2.data ?? [];
            rooms.value = list2;
        }
    } catch (e) {
        console.log("RoomSelect load error:", e?.message || e);
    } finally {
        loading.value = false;
    }
}

// small debounce (no extra library)
let t = null;
function debouncedLoad() {
    clearTimeout(t);
    t = setTimeout(load, 250);
}

function onChange(e) {
    const v = e.target.value ? Number(e.target.value) : null;
    emit("update:modelValue", v);

    const selectedRoom = rooms.value.find((x) => Number(x.id) === Number(v));
    emit("selected", selectedRoom || null);
}

onMounted(load);
</script>