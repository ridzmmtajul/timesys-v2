import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useHolidays() {
    const holiday = ref(null);
    const holidays = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getHolidays = async (params = {}) => {
        is_loading.value = true;

        let query_str = { ...query.value, ...params };
        await axios
            .get('/api/holidays?page=' + query.value.page, query_str)
            .then((response) => {
                holidays.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const storeHoliday = async (data) => {
        is_loading.value = true;
        errors.value = "";

        try {
            await axios
                .post('/api/holidays', data)
                .then((response) => {
                    ThemeSwal.fire({
                        title: "Success",
                        icon: "success",
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

    const updateHoliday = async (data) => {
        errors.value = "";
        is_loading.value = true;
        holiday.value = data;

        try {
            await axios
                .put(`/api/holidays/${holiday.value.id}`, holiday.value)
                .then((response) => {
                    ThemeSwal.fire({
                        title: "Success",
                        icon: "success",
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

    const updateHolidayStatus = async (data) => {
        errors.value = {};

        try {
            const response = await axios.put(`/api/holidays/${data.id}`, data);

            ThemeSwal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: response.data?.message || "Status successfully updated.",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
            });

            is_success.value = true;
            return true;
        } catch (e) {
            is_success.value = false;

            if (e.response?.status == 422) {
                errors.value = e.response.data;
            }

            ThemeSwal.fire({
                icon: "error",
                title: "Oops...",
                text: e.response?.data?.message || "Unable to update holiday status.",
            });

            return false;
        }
    };

    const destroyHoliday = async (id) => {
        ThemeSwal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it!",
            customClass: {
                ...swalClass,
                confirmButton: 'ts-swal-btn ts-swal-btn--danger',
            },
        }).then((result) => {
            if (result.value) {
                axios
                    .delete(`/api/holidays/${id}`)
                    .then((response) => {
                        getHolidays();
                        ThemeSwal.fire({
                            title: "Deleted",
                            text: response.data.message,
                            icon: "success",
                        });
                    })
                    .catch(() => {
                        ThemeSwal.fire({
                            icon: "error",
                            title: "Oops...",
                            text: "Something went wrong!",
                        });
                    });
            }
        });
    };

    return {
        holiday,
        holidays,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getHolidays,
        storeHoliday,
        updateHoliday,
        updateHolidayStatus,
        destroyHoliday,
    };
}
