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
        <div class="lib-modal">
            <!-- Header -->
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon
                            :icon="props.office?.id ? 'mdi-office-building-cog' : 'mdi-office-building-plus'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Office Management</p>
                        <h6 class="lib-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="lib-modal__body">
                <!-- Code -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">
                        Office Code
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['code'] }">
                        <v-icon icon="mdi-identifier" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="e.g. HR"
                            class="lib-modal__input lib-modal__input--upper"
                            @keyup.enter="save()"
                            autofocus
                        />
                    </div>
                    <p v-if="errors['code']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['code'][0] }}
                    </p>
                </div>

                <!-- Name -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">
                        Office Name
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['name'] }">
                        <v-icon icon="mdi-office-building" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Human Resources"
                            class="lib-modal__input"
                            @keyup.enter="save()"
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

                <!-- Prefix -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">Prefix</label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['prefix'] }">
                        <v-icon icon="mdi-tag-outline" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.prefix"
                            type="text"
                            placeholder="e.g. 100000"
                            class="lib-modal__input lib-modal__input--upper"
                        />
                    </div>
                    <p v-if="errors['prefix']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['prefix'][0] }}
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
                    :disabled="!form.code?.trim() || !form.name?.trim() || is_loading"
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
                        :icon="props.office?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.office?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>
