<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import EmployeeForm from './Form/Create.vue';
import ScheduleCalendar from './ScheduleCalendar.vue';
import ThemeSwal, { swalClass } from '../../utils/swal.js';

const employees      = ref([]);
const loading        = ref(false);
const saving         = ref(false);
const showModal      = ref(false);
const editingEmployee = ref(null);
const search         = ref('');
const page           = ref(1);
const activeCount    = ref(0);
const errors         = ref([]);
const pagination     = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
const options        = ref({ offices: [], employment_types: [], positions: [], office_divisions: [] });
const showCalendar   = ref(false);
const calendarEmployee = ref(null);

let searchTimeout = null;

function fullName(emp) {
  let name = `${emp.last_name}, ${emp.first_name}`;
  if (emp.middle_name) name += ` ${emp.middle_name.charAt(0)}.`;
  if (emp.name_ext)    name += ` ${emp.name_ext}`;
  return name;
}

function initials(emp) {
  return `${(emp.first_name || ' ').charAt(0)}${(emp.last_name || ' ').charAt(0)}`.toUpperCase();
}

function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    page.value = 1;
    fetchEmployees();
  }, 400);
}

function goToPage(p) {
  page.value = p;
  fetchEmployees();
}

async function fetchEmployees() {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/employees', {
      params: { search: search.value || undefined, page: page.value },
    });
    employees.value = data.data.data || [];
    pagination.value = {
      current_page: data.data.current_page,
      last_page:    data.data.last_page,
      total:        data.data.total,
      per_page:     data.data.per_page,
    };
    activeCount.value = data.meta?.active_count ?? 0;
  } catch (error) {
    console.error(error);
    ThemeSwal.fire({ icon: 'error', title: 'Error', text: 'Unable to load employees.' });
  } finally {
    loading.value = false;
  }
}

async function fetchOptions() {
  try {
    const { data } = await axios.get('/api/employees/options');
    options.value = data.data;
  } catch (error) {
    console.error('Failed to load form options:', error);
  }
}

function openCreateModal() {
  editingEmployee.value = null;
  errors.value = [];
  showModal.value = true;
}

function openEditModal(emp) {
  editingEmployee.value = emp;
  errors.value = [];
  showModal.value = true;
}

function closeModal() {
  if (saving.value) return;
  showModal.value = false;
  editingEmployee.value = null;
}

async function saveEmployee(payload) {
  errors.value = [];

  if (!payload.employee_no) errors.value.push('Employee No is required.');
  if (!payload.first_name)  errors.value.push('First Name is required.');
  if (!payload.last_name)   errors.value.push('Last Name is required.');
  if (!payload.office_id)   errors.value.push('Office is required.');

  if (errors.value.length) return;

  saving.value = true;
  try {
    if (editingEmployee.value) {
      await axios.put(`/api/employees/${editingEmployee.value.id}`, payload);
    } else {
      await axios.post('/api/employees', payload);
    }
    showModal.value = false;
    editingEmployee.value = null;
    await Promise.all([fetchEmployees(), fetchOptions()]);
  } catch (error) {
    const errData = error?.response?.data;
    const status  = error?.response?.status;
    if (errData?.errors) {
      errors.value = Object.values(errData.errors).flat();
    } else if (status === 422 && errData && typeof errData === 'object') {
      errors.value = Object.values(errData).flat();
    } else {
      errors.value = [errData?.message || 'An error occurred while saving.'];
    }
  } finally {
    saving.value = false;
  }
}

function openScheduleCalendar(emp) {
  calendarEmployee.value = emp;
  showCalendar.value = true;
}

function closeScheduleCalendar() {
  showCalendar.value = false;
  calendarEmployee.value = null;
}

