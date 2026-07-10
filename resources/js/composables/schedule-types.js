import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useScheduleTypes() {
    const scheduleType = ref(null);
    const scheduleTypes = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getScheduleTypes = async (params = {}) => {
        is_loading.value = true;

        await axios
            .get('/api/schedule-types', { params: { ...query.value, ...params } })
            .then((response) => {
                scheduleTypes.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const storeScheduleType = async (data) => {
        is_loading.value = true;
        errors.value = '';

        try {
            await axios
                .post('/api/schedule-types', data)
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

    const updateScheduleType = async (data) => {
        errors.value = '';
        is_loading.value = true;
        scheduleType.value = data;

        try {
            await axios
                .put(`/api/schedule-types/${scheduleType.value.id}`, scheduleType.value)
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

    const destroyScheduleType = async (id) => {
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
                    .delete(`/api/schedule-types/${id}`)
                    .then((response) => {
                        getScheduleTypes();
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
        scheduleType,
        scheduleTypes,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getScheduleTypes,
        storeScheduleType,
        updateScheduleType,
        destroyScheduleType,
    };
}
