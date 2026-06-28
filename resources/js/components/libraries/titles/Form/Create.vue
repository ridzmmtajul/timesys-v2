<script setup>
import { ref, reactive, watch, computed } from "vue";
import useTitles from "../../../../composables/titles.js";

const { errors, is_loading, is_success, storeTitle, updateTitle } = useTitles();

const emit = defineEmits(["input", "reloadTitles"]);
const props = defineProps({
    title: {
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
    abbreviation: null,
    description: null,
};

const form = reactive({ ...initialState });

watch(
    () => props.title,
    (value) => {
        form.id           = value?.id || null;
        form.abbreviation = value?.abbreviation || null;
        form.description  = value?.description || null;
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
    return props.title?.id ? "Edit Title" : "Create New Title";
});

const close = () => {
    Object.assign(form, initialState);
    emit("input", false);
    errors.value = {};
};

const save = async () => {
    if (props.title?.id) {
        await updateTitle({ ...form });
    } else {
        await storeTitle({ ...form });
    }

    if (is_success.value) {
        emit("reloadTitles");
        emit("input", false);
    }
};
</script>

<template>
    <v-dialog v-model="show_form_modal" max-width="480px" persistent>
        <div class="title-modal">
            <!-- Header -->
            <div class="title-modal__header">
                <div class="title-modal__header-left">
                    <div class="title-modal__icon">
                        <v-icon
                            :icon="props.title?.id ? 'mdi-tag-edit-outline' : 'mdi-tag-plus-outline'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="title-modal__eyebrow">Title Management</p>
                        <h6 class="title-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="title-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="title-modal__body">
                <!-- Abbreviation -->
                <div class="title-modal__field">
                    <label class="title-modal__label">
                        Abbreviation
                        <span class="title-modal__required">*</span>
                    </label>
                    <div class="title-modal__input-wrap" :class="{ 'is-error': errors['abbreviation'] }">
                        <v-icon icon="mdi-tag-text-outline" size="16" class="title-modal__input-icon" />
                        <input
                            v-model="form.abbreviation"
                            type="text"
                            placeholder="e.g. DR"
                            class="title-modal__input title-modal__input--upper"
                            @keyup.enter="save()"
                            autofocus
                        />
                    </div>
                    <p v-if="errors['abbreviation']" class="title-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['abbreviation'][0] }}
                    </p>
                </div>

                <!-- Description -->
                <div class="title-modal__field">
                    <label class="title-modal__label">Description</label>
                    <div class="title-modal__input-wrap" :class="{ 'is-error': errors['description'] }">
                        <v-icon icon="mdi-text" size="16" class="title-modal__input-icon" />
                        <input
                            v-model="form.description"
                            type="text"
                            placeholder="e.g. Doctor"
                            class="title-modal__input"
                            @keyup.enter="save()"
                        />
                    </div>
                    <p v-if="errors['description']" class="title-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['description'][0] }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="title-modal__footer">
                <button class="title-modal__btn title-modal__btn--cancel" :disabled="is_loading" @click="close()">
                    Cancel
                </button>
                <button
                    class="title-modal__btn title-modal__btn--save"
                    :disabled="!form.abbreviation?.trim() || is_loading"
                    @click="save()"
                >
                    <v-icon
                        v-if="is_loading"
                        icon="mdi-loading"
                        size="14"
                        class="title-modal__spinner"
                    />
                    <v-icon
                        v-else
                        :icon="props.title?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.title?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>

<style scoped>
.title-modal {
    border-radius: 16px;
    overflow: hidden;
    font-family: inherit;
    background: linear-gradient(160deg, #0e1c3a 0%, #0a1228 100%);
    border: 1px solid rgba(108, 143, 214, 0.18);
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(31, 191, 184, 0.08);
    backdrop-filter: blur(16px);
}

/* ── Header ── */
.title-modal__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 20px 16px;
    border-bottom: 1px solid rgba(31, 191, 184, 0.1);
    background: rgba(31, 191, 184, 0.04);
}

.title-modal__header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.title-modal__icon {
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

.title-modal__eyebrow {
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #1fbfb8;
    margin: 0 0 3px;
    line-height: 1;
    opacity: 0.8;
}

.title-modal__title {
    font-size: 14px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
    color: #e2e8f0;
}

.title-modal__close {
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

.title-modal__close:hover {
    background: rgba(248, 113, 113, 0.12);
    border-color: rgba(248, 113, 113, 0.3);
    color: #f87171;
}

/* ── Body ── */
.title-modal__body {
    padding: 22px 20px 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.title-modal__field {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.title-modal__label {
    font-size: 12px;
    font-weight: 600;
    color: #a8bcd8;
    display: flex;
    align-items: center;
    gap: 3px;
    letter-spacing: 0.02em;
}

.title-modal__required {
    color: #f87171;
    font-size: 13px;
    line-height: 1;
}

.title-modal__input-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid rgba(108, 143, 214, 0.2);
    border-radius: 10px;
    padding: 0 13px;
    background: rgba(255, 255, 255, 0.04);
    transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
}

.title-modal__input-wrap:focus-within {
    border-color: #1fbfb8;
    box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.12);
    background: rgba(31, 191, 184, 0.04);
}

.title-modal__input-wrap.is-error {
    border-color: #f87171;
    box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.1);
}

.title-modal__input-icon {
    color: #1fbfb8;
    flex-shrink: 0;
    opacity: 0.7;
}

.title-modal__input-wrap:focus-within .title-modal__input-icon {
    opacity: 1;
}

.title-modal__input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    font-size: 13.5px;
    color: #e2e8f0;
    padding: 11px 0;
    font-family: inherit;
}

.title-modal__input--upper {
    text-transform: uppercase;
}

.title-modal__input::placeholder {
    color: rgba(138, 160, 215, 0.4);
    text-transform: none;
}

.title-modal__error {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 11.5px;
    color: #f87171;
    margin: 0;
}

/* ── Footer ── */
.title-modal__footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 20px 18px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.title-modal__btn {
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

.title-modal__btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.title-modal__btn--cancel {
    background: rgba(255, 255, 255, 0.06);
    color: #8aa0d7;
    border: 1px solid rgba(108, 143, 214, 0.2);
}

.title-modal__btn--cancel:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    border-color: rgba(108, 143, 214, 0.4);
}

.title-modal__btn--save {
    background: linear-gradient(135deg, #1fbfb8 0%, #05716c 100%);
    color: #fff;
    box-shadow: 0 2px 10px rgba(31, 191, 184, 0.35);
}

.title-modal__btn--save:hover:not(:disabled) {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.5);
    transform: translateY(-1px);
}

.title-modal__btn--save:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 1px 6px rgba(31, 191, 184, 0.3);
}

.title-modal__spinner {
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
