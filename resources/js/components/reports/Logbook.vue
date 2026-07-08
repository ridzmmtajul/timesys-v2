<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const offices = ref([]);
const divisions = ref([]);
const rows = ref([]);
const meta = ref({});
const is_loading = ref(false);
const is_exporting = ref(false);

const today = new Date().toISOString().slice(0, 10);

const filters = ref({
    office_id: '',
    office_division_id: '',
    date: today,
});

const availableDivisions = computed(() =>
    filters.value.office_id
        ? divisions.value.filter(d => String(d.office_id) === String(filters.value.office_id))
        : divisions.value
);

const officeLabel = computed(() => meta.value.office || 'All Offices');
const divisionLabel = computed(() => meta.value.office_division || null);

// ── Office searchable dropdown ─────────────────────────────────
const officeRef    = ref(null);
const officeOpen   = ref(false);
const officeSearch = ref('');

const selectedOfficeName = computed(() =>
    offices.value.find(o => String(o.id) === String(filters.value.office_id))?.name || ''
);

const filteredOfficeList = computed(() => {
    const q = officeSearch.value.toLowerCase();
    return q
        ? offices.value.filter(o =>
            o.name.toLowerCase().includes(q) ||
            (o.code || '').toLowerCase().includes(q))
        : offices.value;
});

const selectOffice = (o) => {
    filters.value.office_id = String(o.id);
    officeOpen.value = false;
    officeSearch.value = '';
    onOfficeChange();
};

const clearOffice = () => {
    filters.value.office_id = '';
    officeOpen.value = false;
    officeSearch.value = '';
    onOfficeChange();
};

const closeOnOutside = (e) => {
    if (officeRef.value && !officeRef.value.contains(e.target)) officeOpen.value = false;
};

const getOptions = async () => {
    const res = await axios.get('/api/dtr/options');
    offices.value = res.data.data.offices;
    divisions.value = res.data.data.divisions;
};

const onOfficeChange = () => {
    if (filters.value.office_division_id) {
        const stillValid = availableDivisions.value.some(d => String(d.id) === String(filters.value.office_division_id));
        if (!stillValid) filters.value.office_division_id = '';
    }
};

const getLogbook = async () => {
    is_loading.value = true;
    try {
        const params = { date: filters.value.date };
        if (filters.value.office_id) params.office_id = filters.value.office_id;
        if (filters.value.office_division_id) params.office_division_id = filters.value.office_division_id;

        const res = await axios.get('/api/reports/logbook', { params });
        rows.value = res.data.data;
        meta.value = res.data.meta;
    } finally {
        is_loading.value = false;
    }
};

const printReport = async () => {
    is_exporting.value = true;
    try {
        const params = { date: filters.value.date };
        if (filters.value.office_id) params.office_id = filters.value.office_id;
        if (filters.value.office_division_id) params.office_division_id = filters.value.office_division_id;

        const res = await axios.get('/api/reports/logbook-export', {
            params,
            responseType: 'blob',
        });

        const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
        window.open(url, '_blank');
        setTimeout(() => window.URL.revokeObjectURL(url), 10000);
    } catch (e) {
        console.error(e);
    } finally {
        is_exporting.value = false;
    }
};

onMounted(async () => {
    document.addEventListener('mousedown', closeOnOutside);
    await getOptions();
    await getLogbook();
});

onBeforeUnmount(() => document.removeEventListener('mousedown', closeOnOutside));
</script>

