<script setup>
import { ref, onMounted, watch } from "vue";
import PositionForm from "../positions/Form/Create.vue";
import usePositions from "../../../composables/positions.js";

const { positions, pagination, query, is_loading, getPositions, destroyPosition } = usePositions();

const position = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Name", key: "name", sortable: true },
    { title: "Description", key: "description", sortable: true },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        position.value = {};
    }
};

onMounted(() => {
    getPositions();
});

const editItem = (value) => {
    position.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyPosition(value.id);
};

const reloadPositions = async () => {
    await getPositions();
    position.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getPositions();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-briefcase-account-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Position Management</h5>
                    <p class="lib-header__subtitle">Manage employee positions and job titles</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Position
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
                    placeholder="Search positions..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="positions"
                :search="query.search"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading positions..."
                hide-default-footer
                item-value="id"
            >
                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-briefcase-account-outline" size="14" />
                        </div>
                        <span class="lib-table__name">{{ item.name }}</span>
                    </div>
                </template>

                <!-- Description Column -->
                <template v-slot:item.description="{ item }">
                    <span class="lib-table__muted">{{ item.description || '—' }}</span>
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
                            <v-icon icon="mdi-briefcase-off-outline" size="32" />
                        </div>
                        <p class="lib-empty__title">No positions found</p>
                        <p class="lib-empty__sub">Get started by creating your first position</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Position
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> positions
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getPositions"
                    class="lib-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Position Form Modal -->
    <position-form
        :value="show_form_modal"
        :position="position"
        @input="showModalForm"
        @reloadPositions="reloadPositions"
    />
</template>
