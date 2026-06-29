<script setup>
import { ref, onMounted, computed } from 'vue';

const offices = ref([]);
const filters = ref({ office_id: '', status: 'all' });
const is_loading = ref(false);

const selectedOfficeName = computed(() => {
    if (!filters.value.office_id) return 'All Offices';
    const found = offices.value.find(o => o.id == filters.value.office_id);
    return found ? found.name : 'All Offices';
});

const getOffices = async () => {
    const res = await axios.get('/api/offices/options');
    offices.value = res.data;
};

onMounted(() => getOffices());

const download = async () => {
    is_loading.value = true;
    try {
        const params = {};
        if (filters.value.office_id) params.office_id = filters.value.office_id;
        if (filters.value.status !== 'all') params.status = filters.value.status;

        const res = await axios.get('/api/reports/employee-list', {
            params,
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([res.data], { type: 'text/csv' }));
        const link = document.createElement('a');
        link.href = url;
        const disposition = res.headers['content-disposition'];
        const match = disposition?.match(/filename="(.+)"/);
        link.setAttribute('download', match ? match[1] : 'employee_list.csv');
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (e) {
        console.error(e);
    } finally {
        is_loading.value = false;
    }
};
</script>

<template>
    <div class="lib-page">
        <!-- Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-file-chart-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Employee List Report</h5>
                    <p class="lib-header__subtitle">Filter and export employee data to Excel</p>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="lib-card rpt-card">
            <div class="rpt-card__header">
                <v-icon icon="mdi-filter-outline" size="15" class="rpt-card__header-icon" />
                <span>Export Filters</span>
            </div>

            <div class="rpt-filters">
                <!-- Office Filter -->
                <div class="rpt-field">
                    <label class="rpt-label">Office</label>
                    <div class="rpt-select-wrap">
                        <v-icon icon="mdi-office-building-outline" size="15" class="rpt-select-icon" />
                        <select v-model="filters.office_id" class="rpt-select">
                            <option value="">All Offices</option>
                            <option v-for="office in offices" :key="office.id" :value="office.id">
                                {{ office.name }}
                            </option>
                        </select>
                        <v-icon icon="mdi-chevron-down" size="15" class="rpt-select-chevron" />
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="rpt-field">
                    <label class="rpt-label">Status</label>
                    <div class="rpt-status-group">
                        <button
                            class="rpt-status-btn"
                            :class="{ active: filters.status === 'all' }"
                            @click="filters.status = 'all'"
                        >All</button>
                        <button
                            class="rpt-status-btn"
                            :class="{ active: filters.status === 'active' }"
                            @click="filters.status = 'active'"
                        >Active</button>
                        <button
                            class="rpt-status-btn"
                            :class="{ active: filters.status === 'inactive' }"
                            @click="filters.status = 'inactive'"
                        >Inactive</button>
                    </div>
                </div>
            </div>

            <!-- Summary & Download -->
            <div class="rpt-footer">
                <div class="rpt-summary">
                    <v-icon icon="mdi-information-outline" size="14" class="rpt-summary__icon" />
                    <span>
                        Downloading <strong>{{ selectedOfficeName }}</strong> —
                        <strong>{{ filters.status === 'all' ? 'All statuses' : filters.status === 'active' ? 'Active only' : 'Inactive only' }}</strong>
                    </span>
                </div>
                <button class="rpt-download-btn" :disabled="is_loading" @click="download">
                    <v-icon v-if="is_loading" icon="mdi-loading" size="16" class="rpt-spinner" />
                    <v-icon v-else icon="mdi-microsoft-excel" size="16" />
                    {{ is_loading ? 'Generating...' : 'Download Excel' }}
                </button>
            </div>
        </div>

        <!-- Info Card -->
        <div class="rpt-info-card">
            <v-icon icon="mdi-table-column" size="15" class="rpt-info-card__icon" />
            <div>
                <p class="rpt-info-card__title">Exported columns</p>
                <p class="rpt-info-card__cols">
                    Employee No &nbsp;·&nbsp; Last Name &nbsp;·&nbsp; First Name &nbsp;·&nbsp; Middle Name &nbsp;·&nbsp;
                    Extension &nbsp;·&nbsp; Gender &nbsp;·&nbsp; Job Title &nbsp;·&nbsp; Position &nbsp;·&nbsp;
                    Office &nbsp;·&nbsp; Office Division &nbsp;·&nbsp; Employment Type &nbsp;·&nbsp; Contact No &nbsp;·&nbsp; Status
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rpt-card {
    padding: 0;
    overflow: visible;
}

.rpt-card__header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
    font-size: 12px;
    font-weight: 600;
    color: #8aa0d7;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rpt-card__header-icon {
    color: #1fbfb8;
}

.rpt-filters {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 22px 20px;
}

.rpt-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rpt-label {
    font-size: 12px;
    font-weight: 600;
    color: #8aa0d7;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.rpt-select-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.rpt-select-icon {
    position: absolute;
    left: 12px;
    color: #5a78b0;
    pointer-events: none;
    z-index: 1;
}

.rpt-select-chevron {
    position: absolute;
    right: 12px;
    color: #5a78b0;
    pointer-events: none;
}

.rpt-select {
    width: 100%;
    padding: 10px 36px 10px 34px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.25);
    border-radius: 10px;
    color: #e2e8f0;
    font-size: 13px;
    font-family: inherit;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    outline: none;
    transition: border-color 0.18s;
}

