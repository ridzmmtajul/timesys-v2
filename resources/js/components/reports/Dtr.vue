<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import useDtr from '../../composables/dtr.js';

const {
    officeOptions, divisionOptions, employeeOptions,
    dtrResult, is_loading, is_pdf_loading, errors,
    getOptions, generateDtr, downloadPdf,
    checkinoutLogs, is_checkinout_loading, fetchCheckinoutLogs,
    workSchedules, is_workschedule_loading, fetchWorkSchedules,
} = useDtr();

const step = ref('form');

const currentYear  = new Date().getFullYear();
const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');

const filters = ref({
    employee_ids:         [],
    office_id:            '',
    division_id:          '',
    date_range:           '1-15',
    month_num:            currentMonth,
    year_num:             String(currentYear),
    signatory_id:         '',
    signatory_custom:     '',
    use_custom_signatory: false,
    with_saturday:        true,
    with_holiday:         true,
    show_signature:       true,
});

const searchQuery = ref('');
const currentPage = ref(1);
const PAGE_SIZE   = 10;

const monthNames = [
    { value: '01', label: 'January' },  { value: '02', label: 'February' },
    { value: '03', label: 'March' },    { value: '04', label: 'April' },
    { value: '05', label: 'May' },      { value: '06', label: 'June' },
    { value: '07', label: 'July' },     { value: '08', label: 'August' },
    { value: '09', label: 'September' },{ value: '10', label: 'October' },
    { value: '11', label: 'November' }, { value: '12', label: 'December' },
];

const years = computed(() => [currentYear, currentYear - 1, currentYear - 2, currentYear - 3]);

const dateRanges = [
    { value: '1-15',   label: '1-15' },
    { value: '16-end', label: '16-End' },
    { value: 'full',   label: 'Full Month' },
];

// ── Custom select state ────────────────────────────────────────
const officeRef    = ref(null);
const officeOpen   = ref(false);
const officeSearch = ref('');

const divRef    = ref(null);
const divOpen   = ref(false);
const divSearch = ref('');

const sigRef    = ref(null);
const sigOpen   = ref(false);
const sigSearch = ref('');

// ── Filtered divisions ─────────────────────────────────────────
const availableDivisions = computed(() =>
    filters.value.office_id
        ? divisionOptions.value.filter(d => String(d.office_id) === String(filters.value.office_id))
        : []
);

// ── Custom select filtered lists ───────────────────────────────
const filteredOfficeList = computed(() => {
    const q = officeSearch.value.toLowerCase();
    return q
        ? officeOptions.value.filter(o =>
            o.name.toLowerCase().includes(q) ||
            (o.code || '').toLowerCase().includes(q))
        : officeOptions.value;
});

const filteredDivisionList = computed(() => {
    const q = divSearch.value.toLowerCase();
    return q
        ? availableDivisions.value.filter(d =>
            d.name.toLowerCase().includes(q) ||
            (d.code || '').toLowerCase().includes(q))
        : availableDivisions.value;
});

const filteredSignatoryList = computed(() => {
    const q = sigSearch.value.toLowerCase();
    return q
        ? employeeOptions.value.filter(e =>
            e.full_name.toLowerCase().includes(q) ||
            String(e.employee_no).includes(sigSearch.value))
        : employeeOptions.value;
});

// ── Selected display names ─────────────────────────────────────
const selectedOfficeName = computed(() => {
    if (!filters.value.office_id) return '';
    return officeOptions.value.find(o => String(o.id) === String(filters.value.office_id))?.name ?? '';
});

const selectedDivisionName = computed(() => {
    if (!filters.value.division_id) return '';
    return divisionOptions.value.find(d => String(d.id) === String(filters.value.division_id))?.name ?? '';
});

// ── Custom select actions ──────────────────────────────────────
const selectOffice = (o) => {
    filters.value.office_id = String(o.id);
    officeOpen.value  = false;
    officeSearch.value = '';
};
const clearOffice = () => {
    filters.value.office_id = '';
    officeOpen.value  = false;
    officeSearch.value = '';
};

const selectDivision = (d) => {
    filters.value.division_id = String(d.id);
    divOpen.value  = false;
    divSearch.value = '';
};
const clearDivision = () => {
    filters.value.division_id = '';
    divOpen.value  = false;
    divSearch.value = '';
};

const selectSignatory = (e) => {
    filters.value.signatory_id = String(e.id);
    sigOpen.value  = false;
    sigSearch.value = '';
};

// ── Filtered + paginated employees ────────────────────────────
const filteredEmployees = computed(() => {
    let emps = employeeOptions.value;
    if (filters.value.office_id)
        emps = emps.filter(e => String(e.office_id) === String(filters.value.office_id));
    if (filters.value.division_id)
        emps = emps.filter(e => String(e.office_division_id) === String(filters.value.division_id));
    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase();
        emps = emps.filter(e =>
            (e.last_name  || '').toLowerCase().includes(q) ||
            (e.first_name || '').toLowerCase().includes(q) ||
            String(e.employee_no).includes(q)
        );
    }
    return emps;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredEmployees.value.length / PAGE_SIZE)));

const paginatedEmployees = computed(() => {
    const start = (currentPage.value - 1) * PAGE_SIZE;
    return filteredEmployees.value.slice(start, start + PAGE_SIZE);
});

watch([() => filters.value.office_id, () => filters.value.division_id, searchQuery], () => {
    currentPage.value = 1;
});
watch(() => filters.value.office_id, () => { filters.value.division_id = ''; });
watch(() => filters.value.use_custom_signatory, (v) => { if (v) sigOpen.value = false; });

// ── Employee selection ─────────────────────────────────────────
const allFilteredSelected = computed(() =>
    filteredEmployees.value.length > 0 &&
    filteredEmployees.value.every(e => filters.value.employee_ids.includes(e.id))
);

