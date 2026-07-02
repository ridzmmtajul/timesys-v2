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
    };
}
