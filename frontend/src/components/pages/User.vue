<template>
    <div class="content-wrapper" style="min-height: 1416px">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Users</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <router-link :to="{ name: 'dashboard' }">Home</router-link>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <CustomTable :title="'Users'" :data="users" :columns="columns" :pageSize="25" />
            </div>
        </section>
    </div>

    <div class="modal fade" ref="userModal" aria-modal="true" role="dialog">
        <form @submit.prevent="saveUser">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            {{ userObject.id ? "Edit User" : "Create User" }}
                        </h4>
                        <button type="button" class="close" @click="hideUserModal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group text-center">
                            <label class="d-block">Photo</label>

                            <img :src="previewImage || defaultAvatar" alt="User Photo" class="img-thumbnail mb-2"
                                style="width: 110px; height: 110px; object-fit: cover; border-radius: 50%;" />

                            <input type="file" class="form-control" accept="image/*" @change="onPhotoChange"
                                :class="{ 'is-invalid': userObjectErr.photo }" />
                            <div class="invalid-feedback d-block">{{ userObjectErr.photo }}</div>
                        </div>

                        <div class="form-group">
                            <label for="userName">Name</label>
                            <input id="userName" type="text" class="form-control" v-model="userObject.name"
                                :class="{ 'is-invalid': userObjectErr.name }" />
                            <div class="invalid-feedback">{{ userObjectErr.name }}</div>
                        </div>

                        <div class="form-group">
                            <label for="userEmail">Email</label>
                            <input id="userEmail" type="email" class="form-control" v-model="userObject.email"
                                :class="{ 'is-invalid': userObjectErr.email }" />
                            <div class="invalid-feedback">{{ userObjectErr.email }}</div>
                        </div>

                        <div class="form-group">
                            <label for="userPhone">Phone</label>
                            <input id="userPhone" type="text" class="form-control" v-model="userObject.phone"
                                :class="{ 'is-invalid': userObjectErr.phone }" />
                            <div class="invalid-feedback">{{ userObjectErr.phone }}</div>
                        </div>

                        <div class="form-group">
                            <label for="userPassword">
                                Password
                                <small v-if="userObject.id" class="text-muted">(Leave blank if not changing)</small>
                            </label>
                            <input id="userPassword" type="password" class="form-control" v-model="userObject.password"
                                :class="{ 'is-invalid': userObjectErr.password }" />
                            <div class="invalid-feedback">{{ userObjectErr.password }}</div>
                        </div>

                        <div class="form-group">
                            <label for="departmentId">Department</label>
                            <select id="departmentId" class="form-control" v-model="userObject.department_id"
                                :class="{ 'is-invalid': userObjectErr.department_id }">
                                <option :value="null">-- Select Department --</option>
                                <option v-for="department in departments" :key="department.id" :value="department.id">
                                    {{ department.name }}
                                    <span v-if="department.code">({{ department.code }})</span>
                                </option>
                            </select>
                            <div class="invalid-feedback">{{ userObjectErr.department_id }}</div>
                        </div>
                    </div>

                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" @click="hideUserModal">
                            Close
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Save changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { onMounted, ref, h, reactive } from "vue";
import CustomTable from "../includes/tables/CustomTable.vue";
import { CloseModal, LoadingModal, MessageModal } from "@func/swal";
import profilePic from "@assets/images/emptyuser.png";

import {
    apiGetDetailUsers,
    apiReadDetailUser,
    apiCreateUser,
    apiUpdateUser,
    apiDeleteUser,
} from "@func/api/user";

import { apiGetDepartments } from "@func/api/department";

const userModal = ref(null);
const users = ref([]);
const departments = ref([]);
const selectedPhoto = ref(null);
const previewImage = ref(null);
const defaultAvatar = profilePic;

const userObject = reactive({
    id: null,
    name: "",
    email: "",
    phone: "",
    password: "",
    department_id: null,
    photo: null,
});

const userObjectErr = reactive({
    name: "",
    email: "",
    phone: "",
    password: "",
    department_id: "",
    photo: "",
});

function getDefaultUserObject() {
    return {
        id: null,
        name: "",
        email: "",
        phone: "",
        password: "",
        department_id: null,
        photo: null,
    };
}

function getDefaultUserObjectErr() {
    return {
        name: "",
        email: "",
        phone: "",
        password: "",
        department_id: "",
        photo: "",
    };
}

const columns = [
    {
        header: "No",
        cell: ({ row }) => row.index + 1,
    },
    {
        header: "Photo",
        cell: ({
            row: {
                original: { photo },
            },
        }) =>
            h("img", {
                src: photo || defaultAvatar,
                alt: "User Photo",
                style: "width:40px;height:40px;border-radius:50%;object-fit:cover;",
            }),
    },
    {
        header: "Name",
        accessorKey: "name",
    },
    {
        header: "Email",
        accessorKey: "email",
    },
    {
        header: "Phone",
        accessorKey: "phone",
    },
    {
        header: "Department",
        cell: ({
            row: {
                original: { department },
            },
        }) => department?.name || "-",
    },
    {
        accessorKey: "action",
        header: () => [
            "Actions",
            h(
                "button",
                {
                    onClick: () => showUserModal(),
                    class: "btn btn-sm btn-success ml-3",
                    type: "button",
                },
                "Create"
            ),
        ],
        cell: ({
            row: {
                original: { id },
            },
        }) => [
                h(
                    "button",
                    {
                        onClick: () => removeUser(id),
                        class: "btn btn-sm btn-outline-danger mx-1",
                        title: "Delete",
                        type: "button",
                    },
                    h("i", { class: "fa fa-trash" })
                ),
                h(
                    "button",
                    {
                        onClick: () => viewUser(id),
                        class: "btn btn-sm btn-outline-secondary mx-1",
                        title: "Edit",
                        type: "button",
                    },
                    h("i", { class: "fa fa-pen" })
                ),
            ],
        enableSorting: false,
    },
];

