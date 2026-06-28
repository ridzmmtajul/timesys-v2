<script setup>
import { ref, reactive, watch, computed } from "vue";
import usePostNumbers from "../../../../composables/post-numbers.js";

const { errors, is_loading, is_success, storePostNumber, updatePostNumber } = usePostNumbers();

const emit = defineEmits(["input", "reloadPostNumbers"]);
const props = defineProps({
    postNumber: {
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
};

const form = reactive({ ...initialState });

watch(
    () => props.postNumber,
    (value) => {
        form.id   = value?.id || null;
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
    return props.postNumber?.id ? "Edit Post Number" : "Create New Post Number";
});

const close = () => {
    Object.assign(form, initialState);
    emit("input", false);
    errors.value = {};
};

const save = async () => {
    if (props.postNumber?.id) {
        await updatePostNumber({ ...form });
    } else {
        await storePostNumber({ ...form });
    }

    if (is_success.value) {
        emit("reloadPostNumbers");
        emit("input", false);
    }
};
</script>

<template>
    <v-dialog v-model="show_form_modal" max-width="440px" persistent>
        <div class="lib-modal">
            <!-- Header -->
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon
                            :icon="props.postNumber?.id ? 'mdi-numeric-positive-1' : 'mdi-numeric-positive-1'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Post Number Management</p>
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
                        <v-icon icon="mdi-numeric" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. PN-001"
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
                        :icon="props.postNumber?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.postNumber?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>
