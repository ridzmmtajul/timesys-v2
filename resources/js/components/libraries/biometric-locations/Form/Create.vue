<script setup>
import { reactive, ref, watch, computed } from "vue";
import useBiometricLocations from "../../../../composables/biometric-locations.js";

const { errors, is_loading, is_success, storeBiometricLocation, updateBiometricLocation } = useBiometricLocations();

const emit = defineEmits(["input", "reloadBiometricLocations"]);
const props = defineProps({
    biometricLocation: {
        type: Object,
        default: null
    },
    value: {
        type: Boolean,
        default: false,
    }
});

const initialState = {
    id: null,
    name: null,
};

const form = reactive({ ...initialState });

watch(
    () => props.biometricLocation,
    (value) => {
        form.id = value?.id || null;
        form.name = value?.name || null;
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
    return props.biometricLocation?.id ? "Edit Biometric Location" : "Create New Biometric Location";
});

const close = () => {
    Object.assign(form, initialState);
    emit("input", false);
    errors.value = {};
}

const save = async () => {
    if (props.biometricLocation?.id) {
        await updateBiometricLocation({ ...form });
    } else {
        await storeBiometricLocation({ ...form });
    }

    if (is_success.value) {
        emit("reloadBiometricLocations");
        emit("input", false);
    }
}
</script>

<template>
    <v-dialog
        v-model="show_form_modal"
        max-width="440px"
        persistent
    >
        <div class="lib-modal">
            <!-- Header -->
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon
                            :icon="props.biometricLocation?.id ? 'mdi-map-marker-radius' : 'mdi-map-marker-plus'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Biometric Location Management</p>
                        <h6 class="lib-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="lib-modal__body">
                <div class="lib-modal__field">
                    <label class="lib-modal__label">
                        Location Name
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['name'] }">
                        <v-icon icon="mdi-map-marker-radius-outline" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. City Hall - Main"
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
                        :icon="props.biometricLocation?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.biometricLocation?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>
