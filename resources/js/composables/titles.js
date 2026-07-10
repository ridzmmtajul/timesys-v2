import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useTitles() {
    const title = ref(null);
    const titles = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getTitles = async (params = {}) => {
        is_loading.value = true;

        await axios
            .get('/api/titles', { params: { ...query.value, ...params } })
            .then((response) => {
                titles.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const storeTitle = async (data) => {
        is_loading.value = true;
        errors.value = '';

        try {
            await axios
                .post('/api/titles', data)
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

    const updateTitle = async (data) => {
        errors.value = '';
        is_loading.value = true;
        title.value = data;

        try {
            await axios
                .put(`/api/titles/${title.value.id}`, title.value)
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

    const destroyTitle = async (id) => {
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
                    .delete(`/api/titles/${id}`)
                    .then((response) => {
                        getTitles();
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
        title,
        titles,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getTitles,
        storeTitle,
        updateTitle,
        destroyTitle,
    };
}
