<script setup>
import { ref, reactive, watch, computed, onMounted, onBeforeUnmount } from "vue";
import useWorkTimeRules from "../../../../composables/work-time-rules.js";

const { errors, is_loading, is_success, officeOptions, getOfficeOptions, storeWorkTimeRule, updateWorkTimeRule } = useWorkTimeRules();

const emit = defineEmits(["input", "reloadWorkTimeRules"]);
const props = defineProps({
    workTimeRule: {
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
    rule: null,
    description: null,
    time: null,
    offices: [],
};

const all_offices = ref(true);

const form = reactive({ ...initialState });

onMounted(() => {
    getOfficeOptions();
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

watch(
    () => props.workTimeRule,
    (value) => {
        form.id          = value?.id || null;
        form.rule        = value?.rule || null;
        form.description = value?.description || null;
        form.time        = value?.time || null;
        form.offices     = value?.offices ? [...value.offices] : [];
        all_offices.value = !value?.offices || value.offices.length === 0;
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
    return props.workTimeRule?.id ? "Edit Work Time Rule" : "Create New Work Time Rule";
});

// Rule dropdown
const ruleOptions = ['Grace Period', 'Work Time'];
const rule_dropdown_open = ref(false);
const rule_dropdown_ref = ref(null);

const selectRule = (option) => {
    form.rule = option;
    rule_dropdown_open.value = false;
};

// Offices multi-select dropdown
const dropdown_open = ref(false);
const dropdown_ref = ref(null);

const selectedOfficesLabel = computed(() => {
    if (!form.offices || form.offices.length === 0) return null;
    if (form.offices.length === 1) {
        const found = officeOptions.value.find((o) => o.id === form.offices[0]);
        return found ? found.name : '1 office';
    }
    return `${form.offices.length} offices selected`;
});

const toggleOffice = (id) => {
    const idx = form.offices.indexOf(id);
    if (idx === -1) {
        form.offices.push(id);
    } else {
        form.offices.splice(idx, 1);
    }
};

const isOfficeSelected = (id) => form.offices.includes(id);

const handleClickOutside = (e) => {
    if (rule_dropdown_ref.value && !rule_dropdown_ref.value.contains(e.target)) {
        rule_dropdown_open.value = false;
    }
    if (dropdown_ref.value && !dropdown_ref.value.contains(e.target)) {
        dropdown_open.value = false;
    }
};

const close = () => {
    Object.assign(form, { ...initialState, offices: [] });
    all_offices.value = true;
    emit("input", false);
    errors.value = {};
};

const save = async () => {
    const data = {
        ...form,
        offices: all_offices.value ? null : (form.offices.length > 0 ? form.offices : null),
    };

    if (props.workTimeRule?.id) {
        await updateWorkTimeRule(data);
    } else {
        await storeWorkTimeRule(data);
    }

    if (is_success.value) {
        emit("reloadWorkTimeRules");
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
                            :icon="props.workTimeRule?.id ? 'mdi-clock-edit-outline' : 'mdi-clock-plus-outline'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Work Time Rules</p>
                        <h6 class="lib-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="lib-modal__body">
                <!-- Rule -->
                <div class="lib-modal__field" ref="rule_dropdown_ref">
                    <label class="lib-modal__label">
                        Rule
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['rule'], 'is-open': rule_dropdown_open }"
                        @click="rule_dropdown_open = !rule_dropdown_open"
                        style="cursor: pointer;"
                    >
                        <v-icon icon="mdi-clock-check-outline" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !form.rule }">
                            {{ form.rule || 'Select a rule' }}
                        </span>
                        <v-icon
                            icon="mdi-chevron-down"
                            size="16"
                            class="lib-dropdown__chevron"
                            :class="{ 'is-open': rule_dropdown_open }"
                        />
                    </div>
                    <div v-if="rule_dropdown_open" class="lib-dropdown__list">
                        <div
                            v-for="option in ruleOptions"
                            :key="option"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.rule === option }"
                            @click="selectRule(option)"
                        >
                            <span class="lib-dropdown__item-name">{{ option }}</span>
                            <v-icon
                                v-if="form.rule === option"
                                icon="mdi-check"
                                size="13"
                                class="lib-dropdown__item-check"
                            />
                        </div>
                    </div>
                    <p v-if="errors['rule']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['rule'][0] }}
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

                <!-- Time -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">
                        Time (minutes)
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['time'] }">
                        <v-icon icon="mdi-clock-outline" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model.number="form.time"
                            type="number"
                            min="0"
                            max="15"
                            placeholder="e.g. 5"
                            class="lib-modal__input"
                        />
                    </div>
                    <p v-if="errors['time']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['time'][0] }}
                    </p>
                </div>

                <!-- Offices Multi-select -->
                <div class="lib-modal__field" ref="dropdown_ref">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <label class="lib-modal__label" style="margin-bottom:0;">Offices</label>
                        <label class="all-offices-check">
                            <input
                                type="checkbox"
                                v-model="all_offices"
                                @change="() => { form.offices = []; dropdown_open = false; }"
                            />
                            <span>All Offices</span>
                        </label>
                    </div>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['offices'], 'is-open': dropdown_open, 'is-disabled': all_offices }"
                        @click="!all_offices && (dropdown_open = !dropdown_open)"
                        :style="all_offices ? 'cursor:not-allowed;opacity:0.45;' : 'cursor:pointer;'"
                    >
                        <v-icon icon="mdi-office-building-outline" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !selectedOfficesLabel }">
                            {{ all_offices ? 'All offices' : (selectedOfficesLabel || 'Select offices') }}
                        </span>
                        <v-icon
                            icon="mdi-chevron-down"
                            size="16"
                            class="lib-dropdown__chevron"
                            :class="{ 'is-open': dropdown_open }"
                        />
                    </div>
                    <div v-if="dropdown_open && !all_offices" class="lib-dropdown__list">
                        <div
                            v-for="office in officeOptions"
                            :key="office.id"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': isOfficeSelected(office.id) }"
                            @click.stop="toggleOffice(office.id)"
                        >
                            <span class="lib-dropdown__item-code">{{ office.code }}</span>
                            <span class="lib-dropdown__item-name">{{ office.name }}</span>
                            <v-icon
                                v-if="isOfficeSelected(office.id)"
                                icon="mdi-check"
                                size="13"
                                class="lib-dropdown__item-check"
                            />
                        </div>
                        <div v-if="officeOptions.length === 0" class="lib-dropdown__empty">
                            No offices available
                        </div>
                    </div>
                    <p v-if="errors['offices']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['offices'][0] }}
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
                    :disabled="!form.rule || !form.time || is_loading"
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
                        :icon="props.workTimeRule?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.workTimeRule?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>

<style scoped>
.all-offices-check {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #8aa0d7;
    cursor: pointer;
    user-select: none;
}
.all-offices-check input[type="checkbox"] {
    accent-color: #1fbfb8;
    width: 14px;
    height: 14px;
    cursor: pointer;
}
</style>