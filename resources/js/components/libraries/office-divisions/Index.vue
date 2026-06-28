<script setup>
import { ref, onMounted, watch } from "vue";
import OfficeDivisionForm from "./Form/Create.vue";
import useOfficeDivisions from "../../../composables/officeDivisions.js";

const { divisions, pagination, query, is_loading, getDivisions, destroyDivision } = useOfficeDivisions();

const division = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Code", key: "code", sortable: true },
    { title: "Name", key: "name", sortable: true },
    { title: "Office", key: "office_name", sortable: true },
    { title: "Description", key: "description", sortable: false },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        division.value = {};
    }
};

onMounted(() => {
    getDivisions();
});

const editItem = (value) => {
    division.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyDivision(value.id);
};

const reloadDivisions = async () => {
    await getDivisions();
    division.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getDivisions();
});
</script>

<template>
    <div class="divisions-page">
        <!-- Page Header -->
        <div class="divisions-header">
            <div class="divisions-header__left">
                <div class="divisions-header__icon">
                    <v-icon icon="mdi-office-building-marker" size="20" />
                </div>
                <div>
                    <h5 class="divisions-header__title">Office Division Management</h5>
                    <p class="divisions-header__subtitle">Manage divisions and departments</p>
                </div>
            </div>
            <button class="divisions-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Division
            </button>
        </div>

        <!-- Main Card -->
        <div class="divisions-card">
            <!-- Search Bar -->
            <div class="divisions-search">
                <v-icon icon="mdi-magnify" size="17" class="divisions-search__icon" />
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Search divisions..."
                    class="divisions-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="divisions"
                :search="query.search"
                class="divisions-table"
                :loading="is_loading"
                loading-text="Loading divisions..."
                hide-default-footer
                item-value="id"
            >
                <!-- Code Column -->
                <template v-slot:item.code="{ item }">
                    <div class="divisions-table__code-cell">
                        <div class="divisions-table__avatar">
                            <v-icon icon="mdi-office-building-marker" size="14" />
                        </div>
                        <span class="divisions-table__code">{{ item.code }}</span>
                    </div>
                </template>

                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <span class="divisions-table__name">{{ item.name }}</span>
                </template>

                <!-- Office Column -->
                <template v-slot:item.office_name="{ item }">
                    <span v-if="item.office_name" class="divisions-table__badge">
                        {{ item.office_code }} — {{ item.office_name }}
                    </span>
                    <span v-else class="divisions-table__muted">—</span>
                </template>

                <!-- Description Column -->
                <template v-slot:item.description="{ item }">
                    <span class="divisions-table__muted">{{ item.description || '—' }}</span>
                </template>

                <!-- Actions Column -->
                <template v-slot:item.actions="{ item }">
                    <div class="divisions-table__actions">
                        <button class="divisions-action-btn divisions-action-btn--edit" @click="editItem(item)">
                            <v-icon icon="mdi-pencil-outline" size="14" />
                            Edit
                        </button>
                        <button class="divisions-action-btn divisions-action-btn--delete" @click="deleteItem(item)">
                            <v-icon icon="mdi-delete-outline" size="14" />
                            Delete
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template v-slot:no-data>
                    <div class="divisions-empty">
                        <div class="divisions-empty__icon">
                            <v-icon icon="mdi-office-building-remove" size="32" />
                        </div>
                        <p class="divisions-empty__title">No divisions found</p>
                        <p class="divisions-empty__sub">Get started by creating your first division</p>
                        <button class="divisions-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Division
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="divisions-pagination" v-if="pagination && pagination.total > 0">
                <span class="divisions-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> divisions
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getDivisions"
                    class="divisions-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Division Form Modal -->
    <office-division-form
        :value="show_form_modal"
        :division="division"
        @input="showModalForm"
        @reloadDivisions="reloadDivisions"
    />
</template>

<style scoped>
.divisions-page {
    padding: 24px 20px;
    font-family: inherit;
}

/* ── Header ── */
.divisions-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.divisions-header__left {
    display: flex;
    align-items: center;
    gap: 13px;
}

.divisions-header__icon {
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

.divisions-header__title {
    font-size: 16px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 2px;
    line-height: 1.2;
}

.divisions-header__subtitle {
    font-size: 12px;
    color: #8aa0d7;
    margin: 0;
}

.divisions-btn-new {
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

.divisions-btn-new:hover {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.45);
    transform: translateY(-1px);
}

.divisions-btn-new:active {
    transform: translateY(0);
}

/* ── Card ── */
.divisions-card {
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
    border: 1px solid rgba(108, 143, 214, 0.15);
    overflow: hidden;
    backdrop-filter: blur(8px);
}

/* ── Search ── */
.divisions-search {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 18px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
    background: rgba(255, 255, 255, 0.02);
}

.divisions-search__icon {
    color: #1fbfb8;
    flex-shrink: 0;
}

.divisions-search__input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 13px;
    color: #e2e8f0;
    background: transparent;
    font-family: inherit;
}

.divisions-search__input::placeholder {
    color: rgba(138, 160, 215, 0.5);
}

/* ── Table overrides ── */
.divisions-table {
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

.divisions-table__code-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.divisions-table__avatar {
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

.divisions-table__code {
    font-weight: 700;
    color: #e2e8f0;
    font-size: 13px;
    letter-spacing: 0.03em;
}

.divisions-table__name {
    font-weight: 600;
    color: #e2e8f0;
}

.divisions-table__badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 6px;
    background: rgba(31, 191, 184, 0.1);
    border: 1px solid rgba(31, 191, 184, 0.2);
    color: #1fbfb8;
    font-size: 11.5px;
    font-weight: 600;
}

.divisions-table__muted {
    color: #8aa0d7;
    font-size: 12.5px;
}

.divisions-table__actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.divisions-action-btn {
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

.divisions-action-btn--edit {
    color: #1fbfb8;
    background: rgba(31, 191, 184, 0.08);
    border-color: rgba(31, 191, 184, 0.2);
}

.divisions-action-btn--edit:hover {
    background: rgba(31, 191, 184, 0.18);
    border-color: rgba(31, 191, 184, 0.4);
}

.divisions-action-btn--delete {
    color: #f87171;
    background: rgba(248, 113, 113, 0.06);
    border-color: rgba(248, 113, 113, 0.18);
}

.divisions-action-btn--delete:hover {
    background: rgba(248, 113, 113, 0.14);
    border-color: rgba(248, 113, 113, 0.35);
}

/* ── Empty State ── */
.divisions-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 52px 24px;
    gap: 8px;
}

.divisions-empty__icon {
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

.divisions-empty__title {
    font-size: 14px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}

.divisions-empty__sub {
    font-size: 12.5px;
    color: #8aa0d7;
    margin: 0;
}

/* ── Pagination ── */
.divisions-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.divisions-pagination__info {
    font-size: 12px;
    color: #8aa0d7;
}

.divisions-pagination__info strong {
    color: #1fbfb8;
    font-weight: 700;
}
</style>
