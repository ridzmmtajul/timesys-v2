import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useOfficeDivisions() {
    const division = ref(null);
    const divisions = ref([]);
    const officeOptions = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getDivisions = async (params = {}) => {
        is_loading.value = true;

        await axios
            .get('/api/office-divisions', { params: { ...query.value, ...params } })
            .then((response) => {
                divisions.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            });
    };

    const getOfficeOptions = async () => {
        await axios.get('/api/offices/options').then((response) => {
            officeOptions.value = response.data;
        });
    };

    const storeDivision = async (data) => {
        is_loading.value = true;
        errors.value = '';

        try {
            await axios
                .post('/api/office-divisions', data)
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

    const updateDivision = async (data) => {
        errors.value = '';
        is_loading.value = true;
        division.value = data;

        try {
            await axios
                .put(`/api/office-divisions/${division.value.id}`, division.value)
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

    const destroyDivision = async (id) => {
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
                    .delete(`/api/office-divisions/${id}`)
                    .then((response) => {
                        getDivisions();
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
        division,
        divisions,
        officeOptions,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        getDivisions,
        getOfficeOptions,
        storeDivision,
        updateDivision,
        destroyDivision,
    };
}