const someFilteredSelected = computed(() =>
    filteredEmployees.value.some(e => filters.value.employee_ids.includes(e.id)) &&
    !allFilteredSelected.value
);

const toggleEmployee = (id) => {
    const idx = filters.value.employee_ids.indexOf(id);
    if (idx === -1) filters.value.employee_ids.push(id);
    else filters.value.employee_ids.splice(idx, 1);
};

const toggleSelectAll = () => {
    if (allFilteredSelected.value) {
        const ids = new Set(filteredEmployees.value.map(e => e.id));
        filters.value.employee_ids = filters.value.employee_ids.filter(id => !ids.has(id));
    } else {
        const current = new Set(filters.value.employee_ids);
        filteredEmployees.value.forEach(e => current.add(e.id));
        filters.value.employee_ids = [...current];
    }
};

// ── Signatory ─────────────────────────────────────────────────
const signatoryName = computed(() => {
    if (filters.value.use_custom_signatory) return filters.value.signatory_custom;
    if (!filters.value.signatory_id) return '';
    return employeeOptions.value.find(e => String(e.id) === String(filters.value.signatory_id))?.full_name ?? '';
});

// ── Payload / Generate / Reset / PDF ──────────────────────────
const buildPayload = () => ({
    employee_ids:        filters.value.employee_ids,
    month:               `${filters.value.year_num}-${filters.value.month_num}`,
    date_range:          filters.value.date_range,
    official_timein_AM:  '08:00',
    official_timeout_AM: '12:00',
    official_timein_PM:  '13:00',
    official_timeout_PM: '17:00',
    regular_days:        '',
    saturdays:           '',
    signatory:           signatoryName.value,
    with_saturday:       filters.value.with_saturday,
    with_holiday:        filters.value.with_holiday,
    show_signature:      filters.value.show_signature,
});

const generate = async () => {
    const ok = await generateDtr(buildPayload());
    if (ok) step.value = 'preview';
};

const resetForm = () => {
    filters.value = {
        employee_ids:         [],
        office_id:            '',
        division_id:          '',
        date_range:           '1-15',
        month_num:            currentMonth,
        year_num:             String(currentYear),
        signatory_id:         '',
        signatory_custom:     '',
        use_custom_signatory: false,
        with_saturday:        true,
        with_holiday:         true,
        show_signature:       true,
    };
    searchQuery.value  = '';
    currentPage.value  = 1;
    officeSearch.value = '';
    divSearch.value    = '';
    sigSearch.value    = '';
};

const triggerPdfDownload = () => downloadPdf(buildPayload());

// ── Outside-click handler ──────────────────────────────────────
const closeOnOutside = (e) => {
    if (officeRef.value && !officeRef.value.contains(e.target)) officeOpen.value = false;
    if (divRef.value    && !divRef.value.contains(e.target))    divOpen.value    = false;
    if (sigRef.value    && !sigRef.value.contains(e.target))    sigOpen.value    = false;
};

onMounted(() => {
    getOptions();
    document.addEventListener('mousedown', closeOnOutside);
});
onBeforeUnmount(() => document.removeEventListener('mousedown', closeOnOutside));

// ── Day-type visibility ────────────────────────────────────────
const showDayType = (rec) => {
    if (!rec?.day_type) return false;
    if (rec.day_type === 'HOLIDAY') return dtrResult.value.with_holiday;
    if (rec.day_type === 'SATURDAY' || rec.day_type === 'SUNDAY') return dtrResult.value.with_saturday;
    return true;
};

// ── Per-employee DTR page actions ──────────────────────────────
const verifyModalOpen = ref(false);
const verifyEmployee  = ref(null);
const verifyDates     = ref({ from_date: '', to_date: '' });

const loadCheckinoutLogs = () => {
    if (!verifyEmployee.value) return;
    fetchCheckinoutLogs({
        employee_id: verifyEmployee.value.id,
        from_date:   verifyDates.value.from_date,
        to_date:     verifyDates.value.to_date,
    });
};

const verifyTime = (emp) => {
    verifyEmployee.value = emp;
    const y = filters.value.year_num;
    const m = filters.value.month_num;
    const fromDay = String(dtrResult.value.from_day).padStart(2, '0');
    const toDay   = String(dtrResult.value.to_day).padStart(2, '0');
    verifyDates.value = { from_date: `${y}-${m}-${fromDay}`, to_date: `${y}-${m}-${toDay}` };
    verifyModalOpen.value = true;
    loadCheckinoutLogs();
};

const closeVerifyModal = () => {
    verifyModalOpen.value = false;
    verifyEmployee.value  = null;
    checkinoutLogs.value  = [];
};

const fmtDateTime = (dt) => {
    if (!dt) return '';
    return new Date(dt).toLocaleString('en-PH', {
        month: 'short', day: 'numeric', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
    });
};

// ── Work Schedule modal ────────────────────────────────────────
const workScheduleModalOpen = ref(false);
const workScheduleEmp       = ref(null);
const workScheduleRange     = ref({ from_date: '', to_date: '' });

const loadWorkSchedules = () => {
    if (!workScheduleEmp.value) return;
    fetchWorkSchedules({
        employee_id: workScheduleEmp.value.id,
        from_date:   workScheduleRange.value.from_date,
        to_date:     workScheduleRange.value.to_date,
    });
};

const viewWorkSchedule = (emp) => {
    workScheduleEmp.value = emp;
    const y = filters.value.year_num;
    const m = filters.value.month_num;
    const fromDay = String(dtrResult.value.from_day).padStart(2, '0');
    const toDay   = String(dtrResult.value.to_day).padStart(2, '0');
    workScheduleRange.value = { from_date: `${y}-${m}-${fromDay}`, to_date: `${y}-${m}-${toDay}` };
    workScheduleModalOpen.value = true;
    loadWorkSchedules();
};

