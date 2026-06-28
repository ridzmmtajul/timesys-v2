import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useSchedules() {
    const schedule = ref(null);
    const schedules = ref([]);
    const scheduleTypeOptions = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getSchedules = async (params = {}) => {
        is_loading.value = true;

        let query_str = { ...query.value, ...params };
        await axios
            .get('/api/schedules?page=' + query.value.page, query_str)
            .then((response) => {
                schedules.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const getScheduleTypeOptions = async () => {
        await axios.get('/api/schedule-types/options').then((response) => {
            scheduleTypeOptions.value = response.data;
        });
    };

    const storeSchedule = async (data) => {
        is_loading.value = true;
        errors.value = '';

        try {
            await axios
                .post('/api/schedules', data)
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

    const updateSchedule = async (data) => {
        errors.value = '';
        is_loading.value = true;
        schedule.value = data;

        try {
            await axios
                .put(`/api/schedules/${schedule.value.id}`, schedule.value)
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

    const destroySchedule = async (id) => {
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
                    .delete(`/api/schedules/${id}`)
                    .then((response) => {
                        getSchedules();
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
        schedule,
        schedules,
        scheduleTypeOptions,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getSchedules,
        getScheduleTypeOptions,
        storeSchedule,
        updateSchedule,
        destroySchedule,
    };
}
