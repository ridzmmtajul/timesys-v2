<script setup>
import { ref, onMounted, watch } from "vue";
import TitleForm from "../titles/Form/Create.vue";
import useTitles from "../../../composables/titles.js";

const { titles, pagination, query, is_loading, getTitles, destroyTitle } = useTitles();

const title = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Abbreviation", key: "abbreviation", sortable: true },
    { title: "Description", key: "description", sortable: true },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        title.value = {};
    }
};

onMounted(() => {
    getTitles();
});

const editItem = (value) => {
    title.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyTitle(value.id);
};

const reloadTitles = async () => {
    await getTitles();
    title.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getTitles();
});
</script>

<template>
    <div class="titles-page">
        <!-- Page Header -->
        <div class="titles-header">
            <div class="titles-header__left">
                <div class="titles-header__icon">
                    <v-icon icon="mdi-tag-text-outline" size="20" />
                </div>
                <div>
                    <h5 class="titles-header__title">Title Management</h5>
                    <p class="titles-header__subtitle">Manage employee titles and designations</p>
                </div>
            </div>
            <button class="titles-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Title
            </button>
        </div>

        <!-- Main Card -->
        <div class="titles-card">
            <!-- Search Bar -->
            <div class="titles-search">
                <v-icon icon="mdi-magnify" size="17" class="titles-search__icon" />
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Search titles..."
                    class="titles-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="titles"
                :search="query.search"
                class="titles-table"
                :loading="is_loading"
                loading-text="Loading titles..."
                hide-default-footer
                item-value="id"
            >
                <!-- Abbreviation Column -->
                <template v-slot:item.abbreviation="{ item }">
                    <div class="titles-table__abbr-cell">
                        <div class="titles-table__avatar">
                            <v-icon icon="mdi-tag-text-outline" size="14" />
                        </div>
                        <span class="titles-table__abbr">{{ item.abbreviation }}</span>
                    </div>
                </template>

                <!-- Description Column -->
                <template v-slot:item.description="{ item }">
                    <span class="titles-table__description">{{ item.description || '—' }}</span>
                </template>

                <!-- Actions Column -->
                <template v-slot:item.actions="{ item }">
                    <div class="titles-table__actions">
                        <button class="titles-action-btn titles-action-btn--edit" @click="editItem(item)">
                            <v-icon icon="mdi-pencil-outline" size="14" />
                            Edit
                        </button>
                        <button class="titles-action-btn titles-action-btn--delete" @click="deleteItem(item)">
                            <v-icon icon="mdi-delete-outline" size="14" />
                            Delete
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template v-slot:no-data>
                    <div class="titles-empty">
                        <div class="titles-empty__icon">
                            <v-icon icon="mdi-tag-off-outline" size="32" />
                        </div>
                        <p class="titles-empty__title">No titles found</p>
                        <p class="titles-empty__sub">Get started by creating your first title</p>
                        <button class="titles-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Title
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="titles-pagination" v-if="pagination && pagination.total > 0">
                <span class="titles-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> titles
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getTitles"
                    class="titles-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Title Form Modal -->
    <title-form
        :value="show_form_modal"
        :title="title"
        @input="showModalForm"
        @reloadTitles="reloadTitles"
    />
</template>

<style scoped>
.titles-page {
    padding: 24px 20px;
    font-family: inherit;
}

/* ── Header ── */
.titles-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
}

.titles-header__left {
    display: flex;
    align-items: center;
    gap: 13px;
}

.titles-header__icon {
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

.titles-header__title {
    font-size: 16px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0 0 2px;
    line-height: 1.2;
}

.titles-header__subtitle {
    font-size: 12px;
    color: #8aa0d7;
    margin: 0;
}

.titles-btn-new {
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

.titles-btn-new:hover {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.45);
    transform: translateY(-1px);
}

.titles-btn-new:active {
    transform: translateY(0);
}

/* ── Card ── */
.titles-card {
    background: rgba(255, 255, 255, 0.04);
    border-radius: 16px;
    border: 1px solid rgba(108, 143, 214, 0.15);
    overflow: hidden;
    backdrop-filter: blur(8px);
}

/* ── Search ── */
.titles-search {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 18px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
    background: rgba(255, 255, 255, 0.02);
}

.titles-search__icon {
    color: #1fbfb8;
    flex-shrink: 0;
}

.titles-search__input {
    flex: 1;
    border: none;
    outline: none;
    font-size: 13px;
    color: #e2e8f0;
    background: transparent;
    font-family: inherit;
}

.titles-search__input::placeholder {
    color: rgba(138, 160, 215, 0.5);
}

/* ── Table overrides ── */
.titles-table {
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

.titles-table__abbr-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.titles-table__avatar {
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

.titles-table__abbr {
    font-weight: 600;
    color: #e2e8f0;
    letter-spacing: 0.04em;
}

.titles-table__description {
    color: #8aa0d7;
    font-size: 12.5px;
}

.titles-table__actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.titles-action-btn {
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

.titles-action-btn--edit {
    color: #1fbfb8;
    background: rgba(31, 191, 184, 0.08);
    border-color: rgba(31, 191, 184, 0.2);
}

.titles-action-btn--edit:hover {
    background: rgba(31, 191, 184, 0.18);
    border-color: rgba(31, 191, 184, 0.4);
}

.titles-action-btn--delete {
    color: #f87171;
    background: rgba(248, 113, 113, 0.06);
    border-color: rgba(248, 113, 113, 0.18);
}

.titles-action-btn--delete:hover {
    background: rgba(248, 113, 113, 0.14);
    border-color: rgba(248, 113, 113, 0.35);
}

/* ── Empty State ── */
.titles-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 52px 24px;
    gap: 8px;
}

.titles-empty__icon {
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

.titles-empty__title {
    font-size: 14px;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}

.titles-empty__sub {
    font-size: 12.5px;
    color: #8aa0d7;
    margin: 0;
}

/* ── Pagination ── */
.titles-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.titles-pagination__info {
    font-size: 12px;
    color: #8aa0d7;
}

.titles-pagination__info strong {
    color: #1fbfb8;
    font-weight: 700;
}
</style>
