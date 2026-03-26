<template>
    <div class="content-wrapper" style="min-height: 1416px">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Rooms</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <router-link :to="{ name: 'dashboard' }">Home</router-link>
                            </li>
                            <li class="breadcrumb-item active">Rooms</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center" style="gap: 10px">
                            <div class="d-flex align-items-center" style="gap: 10px">
                                <div class="input-group input-group-sm" style="width: 280px">
                                    <input v-model="q" class="form-control" placeholder="Search rooms..."
                                        @keyup.enter="loadRooms(true)" />
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" @click="loadRooms(true)">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <select v-model.number="perPage" class="form-control form-control-sm"
                                    style="width: 120px" @change="loadRooms(true)">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>

                                <button type="button" class="btn btn-sm btn-outline-primary" :disabled="loading"
                                    @click="loadRooms()">
                                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                                    Reload
                                </button>
                            </div>

                            <button v-if="isAdmin" type="button" class="btn btn-sm btn-success" @click="openCreate">
                                <i class="fas fa-plus"></i> New Room
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div v-if="error" class="alert alert-danger">{{ error }}</div>

                        <CustomTable :title="'Rooms'" :data="rooms" :columns="columns" :pageSize="perPage" />
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" ref="roomModal" aria-modal="true" role="dialog">
        <form @submit.prevent="saveRoom">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ roomObject.id ? "Edit Room" : "Create Room" }}</h4>
                        <button type="button" class="close" @click="hideRoomModal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Name</label>
                                <input type="text" class="form-control" v-model="roomObject.name"
                                    :class="{ 'is-invalid': roomErr.name }" />
                                <div class="invalid-feedback">{{ roomErr.name }}</div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Department</label>
                                <select class="form-control" v-model="roomObject.department_id"
                                    :class="{ 'is-invalid': roomErr.department_id }">
                                    <option :value="null">-- Select Department --</option>
                                    <option v-for="department in departments" :key="department.id"
                                        :value="department.id">
                                        {{ department.name }}
                                    </option>
                                </select>
                                <div class="invalid-feedback">{{ roomErr.department_id }}</div>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Location</label>
                                <input type="text" class="form-control" v-model="roomObject.location" />
                            </div>

                            <div class="col-md-4 form-group">
                                <label>Capacity</label>
                                <input type="number" min="1" class="form-control" v-model.number="roomObject.capacity"
                                    :class="{ 'is-invalid': roomErr.capacity }" />
                                <div class="invalid-feedback">{{ roomErr.capacity }}</div>
                            </div>

                            <div class="col-md-8 form-group position-relative">
                                <label>Equipment</label>

                                <div class="chip-select-box" @click="focusEquipmentInput">
                                    <span v-for="(item, index) in roomObject.equipment" :key="`${item}-${index}`"
                                        class="chip-item">
                                        {{ item }}
                                        <button type="button" class="chip-remove" @click.stop="removeEquipment(index)">
                                            ×
                                        </button>
                                    </span>

                                    <input ref="equipmentInputRef" v-model="equipmentKeyword" type="text"
                                        class="chip-input" placeholder="Type and select equipment..."
                                        @focus="showEquipmentDropdown = true" @input="showEquipmentDropdown = true"
                                        @keydown.enter.prevent="addEquipmentFromInput"
                                        @keydown.tab.prevent="addEquipmentFromInput"
                                        @keydown.backspace="handleEquipmentBackspace" />
                                </div>

                                <div v-if="showEquipmentDropdown && filteredEquipmentOptions.length"
                                    class="chip-dropdown">
                                    <button v-for="item in filteredEquipmentOptions" :key="item" type="button"
                                        class="chip-dropdown-item" @click="selectEquipment(item)">
                                        {{ item }}
                                    </button>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    បើមាន equipment ក្នុង list អាចជ្រើសបាន, បើអត់មាន អាចវាយថ្មីហើយចុច Enter
                                </small>
                            </div>

                            <div class="col-md-12 form-group">
                                <label>Description</label>
                                <textarea class="form-control" rows="3" v-model="roomObject.description"></textarea>
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Thumbnail (optional)</label>
                                <input type="file" class="form-control" accept="image/*" @change="onThumbnailChange" />
                            </div>

                            <div class="col-md-6 form-group">
                                <label>Images (optional multiple)</label>
                                <input type="file" class="form-control" accept="image/*" multiple
                                    @change="onImagesChange" />
                            </div>

                            <div v-if="roomObject.id && roomObject.images?.length" class="col-12 mt-2">
                                <label>Existing Images</label>
                                <div class="d-flex flex-wrap" style="gap: 10px">
                                    <div v-for="img in roomObject.images" :key="img.id" class="border rounded p-2"
                                        style="width: 160px">
                                        <div class="text-center">
                                            <img :src="imageUrl(img)"
                                                style="max-width: 100%; max-height: 90px; object-fit: cover" />
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">#{{ img.id }}</small>
                                            <button type="button" class="btn btn-xs btn-outline-danger"
                                                @click.prevent="deleteRoomImage(img)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <small class="text-muted d-block mt-2">
                                    Delete uses: DELETE /rooms/delete-image/{room}/{image}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" @click="hideRoomModal">Close</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            {{ saving ? "Saving..." : "Save" }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <RoomDetailModal ref="roomDetailRef" />
