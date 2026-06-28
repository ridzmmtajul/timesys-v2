<script setup>
import { ref, onMounted, watch } from "vue";
import EmploymentTypeForm from "../employment-types/Form/Create.vue";
import useEmploymentTypes from "../../../composables/employment-types.js";

const { employmentTypes, pagination, query, is_loading, getEmploymentTypes, destroyEmploymentType } = useEmploymentTypes();

const employmentType = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Name", key: "name", sortable: true },
    { title: "Description", key: "description", sortable: true },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        employmentType.value = {};
    }
};

onMounted(() => {
    getEmploymentTypes();
});

const editItem = (value) => {
    employmentType.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyEmploymentType(value.id);
};

const reloadEmploymentTypes = async () => {
    await getEmploymentTypes();
    employmentType.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getEmploymentTypes();
});
</script>

<template>
    <div class="employment-types-page">
        <!-- Page Header -->
        <div class="employment-types-header">
            <div class="employment-types-header__left">
                <div class="employment-types-header__icon">
                    <v-icon icon="mdi-briefcase-outline" size="20" />
                </div>
                <div>
                    <h5 class="employment-types-header__title">Employment Type Management</h5>
                    <p class="employment-types-header__subtitle">Manage employment types and classifications</p>
                </div>
            </div>
            <button class="employment-types-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Employment Type
            </button>
        </div>

        <!-- Main Card -->
        <div class="employment-types-card">
            <!-- Search Bar -->
            <div class="employment-types-search">
                <v-icon icon="mdi-magnify" size="17" class="employment-types-search__icon" />
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Search employment types..."
                    class="employment-types-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="employmentTypes"
                :search="query.search"
                class="employment-types-table"
                :loading="is_loading"
                loading-text="Loading employment types..."
                hide-default-footer
                item-value="id"
            >
                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <div class="employment-types-table__name-cell">
                        <div class="employment-types-table__avatar">
                            <v-icon icon="mdi-briefcase-outline" size="14" />
                        </div>
                        <span class="employment-types-table__name">{{ item.name }}</span>
                    </div>
                </template>

                <!-- Description Column -->
                <template v-slot:item.description="{ item }">
                    <span class="employment-types-table__description">{{ item.description || '—' }}</span>
                </template>

                <!-- Actions Column -->
                <template v-slot:item.actions="{ item }">
                    <div class="employment-types-table__actions">
                        <button class="employment-types-action-btn employment-types-action-btn--edit" @click="editItem(item)">
                            <v-icon icon="mdi-pencil-outline" size="14" />
                            Edit
                        </button>
                        <button class="employment-types-action-btn employment-types-action-btn--delete" @click="deleteItem(item)">
                            <v-icon icon="mdi-delete-outline" size="14" />
                            Delete
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template v-slot:no-data>
                    <div class="employment-types-empty">
                        <div class="employment-types-empty__icon">
                            <v-icon icon="mdi-briefcase-off-outline" size="32" />
                        </div>
                        <p class="employment-types-empty__title">No employment types found</p>
                        <p class="employment-types-empty__sub">Get started by creating your first employment type</p>
                        <button class="employment-types-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Employment Type
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="employment-types-pagination" v-if="pagination && pagination.total > 0">
                <span class="employment-types-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> employment types
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getEmploymentTypes"
                    class="employment-types-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Employment Type Form Modal -->
    <employment-type-form
        :value="show_form_modal"
        :employment-type="employmentType"
        @input="showModalForm"
        @reloadEmploymentTypes="reloadEmploymentTypes"
    />
</template>

<style scoped>
.employment-types-page {
    padding: 24px 20px;
    font-family: inherit;
}

/* ── Header ── */
.employment-types-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.employment-types-header__left {
    display: flex;
    align-items: center;
    gap: 13px;
}

.employment-types-header__icon {
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

.employment-types-header__title {
    font-size: 16px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 2px;
    line-height: 1.2;
}

.employment-types-header__subtitle {
    font-size: 12px;
    color: #8aa0d7;
    margin: 0;
}

.employment-types-btn-new {
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

.employment-types-btn-new:hover {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.45);
    transform: translateY(-1px);
}

.employment-types-btn-new:active {
    transform: translateY(0);
}

/* ── Card ── */
.employment-types-card {
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
    border: 1px solid rgba(108, 143, 214, 0.15);
    overflow: hidden;
    backdrop-filter: blur(8px);
}

/* ── Search ── */
.employment-types-search {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 18px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
    background: rgba(255, 255, 255, 0.02);
}

.employment-types-search__icon {
    color: #1fbfb8;
    flex-shrink: 0;
}

.employment-types-search__input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 13px;
    color: #e2e8f0;
    background: transparent;
    font-family: inherit;
}

.employment-types-search__input::placeholder {
    color: rgba(138, 160, 215, 0.5);
}

/* ── Table overrides ── */
.employment-types-table {
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

.employment-types-table__name-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.employment-types-table__avatar {
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

.employment-types-table__name {
    font-weight: 600;
    color: #e2e8f0;
}

.employment-types-table__description {
    color: #8aa0d7;
    font-size: 12.5px;
}

.employment-types-table__actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.employment-types-action-btn {
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

.employment-types-action-btn--edit {
    color: #1fbfb8;
    background: rgba(31, 191, 184, 0.08);
    border-color: rgba(31, 191, 184, 0.2);
}

.employment-types-action-btn--edit:hover {
    background: rgba(31, 191, 184, 0.18);
    border-color: rgba(31, 191, 184, 0.4);
}

.employment-types-action-btn--delete {
    color: #f87171;
    background: rgba(248, 113, 113, 0.06);
    border-color: rgba(248, 113, 113, 0.18);
}

.employment-types-action-btn--delete:hover {
    background: rgba(248, 113, 113, 0.14);
    border-color: rgba(248, 113, 113, 0.35);
}

/* ── Empty State ── */
.employment-types-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 52px 24px;
    gap: 8px;
}

.employment-types-empty__icon {
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

.employment-types-empty__title {
    font-size: 14px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}

.employment-types-empty__sub {
    font-size: 12.5px;
    color: #8aa0d7;
    margin: 0;
}

/* ── Pagination ── */
.employment-types-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.employment-types-pagination__info {
    font-size: 12px;
    color: #8aa0d7;
}

.employment-types-pagination__info strong {
    color: #1fbfb8;
    font-weight: 700;
}
</style>