.rpt-select:focus {
    border-color: rgba(31, 191, 184, 0.5);
}

.rpt-select option {
    background: #0e1c3a;
    color: #e2e8f0;
}

.rpt-status-group {
    display: flex;
    gap: 6px;
}

.rpt-status-btn {
    flex: 1;
    padding: 10px 0;
    border-radius: 10px;
    border: 1px solid rgba(108, 143, 214, 0.25);
    background: rgba(255, 255, 255, 0.04);
    color: #8aa0d7;
    font-size: 13px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.18s;
}

.rpt-status-btn:hover:not(.active) {
    background: rgba(255, 255, 255, 0.07);
    color: #e2e8f0;
}

.rpt-status-btn.active {
    background: rgba(31, 191, 184, 0.15);
    border-color: rgba(31, 191, 184, 0.4);
    color: #1fbfb8;
    font-weight: 600;
}

.rpt-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-top: 1px solid rgba(108, 143, 214, 0.12);
    background: rgba(255, 255, 255, 0.02);
}

.rpt-summary {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    color: #8aa0d7;
}

.rpt-summary__icon {
    color: #5a78b0;
    flex-shrink: 0;
}

.rpt-summary strong {
    color: #c8d6f0;
}

.rpt-download-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #1fbfb8 0%, #05716c 100%);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    box-shadow: 0 2px 12px rgba(31, 191, 184, 0.3);
    transition: all 0.18s;
}

.rpt-download-btn:hover:not(:disabled) {
    box-shadow: 0 4px 18px rgba(31, 191, 184, 0.45);
    transform: translateY(-1px);
}

.rpt-download-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.rpt-spinner {
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.rpt-info-card {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 14px;
    padding: 14px 18px;
    background: rgba(31, 191, 184, 0.05);
    border: 1px solid rgba(31, 191, 184, 0.15);
    border-radius: 12px;
}

.rpt-info-card__icon {
    color: #1fbfb8;
    margin-top: 2px;
    flex-shrink: 0;
}

.rpt-info-card__title {
    font-size: 12px;
    font-weight: 600;
    color: #1fbfb8;
    margin: 0 0 4px;
}

.rpt-info-card__cols {
    font-size: 12px;
    color: #8aa0d7;
    margin: 0;
    line-height: 1.6;
}
</style>
