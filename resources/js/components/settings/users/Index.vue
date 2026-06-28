<script setup>
import { ref, onMounted, watch } from "vue";
import UserForm from "../users/Form/Create.vue";
import useUsers from "../../../composables/users.js";

const { users, pagination, query, is_loading, getUsers, destroyUser } = useUsers();

const user = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Username", key: "username", sortable: true },
    { title: "Employee", key: "employee_name", sortable: false },
    { title: "Role", key: "role_name", sortable: false },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        user.value = {};
    }
};

onMounted(() => {
    getUsers();
});

const editItem = (value) => {
    user.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyUser(value.id);
};

const reloadUsers = async () => {
    await getUsers();
    user.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getUsers();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-account-circle-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Account Management</h5>
                    <p class="lib-header__subtitle">Manage system user accounts</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Account
            </button>
        </div>

        <!-- Main Card -->
        <div class="lib-card">
            <!-- Search Bar -->
            <div class="lib-search">
                <v-icon icon="mdi-magnify" size="17" class="lib-search__icon" />
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Search accounts..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="users"
                :search="query.search"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading accounts..."
                hide-default-footer
                item-value="id"
            >
                <!-- Username Column -->
                <template v-slot:item.username="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-account-outline" size="14" />
                        </div>
                        <span class="lib-table__name">{{ item.username }}</span>
                    </div>
                </template>

                <!-- Employee Column -->
                <template v-slot:item.employee_name="{ item }">
                    <div>
                        <span class="lib-table__name">{{ item.employee_name || '—' }}</span>
                        <span v-if="item.employee_no" class="lib-table__muted" style="display:block;font-size:11px;">{{ item.employee_no }}</span>
                    </div>
                </template>

                <!-- Role Column -->
                <template v-slot:item.role_name="{ item }">
                    <span class="lib-table__badge">{{ item.role_name || '—' }}</span>
                </template>

                <!-- Actions Column -->
                <template v-slot:item.actions="{ item }">
                    <div class="lib-table__actions">
                        <button class="lib-action-btn lib-action-btn--edit" @click="editItem(item)">
                            <v-icon icon="mdi-pencil-outline" size="14" />
                            Edit
                        </button>
                        <button class="lib-action-btn lib-action-btn--delete" @click="deleteItem(item)">
                            <v-icon icon="mdi-delete-outline" size="14" />
                            Delete
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template v-slot:no-data>
                    <div class="lib-empty">
                        <div class="lib-empty__icon">
                            <v-icon icon="mdi-account-off-outline" size="32" />
                        </div>
                        <p class="lib-empty__title">No accounts found</p>
                        <p class="lib-empty__sub">Get started by creating your first user account</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Account
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> accounts
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getUsers"
                    class="lib-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- User Form Modal -->
    <user-form
        :value="show_form_modal"
        :user="user"
        @input="showModalForm"
        @reloadUsers="reloadUsers"
    />
</template>