async function toggleEmployeeStatus(emp) {
  const action = emp.is_active ? 'deactivate' : 'activate';
  const isDeactivating = emp.is_active;

  const result = await ThemeSwal.fire({
    title: isDeactivating ? 'Deactivate Employee?' : 'Activate Employee?',
    text: `${fullName(emp)} will be marked as ${isDeactivating ? 'inactive' : 'active'}.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: isDeactivating ? 'Yes, deactivate' : 'Yes, activate',
    customClass: {
      ...swalClass,
      confirmButton: isDeactivating ? 'ts-swal-btn ts-swal-btn--danger' : 'ts-swal-btn ts-swal-btn--confirm',
    },
  });

  if (!result.isConfirmed) return;

  try {
    await axios.patch(`/api/employees/${emp.id}/toggle-status`);
    await fetchEmployees();
    ThemeSwal.fire({
      icon: 'success',
      title: isDeactivating ? 'Deactivated' : 'Activated',
      text: `${fullName(emp)} has been ${isDeactivating ? 'deactivated' : 'activated'}.`,
    });
  } catch (error) {
    ThemeSwal.fire({
      icon: 'error',
      title: 'Error',
      text: error?.response?.data?.message || `Unable to ${action} employee.`,
    });
  }
}

onMounted(() => {
  fetchEmployees();
  fetchOptions();
});
</script>
<template>
  <section class="employee-dashboard">
    <header class="hero-bar">
      <div class="hero-title">
        <div class="hero-kicker">
          <i class="fas fa-users"></i>
          <span>Employees</span>
        </div>
        <h2>Employee Management</h2>
      </div>

      <div class="hero-actions">
        <div class="kpi-strip">
          <div class="kpi-card">
            <span class="kpi-label">Total Employees</span>
            <strong>{{ pagination.total }}</strong>
          </div>
          <div class="kpi-card">
            <span class="kpi-label">Active</span>
            <strong>{{ activeCount }}</strong>
          </div>
        </div>

        <div class="search-wrap">
          <i class="fas fa-search"></i>
          <input
            v-model="search"
            placeholder="Search by name or ID..."
            @input="onSearchInput"
          />
        </div>

        <button class="btn-primary" @click="openCreateModal">
          <i class="fas fa-plus"></i>
          Add Employee
        </button>
      </div>
    </header>

    <div class="surface">
      <div v-if="loading" class="loading-state">
        <i class="fas fa-circle-notch fa-spin"></i>
        <p>Loading employees...</p>
      </div>

      <div v-else class="table-container">
        <table class="emp-table">
          <thead>
            <tr>
              <th>Employee No</th>
              <th>Name</th>
              <th>Office</th>
              <th>Office Division</th>
              <th>Employment Type</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="emp in employees" :key="emp.id">
              <td><span class="emp-no">{{ emp.employee_no }}</span></td>
              <td>
                <div class="emp-name-cell">
                  <div class="emp-avatar">{{ initials(emp) }}</div>
                  <span class="emp-fullname">{{ fullName(emp) }}</span>
                </div>
              </td>
              <td>{{ emp.office?.name || '—' }}</td>
              <td>{{ emp.office_division?.name || '—' }}</td>
              <td>{{ emp.employment_type?.name || '—' }}</td>
              <td>
                <span class="status-badge" :class="emp.is_active ? 'status-active' : 'status-inactive'">
                  <i class="fas fa-circle"></i>
                  {{ emp.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td>
                <div class="row-actions">
                  <button class="action-btn edit-btn" title="Edit" @click="openEditModal(emp)">
                    <i class="fas fa-pen"></i>
                  </button>
                  <button class="action-btn calendar-btn" title="View Work Schedule" @click="openScheduleCalendar(emp)">
                    <i class="fas fa-calendar-alt"></i>
                  </button>
                  <button
                    v-if="emp.is_active"
                    class="action-btn deactivate-btn"
                    title="Deactivate"
                    @click="toggleEmployeeStatus(emp)"
                  >
                    <i class="fas fa-user-slash"></i>
                  </button>
                  <button
                    v-else
                    class="action-btn activate-btn"
                    title="Activate"
                    @click="toggleEmployeeStatus(emp)"
                  >
                    <i class="fas fa-user-check"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!employees.length">
              <td colspan="9" class="empty-table">
                <i class="fas fa-users"></i>
                <p>No employees found</p>
                <span v-if="search">Try a different search term</span>
                <span v-else>Click "Add Employee" to get started</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.last_page > 1" class="pagination">
        <button
          class="page-btn"
          :disabled="pagination.current_page === 1"
          @click="goToPage(pagination.current_page - 1)"
        >
          <i class="fas fa-chevron-left"></i>
        </button>
        <span class="page-info">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
          <small>({{ pagination.total }} total)</small>
        </span>
        <button
          class="page-btn"
          :disabled="pagination.current_page === pagination.last_page"
          @click="goToPage(pagination.current_page + 1)"
        >
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>

      <footer class="footer-info">
        <span><i class="fas fa-arrow-right"></i> Workforce directory</span>
        <span v-if="pagination.total">
          <i class="fas fa-check-circle"></i> {{ pagination.total }} employee(s)
        </span>
      </footer>
    </div>

    <EmployeeForm
      :show="showModal"
      :employee="editingEmployee"
      :options="options"
      :saving="saving"
      :errors="errors"
      @close="closeModal"
      @save="saveEmployee"
    />

    <ScheduleCalendar
      :show="showCalendar"
      :employee="calendarEmployee"
      @close="closeScheduleCalendar"
    />
  </section>
</template>
<style scoped>
.employee-dashboard {
  min-height: calc(100vh - 40px);
  color: #eef6ff;
}

/* ── Hero bar ── */
.hero-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 18px;
  margin-bottom: 12px;
}

.hero-title {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.hero-kicker {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #c8d6f8;
  font-size: 14px;
}

.hero-kicker i {
  color: #35d0d2;
}

.hero-title h2 {
  font-size: 20px;
  line-height: 1.05;
  letter-spacing: -0.5px;
  color: #f3f7ff;
}

.hero-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

/* ── KPI strip ── */
.kpi-strip {
  display: flex;
  gap: 10px;
}

.kpi-card {
  min-width: 108px;
  padding: 9px 12px;
  border-radius: 12px;
  background: rgba(24, 35, 70, 0.82);
  border: 1px solid rgba(126, 153, 210, 0.16);
  box-shadow: 0 8px 18px rgba(1, 8, 24, 0.16);
}

.kpi-label {
  display: block;
  font-size: 11px;
  color: #99b2df;
  margin-bottom: 4px;
}

.kpi-card strong {
  font-size: 15px;
  color: #f5fbff;
}

/* ── Search ── */
.search-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0 14px;
  height: 40px;
  border-radius: 999px;
  background: rgba(18, 29, 61, 0.72);
  border: 1px solid rgba(126, 153, 210, 0.14);
  min-width: 220px;
}

.search-wrap i {
  color: #6a84bf;
  font-size: 13px;
}

.search-wrap input {
  background: none;
  border: none;
  outline: none;
  color: #edf5ff;
  font-size: 14px;
  width: 100%;
}

.search-wrap input::placeholder {
  color: #6a84bf;
}

/* ── Primary button ── */
.btn-primary {
  border: 0;
  padding: 11px 16px;
  border-radius: 999px;
  background: linear-gradient(90deg, #1fbfb8 0%, #52d3d0 100%);
  color: #06162f;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  box-shadow: 0 16px 30px rgba(31, 191, 184, 0.22);
  white-space: nowrap;
}

/* ── Surface ── */
.surface {
  border-radius: 18px;
  background: rgba(10, 18, 40, 0.58);
  border: 1px solid rgba(121, 146, 207, 0.16);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 24px 60px rgba(2, 7, 20, 0.28);
  padding: 14px;
}

/* ── Loading / empty ── */
.loading-state {
  border-radius: 24px;
  background: rgba(17, 27, 56, 0.6);
  border: 1px solid rgba(121, 146, 207, 0.14);
  min-height: 220px;
  display: grid;
  place-items: center;
  text-align: center;
  color: #9bb0da;
}

.loading-state i {
  font-size: 36px;
  color: #1fbfb8;
  margin-bottom: 10px;
}

.loading-state p {
  font-size: 15px;
  font-weight: 600;
}

/* ── Table ── */
.table-container {
  overflow: auto;
  border-radius: 18px;
  border: 1px solid rgba(121, 146, 207, 0.16);
}

.emp-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  background: rgba(11, 18, 39, 0.96);
}

.emp-table thead {
  background: rgba(16, 24, 50, 0.98);
}

.emp-table th,
.emp-table td {
  padding: 13px 15px;
  border-bottom: 1px solid rgba(121, 146, 207, 0.12);
}

.emp-table th {
  color: #9bb0da;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  text-align: left;
  white-space: nowrap;
}

.emp-table td {
  color: #edf5ff;
}

.emp-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.03);
}

.emp-no {
  font-family: monospace;
  font-size: 13px;
  color: #7dd4f8;
}

.emp-name-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.emp-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 12px;
  font-weight: 700;
  background: linear-gradient(135deg, #1fbfb8, #3f6dc7);
  color: white;
  flex-shrink: 0;
}

.emp-fullname {
  font-weight: 600;
  white-space: nowrap;
}

/* ── Status badges ── */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.status-badge i {
  font-size: 7px;
}

.status-active {
  background: rgba(31, 191, 184, 0.18);
  color: #75e7d7;
}

.status-inactive {
  background: rgba(106, 112, 160, 0.22);
  color: #bcbfe5;
}

/* ── Row actions ── */
.row-actions {
  display: flex;
  gap: 6px;
}

.action-btn {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  border: 0;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 12px;
  transition: background 0.15s;
}

.edit-btn {
  background: rgba(63, 109, 199, 0.2);
  color: #7db3f8;
}

.edit-btn:hover {
  background: rgba(63, 109, 199, 0.38);
}

.deactivate-btn {
  background: rgba(220, 60, 60, 0.16);
  color: #f08080;
}

.deactivate-btn:hover {
  background: rgba(220, 60, 60, 0.3);
}

.activate-btn {
  background: rgba(31, 191, 184, 0.16);
  color: #75e7d7;
}

.activate-btn:hover {
  background: rgba(31, 191, 184, 0.3);
}

.calendar-btn {
  background: rgba(160, 90, 220, 0.16);
  color: #c589f5;
}

.calendar-btn:hover {
  background: rgba(160, 90, 220, 0.3);
}

.empty-table {
  text-align: center;
  padding: 60px 20px !important;
  color: #9bb0da;
}

.empty-table i {
  font-size: 34px;
  color: #1fbfb8;
  display: block;
  margin-bottom: 12px;
}

.empty-table p {
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 6px;
}

.empty-table span {
  font-size: 13px;
}

/* ── Pagination ── */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 14px;
}

.page-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid rgba(121, 146, 207, 0.2);
  background: rgba(17, 27, 56, 0.8);
  color: #9bb0da;
  cursor: pointer;
  display: grid;
  place-items: center;
}

.page-btn:disabled {
  opacity: 0.35;
  cursor: default;
}

.page-btn:not(:disabled):hover {
  background: rgba(31, 191, 184, 0.18);
  color: #75e7d7;
  border-color: rgba(31, 191, 184, 0.3);
}

.page-info {
  font-size: 13px;
  color: #9bb0da;
}

.page-info small {
  opacity: 0.7;
}

/* ── Footer ── */
.footer-info {
  margin-top: 10px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  color: #9bb0da;
  font-size: 13px;
}

.footer-info i {
  color: #1fbfb8;
  margin-right: 6px;
}

@media (max-width: 1024px) {
  .hero-bar {
    flex-direction: column;
  }

  .hero-actions {
    justify-content: flex-start;
  }
}

@media (max-width: 600px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group.full-width {
    grid-column: 1;
  }
}
</style>
