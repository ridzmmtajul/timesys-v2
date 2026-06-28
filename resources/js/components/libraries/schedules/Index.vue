<script setup>
import { ref, onMounted, watch } from "vue";
import ScheduleForm from "../schedules/Form/Create.vue";
import useSchedules from "../../../composables/schedules.js";

const { schedules, pagination, query, is_loading, getSchedules, destroySchedule } = useSchedules();

const schedule = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Name", key: "name", sortable: true },
    { title: "Schedule Type", key: "schedule_type", sortable: false },
    { title: "AM Shift", key: "am_shift", sortable: false },
    { title: "PM Shift", key: "pm_shift", sortable: false },
    { title: "No Lunch Gap", key: "no_lunch_gap", sortable: false, align: "center" },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        schedule.value = {};
    }
};

onMounted(() => {
    getSchedules();
});

const editItem = (value) => {
    schedule.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroySchedule(value.id);
};

const reloadSchedules = async () => {
    await getSchedules();
    schedule.value = {};
};

const formatTime = (time) => {
    if (!time) return '—';
    return time.substring(0, 5);
};

watch(() => query.search, () => {
    query.page = 1;
    getSchedules();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-clock-time-four-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Schedule Management</h5>
                    <p class="lib-header__subtitle">Manage work schedules and shift timings</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Schedule
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
                    placeholder="Search schedules..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="schedules"
                :search="query.search"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading schedules..."
                hide-default-footer
                item-value="id"
            >
                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-clock-time-four-outline" size="14" />
                        </div>
                        <div>
                            <span class="lib-table__name">{{ item.name }}</span>
                            <span v-if="item.description" class="lib-table__muted" style="display:block;font-size:11px;">{{ item.description }}</span>
                        </div>
                    </div>
                </template>

                <!-- Schedule Type Column -->
                <template v-slot:item.schedule_type="{ item }">
                    <span class="lib-table__muted">{{ item.schedule_type?.name || '—' }}</span>
                </template>

                <!-- AM Shift Column -->
                <template v-slot:item.am_shift="{ item }">
                    <span class="lib-table__muted">
                        {{ formatTime(item.default_timein_AM) }} – {{ formatTime(item.default_timeout_AM) }}
                    </span>
                </template>

                <!-- PM Shift Column -->
                <template v-slot:item.pm_shift="{ item }">
                    <span class="lib-table__muted">
                        {{ formatTime(item.default_timein_PM) }} – {{ formatTime(item.default_timeout_PM) }}
                    </span>
                </template>

                <!-- No Lunch Gap Column -->
                <template v-slot:item.no_lunch_gap="{ item }">
                    <span
                        class="lib-table__badge"
                        :style="item.no_lunch_gap
                            ? 'background:rgba(31,191,184,0.12);color:#1fbfb8;'
                            : 'background:rgba(138,160,215,0.10);color:#8aa0d7;'"
                    >
                        {{ item.no_lunch_gap ? 'Yes' : 'No' }}
                    </span>
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
                            <v-icon icon="mdi-clock-remove-outline" size="32" />
                        </div>
                        <p class="lib-empty__title">No schedules found</p>
                        <p class="lib-empty__sub">Get started by creating your first schedule</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Schedule
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> schedules
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getSchedules"
                    class="lib-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Schedule Form Modal -->
    <schedule-form
        :value="show_form_modal"
        :schedule="schedule"
        @input="showModalForm"
        @reloadSchedules="reloadSchedules"
    />
</template>
