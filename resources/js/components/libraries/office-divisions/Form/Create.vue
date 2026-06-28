<script setup>
import { ref, reactive, watch, computed, onMounted, onBeforeUnmount } from "vue";
import useOfficeDivisions from "../../../../composables/officeDivisions.js";

const { errors, is_loading, is_success, officeOptions, getOfficeOptions, storeDivision, updateDivision } = useOfficeDivisions();

const emit = defineEmits(["input", "reloadDivisions"]);
const props = defineProps({
    division: {
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
    code: null,
    name: null,
    description: null,
    office_id: null,
};

const form = reactive({ ...initialState });

onMounted(() => {
    getOfficeOptions();
});

watch(
    () => props.division,
    (value) => {
        form.id = value?.id || null;
        form.code = value?.code || null;
        form.name = value?.name || null;
        form.description = value?.description || null;
        form.office_id = value?.office_id || null;
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
    return props.division?.id ? "Edit Division" : "Create New Division";
});

const dropdown_open = ref(false);
const dropdown_ref = ref(null);

const selectedOfficeLabel = computed(() => {
    const found = officeOptions.value.find((o) => o.id === form.office_id);
    return found ? `${found.code} — ${found.name}` : null;
});

const selectOffice = (id) => {
    form.office_id = id;
    dropdown_open.value = false;
};

const handleClickOutside = (e) => {
    if (dropdown_ref.value && !dropdown_ref.value.contains(e.target)) {
        dropdown_open.value = false;
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
    if (props.division?.id) {
        await updateDivision({ ...form });
    } else {
        await storeDivision({ ...form });
    }

    if (is_success.value) {
        emit("reloadDivisions");
        emit("input", false);
    }
};
</script>

<template>
    <v-dialog v-model="show_form_modal" max-width="480px" persistent>
        <div class="division-modal">
            <!-- Header -->
            <div class="division-modal__header">
                <div class="division-modal__header-left">
                    <div class="division-modal__icon">
                        <v-icon
                            :icon="props.division?.id ? 'mdi-office-building-cog' : 'mdi-office-building-plus'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="division-modal__eyebrow">Office Division Management</p>
                        <h6 class="division-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="division-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="division-modal__body">
                <!-- Office -->
                <div class="division-modal__field" ref="dropdown_ref">
                    <label class="division-modal__label">
                        Office
                        <span class="division-modal__required">*</span>
                    </label>
                    <div
                        class="division-modal__input-wrap"
                        :class="{ 'is-error': errors['office_id'], 'is-open': dropdown_open }"
                        @click="dropdown_open = !dropdown_open"
                        style="cursor: pointer;"
                    >
                        <v-icon icon="mdi-office-building" size="16" class="division-modal__input-icon" />
                        <span class="division-modal__dropdown-value" :class="{ 'is-placeholder': !selectedOfficeLabel }">
                            {{ selectedOfficeLabel || 'Select an office' }}
                        </span>
                        <v-icon
                            icon="mdi-chevron-down"
                            size="16"
                            class="division-modal__chevron"
                            :class="{ 'is-open': dropdown_open }"
                        />
                    </div>
                    <!-- Custom dropdown list -->
                    <div v-if="dropdown_open" class="division-modal__dropdown">
                        <div
                            v-for="office in officeOptions"
                            :key="office.id"
                            class="division-modal__dropdown-item"
                            :class="{ 'is-selected': form.office_id === office.id }"
                            @click="selectOffice(office.id)"
                        >
                            <span class="division-modal__dropdown-code">{{ office.code }}</span>
                            <span class="division-modal__dropdown-name">{{ office.name }}</span>
                            <v-icon
                                v-if="form.office_id === office.id"
                                icon="mdi-check"
                                size="13"
                                class="division-modal__dropdown-check"
                            />
                        </div>
                        <div v-if="officeOptions.length === 0" class="division-modal__dropdown-empty">
                            No offices available
                        </div>
                    </div>
                    <p v-if="errors['office_id']" class="division-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['office_id'][0] }}
                    </p>
                </div>

                <!-- Code -->
                <div class="division-modal__field">
                    <label class="division-modal__label">
                        Division Code
                        <span class="division-modal__required">*</span>
                    </label>
                    <div class="division-modal__input-wrap" :class="{ 'is-error': errors['code'] }">
                        <v-icon icon="mdi-identifier" size="16" class="division-modal__input-icon" />
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="e.g. IT"
                            class="division-modal__input division-modal__input--upper"
                            @keyup.enter="save()"
                        />
                    </div>
                    <p v-if="errors['code']" class="division-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['code'][0] }}
                    </p>
                </div>

                <!-- Name -->
                <div class="division-modal__field">
                    <label class="division-modal__label">
                        Division Name
                        <span class="division-modal__required">*</span>
                    </label>
                    <div class="division-modal__input-wrap" :class="{ 'is-error': errors['name'] }">
                        <v-icon icon="mdi-office-building-marker" size="16" class="division-modal__input-icon" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Information Technology"
                            class="division-modal__input"
                            @keyup.enter="save()"
                        />
                    </div>
                    <p v-if="errors['name']" class="division-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['name'][0] }}
                    </p>
                </div>

                <!-- Description -->
                <div class="division-modal__field">
                    <label class="division-modal__label">Description</label>
                    <div class="division-modal__input-wrap" :class="{ 'is-error': errors['description'] }">
                        <v-icon icon="mdi-text" size="16" class="division-modal__input-icon" />
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Optional description"
                            class="division-modal__input"
                        />
                    </div>
                    <p v-if="errors['description']" class="division-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['description'][0] }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="division-modal__footer">
                <button class="division-modal__btn division-modal__btn--cancel" :disabled="is_loading" @click="close()">
                    Cancel
                </button>
                <button
                    class="division-modal__btn division-modal__btn--save"
                    :disabled="!form.code?.trim() || !form.name?.trim() || !form.office_id || is_loading"
                    @click="save()"
                >
                    <v-icon
                        v-if="is_loading"
                        icon="mdi-loading"
                        size="14"
                        class="division-modal__spinner"
                    />
                    <v-icon
                        v-else
                        :icon="props.division?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.division?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>

<style scoped>
.division-modal {
    border-radius: 16px;
    overflow: hidden;
    font-family: inherit;
    background: linear-gradient(160deg, #0e1c3a 0%, #0a1228 100%);
    border: 1px solid rgba(108, 143, 214, 0.18);
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(31, 191, 184, 0.08);
    backdrop-filter: blur(16px);
}

/* ── Header ── */
.division-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px 16px;
    border-bottom: 1px solid rgba(31, 191, 184, 0.1);
    background: rgba(31, 191, 184, 0.04);
}

.division-modal__header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.division-modal__icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(31, 191, 184, 0.15);
    border: 1px solid rgba(31, 191, 184, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1fbfb8;
    flex-shrink: 0;
}

.division-modal__eyebrow {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #1fbfb8;
    margin: 0 0 3px;
    line-height: 1;
    opacity: 0.8;
}

.division-modal__title {
    font-size: 14px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
    color: #e2e8f0;
}

.division-modal__close {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 1px solid rgba(108, 143, 214, 0.2);
    background: rgba(255, 255, 255, 0.05);
    color: #8aa0d7;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
    flex-shrink: 0;
}

.division-modal__close:hover {
    background: rgba(248, 113, 113, 0.12);
    border-color: rgba(248, 113, 113, 0.3);
    color: #f87171;
}

/* ── Body ── */
.division-modal__body {
    padding: 22px 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.division-modal__field {
    display: flex;
    flex-direction: column;
    gap: 7px;
    position: relative;
}

.division-modal__label {
    font-size: 12px;
    font-weight: 600;
    color: #a8bcd8;
    display: flex;
    align-items: center;
    gap: 3px;
    letter-spacing: 0.02em;
}

.division-modal__required {
    color: #f87171;
    font-size: 13px;
    line-height: 1;
}

.division-modal__input-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid rgba(108, 143, 214, 0.2);
    border-radius: 10px;
    padding: 0 13px;
    background: rgba(255, 255, 255, 0.04);
    transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
}

.division-modal__input-wrap:focus-within {
    border-color: #1fbfb8;
    box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.12);
    background: rgba(31, 191, 184, 0.04);
}

.division-modal__input-wrap.is-error {
    border-color: #f87171;
    box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.1);
}

.division-modal__input-icon {
    color: #1fbfb8;
    flex-shrink: 0;
    opacity: 0.7;
}

.division-modal__input-wrap:focus-within .division-modal__input-icon {
    opacity: 1;
}

.division-modal__input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 13.5px;
    color: #e2e8f0;
    padding: 11px 0;
    font-family: inherit;
}

