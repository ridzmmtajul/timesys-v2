<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  show:     { type: Boolean, default: false },
  employee: { type: Object,  default: null  },
  options:  { type: Object,  default: () => ({ offices: [], employment_types: [], positions: [], office_divisions: [] }) },
  saving:   { type: Boolean, default: false },
  errors:   { type: Array,   default: () => [] },
});

const emit = defineEmits(['close', 'save']);

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

const form = ref({ ...BLANK_FORM });

const isEditing = computed(() => !!props.employee);

const filteredOfficeDivisions = computed(() =>
  props.options.office_divisions.filter(d => d.office_id === form.value.office_id)
);

watch(() => props.show, (val) => {
  if (val && !props.employee) {
    form.value = { ...BLANK_FORM };
  }
});

watch(() => props.employee, (emp) => {
  if (emp) {
    form.value = {
      employee_no:        emp.employee_no        || '',
      first_name:         emp.first_name         || '',
      middle_name:        emp.middle_name        || '',
      last_name:          emp.last_name          || '',
      name_ext:           emp.name_ext           || '',
      gender:             emp.gender             || '',
      contact_no:         emp.contact_no         || '',
      job_title:          emp.job_title          || '',
      is_active:          Boolean(emp.is_active),
      office_id:          emp.office_id          || '',
      employment_type_id: emp.employment_type_id || '',
      position_id:        emp.position_id        || '',
      office_division_id: emp.office_division_id || '',
      title_id:           emp.title_id           || null,
    };
  } else {
    form.value = { ...BLANK_FORM };
  }
});

watch(() => form.value.office_id, (officeId) => {
  if (!filteredOfficeDivisions.value.some(d => d.id === form.value.office_division_id)) {
    form.value.office_division_id = '';
  }

  if (isEditing.value) return;
  const office = props.options.offices.find(o => o.id === officeId);
  if (!office) { form.value.employee_no = ''; return; }
  form.value.employee_no = office.latest_employee_no ? office.latest_employee_no + 1 : office.prefix + 1;
});

function handleSave() {
  emit('save', {
    ...form.value,
    employment_type_id: form.value.employment_type_id || null,
    position_id:        form.value.position_id        || null,
    office_division_id: form.value.office_division_id || null,
    title_id:           form.value.title_id           || null,
  });
}
</script>
<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h3>
          <i class="fas" :class="isEditing ? 'fa-pen' : 'fa-plus'"></i>
          {{ isEditing ? 'Edit Employee' : 'Create Employee' }}
        </h3>
        <button class="modal-close" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-grid">
          <!-- Left: Office info | Right: Name info -->
          <div class="form-group">
            <label>Office <span class="req">*</span></label>
            <select v-model="form.office_id">
              <option value="">— Select office —</option>
              <option v-for="o in options.offices" :key="o.id" :value="o.id">{{ o.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Employee No <span class="req">*</span></label>
            <input v-model.trim="form.employee_no" placeholder="Employee No." class="input-highlight" />
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
            <label>Name Extension</label>
            <input v-model.trim="form.name_ext" placeholder="Jr., Sr., III" />
          </div>
          <div class="form-group">
            <label>Office Division</label>
            <select v-model="form.office_division_id" :disabled="!form.office_id">
              <option value="">— None —</option>
              <option v-for="d in filteredOfficeDivisions" :key="d.id" :value="d.id">{{ d.name }}</option>
            </select>
          </div>
          <div class="form-group">
            <label>Employment Type</label>
            <select v-model="form.employment_type_id">
              <option value="">— None —</option>
              <option v-for="t in options.employment_types" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
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
        <button class="btn-cancel" :disabled="saving" @click="$emit('close')">Cancel</button>
        <button class="btn-save" :disabled="saving" @click="handleSave">
          <i class="fas" :class="saving ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
          {{ saving ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
        </button>
      </div>
    </div>
  </div>
</template>
<style scoped>
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
  min-width: 0;
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
  width: 100%;
  min-width: 0;
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

.input-highlight {
  border-color: rgba(31, 191, 184, 0.5) !important;
  background: rgba(31, 191, 184, 0.07) !important;
  color: #1fbfb8 !important;
  font-weight: 600;
  letter-spacing: 0.04em;
}

.input-highlight:focus {
  box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.22) !important;
}

.form-group select option {
  background: #0e1630;
}

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

@media (max-width: 600px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group.full-width {
    grid-column: 1;
  }
}
</style>
