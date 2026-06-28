import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useEmployees() {
    const employee = ref(null);
    const employees = ref([]);
    const officeOptions = ref([]);
    const employmentTypeOptions = ref([]);
    const positionOptions = ref([]);
    const officeDivisionOptions = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        office_id: null,
        is_active: null,
        page: 1,
    });

    const getEmployees = async (params = {}) => {
        is_loading.value = true;

        let query_str = { ...query.value, ...params };
        await axios
            .get('/api/employees?page=' + query.value.page, { params: query_str })
            .then((response) => {
                employees.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const getEmployeeOptions = async () => {
        await axios.get('/api/employees/options').then((response) => {
            officeOptions.value = response.data.data.offices;
            employmentTypeOptions.value = response.data.data.employment_types;
            positionOptions.value = response.data.data.positions;
            officeDivisionOptions.value = response.data.data.office_divisions;
        });
    };

    const storeEmployee = async (data) => {
        is_loading.value = true;
        errors.value = {};

        try {
            await axios
                .post('/api/employees', data)
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

    const updateEmployee = async (data) => {
        errors.value = {};
        is_loading.value = true;
        employee.value = data;

        try {
            await axios
                .put(`/api/employees/${employee.value.id}`, employee.value)
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

    const destroyEmployee = async (id) => {
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
                    .delete(`/api/employees/${id}`)
                    .then((response) => {
                        getEmployees();
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
        employee,
        employees,
        officeOptions,
        employmentTypeOptions,
        positionOptions,
        officeDivisionOptions,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getEmployees,
        getEmployeeOptions,
        storeEmployee,
        updateEmployee,
        destroyEmployee,
    };
}
