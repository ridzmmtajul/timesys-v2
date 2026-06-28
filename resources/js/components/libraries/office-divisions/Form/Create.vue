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
        <div class="lib-modal">
            <!-- Header -->
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon
                            :icon="props.division?.id ? 'mdi-office-building-cog' : 'mdi-office-building-plus'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Office Division Management</p>
                        <h6 class="lib-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="lib-modal__body">
                <!-- Office -->
                <div class="lib-modal__field" ref="dropdown_ref">
                    <label class="lib-modal__label">
                        Office
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['office_id'], 'is-open': dropdown_open }"
                        @click="dropdown_open = !dropdown_open"
                        style="cursor: pointer;"
                    >
                        <v-icon icon="mdi-office-building" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !selectedOfficeLabel }">
                            {{ selectedOfficeLabel || 'Select an office' }}
                        </span>
                        <v-icon
                            icon="mdi-chevron-down"
                            size="16"
                            class="lib-dropdown__chevron"
                            :class="{ 'is-open': dropdown_open }"
                        />
                    </div>
                    <!-- Custom dropdown list -->
                    <div v-if="dropdown_open" class="lib-dropdown__list">
                        <div
                            v-for="office in officeOptions"
                            :key="office.id"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.office_id === office.id }"
                            @click="selectOffice(office.id)"
                        >
                            <span class="lib-dropdown__item-code">{{ office.code }}</span>
                            <span class="lib-dropdown__item-name">{{ office.name }}</span>
                            <v-icon
                                v-if="form.office_id === office.id"
                                icon="mdi-check"
                                size="13"
                                class="lib-dropdown__item-check"
                            />
                        </div>
                        <div v-if="officeOptions.length === 0" class="lib-dropdown__empty">
                            No offices available
                        </div>
                    </div>
                    <p v-if="errors['office_id']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['office_id'][0] }}
                    </p>
                </div>

                <!-- Code -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">
                        Division Code
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['code'] }">
                        <v-icon icon="mdi-identifier" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="e.g. IT"
                            class="lib-modal__input lib-modal__input--upper"
                            @keyup.enter="save()"
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
                        Division Name
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['name'] }">
                        <v-icon icon="mdi-office-building-marker" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Information Technology"
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
            </div>

            <!-- Footer -->
            <div class="lib-modal__footer">
                <button class="lib-modal__btn lib-modal__btn--cancel" :disabled="is_loading" @click="close()">
                    Cancel
                </button>
                <button
                    class="lib-modal__btn lib-modal__btn--save"
                    :disabled="!form.code?.trim() || !form.name?.trim() || !form.office_id || is_loading"
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
                        :icon="props.division?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.division?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>