</template>

<script setup>
import { computed, h, nextTick, onMounted, onBeforeUnmount, reactive, ref } from "vue";
import { useStore } from "vuex";
import Swal from "sweetalert2";
import CustomTable from "../includes/tables/CustomTable.vue";
import RoomDetailModal from "./RoomDetailModal.vue";
import { LoadingModal, CloseModal, MessageModal } from "@func/swal";

import {
    apiGetRooms,
    apiReadRoom,
    apiCreateRoom,
    apiUpdateRoom,
    apiDeleteRoom,
    apiDeleteRoomImage,
} from "@func/api/room";

import { apiGetDepartments } from "@func/api/department";

const store = useStore();
const userData = computed(() => store.state.user);
const isAdmin = computed(() => userData.value && userData.value.level === "admin");

const roomModal = ref(null);
const roomDetailRef = ref(null);
const equipmentInputRef = ref(null);

const loading = ref(false);
const saving = ref(false);
const error = ref("");
const formError = ref("");

const q = ref("");
const perPage = ref(10);
const page = ref(1);

const rooms = ref([]);
const meta = ref({});
const departments = ref([]);

const equipmentKeyword = ref("");
const showEquipmentDropdown = ref(false);
const equipmentOptions = ref([]);

const roomObject = reactive({
    id: null,
    name: "",
    department_id: null,
    description: "",
    location: "",
    capacity: 1,
    equipment: [],
    images: [],
    department: null,
    thumbnail_path: null,
});

const roomErr = reactive({
    name: "",
    department_id: "",
    capacity: "",
});

const defaultRoomObject = {
    id: null,
    name: "",
    department_id: null,
    description: "",
    location: "",
    capacity: 1,
    equipment: [],
    images: [],
    department: null,
    thumbnail_path: null,
};

const defaultRoomErr = {
    name: "",
    department_id: "",
    capacity: "",
};

let thumbnailFile = null;
let imagesFiles = [];
let modalHiddenHandler = null;

const filteredEquipmentOptions = computed(() => {
    const keyword = equipmentKeyword.value.trim().toLowerCase();

    return equipmentOptions.value
        .filter(Boolean)
        .filter((item) => {
            return !roomObject.equipment.some(
                (selected) => String(selected).toLowerCase() === String(item).toLowerCase()
            );
        })
        .filter((item) => !keyword || item.toLowerCase().includes(keyword))
        .slice(0, 10);
});

function normalizeEquipmentName(value) {
    return String(value || "").trim().replace(/\s+/g, " ");
}

function resetData() {
    Object.assign(roomObject, JSON.parse(JSON.stringify(defaultRoomObject)));
    Object.assign(roomErr, JSON.parse(JSON.stringify(defaultRoomErr)));
    equipmentKeyword.value = "";
    showEquipmentDropdown.value = false;
    thumbnailFile = null;
    imagesFiles = [];
    formError.value = "";
}

function showRoomModal() {
    if (roomModal.value && window.$) {
        window.$(roomModal.value).modal("show");
    }
}

