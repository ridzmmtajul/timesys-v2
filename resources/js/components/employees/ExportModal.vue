<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  show:    { type: Boolean, default: false },
  offices: { type: Array,   default: () => [] },
});

const emit = defineEmits(['close']);

const filters   = ref({ office_id: '', status: 'all' });
const is_loading = ref(false);

const selectedOfficeName = computed(() => {
  if (!filters.value.office_id) return 'All Offices';
  const found = props.offices.find(o => o.id == filters.value.office_id);
  return found ? found.name : 'All Offices';
});

watch(() => props.show, (val) => {
  if (val) filters.value = { office_id: '', status: 'all' };
});

async function download() {
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
}
</script>

<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h3>
          <i class="fas fa-file-export"></i>
          Export Employee List
        </h3>
        <button class="modal-close" @click="$emit('close')">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Office</label>
            <select v-model="filters.office_id">
              <option value="">All Offices</option>
              <option v-for="office in offices" :key="office.id" :value="office.id">
                {{ office.name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Status</label>
            <div class="rpt-status-group">
              <button
                type="button"
                class="rpt-status-btn"
                :class="{ active: filters.status === 'all' }"
                @click="filters.status = 'all'"
              >All</button>
              <button
                type="button"
                class="rpt-status-btn"
                :class="{ active: filters.status === 'active' }"
                @click="filters.status = 'active'"
              >Active</button>
              <button
                type="button"
                class="rpt-status-btn"
                :class="{ active: filters.status === 'inactive' }"
                @click="filters.status = 'inactive'"
              >Inactive</button>
            </div>
          </div>
        </div>

        <div class="rpt-info-card">
          <i class="fas fa-info-circle"></i>
          <span>
            Downloading <strong>{{ selectedOfficeName }}</strong> —
            <strong>{{ filters.status === 'all' ? 'All statuses' : filters.status === 'active' ? 'Active only' : 'Inactive only' }}</strong>
          </span>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-cancel" :disabled="is_loading" @click="$emit('close')">Cancel</button>
        <button class="btn-save" :disabled="is_loading" @click="download">
          <i class="fas" :class="is_loading ? 'fa-circle-notch fa-spin' : 'fa-download'"></i>
          {{ is_loading ? 'Generating...' : 'Download Excel' }}
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
  max-width: 480px;
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
  grid-template-columns: 1fr;
  gap: 14px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.form-group label {
  font-size: 12px;
  color: #97add8;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

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

.form-group select:focus {
  border-color: rgba(31, 191, 184, 0.6);
  box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.14);
}

.form-group select option {
  background: #0e1630;
}

.rpt-status-group {
  display: flex;
  gap: 6px;
}

.rpt-status-btn {
  flex: 1;
  height: 42px;
  border-radius: 12px;
  border: 1px solid rgba(121, 146, 207, 0.18);
  background: rgba(7, 13, 28, 0.7);
  color: #97add8;
  font-size: 13px;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.18s;
}

.rpt-status-btn:hover:not(.active) {
  background: rgba(255, 255, 255, 0.05);
  color: #c8d6f8;
}

.rpt-status-btn.active {
  background: rgba(31, 191, 184, 0.15);
  border-color: rgba(31, 191, 184, 0.4);
  color: #1fbfb8;
  font-weight: 600;
}

.rpt-info-card {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  margin-top: 16px;
  padding: 12px 16px;
  background: rgba(31, 191, 184, 0.06);
  border: 1px solid rgba(31, 191, 184, 0.16);
  border-radius: 12px;
  font-size: 13px;
  color: #8aa0d7;
}

.rpt-info-card i {
  color: #1fbfb8;
  margin-top: 2px;
  flex-shrink: 0;
}

.rpt-info-card strong {
  color: #c8d6f0;
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
</style>
