<template>
    <div class="content-wrapper" style="min-height: 1416px">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Departments</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <router-link :to="{ name: 'dashboard' }">Home</router-link>
                            </li>
                            <li class="breadcrumb-item active">Departments</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center" style="gap:10px;">
                            <div class="d-flex align-items-center" style="gap:10px;">
                                <div class="input-group input-group-sm" style="width:300px;">
                                    <input v-model="q" class="form-control" placeholder="Search departments..." />
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-default" @click="loadDepartments"
                                            :disabled="loading">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-sm btn-outline-primary" @click="loadDepartments"
                                    :disabled="loading">
                                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                                    Reload
                                </button>
                            </div>

                            <button type="button" class="btn btn-sm btn-success" @click="openCreate">
                                <i class="fas fa-plus"></i> New Department
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div v-if="error" class="alert alert-danger">{{ error }}</div>

                        <CustomTable :title="'Departments'" :data="departments" :columns="columns" :pageSize="25" />
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal -->
    <div class="modal fade" ref="depModal" aria-modal="true" role="dialog">
        <form @submit.prevent="saveDepartment">
            <div class="modal-dialog modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ dep.id ? "Edit Department" : "Create Department" }}</h4>
                        <button type="button" class="close" @click="hideModal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div v-if="formError" class="alert alert-danger">{{ formError }}</div>

                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control" v-model="dep.name"
                                :class="{ 'is-invalid': depErr.name }" />
                            <div class="invalid-feedback">{{ depErr.name }}</div>
                        </div>

                        <div class="form-group">
                            <label>Code (optional)</label>
                            <input type="text" class="form-control" v-model="dep.code"
                                :class="{ 'is-invalid': depErr.code }" />
                            <div class="invalid-feedback">{{ depErr.code }}</div>
                            <small class="text-muted">Max 50 chars, unique</small>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" @click="hideModal">Close</button>
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            {{ saving ? "Saving..." : "Save" }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed, h, onMounted, reactive, ref } from "vue";
import CustomTable from "../includes/tables/CustomTable.vue";
import { LoadingModal, CloseModal, MessageModal } from "@func/swal";
import {
    apiGetDepartments,
    apiCreateDepartment,
    apiReadDepartment,
    apiUpdateDepartment,
    apiDeleteDepartment,
} from "@func/api/department";

const depModal = ref(null);

const q = ref("");
const loading = ref(false);
const saving = ref(false);
const error = ref("");
const formError = ref("");

const departments = ref([]);

const dep = reactive({
    id: null,
    name: "",
    code: "",
});

const depErr = reactive({
    name: "",
    code: "",
});

const defaultDep = JSON.parse(JSON.stringify(dep));
const defaultDepErr = JSON.parse(JSON.stringify(depErr));

function resetForm() {
    Object.assign(dep, JSON.parse(JSON.stringify(defaultDep)));
    Object.assign(depErr, JSON.parse(JSON.stringify(defaultDepErr)));
    formError.value = "";
}

function showModal() {
    $(depModal.value).modal("show");
}
function hideModal() {
    $(depModal.value).modal("hide");
}

async function loadDepartments() {
    loading.value = true;
    error.value = "";
    try {
        const res = await apiGetDepartments({ q: q.value || undefined });
        departments.value = res.data ?? [];
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || "Failed to load departments";
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    resetForm();
    showModal();
}

async function openEdit(id) {
    try {
        LoadingModal();
        const res = await apiReadDepartment(id);
        Object.assign(dep, res.data);
        showModal();
        CloseModal();
    } catch (e) {
        CloseModal();
        MessageModal("error", "Error", e?.response?.data?.message || e.message);
    }
}

async function saveDepartment() {
    saving.value = true;
    formError.value = "";
    Object.assign(depErr, JSON.parse(JSON.stringify(defaultDepErr)));

    try {
        LoadingModal();

        let res;
        if (dep.id) {
            res = await apiUpdateDepartment(dep.id, { name: dep.name, code: dep.code || null });
            onUpdate(res.data);
        } else {
            res = await apiCreateDepartment({ name: dep.name, code: dep.code || null });
            onCreate(res.data);
        }

        hideModal();
        CloseModal();
        MessageModal("success", "Success", "Saved");
    } catch (e) {
        CloseModal();

        if (e?.response?.status === 422) {
            const errors = e.response.data.errors || {};
            depErr.name = errors.name?.[0] || "";
            depErr.code = errors.code?.[0] || "";

            // sometimes backend returns message only
            if (e.response.data.message && !Object.keys(errors).length) {
                formError.value = e.response.data.message;
            }
            return;
        }

        formError.value = e?.response?.data?.message || e?.message || "Save failed";
    } finally {
        saving.value = false;
    }
}

async function removeDepartment(id) {
    Swal.fire({
        icon: "warning",
        title: "Delete Department",
        text: "Are you sure you want to delete this department?",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then(async (result) => {
        if (!result.isConfirmed) return;

        try {
            LoadingModal();
            await apiDeleteDepartment(id);
            onDelete(id);
            CloseModal();
            MessageModal("success", "Success", "Department deleted");
        } catch (e) {
            CloseModal();
            MessageModal("error", "Error", e?.response?.data?.message || e.message);
        }
    });
}

/** Local list updates */
function onCreate(item) {
    departments.value.unshift(item);
}
function onUpdate(item) {
    departments.value = departments.value.map((x) => (x.id === item.id ? item : x));
}
function onDelete(id) {
    departments.value = departments.value.filter((x) => x.id !== id);
}

/** Columns (User-style action header has Create button) */
const columns = computed(() => [
    { header: "ID", accessorKey: "id", meta: { align: "center" } },
    { header: "Name", accessorKey: "name", meta: { align: "left" } },
    { header: "Code", accessorKey: "code", meta: { align: "center" } },
    {
        accessorKey: "action",
        header: () => [
            "Actions",
            h(
                "button",
                {
                    type: "button",
                    class: "btn btn-sm btn-success ml-3",
                    onClick: openCreate,
                },
                "Create"
            ),
        ],
        cell: ({ row: { original } }) => [
            h(
                "button",
                {
                    type: "button",
                    class: "btn btn-sm btn-outline-secondary mx-1",
                    onClick: () => openEdit(original.id),
                },
                h("i", { class: "fa fa-pen" })
            ),
            h(
                "button",
                {
                    type: "button",
                    class: "btn btn-sm btn-outline-danger mx-1",
                    onClick: () => removeDepartment(original.id),
                },
                h("i", { class: "fa fa-trash" })
            ),
        ],
        enableSorting: false,
        meta: { align: "center" },
    },
]);

onMounted(async () => {
    $(depModal.value).on("hide.bs.modal", () => resetForm());
    await loadDepartments();
});
</script>