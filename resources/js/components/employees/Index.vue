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
              <th>Gender</th>
              <th>Contact</th>
              <th>Job Title</th>
              <th>Office</th>
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
              <td>{{ emp.gender || '—' }}</td>
              <td>{{ emp.contact_no || '—' }}</td>
              <td>{{ emp.job_title || '—' }}</td>
              <td>{{ emp.office?.name || '—' }}</td>
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
                  <button class="action-btn delete-btn" title="Delete" @click="deleteEmployee(emp)">
                    <i class="fas fa-trash-alt"></i>
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

    <!-- Create / Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h3>
            <i class="fas" :class="isEditing ? 'fa-pen' : 'fa-plus'"></i>
            {{ isEditing ? 'Edit Employee' : 'Add Employee' }}
          </h3>
          <button class="modal-close" @click="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-grid">
            <div class="form-group">
              <label>Employee No <span class="req">*</span></label>
              <input v-model.trim="form.employee_no" placeholder="e.g. EMP-0001" />
            </div>
            <div class="form-group">
              <label>Name Extension</label>
              <input v-model.trim="form.name_ext" placeholder="Jr., Sr., III" />
            </div>

            <div class="form-group">
              <label>First Name <span class="req">*</span></label>
              <input v-model.trim="form.first_name" placeholder="First name" />
            </div>
            <div class="form-group">
              <label>Middle Name</label>
              <input v-model.trim="form.middle_name" placeholder="Middle name" />
            </div>

            <div class="form-group">
              <label>Last Name <span class="req">*</span></label>
              <input v-model.trim="form.last_name" placeholder="Last name" />
            </div>
            <div class="form-group">
              <label>Gender</label>
              <select v-model="form.gender">
                <option value="">— Select —</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>

            <div class="form-group">
              <label>Contact No</label>
              <input v-model.trim="form.contact_no" placeholder="Contact number" />
            </div>
            <div class="form-group">
              <label>Job Title</label>
              <input v-model.trim="form.job_title" placeholder="Job title" />
            </div>

            <div class="form-group">
              <label>Office <span class="req">*</span></label>
              <select v-model="form.office_id">
                <option value="">— Select office —</option>
                <option v-for="o in options.offices" :key="o.id" :value="o.id">{{ o.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Employment Type</label>
              <select v-model="form.employment_type_id">
                <option value="">— None —</option>
                <option v-for="t in options.employment_types" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
            </div>

            <div class="form-group">
              <label>Position</label>
              <select v-model="form.position_id">
                <option value="">— None —</option>
                <option v-for="p in options.positions" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>Office Division</label>
              <select v-model="form.office_division_id">
                <option value="">— None —</option>
                <option v-for="d in options.office_divisions" :key="d.id" :value="d.id">{{ d.name }}</option>
              </select>
            </div>

            <div class="form-group full-width">
              <label class="toggle-row">
                <span>Active Employee</span>
                <span class="toggle-switch">
                  <input type="checkbox" v-model="form.is_active" />
                  <span class="slider"></span>
                </span>
              </label>
            </div>
          </div>

          <div v-if="errors.length" class="error-list">
            <div v-for="(err, i) in errors" :key="i" class="error-item">
              <i class="fas fa-exclamation-circle"></i>
              {{ err }}
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn-cancel" :disabled="saving" @click="closeModal">Cancel</button>
          <button class="btn-save" :disabled="saving" @click="saveEmployee">
            <i class="fas" :class="saving ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
            {{ saving ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import axios from 'axios';

const BLANK_FORM = {
  employee_no: '',
  first_name: '',
  middle_name: '',
  last_name: '',
  name_ext: '',
  gender: '',
  contact_no: '',
  job_title: '',
  is_active: true,
  office_id: '',
  employment_type_id: '',
  position_id: '',
  office_division_id: '',
  title_id: null,
};

export default {
  name: 'EmployeeList',

  data() {
    return {
      employees: [],
      loading: false,
      saving: false,
      showModal: false,
      isEditing: false,
      editingId: null,
      search: '',
      searchTimeout: null,
      page: 1,
      pagination: {
        current_page: 1,
        last_page: 1,
        total: 0,
        per_page: 15,
      },
      activeCount: 0,
      options: {
        offices: [],
        employment_types: [],
        positions: [],
        office_divisions: [],
      },
      form: { ...BLANK_FORM },
      errors: [],
    };
  },

  mounted() {
    this.fetchEmployees();
    this.fetchOptions();
  },

  methods: {
    fullName(emp) {
      let name = `${emp.last_name}, ${emp.first_name}`;
      if (emp.middle_name) name += ` ${emp.middle_name.charAt(0)}.`;
      if (emp.name_ext) name += ` ${emp.name_ext}`;
      return name;
    },

    initials(emp) {
      return `${(emp.first_name || ' ').charAt(0)}${(emp.last_name || ' ').charAt(0)}`.toUpperCase();
    },

    onSearchInput() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.page = 1;
        this.fetchEmployees();
      }, 400);
    },

    goToPage(p) {
      this.page = p;
      this.fetchEmployees();
    },

    async fetchEmployees() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/employees', {
          params: { search: this.search || undefined, page: this.page },
        });
        this.employees = data.data.data || [];
        this.pagination = {
          current_page: data.data.current_page,
          last_page: data.data.last_page,
          total: data.data.total,
          per_page: data.data.per_page,
        };
        this.activeCount = data.meta?.active_count ?? 0;
      } catch (error) {
        console.error(error);
        alert('Unable to load employees.');
      } finally {
        this.loading = false;
      }
    },

    async fetchOptions() {
      try {
        const { data } = await axios.get('/api/employees/options');
        this.options = data.data;
      } catch (error) {
        console.error('Failed to load form options:', error);
      }
    },

    openCreateModal() {
      this.isEditing = false;
      this.editingId = null;
      this.form = { ...BLANK_FORM };
      this.errors = [];
      this.showModal = true;
    },

    openEditModal(emp) {
      this.isEditing = true;
      this.editingId = emp.id;
      this.form = {
        employee_no: emp.employee_no || '',
        first_name: emp.first_name || '',
        middle_name: emp.middle_name || '',
        last_name: emp.last_name || '',
        name_ext: emp.name_ext || '',
        gender: emp.gender || '',
        contact_no: emp.contact_no || '',
        job_title: emp.job_title || '',
        is_active: Boolean(emp.is_active),
        office_id: emp.office_id || '',
        employment_type_id: emp.employment_type_id || '',
        position_id: emp.position_id || '',
        office_division_id: emp.office_division_id || '',
        title_id: emp.title_id || null,
      };
      this.errors = [];
      this.showModal = true;
    },

    closeModal() {
      if (this.saving) return;
      this.showModal = false;
    },

    buildPayload() {
      return {
        ...this.form,
        employment_type_id: this.form.employment_type_id || null,
        position_id: this.form.position_id || null,
        office_division_id: this.form.office_division_id || null,
        title_id: this.form.title_id || null,
      };
    },

    async saveEmployee() {
      this.errors = [];

      if (!this.form.employee_no) this.errors.push('Employee No is required.');
      if (!this.form.first_name) this.errors.push('First Name is required.');
      if (!this.form.last_name) this.errors.push('Last Name is required.');
      if (!this.form.office_id) this.errors.push('Office is required.');

      if (this.errors.length) return;

      this.saving = true;
      try {
        if (this.isEditing) {
          await axios.put(`/api/employees/${this.editingId}`, this.buildPayload());
        } else {
          await axios.post('/api/employees', this.buildPayload());
        }
        this.showModal = false;
        await this.fetchEmployees();
      } catch (error) {
        const errData = error?.response?.data;
        if (errData?.errors) {
          this.errors = Object.values(errData.errors).flat();
        } else {
          this.errors = [errData?.message || 'An error occurred while saving.'];
        }
      } finally {
        this.saving = false;
      }
    },

    async deleteEmployee(emp) {
      if (!confirm(`Delete employee "${this.fullName(emp)}"? This cannot be undone.`)) return;

      try {
        await axios.delete(`/api/employees/${emp.id}`);
        await this.fetchEmployees();
      } catch (error) {
        alert(error?.response?.data?.message || 'Unable to delete employee.');
      }
    },
  },
};
</script>

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