const closeWorkScheduleModal = () => {
    workScheduleModalOpen.value = false;
    workScheduleEmp.value       = null;
    workSchedules.value         = [];
};

const fmtDate = (d) => {
    if (!d) return '—';
    return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
};

const fmtDays = (days) => (days && days.length ? days.map(d => d.substring(0, 3)).join(', ') : '—');

const viewSoloPosting = (emp) => {};

// ── Time format helper ─────────────────────────────────────────
const fmt = (t) => {
    if (!t) return '';
    const [h, m] = t.split(':').map(Number);
    return `${h % 12 || 12}:${String(m).padStart(2, '0')}`;
};
</script>

<template>
    <!-- ── Step 1: Form ─────────────────────────────────── -->
    <div v-if="step === 'form'" class="lib-page">
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-file-document-edit-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Generate Daily Time Record</h5>
                    <p class="lib-header__subtitle">Select employees and configure options to generate CS Form No. 48</p>
                </div>
            </div>
        </div>

        <div class="dtr-two-col">

            <!-- ── Left: Employee selection ── -->
            <div class="dtr-col-left">

                <!-- Office + Division custom selects -->
                <div class="dtr-filter-area">

                    <!-- Office -->
                    <div class="cs-wrap" ref="officeRef">
                        <button
                            class="cs-trigger"
                            :class="{ 'cs-trigger--open': officeOpen }"
                            @click="officeOpen = !officeOpen"
                        >
                            <v-icon icon="mdi-office-building-outline" size="15" class="cs-icon" />
                            <span class="cs-val" :class="{ 'cs-ph': !filters.office_id }">
                                {{ selectedOfficeName || 'Select office' }}
                            </span>
                            <button v-if="filters.office_id" class="cs-x" @click.stop="clearOffice">
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

                    <!-- Division -->
                    <div class="cs-wrap" ref="divRef">
                        <button
                            class="cs-trigger"
                            :class="{ 'cs-trigger--open': divOpen, 'cs-trigger--disabled': !filters.office_id }"
                            :disabled="!filters.office_id"
                            @click="divOpen = !divOpen"
                        >
                            <v-icon icon="mdi-domain" size="15" class="cs-icon" />
                            <span class="cs-val" :class="{ 'cs-ph': !filters.division_id }">
                                {{ selectedDivisionName || (!filters.office_id ? 'Select office first' : 'Select division') }}
                            </span>
                            <button v-if="filters.division_id" class="cs-x" @click.stop="clearDivision">
                                <v-icon icon="mdi-close" size="12" />
                            </button>
                            <v-icon
                                icon="mdi-chevron-down" size="14"
                                class="cs-chevron"
                                :class="{ 'cs-chevron--up': divOpen }"
                            />
                        </button>
                        <div v-show="divOpen" class="cs-dropdown">
                            <div class="cs-search-row">
                                <v-icon icon="mdi-magnify" size="13" class="cs-search-icon" />
                                <input v-model="divSearch" class="cs-search" placeholder="Search division…" />
                            </div>
                            <div class="cs-list">
                                <div
                                    v-for="d in filteredDivisionList" :key="d.id"
                                    class="cs-opt"
                                    :class="{ 'cs-opt--active': String(filters.division_id) === String(d.id) }"
                                    @click="selectDivision(d)"
                                >
                                    <span class="cs-opt-name">{{ d.name }}</span>
                                    <span v-if="d.code" class="cs-badge">{{ d.code }}</span>
                                    <v-icon v-if="String(filters.division_id) === String(d.id)" icon="mdi-check" size="13" class="cs-tick" />
                                </div>
                                <div v-if="!filteredDivisionList.length" class="cs-empty">No divisions found</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toolbar: Select All + Search -->
                <div class="dtr-toolbar">
                    <label class="dtr-selall-label">
                        <input
                            type="checkbox"
                            class="dtr-chk"
                            :checked="allFilteredSelected"
                            :indeterminate.prop="someFilteredSelected"
                            @change="toggleSelectAll"
                        />
                        <span>Select all</span>
                    </label>
                    <div class="dtr-search-wrap">
                        <input v-model="searchQuery" class="dtr-search" placeholder="Search employee…" />
                        <v-icon icon="mdi-magnify" size="15" class="dtr-search-icon" />
                    </div>
                </div>

                <!-- Employee table -->
                <div class="dtr-table-wrap">
                    <table class="dtr-emp-table">
                        <thead>
                            <tr>
                                <th class="col-chk"></th>
                                <th>Employee No.</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="emp in paginatedEmployees" :key="emp.id"
                                class="dtr-emp-row"
                                :class="{ 'is-selected': filters.employee_ids.includes(emp.id) }"
                                @click="toggleEmployee(emp.id)"
                            >
                                <td class="col-chk">
                                    <input
                                        type="checkbox" class="dtr-chk"
                                        :checked="filters.employee_ids.includes(emp.id)"
                                        @click.stop @change="toggleEmployee(emp.id)"
                                    />
                                </td>
                                <td>{{ emp.employee_no }}</td>
                                <td>{{ emp.last_name }}</td>
                                <td>{{ emp.first_name }}</td>
                                <td>{{ emp.middle_name }}</td>
                            </tr>
                            <tr v-if="!paginatedEmployees.length">
                                <td colspan="5" class="dtr-empty">No employees found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="dtr-pagination">
                    <button class="pg-btn" :disabled="currentPage === 1" @click="currentPage--">
                        <v-icon icon="mdi-chevron-left" size="15" />
                    </button>
                    <span class="pg-num">{{ currentPage }}</span>
                    <button class="pg-btn" :disabled="currentPage === totalPages" @click="currentPage++">
                        <v-icon icon="mdi-chevron-right" size="15" />
                    </button>
                </div>
            </div>

            <!-- ── Right: Options ── -->
            <div class="dtr-col-right">

                <!-- Select Date -->
                <div class="opt-field">
                    <label class="opt-label">Select Date</label>
                    <div class="opt-sel-wrap">
                        <select v-model="filters.date_range" class="opt-select">
                            <option v-for="dr in dateRanges" :key="dr.value" :value="dr.value">{{ dr.label }}</option>
                        </select>
                        <v-icon icon="mdi-chevron-down" size="14" class="opt-chevron" />
                    </div>
                </div>

                <!-- Select Month -->
                <div class="opt-field">
                    <label class="opt-label">Select Month</label>
                    <div class="opt-sel-wrap">
                        <select v-model="filters.month_num" class="opt-select">
                            <option v-for="m in monthNames" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                        <v-icon icon="mdi-chevron-down" size="14" class="opt-chevron" />
                    </div>
                </div>

                <!-- Select Year -->
                <div class="opt-field">
                    <label class="opt-label">Select Year</label>
                    <div class="opt-sel-wrap">
                        <select v-model="filters.year_num" class="opt-select">
                            <option v-for="y in years" :key="y" :value="String(y)">{{ y }}</option>
                        </select>
                        <v-icon icon="mdi-chevron-down" size="14" class="opt-chevron" />
                    </div>
                </div>

                <!-- Signatory -->
                <div class="opt-field">
                    <div class="opt-signatory-hdr">
                        <label class="opt-label">Signatory <span class="opt-required">*</span></label>
                        <label class="opt-others-chk">
                            <input type="checkbox" v-model="filters.use_custom_signatory" class="dtr-chk" />
                            <span>others</span>
                        </label>
                    </div>

                    <!-- Searchable signatory dropdown -->
                    <div v-if="!filters.use_custom_signatory" class="cs-wrap" ref="sigRef">
                        <button
                            class="cs-trigger"
                            :class="{ 'cs-trigger--open': sigOpen }"
                            @click="sigOpen = !sigOpen"
                        >
                            <v-icon icon="mdi-account-tie-outline" size="15" class="cs-icon" />
                            <span class="cs-val" :class="{ 'cs-ph': !filters.signatory_id }">
                                {{ signatoryName || 'Select signatory' }}
                            </span>
                            <button v-if="filters.signatory_id" class="cs-x" @click.stop="filters.signatory_id = ''; sigSearch = ''">
                                <v-icon icon="mdi-close" size="12" />
                            </button>
                            <v-icon
                                icon="mdi-chevron-down" size="14"
                                class="cs-chevron"
                                :class="{ 'cs-chevron--up': sigOpen }"
                            />
                        </button>
                        <div v-show="sigOpen" class="cs-dropdown cs-dropdown--up">
                            <div class="cs-search-row">
                                <v-icon icon="mdi-magnify" size="13" class="cs-search-icon" />
                                <input v-model="sigSearch" class="cs-search" placeholder="Search employee…" />
                            </div>
                            <div class="cs-list">
                                <div
                                    v-for="emp in filteredSignatoryList" :key="emp.id"
                                    class="cs-opt"
                                    :class="{ 'cs-opt--active': String(filters.signatory_id) === String(emp.id) }"
                                    @click="selectSignatory(emp)"
                                >
                                    <span class="cs-opt-name">{{ emp.full_name }}</span>
                                    <span class="cs-badge">{{ emp.employee_no }}</span>
                                    <v-icon v-if="String(filters.signatory_id) === String(emp.id)" icon="mdi-check" size="13" class="cs-tick" />
                                </div>
                                <div v-if="!filteredSignatoryList.length" class="cs-empty">No employees found</div>
                            </div>
                        </div>
                    </div>

                    <input
                        v-else
                        v-model="filters.signatory_custom"
                        type="text"
                        class="opt-input"
                        placeholder="Enter signatory name"
                    />
                </div>

                <!-- Checkboxes -->
                <div class="opt-checks">
                    <label class="opt-chk-row">
                        <input type="checkbox" v-model="filters.with_saturday" class="dtr-chk" />
                        <span>with Saturday/Sunday</span>
                    </label>
                    <label class="opt-chk-row">
                        <input type="checkbox" v-model="filters.with_holiday" class="dtr-chk" />
                        <span>with Holiday</span>
                    </label>
                    <label class="opt-chk-row">
                        <input type="checkbox" v-model="filters.show_signature" class="dtr-chk" />
                        <span>show e-signature?</span>
                    </label>
                </div>

                <!-- Count -->
                <div class="opt-count">
                    <strong>{{ filters.employee_ids.length }}</strong>
                    employee{{ filters.employee_ids.length !== 1 ? 's' : '' }} selected
                </div>

                <div v-if="errors.employee_ids" class="opt-error">{{ errors.employee_ids[0] }}</div>

                <button
                    class="btn-generate"
                    :disabled="is_loading || !filters.employee_ids.length"
                    @click="generate"
                >
                    <v-icon v-if="is_loading" icon="mdi-loading" size="15" class="dtr-spinner" />
                    {{ is_loading ? 'GENERATING...' : 'GENERATE' }}
                </button>

                <button class="btn-reset" @click="resetForm">RESET FORM</button>
            </div>
        </div>
    </div>

    <!-- ── Step 2: Preview ──────────────────────────────── -->
    <div v-else-if="step === 'preview'" class="dtr-preview-wrap">
        <div class="dtr-preview-bar">
            <button class="dtr-back-btn" @click="step = 'form'">
                <v-icon icon="mdi-arrow-left" size="16" />
                Back
            </button>
            <div class="dtr-preview-bar__info">
                <v-icon icon="mdi-account-group-outline" size="15" style="color:#1fbfb8;" />
                <span>{{ dtrResult.data.length }} employee{{ dtrResult.data.length > 1 ? 's' : '' }} · {{ dtrResult.month }}</span>
            </div>
            <button class="dtr-print-btn" :disabled="is_pdf_loading" @click="triggerPdfDownload">
                <v-icon v-if="is_pdf_loading" icon="mdi-loading" size="16" class="dtr-spinner" />
                <v-icon v-else icon="mdi-file-pdf-box" size="16" />
                {{ is_pdf_loading ? 'Generating PDF...' : 'Print DTR' }}
            </button>
        </div>

        <div class="dtr-preview-body">
            <div v-for="emp in dtrResult.data" :key="emp.id" class="dtr-page-wrap">
                <div class="dtr-page-label">
                    <v-icon icon="mdi-account-outline" size="13" style="color:#1fbfb8;" />
                    {{ emp.full_name }}
                    <span class="dtr-page-label__no">#{{ emp.employee_no }}</span>

                    <div class="dtr-page-actions">
                        <button class="dtr-action-btn" title="Verify Time" @click="verifyTime(emp)">
                            <v-icon icon="mdi-clock-check-outline" size="16" />
                        </button>
                        <button class="dtr-action-btn" title="Work Schedule" @click="viewWorkSchedule(emp)">
                            <v-icon icon="mdi-calendar-clock-outline" size="16" />
                        </button>
                        <button class="dtr-action-btn" title="Solo Posting" @click="viewSoloPosting(emp)">
                            <v-icon icon="mdi-map-marker-account-outline" size="16" />
                        </button>
                    </div>
                </div>

                <div class="dtr-page">
                    <div class="dpf-header-row">
                        <div class="dpf-cs">Civil Service Form No. 48</div>
                        <img :src="'/images/zamboanga-seal.png'" class="dpf-logo" alt="City of Zamboanga Seal" />
                    </div>
                    <div class="dpf-title">DAILY TIME RECORD</div>
                    <div class="dpf-ooo">-----o0o-----</div>

                    <div class="dpf-name-row">
                        <div class="dpf-name-val">{{ emp.full_name.toUpperCase() }}</div>
                        <div class="dpf-name-label">(Name)</div>
                    </div>

                    <table class="dpf-meta-table">
                        <tr>
                            <td class="dpf-meta-key">For the month of</td>
                            <td class="dpf-meta-val" style="text-align: right;">{{ dtrResult.month.toUpperCase() }}</td>
                        </tr>
                    </table>

                    <table class="dpf-meta-table" style="margin-bottom: 10px;">
                        <tr>
                            <td rowspan="2" class="dpf-meta-key" style="vertical-align:top; font-style:italic;">Official hours for<br>arrival and departure</td>
                            <td class="dpf-meta-key" style="font-style:italic; padding-left:8px; white-space:nowrap;">Regular days</td>
                            <td class="dpf-meta-val" style="font-style:italic;">{{ dtrResult.regular_days || [
                                dtrResult.official_timein_AM && dtrResult.official_timeout_AM ? `AM ${fmt(dtrResult.official_timein_AM)}–${fmt(dtrResult.official_timeout_AM)}` : '',
                                dtrResult.official_timein_PM && dtrResult.official_timeout_PM ? `PM ${fmt(dtrResult.official_timein_PM)}–${fmt(dtrResult.official_timeout_PM)}` : '',
                            ].filter(Boolean).join('  /  ') }}</td>
                        </tr>
                        <tr>
                            <td class="dpf-meta-key" style="font-style:italic; padding-left:8px; white-space:nowrap;">Saturdays</td>
                            <td class="dpf-meta-val" style="font-style:italic;">{{ dtrResult.saturdays }}</td>
                        </tr>
                    </table>

                    <table class="dpf-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="dpf-col-day">Day</th>
                                <th colspan="2">A.M.</th>
                                <th colspan="2">P.M.</th>
                                <th colspan="2">Undertime</th>
                            </tr>
                            <tr>
                                <th>Arrival</th><th>Departure</th>
                                <th>Arrival</th><th>Departure</th>
                                <th>Hours</th><th>Minutes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="day in dtrResult.days_in_month" :key="day"
                                class="dpf-row"
                            >
                                <td>{{ day }}</td>
                                <template v-if="emp.daily_records[day] !== null && emp.daily_records[day] !== undefined">
                                    <template v-if="showDayType(emp.daily_records[day])">
                                        <td colspan="4" style="text-align:center; font-style:italic;">{{ emp.daily_records[day].day_type }}</td>
                                        <td></td><td></td>
                                    </template>
                                    <template v-else>
                                        <td>{{ emp.daily_records[day]?.am_arrival   ? fmt(emp.daily_records[day].am_arrival)   : '' }}</td>
                                        <td>{{ emp.daily_records[day]?.am_departure ? fmt(emp.daily_records[day].am_departure) : '' }}</td>
                                        <td>{{ emp.daily_records[day]?.pm_arrival   ? fmt(emp.daily_records[day].pm_arrival)   : '' }}</td>
                                        <td>{{ emp.daily_records[day]?.pm_departure ? fmt(emp.daily_records[day].pm_departure) : '' }}</td>
                                        <td>{{ emp.daily_records[day]?.undertime_hours   !== null ? emp.daily_records[day].undertime_hours   : '' }}</td>
                                        <td>{{ emp.daily_records[day]?.undertime_minutes !== null ? emp.daily_records[day].undertime_minutes : '' }}</td>
                                    </template>
                                </template>
                                <template v-else><td></td><td></td><td></td><td></td><td></td><td></td></template>
                            </tr>
                            <tr class="dpf-total-row">
                                <td colspan="5" class="dpf-total-label">Total</td>
                                <td>{{ emp.total_undertime_hours > 0   ? emp.total_undertime_hours   : '' }}</td>
                                <td>{{ emp.total_undertime_minutes > 0 ? emp.total_undertime_minutes : '' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <p class="dpf-cert">
                        I certify on my honor that the above is a true and correct report of the hours of work performed,
                        record of which was made daily at the time of arrival and departure from office.
                    </p>

                    <div class="dpf-sig-block">
                        <div class="dpf-sig-name">{{ emp.full_name.toUpperCase() }}</div>
                        <div class="dpf-sig-line"></div>
                    </div>

                    <p class="dpf-verified">VERIFIED as to the prescribed office hours:</p>

                    <div class="dpf-sig-block">
                        <div class="dpf-sig-name">{{ (dtrResult.signatory || ' ').toUpperCase() }}</div>
                        <div class="dpf-sig-line"></div>
                        <div class="dpf-sig-role">In Charge</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Verify Time Modal ────────────────────────────── -->
    <v-dialog v-model="verifyModalOpen" max-width="640px">
        <div class="lib-modal">
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon icon="mdi-clock-check-outline" size="18" />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Verify Time</p>
                        <h6 class="lib-modal__title">
                            {{ verifyEmployee?.full_name }}
                            <span class="dtr-page-label__no">#{{ verifyEmployee?.employee_no }}</span>
                        </h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="closeVerifyModal">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <div class="lib-modal__body">
                <div class="verify-date-row">
                    <div class="verify-date-field">
                        <label class="opt-label">From</label>
                        <input
                            type="date" class="opt-input"
                            v-model="verifyDates.from_date"
                            @change="loadCheckinoutLogs"
                        />
                    </div>
                    <div class="verify-date-field">
                        <label class="opt-label">To</label>
                        <input
                            type="date" class="opt-input"
                            v-model="verifyDates.to_date"
                            @change="loadCheckinoutLogs"
                        />
                    </div>
                </div>

                <div class="dtr-table-wrap verify-table-wrap">
                    <table class="dtr-emp-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date &amp; Time</th>
                                <th>Serial Number</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="is_checkinout_loading">
                                <td colspan="4" class="dtr-empty">Loading…</td>
                            </tr>
                            <template v-else>
                                <tr v-for="(log, idx) in checkinoutLogs" :key="log.id">
                                    <td>{{ idx + 1 }}</td>
                                    <td>{{ fmtDateTime(log.check_time) }}</td>
                                    <td>{{ log.serial_number }}</td>
                                    <td>
                                        <span v-if="log.status" class="status-badge status-badge--posted">Posted</span>
                                    </td>
                                </tr>
                                <tr v-if="!checkinoutLogs.length">
                                    <td colspan="4" class="dtr-empty">No raw check-in/out records found for this range</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lib-modal__footer">
                <button class="lib-modal__btn lib-modal__btn--cancel" @click="closeVerifyModal">Close</button>
            </div>
        </div>
    </v-dialog>

    <!-- ── Work Schedule Modal ──────────────────────────── -->
    <v-dialog v-model="workScheduleModalOpen" max-width="680px">
        <div class="lib-modal">
            <div class="lib-modal__header">
                <div class="lib-modal__header-left">
                    <div class="lib-modal__icon">
                        <v-icon icon="mdi-calendar-clock-outline" size="18" />
                    </div>
                    <div>
                        <p class="lib-modal__eyebrow">Work Schedule</p>
                        <h6 class="lib-modal__title">
                            {{ workScheduleEmp?.full_name }}
                            <span class="dtr-page-label__no">#{{ workScheduleEmp?.employee_no }}</span>
                        </h6>
                    </div>
                </div>
                <button class="lib-modal__close" @click="closeWorkScheduleModal">
                    <v-icon icon="mdi-close" size="16" />
                </button>
            </div>

            <div class="lib-modal__body">
                <div class="verify-date-row">
                    <div class="verify-date-field">
                        <label class="opt-label">From</label>
                        <input
                            type="date" class="opt-input"
                            v-model="workScheduleRange.from_date"
                            @change="loadWorkSchedules"
                        />
                    </div>
                    <div class="verify-date-field">
                        <label class="opt-label">To</label>
                        <input
                            type="date" class="opt-input"
                            v-model="workScheduleRange.to_date"
                            @change="loadWorkSchedules"
                        />
                    </div>
                </div>

                <div class="dtr-table-wrap verify-table-wrap">
                    <table class="dtr-emp-table">
                        <thead>
                            <tr>
                                <th>Schedule</th>
                                <th>Date Range</th>
                                <th>Days</th>
                                <th>A.M.</th>
                                <th>P.M.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="is_workschedule_loading">
                                <td colspan="5" class="dtr-empty">Loading…</td>
                            </tr>
                            <template v-else>
                                <tr v-for="ws in workSchedules" :key="ws.id">
                                    <td>
                                        {{ ws.schedule_name || ws.schedule_for || '—' }}
                                        <span v-if="ws.schedule_type" class="dtr-page-label__no" style="display:block;">{{ ws.schedule_type }}</span>
                                    </td>
                                    <td>{{ fmtDate(ws.from_date) }} – {{ fmtDate(ws.to_date) }}</td>
                                    <td>{{ fmtDays(ws.days) }}</td>
                                    <td>{{ ws.timein_AM ? fmt(ws.timein_AM) : '' }}{{ ws.timein_AM && ws.timeout_AM ? '–' : '' }}{{ ws.timeout_AM ? fmt(ws.timeout_AM) : '' }}</td>
                                    <td>{{ ws.timein_PM ? fmt(ws.timein_PM) : '' }}{{ ws.timein_PM && ws.timeout_PM ? '–' : '' }}{{ ws.timeout_PM ? fmt(ws.timeout_PM) : '' }}</td>
                                </tr>
                                <tr v-if="!workSchedules.length">
                                    <td colspan="5" class="dtr-empty">No work schedule found for this range</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lib-modal__footer">
                <button class="lib-modal__btn lib-modal__btn--cancel" @click="closeWorkScheduleModal">Close</button>
            </div>
        </div>
    </v-dialog>
</template>

<style scoped>
/* ── Two-column layout ── */
.dtr-two-col {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    margin-top: 16px;
}

/* ── Left column ── */
.dtr-col-left {
    flex: 1;
    min-width: 0;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(108, 143, 214, 0.12);
    border-radius: 12px;
}

/* Filter area holding the two cs-wraps */
.dtr-filter-area {
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.1);
}

/* ── Custom searchable select ── */
.cs-wrap { position: relative; }

.cs-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 10px 12px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.22);
    border-radius: 10px;
    color: var(--nav-text, #e2e8f0);
    font-size: 13px;
    font-family: inherit;
    cursor: pointer;
    text-align: left;
    transition: border-color 0.18s, background 0.18s;
}

