import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useWorkSchedules() {
    const workSchedule = ref(null);
    const workSchedules = ref([]);
    const employeeOptions = ref([]);
    const scheduleOptions = ref([]);
    const scheduleTypeOptions = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({ search: null, page: 1 });

    const getWorkSchedules = async (params = {}) => {
        is_loading.value = true;
        const query_str = { ...query.value, ...params };
        await axios
            .get('/api/work-schedules', { params: query_str })
            .then((response) => {
                workSchedules.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const getWorkScheduleOptions = async () => {
        const [optRes, stRes] = await Promise.all([
            axios.get('/api/work-schedules/options'),
            axios.get('/api/schedule-types/options'),
        ]);
        employeeOptions.value = optRes.data.employees;
        scheduleOptions.value = optRes.data.schedules;
        scheduleTypeOptions.value = stRes.data;
    };

    const storeWorkSchedule = async (data) => {
        is_loading.value = true;
        errors.value = {};

        try {
            await axios.post('/api/work-schedules', data).then((response) => {
                ThemeSwal.fire({ title: 'Success', icon: 'success', text: response.data.message });
                errors.value = {};
                is_loading.value = false;
                is_success.value = true;
            });
        } catch (e) {
            if (e.response.status === 422) {
                errors.value = e.response.data;
                is_success.value = false;
                is_loading.value = false;
            }
        }
    };

    const updateWorkSchedule = async (data) => {
        errors.value = {};
        is_loading.value = true;
        workSchedule.value = data;

        try {
            await axios
                .put(`/api/work-schedules/${workSchedule.value.id}`, workSchedule.value)
                .then((response) => {
                    ThemeSwal.fire({ title: 'Success', icon: 'success', text: response.data.message });
                    errors.value = {};
                    is_loading.value = false;
                    is_success.value = true;
                });
        } catch (e) {
            if (e.response.status === 422) {
                errors.value = e.response.data;
                is_success.value = false;
                is_loading.value = false;
            }
        }
    };

    const destroyWorkSchedule = async (id) => {
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
                    .delete(`/api/work-schedules/${id}`)
                    .then((response) => {
                        getWorkSchedules();
                        ThemeSwal.fire({ title: 'Deleted', text: response.data.message, icon: 'success' });
                    })
                    .catch(() => {
                        ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong!' });
                    });
            }
        });
    };

    return {
        workSchedule,
        workSchedules,
        employeeOptions,
        scheduleOptions,
        scheduleTypeOptions,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getWorkSchedules,
        getWorkScheduleOptions,
        storeWorkSchedule,
        updateWorkSchedule,
        destroyWorkSchedule,
    };
}