.delete-btn {
  background: rgba(220, 60, 60, 0.16);
  color: #f08080;
}

.delete-btn:hover {
  background: rgba(220, 60, 60, 0.3);
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

/* ── Modal ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 7, 20, 0.72);
  display: grid;
  place-items: center;
  z-index: 100;
  padding: 20px;
  backdrop-filter: blur(4px);
}

.modal {
  width: 100%;
  max-width: 680px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  border-radius: 20px;
  background: linear-gradient(180deg, rgba(14, 22, 46, 0.98) 0%, rgba(9, 15, 32, 0.99) 100%);
  border: 1px solid rgba(121, 146, 207, 0.22);
  box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px 18px;
  border-bottom: 1px solid rgba(121, 146, 207, 0.14);
  flex-shrink: 0;
}

.modal-header h3 {
  font-size: 18px;
  color: #f3f7ff;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-header h3 i {
  color: #1fbfb8;
  font-size: 14px;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 0;
  background: rgba(255, 255, 255, 0.06);
  color: #9bb0da;
  cursor: pointer;
  display: grid;
  place-items: center;
}

.modal-close:hover {
  background: rgba(255, 255, 255, 0.12);
  color: white;
}

.modal-body {
  overflow-y: auto;
  padding: 20px 24px;
  flex: 1;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-size: 12px;
  color: #97add8;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.req {
  color: #f08080;
}

.form-group input,
.form-group select {
  height: 42px;
  border-radius: 12px;
  border: 1px solid rgba(121, 146, 207, 0.18);
  background: rgba(7, 13, 28, 0.7);
  color: #edf5ff;
  padding: 0 14px;
  outline: none;
  font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
  border-color: rgba(31, 191, 184, 0.6);
  box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.14);
}

.form-group select option {
  background: #0e1630;
}

/* ── Toggle switch ── */
.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-radius: 12px;
  background: rgba(7, 13, 28, 0.5);
  border: 1px solid rgba(121, 146, 207, 0.14);
  cursor: pointer;
  font-size: 14px;
  color: #c8d6f8;
}

