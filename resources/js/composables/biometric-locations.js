import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../utils/swal.js';

export default function useBiometricLocations() {
    const biometricLocation = ref(null);
    const biometricLocations = ref([]);
    const is_loading = ref(false);
    const is_success = ref(false);
    const errors = ref({});
    const pagination = ref({});
    const query = ref({
        search: null,
        page: 1,
    });

    const getBiometricLocations = async (params = {}) => {
        is_loading.value = true;

        await axios
            .get('/api/biometric-locations', { params: { ...query.value, ...params } })
            .then((response) => {
                biometricLocations.value = response.data.data;
                pagination.value = response.data.meta;
                is_loading.value = false;
            })
    }

    const storeBiometricLocation = async (data) => {
        is_loading.value = true;
        errors.value = "";

        try {
            await axios
                .post(`/api/biometric-locations`, data)
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
    }

    const updateBiometricLocation = async (data) => {
        errors.value = "";
        is_loading.value = true;
        biometricLocation.value = data;

        try {
            await axios
                .put(`/api/biometric-locations/${biometricLocation.value.id}`, biometricLocation.value)
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
    }

    const destroyBiometricLocation = async (id) => {
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
                    .delete(`/api/biometric-locations/${id}`)
                    .then((response) => {
                        getBiometricLocations();
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
    }

    return {
        biometricLocation,
        biometricLocations,
        is_loading,
        is_success,
        errors,
        pagination,
        query,
        storeBiometricLocation,
        updateBiometricLocation,
        destroyBiometricLocation,
        getBiometricLocations,
    }
}
