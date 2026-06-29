<script setup>
import { ref, reactive, watch, computed, onMounted, onBeforeUnmount } from "vue";
import useWorkSchedules from "../../../composables/work-schedules.js";

const {
    errors, is_loading, is_success,
    employeeOptions, scheduleOptions, scheduleTypeOptions,
    getWorkScheduleOptions, storeWorkSchedule, updateWorkSchedule,
} = useWorkSchedules();

const emit = defineEmits(["input", "reloadWorkSchedules"]);
const props = defineProps({
    workSchedule: { type: Object, default: null },
    value: { type: Boolean, default: false },
});

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const initialState = {
    id: null,
    employee_id: null,
    schedule_id: null,
    schedule_type_id: null,
    schedule_for: null,
    timein_AM: null,
    timeout_AM: null,
    timein_PM: null,
    timeout_PM: null,
    from_date: null,
    to_date: null,
    is_others: false,
    no_lunch_gap: false,
    days: [],
};

const form = reactive({ ...initialState });

onMounted(() => {
    getWorkScheduleOptions();
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

const trimTime = (t) => t ? t.substring(0, 5) : null;

watch(
    () => props.workSchedule,
    (value) => {
        form.id               = value?.id || null;
        form.employee_id      = value?.employee_id || null;
        form.schedule_id      = value?.schedule_id || null;
        form.schedule_type_id = value?.schedule_type_id || null;
        form.schedule_for     = value?.schedule_for || null;
        form.timein_AM        = trimTime(value?.timein_AM);
        form.timeout_AM       = trimTime(value?.timeout_AM);
        form.timein_PM        = trimTime(value?.timein_PM);
        form.timeout_PM       = trimTime(value?.timeout_PM);
        form.from_date        = value?.from_date || null;
        form.to_date          = value?.to_date || null;
        form.is_others        = value?.is_others || false;
        form.no_lunch_gap     = value?.no_lunch_gap || false;
        form.days             = value?.days ? [...value.days] : [];
    },
    { immediate: true }
);

const show_form_modal = ref(false);
watch(() => props.value, (v) => { show_form_modal.value = v; }, { immediate: true });

const dialogTitle = computed(() => props.workSchedule?.id ? 'Edit Work Schedule' : 'Create New Work Schedule');

// --- Employee dropdown ---
const emp_dropdown_open = ref(false);
const emp_dropdown_ref = ref(null);
const emp_search = ref('');

const filteredEmployees = computed(() => {
    if (!emp_search.value) return employeeOptions.value;
    const q = emp_search.value.toLowerCase();
    return employeeOptions.value.filter(e =>
        `${e.first_name} ${e.last_name}`.toLowerCase().includes(q) ||
        e.employee_no?.toLowerCase().includes(q)
    );
});

const selectedEmployeeLabel = computed(() => {
    const e = employeeOptions.value.find(e => e.id === form.employee_id);
    return e ? `${e.last_name}, ${e.first_name}` : null;
});

const selectEmployee = (id) => {
    form.employee_id = id;
    emp_dropdown_open.value = false;
    emp_search.value = '';
};

// --- Schedule dropdown ---
const sched_dropdown_open = ref(false);
const sched_dropdown_ref = ref(null);

const selectedScheduleLabel = computed(() => {
    const s = scheduleOptions.value.find(s => s.id === form.schedule_id);
    return s ? s.name : null;
});

const selectSchedule = (id) => {
    form.schedule_id = id;
    sched_dropdown_open.value = false;
    if (id) {
        const s = scheduleOptions.value.find(s => s.id === id);
        if (s) {
            form.schedule_type_id = s.schedule_type_id || null;
            form.timein_AM        = trimTime(s.default_timein_AM);
            form.timeout_AM       = trimTime(s.default_timeout_AM);
            form.timein_PM        = trimTime(s.default_timein_PM);
            form.timeout_PM       = trimTime(s.default_timeout_PM);
        }
    } else {
        form.schedule_type_id = null;
        form.timein_AM = null;
        form.timeout_AM = null;
        form.timein_PM = null;
        form.timeout_PM = null;
    }
};

// --- Schedule Type dropdown ---
const st_dropdown_open = ref(false);
const st_dropdown_ref = ref(null);

const selectedScheduleTypeLabel = computed(() => {
    const s = scheduleTypeOptions.value.find(s => s.id === form.schedule_type_id);
    return s ? s.name : null;
});

const selectScheduleType = (id) => {
    form.schedule_type_id = id;
    st_dropdown_open.value = false;
    const s = scheduleTypeOptions.value.find(s => s.id === id);
    if (s?.name?.toLowerCase() !== 'regular') form.no_lunch_gap = false;
};

const isRegularScheduleType = computed(() => {
    const s = scheduleTypeOptions.value.find(s => s.id === form.schedule_type_id);
    return s?.name?.toLowerCase() === 'regular';
});

const handleClickOutside = (e) => {
    if (emp_dropdown_ref.value && !emp_dropdown_ref.value.contains(e.target)) {
        emp_dropdown_open.value = false;
        emp_search.value = '';
    }
    if (sched_dropdown_ref.value && !sched_dropdown_ref.value.contains(e.target)) {
        sched_dropdown_open.value = false;
    }
    if (st_dropdown_ref.value && !st_dropdown_ref.value.contains(e.target)) {
        st_dropdown_open.value = false;
    }
};

const SCHEDULE_FOR_OPTIONS = [
    { value: 'everyday',       label: 'Everyday',        icon: 'mdi-calendar-today' },
    { value: 'day_in_week',    label: 'Day in a Week',   icon: 'mdi-calendar-week' },
    { value: 'inclusive_date', label: 'Inclusive Date',  icon: 'mdi-calendar-range' },
];

const selectScheduleFor = (value) => {
    form.schedule_for = value;
    if (value !== 'day_in_week') form.days = [];
    if (value !== 'inclusive_date') {
        form.from_date = null;
        form.to_date = null;
    }
};

const toggleIsOthers = () => {
    form.is_others = !form.is_others;
    if (form.is_others) {
        form.schedule_id = null;
    } else {
        form.schedule_type_id = null;
        form.timein_AM = null;
        form.timeout_AM = null;
        form.timein_PM = null;
        form.timeout_PM = null;
    }
};

const toggleDay = (day) => {
    const idx = form.days.indexOf(day);
    if (idx >= 0) {
        form.days.splice(idx, 1);
    } else {
        form.days.push(day);
    }
};

const close = () => {
    Object.assign(form, { ...initialState, days: [] });
    emit('input', false);
    errors.value = {};
    emp_search.value = '';
};

const save = async () => {
    const data = { ...form };
    if (props.workSchedule?.id) {
        await updateWorkSchedule(data);
    } else {
        await storeWorkSchedule(data);
    }
    if (is_success.value) {
        emit('reloadWorkSchedules');
        emit('input', false);
    }
};
</script>

<template>
    <v-dialog v-model="show_form_modal" max-width="580px" persistent>
        <div class="lib-modal">
            <!-- Header -->
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon
                            :icon="props.workSchedule?.id ? 'mdi-calendar-edit' : 'mdi-calendar-plus'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Work Schedule</p>
                        <h6 class="lib-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="lib-modal__body" style="max-height:70vh;overflow-y:auto;scrollbar-width: thin;
  scrollbar-color: rgba(108, 143, 214, 0.25) transparent;">

                <!-- Employee -->
                <!-- <div class="lib-modal__field" ref="emp_dropdown_ref">
                    <label class="lib-modal__label">
                        Employee <span class="lib-modal__required">*</span>
                    </label>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['employee_id'], 'is-open': emp_dropdown_open }"
                        @click="emp_dropdown_open = !emp_dropdown_open"
                        style="cursor:pointer;"
                    >
                        <v-icon icon="mdi-account" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !selectedEmployeeLabel }">
                            {{ selectedEmployeeLabel || 'Select an employee' }}
                        </span>
                        <v-icon icon="mdi-chevron-down" size="16" class="lib-dropdown__chevron" :class="{ 'is-open': emp_dropdown_open }" />
                    </div>
                    <div v-if="emp_dropdown_open" class="lib-dropdown__list">
                        <div class="lib-dropdown__search-wrap" @click.stop>
                            <input
                                v-model="emp_search"
                                type="text"
                                placeholder="Search employee..."
                                class="lib-dropdown__search"
                                autofocus
                            />
                        </div>
                        <div
                            v-for="emp in filteredEmployees"
                            :key="emp.id"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.employee_id === emp.id }"
                            @click="selectEmployee(emp.id)"
                        >
                            <div>
                                <span class="lib-dropdown__item-name">{{ emp.last_name }}, {{ emp.first_name }}</span>
                                <span class="lib-dropdown__item-code">{{ emp.employee_no }}</span>
                            </div>
                            <v-icon v-if="form.employee_id === emp.id" icon="mdi-check" size="13" class="lib-dropdown__item-check" />
                        </div>
                        <div v-if="filteredEmployees.length === 0" class="lib-dropdown__empty">No employees found</div>
                    </div>
                    <p v-if="errors['employee_id']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['employee_id'][0] }}
                    </p>
                </div> -->

                <!-- Is Others -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">Is Others</label>
                    <div class="toggle-wrap" @click="toggleIsOthers">
                        <input type="checkbox" v-model="form.is_others" class="toggle-input" id="is_others" @click.stop />
                        <label for="is_others" class="toggle-label" @click.stop>
                            {{ form.is_others ? 'Yes — add manual schedule' : 'No — select schedule' }}
                        </label>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="lib-modal__field" ref="sched_dropdown_ref">
                    <label class="lib-modal__label">Schedule</label>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['schedule_id'], 'is-open': sched_dropdown_open, 'is-disabled': form.is_others }"
                        @click="!form.is_others && (sched_dropdown_open = !sched_dropdown_open)"
                        :style="form.is_others ? 'cursor:not-allowed;opacity:0.45;' : 'cursor:pointer;'"
                    >
                        <v-icon icon="mdi-clock-outline" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !selectedScheduleLabel }">
                            {{ selectedScheduleLabel || 'Select a schedule (optional)' }}
                        </span>
                        <v-icon icon="mdi-chevron-down" size="16" class="lib-dropdown__chevron" :class="{ 'is-open': sched_dropdown_open }" />
                    </div>
                    <div v-if="sched_dropdown_open" class="lib-dropdown__list">
                        <div
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.schedule_id === null }"
                            @click="selectSchedule(null)"
                        >
                            <span class="lib-dropdown__item-name" style="color:#8aa0d7;">None</span>
                        </div>
                        <div
                            v-for="s in scheduleOptions"
                            :key="s.id"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.schedule_id === s.id }"
                            @click="selectSchedule(s.id)"
                        >
                            <span class="lib-dropdown__item-name">{{ s.name }}</span>
                            <v-icon v-if="form.schedule_id === s.id" icon="mdi-check" size="13" class="lib-dropdown__item-check" />
                        </div>
                        <div v-if="scheduleOptions.length === 0" class="lib-dropdown__empty">No schedules available</div>
                    </div>
                </div>

                <!-- Schedule Type -->
                <div class="lib-modal__field" ref="st_dropdown_ref">
                    <label class="lib-modal__label">Schedule Type</label>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['schedule_type_id'], 'is-open': st_dropdown_open, 'is-disabled': !form.is_others }"
                        @click="form.is_others && (st_dropdown_open = !st_dropdown_open)"
                        :style="!form.is_others ? 'cursor:not-allowed;opacity:0.45;' : 'cursor:pointer;'"
                    >
                        <v-icon icon="mdi-calendar-clock-outline" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !selectedScheduleTypeLabel }">
                            {{ selectedScheduleTypeLabel || 'Select a schedule type (optional)' }}
                        </span>
                        <v-icon icon="mdi-chevron-down" size="16" class="lib-dropdown__chevron" :class="{ 'is-open': st_dropdown_open }" />
                    </div>
                    <div v-if="st_dropdown_open" class="lib-dropdown__list">
                        <div
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.schedule_type_id === null }"
                            @click="selectScheduleType(null)"
                        >
                            <span class="lib-dropdown__item-name" style="color:#8aa0d7;">None</span>
                        </div>
                        <div
                            v-for="st in scheduleTypeOptions"
                            :key="st.id"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.schedule_type_id === st.id }"
                            @click="selectScheduleType(st.id)"
                        >
                            <span class="lib-dropdown__item-name">{{ st.name }}</span>
                            <v-icon v-if="form.schedule_type_id === st.id" icon="mdi-check" size="13" class="lib-dropdown__item-check" />
                        </div>
                        <div v-if="scheduleTypeOptions.length === 0" class="lib-dropdown__empty">No schedule types available</div>
                    </div>
                </div>

                <!-- AM Shift -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">AM Shift</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['timein_AM'], 'is-disabled': !form.is_others }" :style="!form.is_others ? 'opacity:0.45;' : ''">
                                <v-icon icon="mdi-login" size="16" class="lib-modal__input-icon" />
                                <input v-model="form.timein_AM" type="time" class="lib-modal__input" :disabled="!form.is_others" />
                            </div>
                            <p v-if="errors['timein_AM']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['timein_AM'][0] }}
                            </p>
                        </div>
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['timeout_AM'], 'is-disabled': !form.is_others }" :style="!form.is_others ? 'opacity:0.45;' : ''">
                                <v-icon icon="mdi-logout" size="16" class="lib-modal__input-icon" />
                                <input v-model="form.timeout_AM" type="time" class="lib-modal__input" :disabled="!form.is_others" />
                            </div>
                            <p v-if="errors['timeout_AM']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['timeout_AM'][0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PM Shift -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">PM Shift</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['timein_PM'], 'is-disabled': !form.is_others }" :style="!form.is_others ? 'opacity:0.45;' : ''">
                                <v-icon icon="mdi-login" size="16" class="lib-modal__input-icon" />
                                <input v-model="form.timein_PM" type="time" class="lib-modal__input" :disabled="!form.is_others" />
                            </div>
                            <p v-if="errors['timein_PM']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['timein_PM'][0] }}
                            </p>
                        </div>
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['timeout_PM'], 'is-disabled': !form.is_others }" :style="!form.is_others ? 'opacity:0.45;' : ''">
                                <v-icon icon="mdi-logout" size="16" class="lib-modal__input-icon" />
                                <input v-model="form.timeout_PM" type="time" class="lib-modal__input" :disabled="!form.is_others" />
                            </div>
                            <p v-if="errors['timeout_PM']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['timeout_PM'][0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- No Lunch Gap -->
                <div v-if="isRegularScheduleType" class="lib-modal__field">
                    <label class="lib-modal__label">No Lunch Gap</label>
                    <div class="toggle-wrap" @click="form.no_lunch_gap = !form.no_lunch_gap">
                        <input type="checkbox" v-model="form.no_lunch_gap" class="toggle-input" id="no_lunch_gap" />
                        <label for="no_lunch_gap" class="toggle-label" @click.stop>
                            {{ form.no_lunch_gap ? 'Enabled — no second gap between lunch break' : 'Disabled — standard lunch break gap applies' }}
                        </label>
                    </div>
                </div>

                <!-- Schedule For -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">
                        Schedule For <span class="lib-modal__required">*</span>
                    </label>
                    <div class="sched-for-group">
                        <div
                            v-for="opt in SCHEDULE_FOR_OPTIONS"
                            :key="opt.value"
                            class="sched-for-chip"
                            :class="{ 'is-selected': form.schedule_for === opt.value }"
                            @click="selectScheduleFor(opt.value)"
                        >
                            <v-icon :icon="opt.icon" size="14" />
                            {{ opt.label }}
                        </div>
                    </div>
                    <p v-if="errors['schedule_for']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['schedule_for'][0] }}
                    </p>
                </div>

                <!-- Date Range -->
                <div v-if="form.schedule_for === 'inclusive_date'" class="lib-modal__field">
                    <label class="lib-modal__label">Date Range</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['from_date'] }">
                                <v-icon icon="mdi-calendar-start" size="16" class="lib-modal__input-icon" />
                                <input v-model="form.from_date" type="date" class="lib-modal__input" />
                            </div>
                            <p v-if="errors['from_date']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['from_date'][0] }}
                            </p>
                        </div>
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['to_date'] }">
                                <v-icon icon="mdi-calendar-end" size="16" class="lib-modal__input-icon" />
                                <input v-model="form.to_date" type="date" class="lib-modal__input" />
                            </div>
                            <p v-if="errors['to_date']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['to_date'][0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Days -->
                <div v-if="form.schedule_for === 'day_in_week'" class="lib-modal__field">
                    <label class="lib-modal__label">Working Days</label>
                    <div class="days-grid">
                        <div
                            v-for="day in DAYS"
                            :key="day"
                            class="day-chip"
                            :class="{ 'is-selected': form.days.includes(day) }"
                            @click="toggleDay(day)"
                        >
                            {{ day.substring(0, 3) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="lib-modal__footer">
                <button class="lib-modal__btn lib-modal__btn--cancel" :disabled="is_loading" @click="close()">
                    Cancel
                </button>
                <button
                    class="lib-modal__btn lib-modal__btn--save"
                    :disabled="!form.employee_id || !form.schedule_for?.trim() || is_loading"
                    @click="save()"
                >
                    <v-icon v-if="is_loading" icon="mdi-loading" size="14" class="lib-modal__spinner" />
                    <v-icon v-else :icon="props.workSchedule?.id ? 'mdi-content-save-outline' : 'mdi-check'" size="14" />
                    {{ props.workSchedule?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>

<style scoped>
.toggle-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.15);
    cursor: pointer;
    user-select: none;
}
.toggle-input {
    accent-color: #1fbfb8;
    width: 16px;
    height: 16px;
    cursor: pointer;
    flex-shrink: 0;
}
.toggle-label {
    color: #c8d6f0;
    font-size: 13px;
    cursor: pointer;
}
.days-grid {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.day-chip {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.2);
    color: #8aa0d7;
    transition: all 0.15s;
}
.day-chip:hover {
    border-color: rgba(31, 191, 184, 0.4);
    color: #c8d6f0;
}
.day-chip.is-selected {
    background: rgba(31, 191, 184, 0.15);
    border-color: #1fbfb8;
    color: #1fbfb8;
}
.lib-dropdown__search-wrap {
    padding: 6px 8px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
}
.lib-dropdown__search {
    width: 100%;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(108, 143, 214, 0.2);
    border-radius: 6px;
    padding: 6px 10px;
    color: #e2e8f0;
    font-size: 13px;
    outline: none;
}
.lib-dropdown__search::placeholder {
    color: #5a78b0;
}
.sched-for-group {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
}
.sched-for-chip {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.2);
    color: #8aa0d7;
    transition: all 0.15s;
    text-align: center;
}
.sched-for-chip:hover {
    border-color: rgba(31, 191, 184, 0.4);
    color: #c8d6f0;
}
.sched-for-chip.is-selected {
    background: rgba(31, 191, 184, 0.15);
    border-color: #1fbfb8;
    color: #1fbfb8;
}
</style>