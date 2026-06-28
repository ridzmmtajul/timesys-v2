<script setup>
import { ref, onMounted, watch } from "vue";
import HolidayForm from "../holidays/Form/Create.vue";
import useHolidays from "../../../composables/holidays.js";

const { holidays, pagination, query, is_loading, getHolidays, destroyHoliday, updateHolidayStatus } = useHolidays();

const holiday = ref({});
const show_form_modal = ref(false);
const updating_status_ids = ref([]);

const headers = [
    { title: "Name", key: "name", sortable: true },
    { title: "Month", key: "month", sortable: true },
    { title: "Day", key: "day", sortable: true, align: "center" },
    { title: "Status", key: "is_active", sortable: true, align: "center" },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        holiday.value = {};
    }
};

onMounted(() => {
    getHolidays();
});

const editItem = (value) => {
    holiday.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyHoliday(value.id);
};

const toggleStatus = async (item, enabled) => {
    const previousStatus = item.is_active;
    const nextStatus = enabled;

    if (previousStatus === nextStatus) return;

    updating_status_ids.value = [...updating_status_ids.value, item.id];
    item.is_active = nextStatus;

    const isUpdated = await updateHolidayStatus({
        id:          item.id,
        name:        item.name,
        description: item.description,
        month:       item.month,
        day:         item.day,
        is_active:   nextStatus,
    });

    if (!isUpdated) {
        item.is_active = previousStatus;
    }

    updating_status_ids.value = updating_status_ids.value.filter((id) => id !== item.id);
};

const reloadHolidays = async () => {
    await getHolidays();
    holiday.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getHolidays();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-calendar-star" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Holiday Management</h5>
                    <p class="lib-header__subtitle">Manage public holidays and observances</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Holiday
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
                    placeholder="Search holidays..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="holidays"
                :search="query.search"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading holidays..."
                hide-default-footer
                item-value="id"
            >
                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-calendar-star" size="14" />
                        </div>
                        <span class="lib-table__name">{{ item.name }}</span>
                    </div>
                </template>

                <!-- Month Column -->
                <template v-slot:item.month="{ item }">
                    <span class="lib-table__muted">{{ item.month }}</span>
                </template>

                <!-- Day Column -->
                <template v-slot:item.day="{ item }">
                    <span class="lib-table__muted">{{ item.day }}</span>
                </template>

                <!-- Status Column -->
                <template v-slot:item.is_active="{ item }">
                    <div class="lib-table__status-cell">
                        <v-switch
                            :model-value="item.is_active"
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
                            <v-icon icon="mdi-calendar-remove-outline" size="32" />
                        </div>
                        <p class="lib-empty__title">No holidays found</p>
                        <p class="lib-empty__sub">Get started by creating your first holiday</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Holiday
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> holidays
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getHolidays"
                    class="lib-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Holiday Form Modal -->
    <holiday-form
        :value="show_form_modal"
        :holiday="holiday"
        @input="showModalForm"
        @reloadHolidays="reloadHolidays"
    />
</template>
