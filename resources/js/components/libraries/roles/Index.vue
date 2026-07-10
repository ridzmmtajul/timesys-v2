<script setup>
import { ref, onMounted, watch } from "vue";
import RoleForm from "../roles/Form/Create.vue";
import useRoles from "../../../composables/roles.js";

const { roles, pagination, query, is_loading, getRoles, destoryRole, updateRoleStatus } = useRoles();

const role = ref({});
const show_form_modal = ref(false);
const updating_status_ids = ref([]);

const headers = [
    { title: "Name", key: "name", sortable: true },
    { title: "Status", key: "status", sortable: true, align: "center" },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        role.value = {};
    }
};

onMounted(() => {
    getRoles();
});

const editItem = (value) => {
    role.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destoryRole(value.id);
};

const toggleStatus = async (item, enabled) => {
    const previousStatus = item.status;
    const nextStatus = enabled ? "active" : "inactive";

    if (previousStatus === nextStatus) return;

    updating_status_ids.value = [...updating_status_ids.value, item.id];
    item.status = nextStatus;

    const isUpdated = await updateRoleStatus({
        id: item.id,
        name: item.name,
        status: nextStatus,
    });

    if (!isUpdated) {
        item.status = previousStatus;
    }

    updating_status_ids.value = updating_status_ids.value.filter((id) => id !== item.id);
};

const reloadRoles = async () => {
    await getRoles();
    role.value = {};
};

watch(() => query.value.search, () => {
    query.value.page = 1;
    getRoles();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-account-tie" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Role Management</h5>
                    <p class="lib-header__subtitle">Manage system roles and permissions</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Role
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
                    placeholder="Search roles..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="roles"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading roles..."
                hide-default-footer
                item-value="id"
            >
                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-account-tie" size="14" />
                        </div>
                        <span class="lib-table__name">{{ item.name }}</span>
                    </div>
                </template>

                <!-- Status Column -->
                <template v-slot:item.status="{ item }">
                    <div class="lib-table__status-cell">
                        <v-switch
                            :model-value="item.status === 'active'"
                            hide-details
                            inset
                            color="#1fbfb8"
                            density="compact"
                            :loading="updating_status_ids.includes(item.id)"
                            :disabled="updating_status_ids.includes(item.id)"
                            @update:model-value="toggleStatus(item, $event)"
                        />
                    </div>
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
                            <v-icon icon="mdi-shield-off-outline" size="32" />
                        </div>
                        <p class="lib-empty__title">No roles found</p>
                        <p class="lib-empty__sub">Get started by creating your first role</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Role
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> roles
                </span>
                <div class="lib-pager">
                    <button class="lib-pager__btn" :disabled="query.page <= 1" @click="query.page--; getRoles()">
                        <v-icon icon="mdi-chevron-left" size="18" />
                    </button>
                    <span class="lib-pager__current">{{ query.page }}</span>
                    <button class="lib-pager__btn" :disabled="query.page >= (pagination.last_page || 1)" @click="query.page++; getRoles()">
                        <v-icon icon="mdi-chevron-right" size="18" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Form Modal -->
    <role-form
        :value="show_form_modal"
        :role="role"
        @input="showModalForm"
        @reloadRoles="reloadRoles"
    />
</template>
