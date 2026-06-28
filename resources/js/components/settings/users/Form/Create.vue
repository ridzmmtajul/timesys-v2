<script setup>
import { ref, reactive, watch, computed, onMounted, onBeforeUnmount } from "vue";
import useUsers from "../../../../composables/users.js";

const { errors, is_loading, is_success, employeeOptions, roleOptions, getUserOptions, storeUser, updateUser } = useUsers();

const emit = defineEmits(["input", "reloadUsers"]);
const props = defineProps({
    user: {
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
    username: null,
    password: '*1234#',
    employee_id: null,
    role_id: null,
};

const form = reactive({ ...initialState });

onMounted(() => {
    getUserOptions();
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

watch(
    () => props.user,
    (value) => {
        form.id          = value?.id || null;
        form.username    = value?.username || null;
        form.password    = null;
        form.employee_id = value?.employee_id || null;
        form.role_id     = value?.role_id || null;
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
    return props.user?.id ? 'Edit Account' : 'Create New Account';
});

// Employee dropdown
const employee_dropdown_open = ref(false);
const employee_dropdown_ref = ref(null);

const selectedEmployee = computed(() =>
    employeeOptions.value.find((e) => e.id === form.employee_id) || null
);

const generateUsername = (emp) => {
    const firstInitial  = (emp.first_name || '').trim()[0] || '';
    const middleInitial = (emp.middle_name || '').trim()[0] || '';
    const last   = (emp.last_name || '').trim().replace(/\s+/g, '').toUpperCase();
    const suffix = (emp.employee_no || '').slice(-2);
    return (firstInitial + middleInitial).toUpperCase() + last + suffix;
};

const selectEmployee = (option) => {
    form.employee_id = option.id;
    if (!props.user?.id) {
        form.username = generateUsername(option);
    }
    employee_dropdown_open.value = false;
};

// Role dropdown
const role_dropdown_open = ref(false);
const role_dropdown_ref = ref(null);

const selectedRole = computed(() =>
    roleOptions.value.find((r) => r.id === form.role_id) || null
);

const selectRole = (option) => {
    form.role_id = option.id;
    role_dropdown_open.value = false;
};

const handleClickOutside = (e) => {
    if (employee_dropdown_ref.value && !employee_dropdown_ref.value.contains(e.target)) {
        employee_dropdown_open.value = false;
    }
    if (role_dropdown_ref.value && !role_dropdown_ref.value.contains(e.target)) {
        role_dropdown_open.value = false;
    }
};

const close = () => {
    Object.assign(form, initialState);
    emit('input', false);
    errors.value = {};
};

const save = async () => {
    if (props.user?.id) {
        await updateUser({ ...form });
    } else {
        await storeUser({ ...form });
    }

    if (is_success.value) {
        emit('reloadUsers');
        emit('input', false);
    }
};

const isFormValid = computed(() => {
    if (!form.username?.trim() || !form.employee_id || !form.role_id) return false;
    return true;
});
</script>

<template>
    <v-dialog v-model="show_form_modal" max-width="480px" persistent>
        <div class="lib-modal">
            <!-- Header -->
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon
                            :icon="props.user?.id ? 'mdi-account-edit-outline' : 'mdi-account-plus-outline'"
                            size="18"
                        />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Account Management</p>
                        <h6 class="lib-modal__title">{{ dialogTitle }}</h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="close()">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <!-- Body -->
            <div class="lib-modal__body">
                <!-- Employee -->
                <div class="lib-modal__field" ref="employee_dropdown_ref">
                    <label class="lib-modal__label">
                        Employee
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['employee_id'], 'is-open': employee_dropdown_open }"
                        @click="employee_dropdown_open = !employee_dropdown_open"
                        style="cursor: pointer;"
                    >
                        <v-icon icon="mdi-account-tie-outline" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !selectedEmployee }">
                            {{ selectedEmployee ? selectedEmployee.name : 'Select an employee' }}
                        </span>
                        <v-icon
                            icon="mdi-chevron-down"
                            size="16"
                            class="lib-dropdown__chevron"
                            :class="{ 'is-open': employee_dropdown_open }"
                        />
                    </div>
                    <div v-if="employee_dropdown_open" class="lib-dropdown__list">
                        <div
                            v-for="emp in employeeOptions"
                            :key="emp.id"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.employee_id === emp.id }"
                            @click="selectEmployee(emp)"
                        >
                            <span class="lib-dropdown__item-code">{{ emp.employee_no }}</span>
                            <span class="lib-dropdown__item-name">{{ emp.name }}</span>
                            <v-icon
                                v-if="form.employee_id === emp.id"
                                icon="mdi-check"
                                size="13"
                                class="lib-dropdown__item-check"
                            />
                        </div>
                        <div v-if="employeeOptions.length === 0" class="lib-dropdown__empty">
                            No employees available
                        </div>
                    </div>
                    <p v-if="errors['employee_id']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['employee_id'][0] }}
                    </p>
                </div>
                
                <!-- Username -->
                <div class="lib-modal__field">
                    <label class="lib-modal__label">
                        Username
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div class="lib-modal__input-wrap" :class="{ 'is-error': errors['username'] }">
                        <v-icon icon="mdi-account-outline" size="16" class="lib-modal__input-icon" />
                        <input
                            v-model="form.username"
                            type="text"
                            placeholder="e.g. jdelacruz"
                            class="lib-modal__input"
                            autofocus
                        />
                    </div>
                    <p v-if="errors['username']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['username'][0] }}
                    </p>
                </div>

                <!-- Role -->
                <div class="lib-modal__field" ref="role_dropdown_ref">
                    <label class="lib-modal__label">
                        Role
                        <span class="lib-modal__required">*</span>
                    </label>
                    <div
                        class="lib-modal__input-wrap"
                        :class="{ 'is-error': errors['role_id'], 'is-open': role_dropdown_open }"
                        @click="role_dropdown_open = !role_dropdown_open"
                        style="cursor: pointer;"
                    >
                        <v-icon icon="mdi-account-tag-outline" size="16" class="lib-modal__input-icon" />
                        <span class="lib-dropdown__value" :class="{ 'is-placeholder': !selectedRole }">
                            {{ selectedRole ? selectedRole.name : 'Select a role' }}
                        </span>
                        <v-icon
                            icon="mdi-chevron-down"
                            size="16"
                            class="lib-dropdown__chevron"
                            :class="{ 'is-open': role_dropdown_open }"
                        />
                    </div>
                    <div v-if="role_dropdown_open" class="lib-dropdown__list">
                        <div
                            v-for="role in roleOptions"
                            :key="role.id"
                            class="lib-dropdown__item"
                            :class="{ 'is-selected': form.role_id === role.id }"
                            @click="selectRole(role)"
                        >
                            <span class="lib-dropdown__item-name">{{ role.name }}</span>
                            <v-icon
                                v-if="form.role_id === role.id"
                                icon="mdi-check"
                                size="13"
                                class="lib-dropdown__item-check"
                            />
                        </div>
                        <div v-if="roleOptions.length === 0" class="lib-dropdown__empty">
                            No roles available
                        </div>
                    </div>
                    <p v-if="errors['role_id']" class="lib-modal__error">
                        <v-icon icon="mdi-alert-circle-outline" size="12" />
                        {{ errors['role_id'][0] }}
                    </p>
                </div>
            </div>

            <!-- Tip -->
            <div v-if="!props.user?.id" class="lib-modal__tip">
                <v-icon icon="mdi-information-outline" size="13" />
                Default password will be set to <strong>*1234#</strong>
            </div>

            <!-- Footer -->
            <div class="lib-modal__footer">
                <button class="lib-modal__btn lib-modal__btn--cancel" :disabled="is_loading" @click="close()">
                    Cancel
                </button>
                <button
                    class="lib-modal__btn lib-modal__btn--save"
                    :disabled="!isFormValid || is_loading"
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
                        :icon="props.user?.id ? 'mdi-content-save-outline' : 'mdi-check'"
                        size="14"
                    />
                    {{ props.user?.id ? 'Update' : 'Save' }}
                </button>
            </div>
        </div>
    </v-dialog>
</template>