onMounted(async () => {
    $(userModal.value).on("hide.bs.modal", function () {
        resetData();
    });

    await fetchInitialData();
});

async function fetchInitialData() {
    try {
        LoadingModal();

        const [userResponse, departmentResponse] = await Promise.all([
            apiGetDetailUsers(),
            apiGetDepartments(),
        ]);

        users.value = userResponse.data.data || [];
        departments.value =
            departmentResponse.data.data ||
            departmentResponse.data.departments ||
            departmentResponse.data ||
            [];

        CloseModal();
    } catch (error) {
        CloseModal();
        return MessageModal(
            "error",
            "Error",
            error?.response?.data?.message || "An error occurred while fetching data."
        );
    }
}

function onPhotoChange(event) {
    const file = event.target.files[0];
    if (!file) return;

    selectedPhoto.value = file;
    userObject.photo = file;

    if (previewImage.value && String(previewImage.value).startsWith("blob:")) {
        URL.revokeObjectURL(previewImage.value);
    }

    previewImage.value = URL.createObjectURL(file);
}

async function saveUser() {
    try {
        LoadingModal();

        const formData = new FormData();
        formData.append("name", userObject.name || "");
        formData.append("email", userObject.email || "");

        if (userObject.phone && userObject.phone.trim() !== "") {
            formData.append("phone", userObject.phone.trim());
        }

        if (userObject.department_id !== null && userObject.department_id !== "") {
            formData.append("department_id", userObject.department_id);
        }

        if (selectedPhoto.value) {
            formData.append("photo", selectedPhoto.value);
        }

        let response = null;

        if (userObject.id) {
            if (userObject.password && userObject.password.trim() !== "") {
                formData.append("password", userObject.password);
            }

            formData.append("_method", "PUT");
            response = await apiUpdateUser(userObject.id, formData);
            onUserUpdate(response.data.user);
        } else {
            formData.append("password", userObject.password || "");
            response = await apiCreateUser(formData);
            onUserCreate(response.data.user);
        }

        hideUserModal();
        MessageModal("success", "Success", response.data.message);
    } catch (error) {
        if (!error.response) {
            CloseModal();
            return MessageModal("error", "Error", error.message);
        }

        if (error.response.status === 422) {
            const errors = error.response.data.errors || {};

            Object.keys(userObjectErr).forEach((key) => {
                userObjectErr[key] = errors[key] ? errors[key][0] : "";
            });

            return CloseModal();
        }

        CloseModal();
        return MessageModal(
            "error",
            "Error",
            error?.response?.data?.message || "Something went wrong."
        );
    }
}

async function viewUser(id) {
    try {
        LoadingModal();

        const response = await apiReadDetailUser(id);
        const user = response.data.user;

        Object.assign(userObject, {
            id: user.id,
            name: user.name || "",
            email: user.email || "",
            phone: user.phone || "",
            password: "",
            department_id: user.department_id ?? user.department?.id ?? null,
            photo: user.photo || null,
        });

        selectedPhoto.value = null;
        previewImage.value = user.photo || null;

        showUserModal();
        CloseModal();
    } catch (error) {
        CloseModal();
        return MessageModal(
            "error",
            "Error",
            error?.response?.data?.message || error.message
        );
    }
}

async function removeUser(id) {
    Swal.fire({
        icon: "warning",
        title: "Delete User",
        text: "Are you sure you want to delete this user? This action cannot be undone.",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                LoadingModal();
                const response = await apiDeleteUser(id);
                onUserDelete(id);
                MessageModal("success", "Success", response.data.message);
            } catch (error) {
                CloseModal();
                return MessageModal(
                    "error",
                    "Error",
                    error?.response?.data?.message || error.message
                );
            }
        }
    });
}

function showUserModal() {
    $(userModal.value).modal("show");
}

function hideUserModal() {
    $(userModal.value).modal("hide");
}

function resetData() {
    Object.assign(userObject, getDefaultUserObject());
    Object.assign(userObjectErr, getDefaultUserObjectErr());

    if (previewImage.value && String(previewImage.value).startsWith("blob:")) {
        URL.revokeObjectURL(previewImage.value);
    }

    selectedPhoto.value = null;
    previewImage.value = null;
}

function onUserCreate(user) {
    users.value.unshift(user);
}

function onUserUpdate(user) {
    users.value = users.value.map((u) => (u.id === user.id ? user : u));
}

function onUserDelete(id) {
    users.value = users.value.filter((u) => u.id !== id);
}
</script>