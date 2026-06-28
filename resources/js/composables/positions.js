import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function usePositions() {
    const position = ref(null);
    const positions = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getPositions = async (params = {}) => {
        is_loading.value = true;

        let query_str = { ...query.value, ...params };
        await axios
            .get('/api/positions?page=' + query.value.page, query_str)
            .then((response) => {
                positions.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const storePosition = async (data) => {
        is_loading.value = true;
        errors.value = '';

        try {
            await axios
                .post('/api/positions', data)
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

    const updatePosition = async (data) => {
        errors.value = '';
        is_loading.value = true;
        position.value = data;

        try {
            await axios
                .put(`/api/positions/${position.value.id}`, position.value)
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

    const destroyPosition = async (id) => {
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
                    .delete(`/api/positions/${id}`)
                    .then((response) => {
                        getPositions();
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
        position,
        positions,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getPositions,
        storePosition,
        updatePosition,
        destroyPosition,
    };
}
