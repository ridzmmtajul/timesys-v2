<script setup>
import { ref, onMounted, watch } from "vue";
import ScheduleTypeForm from "../schedule-types/Form/Create.vue";
import useScheduleTypes from "../../../composables/schedule-types.js";

const { scheduleTypes, pagination, query, is_loading, getScheduleTypes, destroyScheduleType } = useScheduleTypes();

const scheduleType = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Name", key: "name", sortable: true },
    { title: "Description", key: "description", sortable: true },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        scheduleType.value = {};
    }
};

onMounted(() => {
    getScheduleTypes();
});

const editItem = (value) => {
    scheduleType.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyScheduleType(value.id);
};

const reloadScheduleTypes = async () => {
    await getScheduleTypes();
    scheduleType.value = {};
};

watch(() => query.value.search, () => {
    query.value.page = 1;
    getScheduleTypes();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-calendar-clock-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Schedule Type Management</h5>
                    <p class="lib-header__subtitle">Manage schedule types and their descriptions</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Schedule Type
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
                    placeholder="Search schedule types..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="scheduleTypes"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading schedule types..."
                hide-default-footer
                item-value="id"
            >
                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-calendar-clock-outline" size="14" />
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
                            <v-icon icon="mdi-calendar-remove-outline" size="32" />
                        </div>
                        <p class="lib-empty__title">No schedule types found</p>
                        <p class="lib-empty__sub">Get started by creating your first schedule type</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Schedule Type
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> schedule types
                </span>
                <div class="lib-pager">
                    <button class="lib-pager__btn" :disabled="query.page <= 1" @click="query.page--; getScheduleTypes()">
                        <v-icon icon="mdi-chevron-left" size="18" />
                    </button>
                    <span class="lib-pager__current">{{ query.page }}</span>
                    <button class="lib-pager__btn" :disabled="query.page >= (pagination.last_page || 1)" @click="query.page++; getScheduleTypes()">
                        <v-icon icon="mdi-chevron-right" size="18" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Type Form Modal -->
    <schedule-type-form
        :value="show_form_modal"
        :schedule-type="scheduleType"
        @input="showModalForm"
        @reloadScheduleTypes="reloadScheduleTypes"
    />
</template>