<template>
    <div class="lib-page">
        <!-- Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-notebook-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Logbook Report</h5>
                    <p class="lib-header__subtitle">Daily attendance logbook by office and division</p>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="lib-card rpt-card">
            <div class="rpt-card__header">
                <v-icon icon="mdi-filter-outline" size="15" class="rpt-card__header-icon" />
                <span>Filters</span>
            </div>

            <div class="rpt-filters">
                <!-- Office Filter -->
                <div class="rpt-field">
                    <label class="rpt-label">Office</label>
                    <div class="cs-wrap" ref="officeRef">
                        <button
                            type="button"
                            class="cs-trigger"
                            :class="{ 'cs-trigger--open': officeOpen }"
                            @click="officeOpen = !officeOpen"
                        >
                            <v-icon icon="mdi-office-building-outline" size="15" class="cs-icon" />
                            <span class="cs-val" :class="{ 'cs-ph': !filters.office_id }">
                                {{ selectedOfficeName || 'All Offices' }}
                            </span>
                            <button v-if="filters.office_id" type="button" class="cs-x" @click.stop="clearOffice">
                                <v-icon icon="mdi-close" size="12" />
                            </button>
                            <v-icon
                                icon="mdi-chevron-down" size="14"
                                class="cs-chevron"
                                :class="{ 'cs-chevron--up': officeOpen }"
                            />
                        </button>
                        <div v-show="officeOpen" class="cs-dropdown">
                            <div class="cs-search-row">
                                <v-icon icon="mdi-magnify" size="13" class="cs-search-icon" />
                                <input v-model="officeSearch" class="cs-search" placeholder="Search office…" />
                            </div>
                            <div class="cs-list">
                                <div
                                    v-for="o in filteredOfficeList" :key="o.id"
                                    class="cs-opt"
                                    :class="{ 'cs-opt--active': String(filters.office_id) === String(o.id) }"
                                    @click="selectOffice(o)"
                                >
                                    <span class="cs-opt-name">{{ o.name }}</span>
                                    <span v-if="o.code" class="cs-badge">{{ o.code }}</span>
                                    <v-icon v-if="String(filters.office_id) === String(o.id)" icon="mdi-check" size="13" class="cs-tick" />
                                </div>
                                <div v-if="!filteredOfficeList.length" class="cs-empty">No offices found</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Office Division Filter -->
                <div class="rpt-field">
                    <label class="rpt-label">Office Division</label>
                    <div class="rpt-select-wrap">
                        <v-icon icon="mdi-sitemap" size="15" class="rpt-select-icon" />
                        <select v-model="filters.office_division_id" class="rpt-select">
                            <option value="">All Divisions</option>
                            <option v-for="division in availableDivisions" :key="division.id" :value="division.id">
                                {{ division.name }}
                            </option>
                        </select>
                        <v-icon icon="mdi-chevron-down" size="15" class="rpt-select-chevron" />
                    </div>
                </div>

                <!-- Date -->
                <div class="rpt-field">
                    <label class="rpt-label">Date</label>
                    <div class="rpt-select-wrap">
                        <v-icon icon="mdi-calendar" size="15" class="rpt-select-icon" />
                        <input v-model="filters.date" type="date" class="rpt-select rpt-select--input" />
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="rpt-footer">
                <div class="rpt-summary">
                    <v-icon icon="mdi-information-outline" size="14" class="rpt-summary__icon" />
                    <span>
                        Showing <strong>{{ officeLabel }}</strong>
                        <template v-if="divisionLabel"> — <strong>{{ divisionLabel }}</strong></template>
                    </span>
                </div>
                <div class="rpt-actions">
                    <button class="rpt-apply-btn" :disabled="is_loading" @click="getLogbook">
                        <v-icon v-if="is_loading" icon="mdi-loading" size="16" class="rpt-spinner" />
                        <v-icon v-else icon="mdi-magnify" size="16" />
                        {{ is_loading ? 'Loading...' : 'Apply' }}
                    </button>
                    <button class="rpt-download-btn" :disabled="is_exporting" @click="printReport">
                        <v-icon v-if="is_exporting" icon="mdi-loading" size="16" class="rpt-spinner" />
                        <v-icon v-else icon="mdi-printer-outline" size="16" />
                        {{ is_exporting ? 'Generating...' : 'Print Report' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Logbook Card -->
        <div class="lib-card lbk-card">
            <div class="lbk-table-wrap">
                <table class="lbk-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="lbk-th-name">Employee Name</th>
                            <th colspan="2">AM</th>
                            <th colspan="2">PM</th>
                            <th rowspan="2">Undertime</th>
                        </tr>
                        <tr>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.employee_id">
                            <td class="lbk-name">{{ row.employee_name }}</td>
                            <td>{{ row.am_arrival || '—' }}</td>
                            <td>{{ row.am_departure || '—' }}</td>
                            <td>{{ row.pm_arrival || '—' }}</td>
                            <td>{{ row.pm_departure || '—' }}</td>
                            <td class="lbk-undertime">{{ row.undertime_label || '' }}</td>
                        </tr>
                        <tr v-if="!is_loading && !rows.length">
                            <td colspan="6" class="lbk-empty">No attendance logged for this date.</td>
                        </tr>
                        <tr v-if="is_loading">
                            <td colspan="6" class="lbk-empty">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rpt-card {
    padding: 0;
    overflow: visible;
    position: relative;
    z-index: 20;
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
    grid-template-columns: repeat(3, 1fr);
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

.rpt-select--input {
    padding-right: 12px;
    cursor: pointer;
    color-scheme: dark;
}

.rpt-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
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

.rpt-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rpt-apply-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    border: 1px solid rgba(108, 143, 214, 0.25);
    background: rgba(255, 255, 255, 0.04);
    color: #c8d6f0;
    font-size: 13px;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.18s;
}

.rpt-apply-btn:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.08);
}