.division-modal__input--upper {
    text-transform: uppercase;
}

.division-modal__input::placeholder {
    color: rgba(138, 160, 215, 0.4);
    text-transform: none;
}

.division-modal__dropdown-value {
    flex: 1;
    font-size: 13.5px;
    color: #e2e8f0;
    padding: 11px 0;
    user-select: none;
}

.division-modal__dropdown-value.is-placeholder {
    color: rgba(138, 160, 215, 0.4);
}

.division-modal__chevron {
    color: #1fbfb8;
    flex-shrink: 0;
    opacity: 0.6;
    transition: transform 0.2s;
}

.division-modal__chevron.is-open {
    transform: rotate(180deg);
    opacity: 1;
}

.division-modal__input-wrap.is-open {
    border-color: #1fbfb8;
    box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.12);
    background: rgba(31, 191, 184, 0.04);
}

.division-modal__dropdown {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 4px);
    z-index: 100;
    background: #0e1c3a;
    border: 1px solid rgba(31, 191, 184, 0.25);
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    overflow: hidden;
    max-height: 200px;
    overflow-y: auto;
}

.division-modal__dropdown::-webkit-scrollbar {
    width: 4px;
}

.division-modal__dropdown::-webkit-scrollbar-thumb {
    background: rgba(31, 191, 184, 0.3);
    border-radius: 4px;
}

