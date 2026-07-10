import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function usePostNumbers() {
    const postNumber = ref(null);
    const postNumbers = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getPostNumbers = async (params = {}) => {
        is_loading.value = true;

        await axios
            .get('/api/post-numbers', { params: { ...query.value, ...params } })
            .then((response) => {
                postNumbers.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const storePostNumber = async (data) => {
        is_loading.value = true;
        errors.value = '';

        try {
            await axios
                .post('/api/post-numbers', data)
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

    const updatePostNumber = async (data) => {
        errors.value = '';
        is_loading.value = true;
        postNumber.value = data;

        try {
            await axios
                .put(`/api/post-numbers/${postNumber.value.id}`, postNumber.value)
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

    const destroyPostNumber = async (id) => {
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
                    .delete(`/api/post-numbers/${id}`)
                    .then((response) => {
                        getPostNumbers();
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
        postNumber,
        postNumbers,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getPostNumbers,
        storePostNumber,
        updatePostNumber,
        destroyPostNumber,
    };
}
