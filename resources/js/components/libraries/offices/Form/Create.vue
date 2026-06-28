<script setup>
import { ref, reactive, watch, computed } from "vue";
import useOffices from "../../../../composables/offices.js";

const { errors, is_loading, is_success, storeOffice, updateOffice } = useOffices();

const emit = defineEmits(["input", "reloadOffices"]);
const props = defineProps({
    office: {
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
    prefix: null,
    latest_employee_no: null,
};

const form = reactive({ ...initialState });

watch(
    () => props.office,
    (value) => {
        form.id = value?.id || null;
        form.code = value?.code || null;
        form.name = value?.name || null;
        form.description = value?.description || null;
        form.prefix = value?.prefix || null;
        form.latest_employee_no = value?.latest_employee_no ?? null;
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
    return props.office?.id ? "Edit Office" : "Create New Office";
});

const close = () => {
    Object.assign(form, initialState);
    emit("input", false);
    errors.value = {};
};

const save = async () => {
    if (props.office?.id) {
        await updateOffice({ ...form });
    } else {
        await storeOffice({ ...form });
    }

    if (is_success.value) {
        emit("reloadOffices");
        emit("input", false);
    }
};
</script>

<template>
    <v-dialog v-model="show_form_modal" max-width="480px" persistent>
        <div class="office-modal">
            <!-- Header -->
            <div class="office-modal__header">
                <div class="office-modal__header-left">
                    <div class="office-modal__icon">
                        <v-icon
                            :icon="props.office?.id ? 'mdi-office-building-cog' : 'mdi-office-building-plus'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="office-modal__eyebrow">Office Management</p>
                        <h6 class="office-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="office-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="office-modal__body">
                <!-- Code -->
                <div class="office-modal__field">
                    <label class="office-modal__label">
                        Office Code
                        <span class="office-modal__required">*</span>
                    </label>
                    <div class="office-modal__input-wrap" :class="{ 'is-error': errors['code'] }">
                        <v-icon icon="mdi-identifier" size="16" class="office-modal__input-icon" />
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="e.g. HR"
                            class="office-modal__input office-modal__input--upper"
                            @keyup.enter="save()"
                            autofocus
                        />
                    </div>
                    <p v-if="errors['code']" class="office-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['code'][0] }}
                    </p>
                </div>

                <!-- Name -->
                <div class="office-modal__field">
                    <label class="office-modal__label">
                        Office Name
                        <span class="office-modal__required">*</span>
                    </label>
                    <div class="office-modal__input-wrap" :class="{ 'is-error': errors['name'] }">
                        <v-icon icon="mdi-office-building" size="16" class="office-modal__input-icon" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Human Resources"
                            class="office-modal__input"
                            @keyup.enter="save()"
                        />
                    </div>
                    <p v-if="errors['name']" class="office-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['name'][0] }}
                    </p>
                </div>

                <!-- Description -->
                <div class="office-modal__field">
                    <label class="office-modal__label">Description</label>
                    <div class="office-modal__input-wrap" :class="{ 'is-error': errors['description'] }">
                        <v-icon icon="mdi-text" size="16" class="office-modal__input-icon" />
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="Optional description"
                            class="office-modal__input"
                        />
                    </div>
                    <p v-if="errors['description']" class="office-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['description'][0] }}
                    </p>
                </div>

                <!-- Prefix -->
                <div class="office-modal__field">
                    <label class="office-modal__label">Prefix</label>
                    <div class="office-modal__input-wrap" :class="{ 'is-error': errors['prefix'] }">
                        <v-icon icon="mdi-tag-outline" size="16" class="office-modal__input-icon" />
                        <input
                            v-model="form.prefix"
                            type="text"
                            placeholder="e.g. 100000"
                            class="office-modal__input office-modal__input--upper"
                        />
                    </div>
                    <p v-if="errors['prefix']" class="office-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['prefix'][0] }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="office-modal__footer">
                <button class="office-modal__btn office-modal__btn--cancel" :disabled="is_loading" @click="close()">
                    Cancel
                </button>
                <button
                    class="office-modal__btn office-modal__btn--save"
                    :disabled="!form.code?.trim() || !form.name?.trim() || is_loading"
                    @click="save()"
                >
                    <v-icon
                        v-if="is_loading"
                        icon="mdi-loading"
                        size="14"
                        class="office-modal__spinner"
                    />
                    <v-icon
                        v-else
                        :icon="props.office?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.office?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>

<style scoped>
.office-modal {
    border-radius: 16px;
    overflow: hidden;
    font-family: inherit;
    background: linear-gradient(160deg, #0e1c3a 0%, #0a1228 100%);
    border: 1px solid rgba(108, 143, 214, 0.18);
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(31, 191, 184, 0.08);
    backdrop-filter: blur(16px);
}

/* ── Header ── */
.office-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px 16px;
    border-bottom: 1px solid rgba(31, 191, 184, 0.1);
    background: rgba(31, 191, 184, 0.04);
}

.office-modal__header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.office-modal__icon {
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

.office-modal__eyebrow {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #1fbfb8;
    margin: 0 0 3px;
    line-height: 1;
    opacity: 0.8;
}

.office-modal__title {
    font-size: 14px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
    color: #e2e8f0;
}

.office-modal__close {
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

.office-modal__close:hover {
    background: rgba(248, 113, 113, 0.12);
    border-color: rgba(248, 113, 113, 0.3);
    color: #f87171;
}

/* ── Body ── */
.office-modal__body {
    padding: 22px 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.office-modal__field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.office-modal__label {
    font-size: 12px;
    font-weight: 600;
    color: #a8bcd8;
    display: flex;
    align-items: center;
    gap: 3px;
    letter-spacing: 0.02em;
}

.office-modal__required {
    color: #f87171;
    font-size: 13px;
    line-height: 1;
}

.office-modal__input-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid rgba(108, 143, 214, 0.2);
    border-radius: 10px;
    padding: 0 13px;
    background: rgba(255, 255, 255, 0.04);
    transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
}

.office-modal__input-wrap:focus-within {
    border-color: #1fbfb8;
    box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.12);
    background: rgba(31, 191, 184, 0.04);
}

.office-modal__input-wrap.is-error {
    border-color: #f87171;
    box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.1);
}

.office-modal__input-icon {
    color: #1fbfb8;
    flex-shrink: 0;
    opacity: 0.7;
}

.office-modal__input-wrap:focus-within .office-modal__input-icon {
    opacity: 1;
}

.office-modal__input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 13.5px;
    color: #e2e8f0;
    padding: 11px 0;
    font-family: inherit;
}

.office-modal__input--upper {
    text-transform: uppercase;
}

.office-modal__input::placeholder {
    color: rgba(138, 160, 215, 0.4);
    text-transform: none;
}

.office-modal__input[type="number"]::-webkit-inner-spin-button,
.office-modal__input[type="number"]::-webkit-outer-spin-button {
    opacity: 0.4;
}

.office-modal__error {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    color: #f87171;
    margin: 0;
}

/* ── Footer ── */
.office-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 20px 18px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.office-modal__btn {
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

.office-modal__btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.office-modal__btn--cancel {
    background: rgba(255, 255, 255, 0.06);
    color: #8aa0d7;
    border: 1px solid rgba(108, 143, 214, 0.2);
}

.office-modal__btn--cancel:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    border-color: rgba(108, 143, 214, 0.4);
}

.office-modal__btn--save {
    background: linear-gradient(135deg, #1fbfb8 0%, #05716c 100%);
    color: #fff;
    box-shadow: 0 2px 10px rgba(31, 191, 184, 0.35);
}

.office-modal__btn--save:hover:not(:disabled) {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.5);
    transform: translateY(-1px);
}

.office-modal__btn--save:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 1px 6px rgba(31, 191, 184, 0.3);
}

.office-modal__spinner {
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