.division-modal__dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    cursor: pointer;
    transition: background 0.12s;
}

.division-modal__dropdown-item:hover {
    background: rgba(31, 191, 184, 0.08);
}

.division-modal__dropdown-item.is-selected {
    background: rgba(31, 191, 184, 0.12);
}

.division-modal__dropdown-code {
    font-size: 11.5px;
    font-weight: 700;
    color: #1fbfb8;
    background: rgba(31, 191, 184, 0.1);
    border: 1px solid rgba(31, 191, 184, 0.2);
    border-radius: 5px;
    padding: 2px 7px;
    flex-shrink: 0;
    letter-spacing: 0.04em;
}

.division-modal__dropdown-name {
    flex: 1;
    font-size: 13px;
    color: #e2e8f0;
}

.division-modal__dropdown-check {
    color: #1fbfb8;
    flex-shrink: 0;
}

.division-modal__dropdown-empty {
    padding: 14px;
    text-align: center;
    font-size: 12.5px;
    color: #8aa0d7;
}

.division-modal__error {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    color: #f87171;
    margin: 0;
}

/* ── Footer ── */
.division-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 20px 18px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.division-modal__btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 9px;
    border: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.18s;
    font-family: inherit;
    line-height: 1;
}

.division-modal__btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.division-modal__btn--cancel {
    background: rgba(255, 255, 255, 0.06);
    color: #8aa0d7;
    border: 1px solid rgba(108, 143, 214, 0.2);
}

.division-modal__btn--cancel:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    border-color: rgba(108, 143, 214, 0.4);
}

.division-modal__btn--save {
    background: linear-gradient(135deg, #1fbfb8 0%, #05716c 100%);
    color: #fff;
    box-shadow: 0 2px 10px rgba(31, 191, 184, 0.35);
}

.division-modal__btn--save:hover:not(:disabled) {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.5);
    transform: translateY(-1px);
}

.division-modal__btn--save:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 1px 6px rgba(31, 191, 184, 0.3);
}

.division-modal__spinner {
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
