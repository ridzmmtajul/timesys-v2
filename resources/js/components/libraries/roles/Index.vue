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

watch(() => query.search, () => {
    query.page = 1;
    getRoles();
});
</script>

<template>
    <div class="roles-page">
        <!-- Page Header -->
        <div class="roles-header">
            <div class="roles-header__left">
                <div class="roles-header__icon">
                    <v-icon icon="mdi-account-tie" size="20" />
                </div>
                <div>
                    <h5 class="roles-header__title">Role Management</h5>
                    <p class="roles-header__subtitle">Manage system roles and permissions</p>
                </div>
            </div>
            <button class="roles-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Role
            </button>
        </div>

        <!-- Main Card -->
        <div class="roles-card">
            <!-- Search Bar -->
            <div class="roles-search">
                <v-icon icon="mdi-magnify" size="17" class="roles-search__icon" />
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Search roles..."
                    class="roles-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="roles"
                :search="query.search"
                class="roles-table"
                :loading="is_loading"
                loading-text="Loading roles..."
                hide-default-footer
                item-value="id"
            >
                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <div class="roles-table__name-cell">
                        <div class="roles-table__avatar">
                            <v-icon icon="mdi-account-tie" size="14" />
                        </div>
                        <span class="roles-table__name">{{ item.name }}</span>
                    </div>
                </template>

                <!-- Status Column -->
                <template v-slot:item.status="{ item }">
                    <div class="roles-table__status-cell">
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
                    <div class="roles-table__actions">
                        <button class="roles-action-btn roles-action-btn--edit" @click="editItem(item)">
                            <v-icon icon="mdi-pencil-outline" size="14" />
                            Edit
                        </button>
                        <button class="roles-action-btn roles-action-btn--delete" @click="deleteItem(item)">
                            <v-icon icon="mdi-delete-outline" size="14" />
                            Delete
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template v-slot:no-data>
                    <div class="roles-empty">
                        <div class="roles-empty__icon">
                            <v-icon icon="mdi-shield-off-outline" size="32" />
                        </div>
                        <p class="roles-empty__title">No roles found</p>
                        <p class="roles-empty__sub">Get started by creating your first role</p>
                        <button class="roles-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Role
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="roles-pagination" v-if="pagination && pagination.total > 0">
                <span class="roles-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> roles
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getRoles"
                    class="roles-pagination__control"
                    active-color="#1fbfb8"
                />
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

<style scoped>
.roles-page {
    padding: 24px 20px;
    font-family: inherit;
}

/* ── Header ── */
.roles-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.roles-header__left {
    display: flex;
    align-items: center;
    gap: 13px;
}

.roles-header__icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(31, 191, 184, 0.15);
    border: 1px solid rgba(31, 191, 184, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1fbfb8;
    flex-shrink: 0;
}

.roles-header__title {
    font-size: 16px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 2px;
    line-height: 1.2;
}

.roles-header__subtitle {
    font-size: 12px;
    color: #8aa0d7;
    margin: 0;
}

.roles-btn-new {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #1fbfb8 0%, #05716c 100%);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    box-shadow: 0 2px 12px rgba(31, 191, 184, 0.3);
    transition: all 0.18s;
}

.roles-btn-new:hover {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.45);
    transform: translateY(-1px);
}

.roles-btn-new:active {
    transform: translateY(0);
}

/* ── Card ── */
.roles-card {
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
    border: 1px solid rgba(108, 143, 214, 0.15);
    overflow: hidden;
    backdrop-filter: blur(8px);
}

/* ── Search ── */
.roles-search {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 18px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
    background: rgba(255, 255, 255, 0.02);
}

.roles-search__icon {
    color: #1fbfb8;
    flex-shrink: 0;
}

.roles-search__input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 13px;
    color: #e2e8f0;
    background: transparent;
    font-family: inherit;
}

.roles-search__input::placeholder {
    color: rgba(138, 160, 215, 0.5);
}

/* ── Table overrides ── */
.roles-table {
    background: transparent !important;
    font-family: inherit !important;
}

:deep(.v-data-table__thead th) {
    background: rgba(31, 191, 184, 0.06) !important;
    color: #1fbfb8 !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    letter-spacing: 0.08em !important;
    text-transform: uppercase !important;
    border-bottom: 1px solid rgba(31, 191, 184, 0.15) !important;
}

:deep(.v-data-table__tr:hover > td) {
    background: rgba(31, 191, 184, 0.04) !important;
}

:deep(.v-data-table__td) {
    border-bottom: 1px solid rgba(108, 143, 214, 0.08) !important;
    font-size: 13px !important;
    color: #c8d6f0 !important;
}

:deep(.v-data-table) {
    background: transparent !important;
}

.roles-table__name-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.roles-table__avatar {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: rgba(31, 191, 184, 0.12);
    border: 1px solid rgba(31, 191, 184, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1fbfb8;
    flex-shrink: 0;
}

.roles-table__name {
    font-weight: 600;
    color: #e2e8f0;
}

.roles-table__status-cell {
    display: flex;
    justify-content: center;
}

.roles-table__actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.roles-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 11px;
    border-radius: 7px;
    border: 1px solid transparent;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.15s;
}

.roles-action-btn--edit {
    color: #1fbfb8;
    background: rgba(31, 191, 184, 0.08);
    border-color: rgba(31, 191, 184, 0.2);
}

.roles-action-btn--edit:hover {
    background: rgba(31, 191, 184, 0.18);
    border-color: rgba(31, 191, 184, 0.4);
}

.roles-action-btn--delete {
    color: #f87171;
    background: rgba(248, 113, 113, 0.06);
    border-color: rgba(248, 113, 113, 0.18);
}

.roles-action-btn--delete:hover {
    background: rgba(248, 113, 113, 0.14);
    border-color: rgba(248, 113, 113, 0.35);
}

/* ── Empty State ── */
.roles-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 52px 24px;
    gap: 8px;
}

.roles-empty__icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(31, 191, 184, 0.08);
    border: 2px dashed rgba(31, 191, 184, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(31, 191, 184, 0.5);
    margin-bottom: 6px;
}

.roles-empty__title {
    font-size: 14px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}

.roles-empty__sub {
    font-size: 12.5px;
    color: #8aa0d7;
    margin: 0;
}

/* ── Pagination ── */
.roles-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.roles-pagination__info {
    font-size: 12px;
    color: #8aa0d7;
}

.roles-pagination__info strong {
    color: #1fbfb8;
    font-weight: 700;
}
</style>
