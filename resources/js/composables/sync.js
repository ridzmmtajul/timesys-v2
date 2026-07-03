import { ref } from 'vue';
import axios from 'axios';
import ThemeSwal from '../utils/swal.js';

export const SYNC_MODULES = [
    { key: 'employees', label: 'Employees', icon: 'mdi mdi-account-group', endpoint: 'push-employees' },
    { key: 'work_schedules', label: 'Work Schedules', icon: 'mdi mdi-calendar-account', endpoint: 'push-work-schedules' },
    { key: 'attendances', label: 'Attendances', icon: 'mdi mdi-fingerprint', endpoint: 'push-attendances' },
];

export default function useSync() {
    const pendingCounts = ref({});
    const logs = ref([]);
    const pagination = ref({});
    const is_loading = ref(false);
    const syncingAll = ref(false);

    const getPendingCounts = async () => {
        const { data } = await axios.get('/api/sync/pending-counts');
        pendingCounts.value = data;
    };

    const getLogs = async (page = 1) => {
        is_loading.value = true;
        try {
            const { data } = await axios.get('/api/sync/logs?page=' + page);
            logs.value = data.data;
            pagination.value = {
                current_page: data.current_page,
                last_page: data.last_page,
                total: data.total,
            };
        } finally {
            is_loading.value = false;
        }
    };

    const pushAll = async () => {
        syncingAll.value = true;
        const results = [];

        try {
            for (const mod of SYNC_MODULES) {
                try {
                    const { data } = await axios.post(`/api/sync/${mod.endpoint}`);
                    results.push({ mod, data });
                } catch (error) {
                    results.push({ mod, error: error?.response?.data?.message || `Unable to sync ${mod.label.toLowerCase()}.` });
                }
            }

            const totalSynced = results.reduce((sum, r) => sum + (r.data?.synced ?? 0), 0);
            const totalExisting = results.reduce((sum, r) => sum + (r.data?.existing ?? 0), 0);
            const totalSkipped = results.reduce((sum, r) => sum + (r.data?.skipped ?? 0), 0);
            const hasIssues = results.some((r) => r.error || (Array.isArray(r.data?.errors) && r.data.errors.length > 0));

            ThemeSwal.fire({
                icon: hasIssues ? 'warning' : 'success',
                title: hasIssues ? 'Sync Completed with Issues' : 'Sync Complete',
                html: `
                    <div class="sync-summary">
                        ${results.map((r) => `
                            <div class="sync-summary__row">
                                <span class="sync-summary__label">${r.mod.label}</span>
                                ${r.error ? `
                                    <span class="sync-summary__error">${r.error}</span>
                                ` : `
                                    <span class="sync-summary__stats">
                                        <span class="sync-summary__stat sync-summary__stat--synced">${r.data.synced} synced</span>
                                        <span class="sync-summary__stat sync-summary__stat--existing">${r.data.existing} existing</span>
                                        <span class="sync-summary__stat sync-summary__stat--skipped">${r.data.skipped} skipped</span>
                                    </span>
                                `}
                            </div>
                        `).join('')}
                        <div class="sync-summary__total">
                            <strong>${totalSynced}</strong> synced &nbsp;·&nbsp; <strong>${totalExisting}</strong> existing &nbsp;·&nbsp; <strong>${totalSkipped}</strong> skipped
                        </div>
                    </div>
                `,
            });

            await Promise.all([getPendingCounts(), getLogs()]);
        } finally {
            syncingAll.value = false;
        }
    };

    return {
        pendingCounts,
        logs,
        pagination,
        is_loading,
        syncingAll,
        getPendingCounts,
        getLogs,
        pushAll,
    };
}