.toggle-switch {
  position: relative;
  width: 42px;
  height: 24px;
  flex-shrink: 0;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
  position: absolute;
}

.slider {
  position: absolute;
  inset: 0;
  border-radius: 999px;
  background: rgba(121, 146, 207, 0.2);
  transition: background 0.2s;
  cursor: pointer;
}

.slider::before {
  content: '';
  position: absolute;
  width: 18px;
  height: 18px;
  left: 3px;
  top: 3px;
  border-radius: 50%;
  background: #9bb0da;
  transition: transform 0.2s, background 0.2s;
}

.toggle-switch input:checked + .slider {
  background: rgba(31, 191, 184, 0.3);
}

.toggle-switch input:checked + .slider::before {
  transform: translateX(18px);
  background: #1fbfb8;
}

/* ── Error list ── */
.error-list {
  margin-top: 14px;
  padding: 12px 16px;
  border-radius: 12px;
  background: rgba(220, 60, 60, 0.12);
  border: 1px solid rgba(220, 60, 60, 0.25);
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.error-item {
  font-size: 13px;
  color: #f08080;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ── Modal footer ── */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px 20px;
  border-top: 1px solid rgba(121, 146, 207, 0.14);
  flex-shrink: 0;
}

.btn-cancel {
  height: 40px;
  padding: 0 18px;
  border-radius: 10px;
  border: 1px solid rgba(121, 146, 207, 0.2);
  background: transparent;
  color: #9bb0da;
  cursor: pointer;
  font-size: 14px;
}

.btn-cancel:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.05);
}

.btn-save {
  height: 40px;
  padding: 0 20px;
  border-radius: 10px;
  border: 0;
  background: linear-gradient(90deg, #1fbfb8 0%, #52d3d0 100%);
  color: #06162f;
  font-weight: 700;
  cursor: pointer;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-save:disabled {
  opacity: 0.7;
  cursor: wait;
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
