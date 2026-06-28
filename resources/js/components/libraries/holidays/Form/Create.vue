<script setup>
import { ref, reactive, watch, computed, onMounted, onBeforeUnmount } from "vue";
import useHolidays from "../../../../composables/holidays.js";

const { errors, is_loading, is_success, storeHoliday, updateHoliday } = useHolidays();

const emit = defineEmits(["input", "reloadHolidays"]);
const props = defineProps({
    holiday: {
        type: Object,
        default: null,
    },
    value: {
        type: Boolean,
        default: false,
    },
});

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];


const initialState = {
    id:          null,
    name:        null,
    description: null,
    month:       null,
    day:         null,
    is_active:   true,
};

const form = reactive({ ...initialState });

watch(
    () => props.holiday,
    (value) => {
        form.id          = value?.id || null;
        form.name        = value?.name || null;
        form.description = value?.description || null;
        form.month       = value?.month || null;
        form.day         = value?.day || null;
        form.is_active   = value?.is_active ?? true;
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
    return props.holiday?.id ? "Edit Holiday" : "Create New Holiday";
});

// Month dropdown
const month_dropdown_open = ref(false);
const month_dropdown_ref = ref(null);

const selectMonth = (month) => {
    form.month = month;
    month_dropdown_open.value = false;
};

const handleClickOutside = (e) => {
    if (month_dropdown_ref.value && !month_dropdown_ref.value.contains(e.target)) {
        month_dropdown_open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

const close = () => {
    Object.assign(form, initialState);
    emit("input", false);
    errors.value = {};
};

const save = async () => {
    if (props.holiday?.id) {
        await updateHoliday({ ...form });
    } else {
        await storeHoliday({ ...form });
    }

    if (is_success.value) {
        emit("reloadHolidays");
        emit("input", false);
    }
};
</script>

<template>
    <v-dialog v-model="show_form_modal" max-width="480px" persistent>
        <div class="lib-modal">
            <!-- Header -->
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon
                            :icon="props.holiday?.id ? 'mdi-calendar-edit' : 'mdi-calendar-plus'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Holiday Management</p>
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
                        Holiday Name
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['name'] }">
                        <v-icon icon="mdi-calendar-star" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. New Year's Day"
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

                <!-- Month & Day row -->
                <div style="display: flex; gap: 12px;">
                    <!-- Month -->
                    <div class="lib-modal__field" style="flex: 1;" ref="month_dropdown_ref">
                        <label class="lib-modal__label">
                            Month
                            <span class="lib-modal__required">*</span>
                        </label>
                        <div
                            class="lib-modal__input-wrap"
                            :class="{ 'is-error': errors['month'], 'is-open': month_dropdown_open }"
                            @click="month_dropdown_open = !month_dropdown_open"
                            style="cursor: pointer;"
                        >
                            <v-icon icon="mdi-calendar-month-outline" size="16" class="lib-modal__input-icon" />
                            <span class="lib-dropdown__value" :class="{ 'is-placeholder': !form.month }">
                                {{ form.month || 'Select month' }}
                            </span>
                            <v-icon
                                icon="mdi-chevron-down"
                                size="16"
                                class="lib-dropdown__chevron"
                                :class="{ 'is-open': month_dropdown_open }"
                            />
                        </div>
                        <div v-if="month_dropdown_open" class="lib-dropdown__list">
                            <div
                                v-for="m in months"
                                :key="m"
                                class="lib-dropdown__item"
                                :class="{ 'is-selected': form.month === m }"
                                @click="selectMonth(m)"
                            >
                                <span class="lib-dropdown__item-name">{{ m }}</span>
                                <v-icon
                                    v-if="form.month === m"
                                    icon="mdi-check"
                                    size="13"
                                    class="lib-dropdown__item-check"
                                />
                            </div>
                        </div>
                        <p v-if="errors['month']" class="lib-modal__error">
                            <v-icon icon="mdi-alert-circle-outline" size="12" />
                            {{ errors['month'][0] }}
                        </p>
                    </div>

                    <!-- Day -->
                    <div class="lib-modal__field" style="flex: 1;">
                        <label class="lib-modal__label">
                            Day
                            <span class="lib-modal__required">*</span>
                        </label>
                        <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['day'] }">
                            <v-icon icon="mdi-calendar-today" size="16" class="lib-modal__input-icon" />
                            <input
                                v-model.number="form.day"
                                type="number"
                                min="1"
                                max="31"
                                placeholder="1–31"
                                class="lib-modal__input"
                                @keyup.enter="save()"
                            />
                        </div>
                        <p v-if="errors['day']" class="lib-modal__error">
                            <v-icon icon="mdi-alert-circle-outline" size="12" />
                            {{ errors['day'][0] }}
                        </p>
                    </div>
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
                            @keyup.enter="save()"
                        />
                    </div>
                    <p v-if="errors['description']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['description'][0] }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="lib-modal__footer">
                <button class="lib-modal__btn lib-modal__btn--cancel" :disabled="is_loading" @click="close()">
                    Cancel
                </button>
                <button
                    class="lib-modal__btn lib-modal__btn--save"
                    :disabled="!form.name?.trim() || !form.month || !form.day || is_loading"
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
                        :icon="props.holiday?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.holiday?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>
