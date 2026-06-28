import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useUsers() {
    const user = ref(null);
    const users = ref([]);
    const employeeOptions = ref([]);
    const roleOptions = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getUsers = async (params = {}) => {
        is_loading.value = true;

        let query_str = { ...query.value, ...params };
        await axios
            .get('/api/users?page=' + query.value.page, query_str)
            .then((response) => {
                users.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const getUserOptions = async () => {
        await axios.get('/api/users/options').then((response) => {
            employeeOptions.value = response.data.employees;
            roleOptions.value = response.data.roles;
        });
    };

    const storeUser = async (data) => {
        is_loading.value = true;
        errors.value = '';

        try {
            await axios
                .post('/api/users', data)
                .then((response) => {
                    ThemeSwal.fire({
                        title: 'Success',
                        icon: 'success',
                        text: response.data.message,
                    });
                    errors.value = {};
                    is_loading.value = false;
                    is_success.value = true;
                });
        } catch (e) {
            if (e.response.status == 422) {
                errors.value = e.response.data;
                is_success.value = false;
                is_loading.value = false;
            }
        }
    };

    const updateUser = async (data) => {
        errors.value = '';
        is_loading.value = true;
        user.value = data;

        try {
            await axios
                .put(`/api/users/${user.value.id}`, user.value)
                .then((response) => {
                    ThemeSwal.fire({
                        title: 'Success',
                        icon: 'success',
                        text: response.data.message,
                    });
                    errors.value = {};
                    is_loading.value = false;
                    is_success.value = true;
                });
        } catch (e) {
            if (e.response.status == 422) {
                errors.value = e.response.data;
                is_success.value = false;
                is_loading.value = false;
            }
        }
    };

    const destroyUser = async (id) => {
        ThemeSwal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                ...swalClass,
                confirmButton: 'ts-swal-btn ts-swal-btn--danger',
            },
        }).then((result) => {
            if (result.value) {
                axios
                    .delete(`/api/users/${id}`)
                    .then((response) => {
                        getUsers();
                        ThemeSwal.fire({
                            title: 'Deleted',
                            text: response.data.message,
                            icon: 'success',
                        });
                    })
                    .catch(() => {
                        ThemeSwal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                        });
                    });
            }
        });
    };

    return {
        user,
        users,
        employeeOptions,
        roleOptions,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getUsers,
        getUserOptions,
        storeUser,
        updateUser,
        destroyUser,
    };
}