.rpt-apply-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
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

/* ── Custom searchable select (Office) ─────────────────────────── */
.cs-wrap { position: relative; }

.cs-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 12px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.25);
    border-radius: 10px;
    color: #e2e8f0;
    font-size: 13px;
    font-family: inherit;
    cursor: pointer;
    text-align: left;
    transition: border-color 0.18s, background 0.18s;
}

.cs-trigger:hover { border-color: rgba(31, 191, 184, 0.4); background: rgba(255,255,255,0.06); }
.cs-trigger--open { border-color: rgba(31, 191, 184, 0.55) !important; background: rgba(31,191,184,0.05) !important; }

.cs-icon { color: #5a78b0; flex-shrink: 0; }
.cs-val  { flex: 1; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cs-ph   { color: #5a78b0; }

.cs-x {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    color: #5a78b0;
    padding: 1px;
    border-radius: 4px;
    cursor: pointer;
    transition: color 0.12s, background 0.12s;
    flex-shrink: 0;
}
.cs-x:hover { color: #e2e8f0; background: rgba(255,255,255,0.08); }

.cs-chevron { color: #5a78b0; flex-shrink: 0; transition: transform 0.2s; }
.cs-chevron--up { transform: rotate(180deg); }

.cs-dropdown {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    z-index: 200;
    background: #0e1c3a;
    border: 1px solid rgba(108, 143, 214, 0.25);
    border-radius: 12px;
    box-shadow: 0 10px 36px rgba(0, 0, 0, 0.5), 0 2px 8px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.cs-search-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 12px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
}

.cs-search-icon { color: #5a78b0; flex-shrink: 0; }

.cs-search {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: #e2e8f0;
    font-size: 12px;
    font-family: inherit;
}
.cs-search::placeholder { color: #5a78b0; }

.cs-list {
    max-height: 210px;
    overflow-y: auto;
    padding: 4px 0;
}

.cs-opt {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    cursor: pointer;
    transition: background 0.12s;
    font-size: 13px;
    color: #e2e8f0;
}
.cs-opt:hover           { background: rgba(255, 255, 255, 0.05); }
.cs-opt--active         { background: rgba(31, 191, 184, 0.1); color: #1fbfb8; }
.cs-opt--active .cs-badge { background: rgba(31,191,184,0.12); color: #1fbfb8; }

.cs-opt-name { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.cs-badge {
    font-size: 11px;
    color: #5a78b0;
    background: rgba(108, 143, 214, 0.1);
    padding: 1px 7px;
    border-radius: 4px;
    white-space: nowrap;
    flex-shrink: 0;
}

.cs-tick { color: #1fbfb8; flex-shrink: 0; margin-left: auto; }

.cs-empty { padding: 14px; text-align: center; font-size: 12px; color: #5a78b0; }

/* ── Logbook report card ────────────────────────────────────── */
.lbk-card {
    margin-top: 16px;
    padding: 28px 24px;
    text-align: center;
}

.lbk-title {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 0.03em;
    color: #e2e8f0;
}

.lbk-office {
    margin-top: 6px;
    font-size: 14px;
    color: #c8d6f0;
}

.lbk-date {
    margin-top: 2px;
    font-size: 13px;
    color: #8aa0d7;
}

.lbk-date strong {
    color: #c8d6f0;
    text-decoration: underline;
}

.lbk-updated {
    margin-top: 10px;
    text-align: right;
    font-size: 12px;
    color: #5a78b0;
}

.lbk-table-wrap {
    overflow-x: auto;
}

.lbk-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.lbk-table th,
.lbk-table td {
    padding: 12px 14px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.12);
    border-right: 1px solid rgba(108, 143, 214, 0.1);
    text-align: center;
    color: #e2e8f0;
}

.lbk-table th:last-child,
.lbk-table td:last-child {
    border-right: none;
}

.lbk-table thead th {
    background: rgba(31, 191, 184, 0.06);
    color: #1fbfb8;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
}

.lbk-th-name {
    text-align: left;
}

.lbk-name {
    text-align: left;
    color: #c8d6f0;
}

.lbk-undertime {
    color: #f87171;
    font-weight: 600;
}

.lbk-empty {
    padding: 40px 20px;
    color: #8aa0d7;
}

@media (max-width: 900px) {
    .rpt-filters {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 560px) {
    .rpt-filters {
        grid-template-columns: 1fr;
    }
}
</style>
