import { ref } from 'vue';
import axios from 'axios';

export default function useDtr() {
    const officeOptions   = ref([]);
    const divisionOptions = ref([]);
    const employeeOptions = ref([]);
    const dtrResult       = ref(null);
    const is_loading      = ref(false);
    const is_pdf_loading  = ref(false);
    const errors          = ref({});

    const checkinoutLogs     = ref([]);
    const checkinoutEmployee = ref(null);
    const is_checkinout_loading = ref(false);

    const workSchedules        = ref([]);
    const workScheduleEmployee = ref(null);
    const is_workschedule_loading = ref(false);

    const getOptions = async () => {
        const res = await axios.get('/api/dtr/options');
        officeOptions.value   = res.data.data.offices;
        divisionOptions.value = res.data.data.divisions;
        employeeOptions.value = res.data.data.employees;
    };

    const generateDtr = async (payload) => {
        is_loading.value = true;
        errors.value = {};
        try {
            const res = await axios.post('/api/dtr/generate', payload);
            dtrResult.value = res.data;
            return true;
        } catch (e) {
            if (e.response?.status === 422) {
                errors.value = e.response.data.errors ?? {};
            }
            return false;
        } finally {
            is_loading.value = false;
        }
    };

    const downloadPdf = async (payload) => {
        is_pdf_loading.value = true;
        try {
            const res = await axios.post('/api/dtr/pdf', payload, { responseType: 'blob' });
            const url = URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
            window.open(url, '_blank');
            setTimeout(() => URL.revokeObjectURL(url), 10000);
        } catch (e) {
            console.error('PDF generation failed', e);
        } finally {
            is_pdf_loading.value = false;
        }
    };

    const fetchCheckinoutLogs = async (payload) => {
        is_checkinout_loading.value = true;
        try {
            const res = await axios.get('/api/dtr/checkinout', { params: payload });
            checkinoutLogs.value     = res.data.data;
            checkinoutEmployee.value = res.data.employee;
            return true;
        } catch (e) {
            checkinoutLogs.value = [];
            return false;
        } finally {
            is_checkinout_loading.value = false;
        }
    };

    const fetchWorkSchedules = async (payload) => {
        is_workschedule_loading.value = true;
        try {
            const res = await axios.get('/api/dtr/workschedule', { params: payload });
            workSchedules.value       = res.data.data;
            workScheduleEmployee.value = res.data.employee;
            return true;
        } catch (e) {
            workSchedules.value = [];
            return false;
        } finally {
            is_workschedule_loading.value = false;
        }
    };

    return {
        officeOptions,
        divisionOptions,
        employeeOptions,
        dtrResult,
        is_loading,
        is_pdf_loading,
        errors,
        getOptions,
        generateDtr,
        downloadPdf,
        checkinoutLogs,
        checkinoutEmployee,
        is_checkinout_loading,
        fetchCheckinoutLogs,
        workSchedules,
        workScheduleEmployee,
        is_workschedule_loading,
        fetchWorkSchedules,
    };
}