function hideRoomModal() {
    if (roomModal.value && window.$) {
        window.$(roomModal.value).modal("hide");
    }
}

function openDetail(id) {
    roomDetailRef.value?.open(id);
}

async function loadRooms(resetPage = false) {
    if (resetPage) {
        page.value = 1;
    }

    loading.value = true;
    error.value = "";

    try {
        const res = await apiGetRooms({
            q: q.value,
            per_page: perPage.value,
            page: page.value,
        });

        rooms.value = res.data?.data ?? [];
        meta.value = res.data ?? {};
        loadEquipmentOptionsFromRooms();
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to load rooms";
    } finally {
        loading.value = false;
    }
}

async function loadDepartments() {
    try {
        const res = await apiGetDepartments({ per_page: 500, page: 1 });
        departments.value = res.data?.data ?? res.data ?? [];
    } catch (e) {
        console.error("Failed to load departments", e);
    }
}

function loadEquipmentOptionsFromRooms() {
    const map = new Map();

    (rooms.value ?? []).forEach((room) => {
        (room.equipment ?? []).forEach((item) => {
            const name = typeof item === "string" ? item : item?.name;
            const normalized = normalizeEquipmentName(name);
            if (!normalized) return;

            const key = normalized.toLowerCase();
            if (!map.has(key)) {
                map.set(key, normalized);
            }
        });
    });

    equipmentOptions.value = Array.from(map.values()).sort((a, b) => a.localeCompare(b));
}

function openCreate() {
    resetData();
    showRoomModal();
}

async function openEdit(id) {
    try {
        LoadingModal();

        const res = await apiReadRoom(id);
        const r = res.data?.data ?? res.data;

        Object.assign(roomObject, {
            id: r.id ?? null,
            name: r.name ?? "",
            department_id: r.department_id ?? r.department?.id ?? null,
            description: r.description ?? "",
            location: r.location ?? "",
            capacity: r.capacity ?? 1,
            images: r.images ?? [],
            equipment: Array.isArray(r.equipment)
                ? r.equipment.map((x) => (typeof x === "string" ? x : x.name)).filter(Boolean)
                : [],
            department: r.department ?? null,
            thumbnail_path: r.thumbnail_path ?? null,
        });

        equipmentKeyword.value = "";
        showEquipmentDropdown.value = false;
        showRoomModal();
    } catch (e) {
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Failed to load room");
    } finally {
        CloseModal();
    }
}

function onThumbnailChange(e) {
    thumbnailFile = e.target.files?.[0] ?? null;
}

function onImagesChange(e) {
    imagesFiles = Array.from(e.target.files ?? []);
}

function focusEquipmentInput() {
    nextTick(() => {
        equipmentInputRef.value?.focus();
    });
}

function addEquipment(value) {
    const name = normalizeEquipmentName(value);
    if (!name) return;

    const exists = roomObject.equipment.some(
        (item) => String(item).toLowerCase() === name.toLowerCase()
    );

    if (exists) {
        equipmentKeyword.value = "";
        showEquipmentDropdown.value = false;
        return;
    }

    roomObject.equipment.push(name);

    const optionExists = equipmentOptions.value.some(
        (item) => String(item).toLowerCase() === name.toLowerCase()
    );

    if (!optionExists) {
        equipmentOptions.value.unshift(name);
    }

    equipmentKeyword.value = "";
    showEquipmentDropdown.value = false;
}

function addEquipmentFromInput() {
    if (!equipmentKeyword.value.trim()) return;

    const matched = filteredEquipmentOptions.value.find(
        (item) => item.toLowerCase() === equipmentKeyword.value.trim().toLowerCase()
    );

    addEquipment(matched || equipmentKeyword.value);
}

function selectEquipment(item) {
    addEquipment(item);
    focusEquipmentInput();
}

function removeEquipment(index) {
    roomObject.equipment.splice(index, 1);
}

function handleEquipmentBackspace(e) {
    if (equipmentKeyword.value) return;
    if (!roomObject.equipment.length) return;

    e.preventDefault();
    roomObject.equipment.pop();
}

function buildEquipmentArray() {
    return (roomObject.equipment ?? [])
        .map((item) => normalizeEquipmentName(item))
        .filter(Boolean);
}

