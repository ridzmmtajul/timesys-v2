<script setup>
import { ref, onMounted, watch } from "vue";
import WorkScheduleForm from "./Form/Create.vue";
import useWorkSchedules from "../../composables/work-schedules.js";

const { workSchedules, pagination, query, is_loading, getWorkSchedules, destroyWorkSchedule } = useWorkSchedules();

const workSchedule = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Employee", key: "employee", sortable: false },
    { title: "Schedule For", key: "schedule_for", sortable: true },
    { title: "Schedule", key: "schedule", sortable: false },
    { title: "Date Range", key: "date_range", sortable: false },
    { title: "Days", key: "days", sortable: false },
    { title: "AM Shift", key: "am_shift", sortable: false },
    { title: "PM Shift", key: "pm_shift", sortable: false },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (!val) workSchedule.value = {};
};

onMounted(() => {
    getWorkSchedules();
});

const editItem = (value) => {
    workSchedule.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyWorkSchedule(value.id);
};

const reloadWorkSchedules = async () => {
    await getWorkSchedules();
    workSchedule.value = {};
};

const formatTime = (time) => {
    if (!time) return '—';
    return time.substring(0, 5);
};

const formatDate = (date) => {
    if (!date) return '—';
    return date;
};

watch(() => query.search, () => {
    query.page = 1;
    getWorkSchedules();
});
</script>

<template>
    <div class="lib-page">
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-calendar-account" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Work Schedules</h5>
                    <p class="lib-header__subtitle">Manage employee work schedule assignments</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Work Schedule
            </button>
        </div>

        <div class="lib-card">
            <div class="lib-search">
                <v-icon icon="mdi-magnify" size="17" class="lib-search__icon" />
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Search by employee or schedule..."
                    class="lib-search__input"
                />
            </div>

            <v-data-table
                :headers="headers"
                :items="workSchedules"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading work schedules..."
                hide-default-footer
                item-value="id"
            >
                <!-- Employee -->
                <template v-slot:item.employee="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-account" size="14" />
                        </div>
                        <div>
                            <span class="lib-table__name">
                                {{ item.employee ? `${item.employee.last_name}, ${item.employee.first_name}` : '—' }}
                            </span>
                            <span class="lib-table__muted" style="display:block;font-size:11px;">
                                {{ item.employee?.employee_no || '' }}
                            </span>
                        </div>
                    </div>
                </template>

                <!-- Schedule For -->
                <template v-slot:item.schedule_for="{ item }">
                    <span class="lib-table__name">{{ item.schedule_for }}</span>
                    <span v-if="item.schedule_type" class="lib-table__muted" style="display:block;font-size:11px;">
                        {{ item.schedule_type.name }}
                    </span>
                </template>

                <!-- Schedule -->
                <template v-slot:item.schedule="{ item }">
                    <span class="lib-table__muted">{{ item.schedule?.name || '—' }}</span>
                </template>

                <!-- Date Range -->
                <template v-slot:item.date_range="{ item }">
                    <span class="lib-table__muted">
                        {{ formatDate(item.from_date) }} – {{ formatDate(item.to_date) }}
                    </span>
                </template>

                <!-- Days -->
                <template v-slot:item.days="{ item }">
                    <div v-if="item.days && item.days.length" style="display:flex;flex-wrap:wrap;gap:3px;">
                        <span
                            v-for="day in item.days"
                            :key="day"
                            class="lib-table__badge"
                            style="font-size:10px;padding:2px 6px;"
                        >
                            {{ day.substring(0, 3) }}
                        </span>
                    </div>
                    <span v-else class="lib-table__muted">—</span>
                </template>

                <!-- AM Shift -->
                <template v-slot:item.am_shift="{ item }">
                    <span class="lib-table__muted">
                        {{ formatTime(item.timein_AM) }} – {{ formatTime(item.timeout_AM) }}
                    </span>
                </template>

                <!-- PM Shift -->
                <template v-slot:item.pm_shift="{ item }">
                    <span class="lib-table__muted">
                        {{ formatTime(item.timein_PM) }} – {{ formatTime(item.timeout_PM) }}
                    </span>
                </template>

                <!-- Actions -->
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
                        <p class="lib-empty__title">No work schedules found</p>
                        <p class="lib-empty__sub">Assign schedules to employees to get started</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Work Schedule
                        </button>
                    </div>
                </template>
            </v-data-table>

            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> records
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getWorkSchedules"
                    class="lib-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <work-schedule-form
        :value="show_form_modal"
        :work-schedule="workSchedule"
        @input="showModalForm"
        @reloadWorkSchedules="reloadWorkSchedules"
    />
</template>