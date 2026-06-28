<script setup>
import { ref, reactive, watch, computed, onMounted, onBeforeUnmount } from "vue";
import useSchedules from "../../../../composables/schedules.js";

const { errors, is_loading, is_success, scheduleTypeOptions, getScheduleTypeOptions, storeSchedule, updateSchedule } = useSchedules();

const emit = defineEmits(["input", "reloadSchedules"]);
const props = defineProps({
    schedule: {
        type: Object,
        default: null,
    },
    value: {
        type: Boolean,
        default: false,
    },
});

const initialState = {
    id: null,
    name: null,
    description: null,
    default_timein_AM: null,
    default_timeout_AM: null,
    default_timein_PM: null,
    default_timeout_PM: null,
    schedule_type_id: null,
    no_lunch_gap: false,
};

const form = reactive({ ...initialState });

onMounted(() => {
    getScheduleTypeOptions();
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

watch(
    () => props.schedule,
    (value) => {
        form.id               = value?.id || null;
        form.name             = value?.name || null;
        form.description      = value?.description || null;
        form.default_timein_AM  = value?.default_timein_AM ? value.default_timein_AM.substring(0, 5) : null;
        form.default_timeout_AM = value?.default_timeout_AM ? value.default_timeout_AM.substring(0, 5) : null;
        form.default_timein_PM  = value?.default_timein_PM ? value.default_timein_PM.substring(0, 5) : null;
        form.default_timeout_PM = value?.default_timeout_PM ? value.default_timeout_PM.substring(0, 5) : null;
        form.schedule_type_id = value?.schedule_type_id || null;
        form.no_lunch_gap     = value?.no_lunch_gap || false;
    },
    { immediate: true }
);

const show_form_modal = ref(false);
watch(
    () => props.value,
    (value) => {
        show_form_modal.value = value;
    },
    { immediate: true }
);

const dialogTitle = computed(() => {
    return props.schedule?.id ? "Edit Schedule" : "Create New Schedule";
});

// Schedule type dropdown
const dropdown_open = ref(false);
const dropdown_ref = ref(null);

const selectedScheduleTypeLabel = computed(() => {
    const found = scheduleTypeOptions.value.find((t) => t.id === form.schedule_type_id);
    return found ? found.name : null;
});

const selectScheduleType = (id) => {
    form.schedule_type_id = id;
    dropdown_open.value = false;
};

const handleClickOutside = (e) => {
    if (dropdown_ref.value && !dropdown_ref.value.contains(e.target)) {
        dropdown_open.value = false;
    }
};

const close = () => {
    Object.assign(form, initialState);
    emit("input", false);
    errors.value = {};
};

const save = async () => {
    const data = {
        ...form,
        default_timein_AM:  form.default_timein_AM  || null,
        default_timeout_AM: form.default_timeout_AM || null,
        default_timein_PM:  form.default_timein_PM  || null,
        default_timeout_PM: form.default_timeout_PM || null,
    };

    if (props.schedule?.id) {
        await updateSchedule(data);
    } else {
        await storeSchedule(data);
    }

    if (is_success.value) {
        emit("reloadSchedules");
        emit("input", false);
    }
};
</script>

<template>
    <v-dialog v-model="show_form_modal" max-width="560px" persistent>
        <div class="lib-modal">
            <!-- Header -->
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon
                            :icon="props.schedule?.id ? 'mdi-clock-edit-outline' : 'mdi-clock-plus-outline'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Schedule Management</p>
                        <h6 class="lib-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="lib-modal__body">
                <!-- Name -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">
                        Name
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['name'] }">
                        <v-icon icon="mdi-clock-time-four-outline" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Morning Shift"
                            class="lib-modal__input"
                            @keyup.enter="save()"
                            autofocus
                        />
                    </div>
                    <p v-if="errors['name']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['name'][0] }}
                    </p>
                </div>

                <!-- Description -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">Description</label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['description'] }">
                        <v-icon icon="mdi-text" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Optional description"
                            class="lib-modal__input"
                        />
                    </div>
                    <p v-if="errors['description']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['description'][0] }}
                    </p>
                </div>

                <!-- Schedule Type -->
                <div class="lib-modal__field" ref="dropdown_ref">
                    <label class="lib-modal__label">Schedule Type</label>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['schedule_type_id'], 'is-open': dropdown_open }"
                        @click="dropdown_open = !dropdown_open"
                        style="cursor: pointer;"
                    >
                        <v-icon icon="mdi-calendar-clock-outline" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !selectedScheduleTypeLabel }">
                            {{ selectedScheduleTypeLabel || 'Select a schedule type' }}
                        </span>
                        <v-icon
                            icon="mdi-chevron-down"
                            size="16"
                            class="lib-dropdown__chevron"
                            :class="{ 'is-open': dropdown_open }"
                        />
                    </div>
                    <div v-if="dropdown_open" class="lib-dropdown__list">
                        <div
                            v-for="type in scheduleTypeOptions"
                            :key="type.id"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.schedule_type_id === type.id }"
                            @click="selectScheduleType(type.id)"
                        >
                            <span class="lib-dropdown__item-name">{{ type.name }}</span>
                            <v-icon
                                v-if="form.schedule_type_id === type.id"
                                icon="mdi-check"
                                size="13"
                                class="lib-dropdown__item-check"
                            />
                        </div>
                        <div v-if="scheduleTypeOptions.length === 0" class="lib-dropdown__empty">
                            No schedule types available
                        </div>
                    </div>
                    <p v-if="errors['schedule_type_id']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['schedule_type_id'][0] }}
                    </p>
                </div>

                <!-- AM Shift -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">AM Shift</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['default_timein_AM'] }">
                                <v-icon icon="mdi-login" size="16" class="lib-modal__input-icon" />
                                <input
                                    v-model="form.default_timein_AM"
                                    type="time"
                                    class="lib-modal__input"
                                />
                            </div>
                            <p v-if="errors['default_timein_AM']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['default_timein_AM'][0] }}
                            </p>
                        </div>
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['default_timeout_AM'] }">
                                <v-icon icon="mdi-logout" size="16" class="lib-modal__input-icon" />
                                <input
                                    v-model="form.default_timeout_AM"
                                    type="time"
                                    class="lib-modal__input"
                                />
                            </div>
                            <p v-if="errors['default_timeout_AM']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['default_timeout_AM'][0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- PM Shift -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">PM Shift</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['default_timein_PM'] }">
                                <v-icon icon="mdi-login" size="16" class="lib-modal__input-icon" />
                                <input
                                    v-model="form.default_timein_PM"
                                    type="time"
                                    class="lib-modal__input"
                                />
                            </div>
                            <p v-if="errors['default_timein_PM']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['default_timein_PM'][0] }}
                            </p>
                        </div>
                        <div>
                            <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['default_timeout_PM'] }">
                                <v-icon icon="mdi-logout" size="16" class="lib-modal__input-icon" />
                                <input
                                    v-model="form.default_timeout_PM"
                                    type="time"
                                    class="lib-modal__input"
                                />
                            </div>
                            <p v-if="errors['default_timeout_PM']" class="lib-modal__error">
                                <v-icon icon="mdi-alert-circle-outline" size="12" />
                                {{ errors['default_timeout_PM'][0] }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- No Lunch Gap -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">No Lunch Gap</label>
                    <div class="toggle-wrap" @click="form.no_lunch_gap = !form.no_lunch_gap">
                        <input type="checkbox" v-model="form.no_lunch_gap" class="toggle-input" id="no_lunch_gap" />
                        <label for="no_lunch_gap" class="toggle-label" @click.stop>
                            {{ form.no_lunch_gap ? 'Enabled — no break between AM and PM shift' : 'Disabled — standard lunch break applies' }}
                        </label>
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
                    :disabled="!form.name?.trim() || is_loading"
                    @click="save()"
                >
                    <v-icon
                        v-if="is_loading"
                        icon="mdi-loading"
                        size="14"
                        class="lib-modal__spinner"
                    />
                    <v-icon
                        v-else
                        :icon="props.schedule?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.schedule?.id ? 'Update' : 'Save' }}
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
</style>