function toFormData(isUpdate = false) {
    const fd = new FormData();

    fd.append("name", roomObject.name ?? "");
    fd.append("department_id", roomObject.department_id ?? "");
    fd.append("description", roomObject.description ?? "");
    fd.append("location", roomObject.location ?? "");
    fd.append("capacity", String(roomObject.capacity ?? 1));

    const eq = buildEquipmentArray();
    eq.forEach((name) => fd.append("equipment[]", name));

    if (thumbnailFile) {
        fd.append("thumbnail", thumbnailFile);
    }

    imagesFiles.forEach((f) => fd.append("images[]", f));

    if (isUpdate) {
        fd.append("_method", "POST");
    }

    return fd;
}

async function saveRoom() {
    saving.value = true;
    formError.value = "";
    roomErr.name = "";
    roomErr.department_id = "";
    roomErr.capacity = "";

    try {
        LoadingModal();
        let res;

        if (roomObject.id) {
            const fd = toFormData(true);
            res = await apiUpdateRoom(roomObject.id, fd);
            onRoomUpdate(res.data?.data ?? res.data);
        } else {
            const fd = toFormData(false);
            res = await apiCreateRoom(fd);
            onRoomCreate(res.data?.data ?? res.data);
        }

        hideRoomModal();
        MessageModal("success", "Success", res.data?.message || "Saved");
        await loadRooms();
    } catch (e) {
        if (e?.response?.status === 422) {
            const errors = e.response.data.errors || {};
            roomErr.name = errors.name?.[0] || "";
            roomErr.department_id = errors.department_id?.[0] || "";
            roomErr.capacity = errors.capacity?.[0] || "";
            formError.value = e.response.data.message || "Validation failed";
            return;
        }

        formError.value = e?.response?.data?.message || e?.message || "Save failed";
    } finally {
        CloseModal();
        saving.value = false;
    }
}

async function removeRoom(id) {
    const result = await Swal.fire({
        icon: "warning",
        title: "Delete Room",
        text: "Are you sure you want to delete this room?",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    });

    if (!result.isConfirmed) return;

    try {
        LoadingModal();
        await apiDeleteRoom(id);
        onRoomDelete(id);
        MessageModal("success", "Success", "Deleted");
    } catch (e) {
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Delete failed");
    } finally {
        CloseModal();
    }
}

function onRoomCreate(room) {
    if (!room?.id) return;
    rooms.value.unshift(room);
    loadEquipmentOptionsFromRooms();
}

function onRoomUpdate(room) {
    if (!room?.id) return;
    rooms.value = rooms.value.map((r) => (r.id === room.id ? room : r));
    loadEquipmentOptionsFromRooms();
}

function onRoomDelete(id) {
    rooms.value = rooms.value.filter((r) => r.id !== id);
    loadEquipmentOptionsFromRooms();
}

function storageBase() {
    return window.API_URL.replace(/\/api\/?$/, "");
}

function imageUrl(img) {
    const p = img?.image_path || img?.path;
    if (!p) return "";
    return `${storageBase()}/storage/${p}`;
}

function roomThumb(row) {
    if (row?.thumbnail_path) {
        return `${storageBase()}/storage/${row.thumbnail_path}`;
    }

    const first = row?.images?.[0];
    const p = first?.image_path || first?.path;
    if (p) {
        return `${storageBase()}/storage/${p}`;
    }

    return "";
}

async function deleteRoomImage(img) {
    const ok = await Swal.fire({
        icon: "warning",
        title: "Delete image",
        text: `Delete image #${img.id}?`,
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete",
    });

    if (!ok.isConfirmed) return;

    try {
        LoadingModal();
        await apiDeleteRoomImage(roomObject.id, img.id);
        roomObject.images = roomObject.images.filter((x) => x.id !== img.id);
        MessageModal("success", "Success", "Image deleted");
    } catch (e) {
        MessageModal("error", "Error", e?.response?.data?.message || e?.message || "Delete failed");
    } finally {
        CloseModal();
    }
}

