<script setup>
import { ref, onMounted, watch } from "vue";
import OfficeForm from "./Form/Create.vue";
import useOffices from "../../../composables/offices.js";

const { offices, pagination, query, is_loading, getOffices, destroyOffice } = useOffices();

const office = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Code", key: "code", sortable: true },
    { title: "Name", key: "name", sortable: true },
    { title: "Description", key: "description", sortable: false },
    { title: "Prefix", key: "prefix", sortable: true },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        office.value = {};
    }
};

onMounted(() => {
    getOffices();
});

const editItem = (value) => {
    office.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyOffice(value.id);
};

const reloadOffices = async () => {
    await getOffices();
    office.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getOffices();
});
</script>

<template>
    <div class="offices-page">
        <!-- Page Header -->
        <div class="offices-header">
            <div class="offices-header__left">
                <div class="offices-header__icon">
                    <v-icon icon="mdi-office-building" size="20" />
                </div>
                <div>
                    <h5 class="offices-header__title">Office Management</h5>
                    <p class="offices-header__subtitle">Manage offices and departments</p>
                </div>
            </div>
            <button class="offices-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Office
            </button>
        </div>

        <!-- Main Card -->
        <div class="offices-card">
            <!-- Search Bar -->
            <div class="offices-search">
                <v-icon icon="mdi-magnify" size="17" class="offices-search__icon" />
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Search offices..."
                    class="offices-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="offices"
                :search="query.search"
                class="offices-table"
                :loading="is_loading"
                loading-text="Loading offices..."
                hide-default-footer
                item-value="id"
            >
                <!-- Code Column -->
                <template v-slot:item.code="{ item }">
                    <div class="offices-table__code-cell">
                        <div class="offices-table__avatar">
                            <v-icon icon="mdi-office-building" size="14" />
                        </div>
                        <span class="offices-table__code">{{ item.code }}</span>
                    </div>
                </template>

                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <span class="offices-table__name">{{ item.name }}</span>
                </template>

                <!-- Description Column -->
                <template v-slot:item.description="{ item }">
                    <span class="offices-table__description">{{ item.description || '—' }}</span>
                </template>

                <!-- Prefix Column -->
                <template v-slot:item.prefix="{ item }">
                    <span v-if="item.prefix" class="offices-table__badge">{{ item.prefix }}</span>
                    <span v-else class="offices-table__description">—</span>
                </template>

                <!-- Actions Column -->
                <template v-slot:item.actions="{ item }">
                    <div class="offices-table__actions">
                        <button class="offices-action-btn offices-action-btn--edit" @click="editItem(item)">
                            <v-icon icon="mdi-pencil-outline" size="14" />
                            Edit
                        </button>
                        <button class="offices-action-btn offices-action-btn--delete" @click="deleteItem(item)">
                            <v-icon icon="mdi-delete-outline" size="14" />
                            Delete
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template v-slot:no-data>
                    <div class="offices-empty">
                        <div class="offices-empty__icon">
                            <v-icon icon="mdi-office-building-remove" size="32" />
                        </div>
                        <p class="offices-empty__title">No offices found</p>
                        <p class="offices-empty__sub">Get started by creating your first office</p>
                        <button class="offices-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Office
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="offices-pagination" v-if="pagination && pagination.total > 0">
                <span class="offices-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> offices
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getOffices"
                    class="offices-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Office Form Modal -->
    <office-form
        :value="show_form_modal"
        :office="office"
        @input="showModalForm"
        @reloadOffices="reloadOffices"
    />
</template>

<style scoped>
.offices-page {
    padding: 24px 20px;
    font-family: inherit;
}

/* ── Header ── */
.offices-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.offices-header__left {
    display: flex;
    align-items: center;
    gap: 13px;
}

.offices-header__icon {
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

.offices-header__title {
    font-size: 16px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 2px;
    line-height: 1.2;
}

.offices-header__subtitle {
    font-size: 12px;
    color: #8aa0d7;
    margin: 0;
}

.offices-btn-new {
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

.offices-btn-new:hover {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.45);
    transform: translateY(-1px);
}

.offices-btn-new:active {
    transform: translateY(0);
}

/* ── Card ── */
.offices-card {
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
    border: 1px solid rgba(108, 143, 214, 0.15);
    overflow: hidden;
    backdrop-filter: blur(8px);
}

/* ── Search ── */
.offices-search {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 18px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
    background: rgba(255, 255, 255, 0.02);
}

.offices-search__icon {
    color: #1fbfb8;
    flex-shrink: 0;
}

.offices-search__input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 13px;
    color: #e2e8f0;
    background: transparent;
    font-family: inherit;
}

.offices-search__input::placeholder {
    color: rgba(138, 160, 215, 0.5);
}

/* ── Table overrides ── */
.offices-table {
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

.offices-table__code-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.offices-table__avatar {
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

.offices-table__code {
    font-weight: 700;
    color: #e2e8f0;
    font-size: 13px;
    letter-spacing: 0.03em;
}

.offices-table__name {
    font-weight: 600;
    color: #e2e8f0;
}

.offices-table__description {
    color: #8aa0d7;
    font-size: 12.5px;
}

.offices-table__badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 6px;
    background: rgba(31, 191, 184, 0.1);
    border: 1px solid rgba(31, 191, 184, 0.2);
    color: #1fbfb8;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.05em;
}

.offices-table__actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.offices-action-btn {
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

.offices-action-btn--edit {
    color: #1fbfb8;
    background: rgba(31, 191, 184, 0.08);
    border-color: rgba(31, 191, 184, 0.2);
}

.offices-action-btn--edit:hover {
    background: rgba(31, 191, 184, 0.18);
    border-color: rgba(31, 191, 184, 0.4);
}

.offices-action-btn--delete {
    color: #f87171;
    background: rgba(248, 113, 113, 0.06);
    border-color: rgba(248, 113, 113, 0.18);
}

.offices-action-btn--delete:hover {
    background: rgba(248, 113, 113, 0.14);
    border-color: rgba(248, 113, 113, 0.35);
}

/* ── Empty State ── */
.offices-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 52px 24px;
    gap: 8px;
}

.offices-empty__icon {
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

.offices-empty__title {
    font-size: 14px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}

.offices-empty__sub {
    font-size: 12.5px;
    color: #8aa0d7;
    margin: 0;
}

/* ── Pagination ── */
.offices-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.offices-pagination__info {
    font-size: 12px;
    color: #8aa0d7;
}

.offices-pagination__info strong {
    color: #1fbfb8;
    font-weight: 700;
}
</style>
