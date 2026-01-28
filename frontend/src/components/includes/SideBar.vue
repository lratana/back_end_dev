<template>
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <router-link :to="{ name: 'dashboard' }" class="brand-link">
            <img :src="logoImg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" />
            <span class="brand-text font-weight-light">AdminLTE 3</span>
        </router-link>
        <div class="sidebar">
            <router-link :to="{ name: 'profile' }">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img :src="userData.photo ?? profilePic" class="img-circle elevation-2" alt="User Image" />
                    </div>
                    <div class="info">
                        <a href="#" class="d-block">{{ userData.name }}</a>
                    </div>
                </div>
            </router-link>
            <nav class="mt-2">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                        data-accordion="false">
                        <li class="nav-item">
                            <router-link :to="{ name: 'dashboard' }" class="nav-link" active-class="active">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </router-link>
                        </li>
                        <li v-if="isAdmin" class="nav-header">Systems</li>
                        <li v-if="isAdmin" class="nav-item">
                            <router-link :to="{ name: 'backups' }" class="nav-link" active-class="active">
                                <i class="nav-icon fas fa-database"></i>
                                <p>Backups</p>
                            </router-link>
                        </li>
                    </ul>
                </nav>

            </nav>
        </div>
    </aside>
</template>

<script setup>
import { useStore } from "vuex";
import { computed } from "vue";
const store = useStore();
const userData = computed(() => store.state.user);
const isAdmin = computed(() => userData.value && userData.value.level === "admin");
import logoImg from "admin-lte/dist/img/AdminLTELogo.png";
import profilePic from "admin-lte/dist/img/user4-128x128.jpg";
</script>