function handleDocumentClick(event) {
    const modalEl = roomModal.value;
    if (!modalEl) return;

    const chipBox = modalEl.querySelector(".chip-select-box");
    const dropdown = modalEl.querySelector(".chip-dropdown");

    if (chipBox?.contains(event.target) || dropdown?.contains(event.target)) {
        return;
    }

    showEquipmentDropdown.value = false;
}

const columns = computed(() => [
    { header: "No", accessorKey: "id", meta: { align: "center" } },
    {
        header: "Photo",
        accessorKey: "photo",
        meta: { align: "center" },
        cell: ({ row: { original } }) => {
            const src = roomThumb(original);
            if (!src) return h("span", { class: "text-muted small" }, "No photo");

            return h("img", {
                src,
                alt: original.name || "room",
                style: "width:70px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #eee;",
            });
        },
        enableSorting: false,
    },
    { header: "Name", accessorKey: "name", meta: { align: "left" } },
    {
        header: "Department",
        accessorFn: (row) => row.department?.name || "-",
        meta: { align: "left" },
    },
    { header: "Location", accessorKey: "location", meta: { align: "left" } },
    { header: "Capacity", accessorKey: "capacity", meta: { align: "center" } },
    {
        header: "Equipment",
        accessorFn: (row) =>
            (row.equipment ?? [])
                .map((x) => (typeof x === "string" ? x : x.name))
                .join(", "),
        meta: { align: "left" },
    },
    {
        accessorKey: "action",
        header: () => [
            "Actions",
            ...(isAdmin.value
                ? [
                    h(
                        "button",
                        {
                            type: "button",
                            class: "btn btn-sm btn-success ml-3",
                            onClick: openCreate,
                        },
                        "Create"
                    ),
                ]
                : []),
        ],
        cell: ({ row: { original } }) => {
            const buttons = [
                h(
                    "button",
                    {
                        type: "button",
                        class: "btn btn-sm btn-outline-info mx-1",
                        onClick: () => openDetail(original.id),
                        title: "View detail",
                    },
                    h("i", { class: "fa fa-eye" })
                ),
            ];

            if (isAdmin.value) {
                buttons.push(
                    h(
                        "button",
                        {
                            type: "button",
                            class: "btn btn-sm btn-outline-secondary mx-1",
                            onClick: () => openEdit(original.id),
                            title: "Edit",
                        },
                        h("i", { class: "fa fa-pen" })
                    ),
                    h(
                        "button",
                        {
                            type: "button",
                            class: "btn btn-sm btn-outline-danger mx-1",
                            onClick: () => removeRoom(original.id),
                            title: "Delete",
                        },
                        h("i", { class: "fa fa-trash" })
                    )
                );
            }

            return buttons;
        },
        enableSorting: false,
        meta: { align: "center" },
    },
]);

onMounted(async () => {
    modalHiddenHandler = () => resetData();

    if (roomModal.value && window.$) {
        window.$(roomModal.value).on("hide.bs.modal", modalHiddenHandler);
    }

    document.addEventListener("click", handleDocumentClick);

    await Promise.all([loadRooms(), loadDepartments()]);
});

onBeforeUnmount(() => {
    if (roomModal.value && modalHiddenHandler && window.$) {
        window.$(roomModal.value).off("hide.bs.modal", modalHiddenHandler);
    }

    document.removeEventListener("click", handleDocumentClick);
});
</script>

<style scoped>
.chip-select-box {
    min-height: 38px;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 4px 6px;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    background: #fff;
    cursor: text;
}

.chip-select-box:focus-within {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

.chip-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #e9f3ff;
    color: #0b5ed7;
    border: 1px solid #b6d4fe;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 13px;
    line-height: 1.2;
}

.chip-remove {
    border: 0;
    background: transparent;
    color: #0b5ed7;
    cursor: pointer;
    font-size: 14px;
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
    top: calc(100% - 2px);
    left: 15px;
    right: 15px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    z-index: 20;
    max-height: 220px;
    overflow-y: auto;
}

.chip-dropdown-item {
    width: 100%;
    border: 0;
    background: #fff;
    text-align: left;
    padding: 9px 12px;
    cursor: pointer;
    font-size: 14px;
}

.chip-dropdown-item:hover {
    background: #f8f9fa;
}
</style>