.cs-trigger:hover { border-color: rgba(31, 191, 184, 0.4); background: rgba(255,255,255,0.06); }
.cs-trigger--open { border-color: rgba(31, 191, 184, 0.55) !important; background: rgba(31,191,184,0.05) !important; }
.cs-trigger--disabled { opacity: 0.38; cursor: not-allowed; }
.cs-trigger--disabled:hover { border-color: rgba(108, 143, 214, 0.22); background: rgba(255,255,255,0.04); }

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
    background: var(--sidebar-bg, #0d1b33);
    border: 1px solid rgba(108, 143, 214, 0.25);
    border-radius: 12px;
    box-shadow: 0 10px 36px rgba(0, 0, 0, 0.5), 0 2px 8px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

/* Signatory dropdown opens upward to avoid clipping */
.cs-dropdown--up {
    top: auto;
    bottom: calc(100% + 6px);
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
    color: var(--nav-text, #e2e8f0);
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
    color: var(--nav-text, #e2e8f0);
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

/* ── Toolbar ── */
.dtr-toolbar {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(108, 143, 214, 0.1);
}

.dtr-selall-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--nav-text, #e2e8f0);
    cursor: pointer;
    white-space: nowrap;
}

.dtr-search-wrap {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
}

.dtr-search {
    width: 100%;
    padding: 7px 32px 7px 10px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.2);
    border-radius: 6px;
    color: var(--nav-text, #e2e8f0);
    font-size: 12px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.18s;
}
.dtr-search:focus { border-color: rgba(31, 191, 184, 0.4); }
.dtr-search::placeholder { color: #5a78b0; }

.dtr-search-icon { position: absolute; right: 8px; color: #5a78b0; pointer-events: none; }

/* ── Employee table ── */
.dtr-table-wrap {
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(108, 143, 214, 0.25) transparent;
}

.dtr-emp-table { width: 100%; border-collapse: collapse; }

.dtr-emp-table thead tr { border-bottom: 1px solid rgba(108, 143, 214, 0.15); }

.dtr-emp-table th {
    padding: 8px 12px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    color: #8aa0d7;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
}

.col-chk { width: 36px; padding-left: 16px !important; }

.dtr-emp-row {
    border-bottom: 1px solid rgba(108, 143, 214, 0.06);
    cursor: pointer;
    transition: background 0.1s;
}
.dtr-emp-row:hover      { background: rgba(255, 255, 255, 0.03); }
.dtr-emp-row.is-selected { background: rgba(31, 191, 184, 0.07); }

.dtr-emp-table td {
    padding: 10px 12px;
    font-size: 13px;
    color: var(--nav-text, #e2e8f0);
}

.dtr-empty { text-align: center; color: #5a78b0; padding: 24px; }

.dtr-chk { width: 15px; height: 15px; accent-color: #1fbfb8; cursor: pointer; flex-shrink: 0; }

/* ── Pagination ── */
.dtr-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    border-top: 1px solid rgba(108, 143, 214, 0.1);
}

.pg-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    border: 1px solid rgba(108, 143, 214, 0.2);
    background: none;
    color: #8aa0d7;
    cursor: pointer;
    transition: all 0.15s;
}
.pg-btn:hover:not(:disabled) { background: rgba(31,191,184,0.1); color: #1fbfb8; border-color: rgba(31,191,184,0.3); }
.pg-btn:disabled { opacity: 0.3; cursor: not-allowed; }

.pg-num {
    min-width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(31, 191, 184, 0.15);
    color: #1fbfb8;
    font-size: 13px;
    font-weight: 600;
}

/* ── Right column ── */
.dtr-col-right {
    width: 280px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: relative;
}

.opt-field { display: flex; flex-direction: column; gap: 7px; }

.opt-label {
    font-size: 11px;
    font-weight: 600;
    color: #8aa0d7;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.opt-required { color: #f87171; }

.opt-sel-wrap { position: relative; }

.opt-select {
    width: 100%;
    padding: 10px 36px 10px 14px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.25);
    border-radius: 8px;
    color: var(--nav-text, #e2e8f0);
    font-size: 13px;
    font-family: inherit;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    outline: none;
    transition: border-color 0.18s;
}
.opt-select:focus { border-color: rgba(31, 191, 184, 0.5); }
.opt-select option { background: #0e1c3a; }

.opt-chevron {
    position: absolute;
    right: 11px;
    top: 50%;
    transform: translateY(-50%);
    color: #5a78b0;
    pointer-events: none;
}

.opt-input {
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(108, 143, 214, 0.25);
    border-radius: 8px;
    color: var(--nav-text, #e2e8f0);
    font-size: 13px;
    font-family: inherit;
    outline: none;
    width: 100%;
    transition: border-color 0.18s;
}
.opt-input:focus { border-color: rgba(31, 191, 184, 0.5); }

.opt-signatory-hdr { display: flex; align-items: center; }

.opt-others-chk {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    color: #8aa0d7;
    cursor: pointer;
    margin-left: auto;
}

.opt-checks { display: flex; flex-direction: column; gap: 10px; }

.opt-chk-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: var(--nav-text, #e2e8f0);
    cursor: pointer;
}

.opt-count { font-size: 13px; color: #8aa0d7; text-align: right; }
.opt-count strong { color: var(--nav-text, #e2e8f0); }

.opt-error { font-size: 12px; color: #f87171; margin-top: -6px; }

.btn-generate {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 6px;
    background: linear-gradient(135deg, #1fbfb8 0%, #05716c 100%);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    font-family: inherit;
    letter-spacing: 0.6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 2px 14px rgba(31, 191, 184, 0.35);
    transition: all 0.18s;
}
.btn-generate:hover:not(:disabled) { box-shadow: 0 4px 22px rgba(31,191,184,0.5); transform: translateY(-1px); }
.btn-generate:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-reset {
    width: 100%;
    padding: 12px;
    border: 1px solid rgba(108, 143, 214, 0.2);
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.03);
    color: #8aa0d7;
    font-size: 13px;
    font-weight: 700;
    font-family: inherit;
    letter-spacing: 0.6px;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-reset:hover { background: rgba(255,255,255,0.06); color: #e2e8f0; }

.dtr-spinner { animation: spin 0.8s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ── Preview ── */
.dtr-preview-wrap { display: flex; flex-direction: column; height: 100vh; overflow: hidden; }

.dtr-preview-bar {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 24px;
    background: #20253c;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    flex-shrink: 0;
    z-index: 1;
}

.dtr-preview-bar__info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    color: #8aa0d7;
}

.dtr-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(255, 255, 255, 0.03);
    color: #8aa0d7;
    font-size: 13px;
    font-family: inherit;
    cursor: pointer;
    transition: all 0.15s;
}
.dtr-back-btn:hover { background: rgba(255,255,255,0.08); color: #e2e8f0; border-color: rgba(255,255,255,0.18); }

.dtr-print-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 22px;
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
.dtr-print-btn:hover { box-shadow: 0 4px 18px rgba(31,191,184,0.45); transform: translateY(-1px); }

.dtr-preview-body {
    flex: 1;
    overflow-y: auto;
    padding: 32px 24px 48px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 40px;
    background: #1e2235;
}

.dtr-page-wrap { width: 100%; max-width: 620px; display: flex; flex-direction: column; gap: 8px; }

.dtr-page-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #8aa0d7;
    padding-left: 2px;
}
.dtr-page-label__no { font-size: 11px; color: #5a78b0; font-weight: 400; margin-left: 2px; }

.dtr-page-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-left: auto;
}

.dtr-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 7px;
    border: 1px solid rgba(108, 143, 214, 0.25);
    background: rgba(255, 255, 255, 0.03);
    color: #8aa0d7;
    cursor: pointer;
    transition: all 0.15s;
}
.dtr-action-btn:hover { background: rgba(31, 191, 184, 0.12); color: #1fbfb8; border-color: rgba(31, 191, 184, 0.4); }

.dtr-page {
    background: #fff;
    color: #000;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 8.5pt;
    padding: 24px 28px 28px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.55), 0 2px 8px rgba(0,0,0,0.3);
    border-radius: 2px;
}

.dpf-header-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2px; }
.dpf-cs { font-size: 7pt; }
.dpf-logo { width: 64px; height: 64px; object-fit: contain; }
.dpf-title { font-weight: bold; font-size: 12pt; text-align: center; margin-bottom: 1px; }
.dpf-ooo { font-size: 7pt; text-align: center; margin-bottom: 14px; color: #444; }

.dpf-name-row { margin-bottom: 6px; }
.dpf-name-val {
    font-weight: bold; font-size: 10pt;
    border-bottom: 0.75px solid #000;
    text-align: center; padding-bottom: 1px; min-height: 16px;
}
.dpf-name-label { font-size: 7pt; text-align: center; color: #555; }

.dpf-meta-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 4px; }
.dpf-meta-key { white-space: nowrap; padding-right: 6px; color: #333; }
.dpf-meta-val { border-bottom: 0.75px solid #000; font-weight: 600; padding-bottom: 1px; width: 100%; }

.dpf-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
.dpf-table th, .dpf-table td { border: 0.75px solid #000; text-align: center; padding: 2px 3px; }
.dpf-table th { font-weight: bold; background: #f8f8f8; font-size: 7pt; }
.dpf-col-day { width: 8%; }
.dpf-row { height: 14px; }
.dpf-total-row td { font-weight: bold; background: #f8f8f8; }
.dpf-total-label { text-align: right; font-style: italic; padding-right: 6px; }

.dpf-cert { font-size: 7pt; font-style: italic; margin: 10px 0 0; line-height: 1.55; color: #222; }

.dpf-sig-block { text-align: center; margin-top: 14px; }
.dpf-sig-name {
    font-weight: bold; font-size: 8.5pt; min-height: 28px;
    display: flex; align-items: flex-end; justify-content: center; padding-bottom: 2px;
}
.dpf-sig-line { border-top: 0.75px solid #000; width: 65%; margin: 0 auto; }
.dpf-sig-role { font-size: 7pt; margin-top: 2px; color: #444; }

.dpf-verified { font-size: 7pt; font-style: italic; margin-top: 12px; color: #333; }

/* ── Verify Time modal ── */
.verify-date-row {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
}
.verify-date-field { flex: 1; display: flex; flex-direction: column; gap: 7px; }

.verify-table-wrap { max-height: 320px; overflow-y: auto; border: 1px solid rgba(108, 143, 214, 0.12); border-radius: 8px; }

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}
.status-badge--posted { background: rgba(31, 191, 184, 0.12); color: #1fbfb8; }
</style>
