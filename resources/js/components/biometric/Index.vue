<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const emit = defineEmits(['device-action']);

const viewMode = ref('card');
const devices = ref([]);
const showForm = ref(false);
const loading = ref(false);
const saving = ref(false);
const openDeviceMenuId = ref(null);
const menuPosition = reactive({ top: 0, left: 0 });
const newDevice = reactive({ device_name: '', ip_address: '' });

const totalUsers = computed(() => devices.value.reduce((sum, d) => sum + Number(d.user_count || 0), 0));
const totalLogs = computed(() => devices.value.reduce((sum, d) => sum + Number(d.log_count || 0), 0));

function statusClass(status) {
  return String(status || '').toLowerCase() === 'connected' ? 'online' : 'offline';
}

function toggleDeviceMenu(deviceId, event) {
  if (openDeviceMenuId.value === deviceId) {
    openDeviceMenuId.value = null;
    return;
  }
  if (event) {
    const rect = event.currentTarget.getBoundingClientRect();
    menuPosition.top = rect.bottom + 8;
    menuPosition.left = rect.right - 220;
  }
  openDeviceMenuId.value = deviceId;
}

function closeDeviceMenu() {
  openDeviceMenuId.value = null;
}

async function fetchDevices() {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/biometrics');
    devices.value = data.data || [];
  } catch (error) {
    console.error(error);
    alert('Unable to load biometric devices.');
  } finally {
    loading.value = false;
  }
}

function toggleForm() {
  showForm.value = !showForm.value;
  if (!showForm.value) resetForm();
}

function resetForm() {
  newDevice.device_name = '';
  newDevice.ip_address = '';
}

function cancelForm() {
  showForm.value = false;
  resetForm();
}

async function addDevice() {
  if (!newDevice.device_name.trim()) {
    alert('Please enter a device name.');
    return;
  }
  if (!newDevice.ip_address.trim()) {
    alert('Please enter the IP address.');
    return;
  }
  saving.value = true;
  try {
    await axios.post('/api/biometrics', { device_name: newDevice.device_name, ip_address: newDevice.ip_address });
    await fetchDevices();
    resetForm();
    showForm.value = false;
  } catch (error) {
    console.error(error);
    alert(error?.response?.data?.message || 'Unable to connect to biometric device.');
  } finally {
    saving.value = false;
  }
}

async function removeDevice(device) {
  if (!confirm('Remove this biometric device?')) return;
  try {
    await axios.delete(`/api/biometrics/${device.id}`);
    await fetchDevices();
  } catch (error) {
    console.error(error);
    alert('Unable to remove biometric device.');
  }
}

async function connectDevice(device) {
  try {
    const { data } = await axios.post(`/api/biometrics/${device.id}/connect`);
    syncDeviceFromResponse(data?.data);
  } catch (error) {
    console.error(error);
    alert(error?.response?.data?.message || 'Unable to connect biometric device.');
  }
}

async function disconnectDevice(device) {
  try {
    const { data } = await axios.post(`/api/biometrics/${device.id}/disconnect`);
    syncDeviceFromResponse(data?.data);
  } catch (error) {
    console.error(error);
    alert(error?.response?.data?.message || 'Unable to disconnect biometric device.');
  }
}

async function downloadLog(device) {
  try {
    const response = await axios.get(`/api/biometrics/${device.id}/download-log`, { responseType: 'blob' });
    const contentDisposition = response.headers?.['content-disposition'] || '';
    const fileNameMatch = contentDisposition.match(/filename="?([^"]+)"?/i);
    const fileName = fileNameMatch?.[1] || `biometric-${device.id}-logs.csv`;
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = fileName;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error(error);
    alert(error?.response?.data?.message || 'Unable to download biometric logs.');
  }
}

async function updateDevice(device) {
  const deviceName = prompt('Update device name', device.device_name || '');
  if (deviceName === null) return;
  const ipAddress = prompt('Update IP address', device.ip_address || '');
  if (ipAddress === null) return;
  const productName = prompt('Update product name', device.product_name || '');
  if (productName === null) return;
  try {
    const { data } = await axios.put(`/api/biometrics/${device.id}`, {
      device_name: deviceName.trim(),
      ip_address: ipAddress.trim(),
      product_name: productName.trim()
    });
    syncDeviceFromResponse(data?.data);
  } catch (error) {
    console.error(error);
    alert(error?.response?.data?.message || 'Unable to update biometric device.');
  }
}

async function syncTime(device) {
  try {
    const { data } = await axios.post(`/api/biometrics/${device.id}/sync-time`);
    alert(data?.message || 'Biometric device time synced.');
  } catch (error) {
    console.error(error);
    alert(error?.response?.data?.message || 'Unable to sync biometric device time.');
  }
}

async function refreshDevice(device) {
  try {
    const { data } = await axios.post(`/api/biometrics/${device.id}/refresh`);
    syncDeviceFromResponse(data?.data);
  } catch (error) {
    console.error(error);
    alert(error?.response?.data?.message || 'Unable to refresh biometric device data.');
  }
}

function syncDeviceFromResponse(updatedDevice) {
  if (!updatedDevice?.id) {
    fetchDevices();
    return;
  }
  const index = devices.value.findIndex((d) => d.id === updatedDevice.id);
  if (index !== -1) {
    devices.value.splice(index, 1, updatedDevice);
    return;
  }
  fetchDevices();
}

async function handleDeviceAction(action, device) {
  openDeviceMenuId.value = null;
  emit('device-action', { action, device });

  if (action === 'connect') { await connectDevice(device); return; }
  if (action === 'disconnect') { await disconnectDevice(device); return; }
  if (action === 'download-log') { await downloadLog(device); return; }
  if (action === 'update-device') { await updateDevice(device); return; }
  if (action === 'delete-device') { await removeDevice(device); return; }
  if (action === 'sync-time') { await syncTime(device); return; }
  if (action === 'refresh') { await refreshDevice(device); return; }

  console.info(`Device action "${action}" triggered for`, device);
}

onMounted(() => {
  fetchDevices();
  document.addEventListener('click', closeDeviceMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeDeviceMenu);
});
</script>

<template>
  <section class="biometric-dashboard">
    <header class="hero-bar">
      <div class="hero-title">
        <div class="hero-kicker">
          <i class="fas fa-bars-staggered"></i>
          <span>Biometric devices</span>
        </div>
        <h2>Biometric devices</h2>
      </div>

      <div class="hero-actions">
        <div class="kpi-strip">
          <div class="kpi-card">
            <span class="kpi-label">Total Devices</span>
            <strong>{{ devices.length }}</strong>
          </div>
          <div class="kpi-card">
            <span class="kpi-label">Total Users</span>
            <strong>{{ totalUsers }}</strong>
          </div>
          <div class="kpi-card">
            <span class="kpi-label">Active Logs Today</span>
            <strong>{{ totalLogs }}</strong>
          </div>
        </div>

        <div class="view-toggle">
          <button class="view-btn" :class="{ active: viewMode === 'card' }" @click="viewMode = 'card'" title="Card view">
            <i class="fas fa-th-large"></i>
          </button>
          <button class="view-btn" :class="{ active: viewMode === 'table' }" @click="viewMode = 'table'" title="List view">
            <i class="fas fa-list"></i>
          </button>
        </div>

        <button class="btn-primary" @click="toggleForm">
          <i class="fas fa-plus"></i>
          Add device
        </button>
      </div>
    </header>

    <div class="surface">
      <div v-if="showForm" class="add-form">
        <div class="form-group">
          <label>Device name</label>
          <input v-model.trim="newDevice.device_name" placeholder="Enter device name" />
        </div>
        <div class="form-group">
          <label>IP address</label>
          <input v-model.trim="newDevice.ip_address" placeholder="Enter IP address" @keydown.enter="addDevice" />
        </div>
        <button class="btn-add" :disabled="saving" @click="addDevice">
          <i class="fas fa-plug"></i>
          {{ saving ? 'Connecting...' : 'Connect' }}
        </button>
        <button class="cancel-add" @click="cancelForm">Cancel</button>
      </div>

      <div v-if="loading" class="loading-state">
        <i class="fas fa-circle-notch fa-spin"></i>
        <p>Loading biometric devices...</p>
      </div>

      <div v-else-if="viewMode === 'card'" class="device-grid">
        <article v-for="device in devices" :key="device.id || device.device_name" class="device-card">
          <div class="card-top">
            <div class="device-icon">
              <i class="fas fa-fingerprint"></i>
            </div>
            <span class="card-period">Last 7 days</span>
          </div>

          <div class="device-name">{{ device.device_name }}</div>

          <div class="device-meta">
            <span><i class="fas fa-network-wired"></i> {{ device.ip_address }}</span>
            <span><i class="fas fa-box"></i> {{ device.product_name || 'N/A' }}</span>
            <span><i class="fas fa-hashtag"></i> {{ device.serial_number || 'N/A' }}</span>
          </div>

          <div class="device-status">
            <span class="status-badge" :class="statusClass(device.status)">
              <i class="fas fa-circle"></i>
              {{ device.status }}
            </span>
            <span class="log-count">
              <i class="fas fa-database"></i>
              {{ device.log_count || 0 }} logs
            </span>
          </div>

          <div class="device-stats">
            <span><strong>{{ device.user_count || 0 }}</strong> users</span>
            <span><strong>{{ device.admin_count || 0 }}</strong> admins</span>
          </div>

          <div class="device-footer">
            <div class="avatar-stack">
              <span></span><span></span><span></span>
            </div>

            <div class="device-action-wrap" @click.stop>
              <button
                class="action-menu-btn"
                type="button"
                title="Actions"
                aria-label="Actions"
                @click="toggleDeviceMenu(device.id)"
              >
                <i class="fas fa-ellipsis-v"></i>
              </button>

              <div v-if="openDeviceMenuId === device.id" class="device-action-menu device-action-menu--card" @click.stop>
                <button class="device-action-item" type="button" @click="handleDeviceAction('connect', device)">
                  <i class="fas fa-arrow-left"></i>
                  <span>Connect</span>
                </button>
                <button class="device-action-item" type="button" @click="handleDeviceAction('disconnect', device)">
                  <i class="fas fa-plug"></i>
                  <span>Disconnect</span>
                </button>
                <button class="device-action-item" type="button" @click="handleDeviceAction('download-log', device)">
                  <i class="fas fa-download"></i>
                  <span>Download log</span>
                </button>
                <button class="device-action-item" type="button" @click="handleDeviceAction('update-device', device)">
                  <i class="fas fa-pen"></i>
                  <span>Update device</span>
                </button>
                <button class="device-action-item" type="button" @click="handleDeviceAction('delete-device', device)">
                  <i class="fas fa-trash-alt"></i>
                  <span>Delete device</span>
                </button>
                <button class="device-action-item" type="button" @click="handleDeviceAction('sync-time', device)">
                  <i class="fas fa-clock"></i>
                  <span>Sync time</span>
                </button>
                <button class="device-action-item" type="button" @click="handleDeviceAction('refresh', device)">
                  <i class="fas fa-sync-alt"></i>
                  <span>Refresh</span>
                </button>
              </div>
            </div>
          </div>
        </article>

        <div v-if="!devices.length" class="empty-state">
          <i class="fas fa-microchip"></i>
          <p>No biometric devices added</p>
          <span>Click "Add device" to get started</span>
        </div>
      </div>

      <div v-else class="table-container">
        <table class="device-table">
          <thead>
            <tr>
              <th>Device Name</th>
              <th>Status</th>
              <th>IP Address</th>
              <th>Product Name</th>
              <th>User Count</th>
              <th>Admin Count</th>
              <th>Log Count</th>
              <th>Serial Number</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="device in devices" :key="device.id || device.device_name">
              <td>
                <div class="table-device-info">
                  <i class="fas fa-fingerprint"></i>
                  <span class="device-name-table">{{ device.device_name }}</span>
                </div>
              </td>
              <td>
                <span class="status-badge" :class="statusClass(device.status)">
                  {{ device.status }}
                </span>
              </td>
              <td>{{ device.ip_address }}</td>
              <td>{{ device.product_name || 'N/A' }}</td>
              <td>{{ device.user_count || 0 }}</td>
              <td>{{ device.admin_count || 0 }}</td>
              <td>{{ device.log_count || 0 }}</td>
              <td>{{ device.serial_number || 'N/A' }}</td>
              <td>
                <div class="device-action-wrap" @click.stop>
                  <button class="action-menu-btn" type="button" title="Actions" aria-label="Actions" @click="toggleDeviceMenu(device.id, $event)">
                    <i class="fas fa-ellipsis-v"></i>
                  </button>

                  <div v-if="openDeviceMenuId === device.id" class="device-action-menu device-action-menu--fixed" :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }" @click.stop>
                    <button class="device-action-item" type="button" @click="handleDeviceAction('connect', device)">
                      <i class="fas fa-arrow-left"></i>
                      <span>Connect</span>
                    </button>
                    <button class="device-action-item" type="button" @click="handleDeviceAction('disconnect', device)">
                      <i class="fas fa-plug"></i>
                      <span>Disconnect</span>
                    </button>
                    <button class="device-action-item" type="button" @click="handleDeviceAction('download-log', device)">
                      <i class="fas fa-download"></i>
                      <span>Download log</span>
                    </button>
                    <button class="device-action-item" type="button" @click="handleDeviceAction('update-device', device)">
                      <i class="fas fa-pen"></i>
                      <span>Update device</span>
                    </button>
                    <button class="device-action-item" type="button" @click="handleDeviceAction('delete-device', device)">
                      <i class="fas fa-trash-alt"></i>
                      <span>Delete device</span>
                    </button>
                    <button class="device-action-item" type="button" @click="handleDeviceAction('sync-time', device)">
                      <i class="fas fa-clock"></i>
                      <span>Sync time</span>
                    </button>
                    <button class="device-action-item" type="button" @click="handleDeviceAction('refresh', device)">
                      <i class="fas fa-sync-alt"></i>
                      <span>Refresh</span>
                    </button>
                  </div>
                </div>
              </td>
            </tr>
            <tr v-if="!devices.length">
              <td colspan="9" class="empty-table">
                <i class="fas fa-microchip"></i>
                <p>No biometric devices added</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="recent-logs">
        <div class="recent-logs__title">Recent Sync Logs</div>
        <div class="recent-avatars">
          <span></span><span></span><span></span><span></span><span></span>
        </div>
        <i class="fas fa-chevron-up"></i>
      </div>

      <footer class="footer-info">
        <span><i class="fas fa-arrow-right"></i> Live data pull from biometric devices</span>
        <span><i class="fas fa-check-circle"></i> {{ devices.length }} device(s) connected</span>
      </footer>
    </div>
  </section>
</template>

<style scoped>
.biometric-dashboard {
  min-height: calc(100vh - 40px);
  color: #eef6ff;
}

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
  opacity: 0.95;
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

.kpi-strip {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
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

.view-toggle {
  display: flex;
  padding: 4px;
  border-radius: 999px;
  background: rgba(18, 29, 61, 0.72);
  border: 1px solid rgba(126, 153, 210, 0.14);
}

.view-btn {
  width: 34px;
  height: 30px;
  border: 0;
  border-radius: 999px;
  background: transparent;
  color: #9bb0da;
  cursor: pointer;
}

.view-btn.active {
  background: #1fbfb8;
  color: #06162f;
}

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
}

.surface {
  border-radius: 18px;
  background: rgba(10, 18, 40, 0.58);
  border: 1px solid rgba(121, 146, 207, 0.16);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 24px 60px rgba(2, 7, 20, 0.28);
  padding: 14px;
}

.add-form {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  align-items: flex-end;
  margin-bottom: 14px;
  padding: 14px;
  border-radius: 18px;
  background: rgba(17, 27, 56, 0.85);
  border: 1px solid rgba(121, 146, 207, 0.16);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 220px;
  flex: 1;
}

.form-group label {
  font-size: 12px;
  color: #97add8;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.form-group input {
  height: 44px;
  border-radius: 14px;
  border: 1px solid rgba(121, 146, 207, 0.18);
  background: rgba(7, 13, 28, 0.7);
  color: #edf5ff;
  padding: 0 14px;
  outline: none;
}

.form-group input:focus {
  border-color: rgba(31, 191, 184, 0.6);
  box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.14);
}

.btn-add,
.cancel-add {
  height: 44px;
  border-radius: 14px;
  border: 0;
  padding: 0 16px;
  cursor: pointer;
}

.btn-add {
  background: #031163;
  color: white;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.btn-add:disabled {
  opacity: 0.7;
  cursor: wait;
}

.cancel-add {
  background: transparent;
  color: #9bb0da;
}

.loading-state,
.empty-state {
  border-radius: 24px;
  background: rgba(17, 27, 56, 0.6);
  border: 1px solid rgba(121, 146, 207, 0.14);
  min-height: 220px;
  display: grid;
  place-items: center;
  text-align: center;
  color: #9bb0da;
}

.loading-state i,
.empty-state i {
  font-size: 36px;
  color: #1fbfb8;
  margin-bottom: 10px;
}

.loading-state p,
.empty-state p {
  font-size: 15px;
  font-weight: 600;
}

.empty-state span {
  display: block;
  margin-top: 6px;
  font-size: 13px;
}

.device-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;
}

.device-card {
  position: relative;
  min-height: 252px;
  border-radius: 20px;
  padding: 16px 15px 14px;
  background:
    linear-gradient(180deg, rgba(28, 41, 75, 0.94) 0%, rgba(15, 24, 46, 0.98) 100%);
  border: 1px solid rgba(121, 146, 207, 0.16);
  box-shadow: 0 12px 26px rgba(2, 7, 20, 0.24);
}

.device-card:hover {
  border-color: rgba(31, 191, 184, 0.45);
  box-shadow: 0 18px 38px rgba(2, 7, 20, 0.38);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.device-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  color: #77dde0;
  background: rgba(31, 191, 184, 0.15);
  box-shadow: inset 0 0 0 1px rgba(31, 191, 184, 0.18);
}

.card-period {
  color: #b1c0e4;
  font-size: 11px;
  align-self: flex-start;
  margin-top: 4px;
}

.device-name {
  margin-top: 14px;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: -0.2px;
  color: #f5fbff;
}

.device-meta {
  margin-top: 10px;
  display: grid;
  gap: 7px;
  color: #9bb0da;
  font-size: 12px;
}

.device-meta span {
  display: flex;
  align-items: center;
  gap: 8px;
}

.device-meta i {
  color: #59d2d7;
  width: 16px;
}

.device-status {
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 5px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.01em;
  background: rgba(96, 107, 158, 0.22);
  color: #dfe8ff;
}

.status-badge.online {
  background: rgba(31, 191, 184, 0.18);
  color: #75e7d7;
}

.status-badge.offline {
  background: rgba(106, 112, 160, 0.22);
  color: #bcbfe5;
}

.status-badge i {
  font-size: 8px;
}

.log-count {
  color: #9bb0da;
  font-size: 11px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.device-stats {
  margin-top: 10px;
  display: flex;
  gap: 14px;
  color: #9bb0da;
  font-size: 12px;
}

.device-stats strong {
  color: #ffffff;
}

.device-footer {
  position: absolute;
  left: 16px;
  right: 16px;
  bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.avatar-stack {
  display: flex;
}

.avatar-stack span {
  width: 20px;
  height: 20px;
  margin-left: -6px;
  border-radius: 999px;
  border: 2px solid rgba(15, 24, 46, 1);
  background: linear-gradient(180deg, #ffb39b, #ff7f63);
}

.avatar-stack span:nth-child(2) {
  background: linear-gradient(180deg, #8ed5ff, #4b87ff);
}

.avatar-stack span:nth-child(3) {
  background: linear-gradient(180deg, #83f2c6, #2cbf92);
}

.device-action-wrap {
  position: relative;
}

.action-menu-btn {
  width: 30px;
  height: 30px;
  border-radius: 9px;
  border: 0;
  background: rgba(255, 255, 255, 0.06);
  color: #d6e2ff;
  cursor: pointer;
}

.device-action-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 10px);
  min-width: 220px;
  padding: 8px 0;
  border-radius: 14px;
  background: rgba(11, 18, 39, 0.98);
  border: 1px solid rgba(121, 146, 207, 0.16);
  box-shadow: 0 22px 40px rgba(0, 0, 0, 0.35);
  z-index: 20;
}

.device-action-menu--card {
  bottom: calc(100% + 10px);
  top: auto;
}

.device-action-menu--fixed {
  position: fixed;
  right: auto;
}

.device-action-item {
  width: 100%;
  border: 0;
  background: transparent;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  color: #e5eeff;
  cursor: pointer;
  text-align: left;
}

.device-action-item i {
  width: 16px;
  color: #8ea3d4;
}

.device-action-item:hover {
  background: rgba(255, 255, 255, 0.05);
}

.table-container {
  overflow: auto;
  border-radius: 18px;
  border: 1px solid rgba(121, 146, 207, 0.16);
}

.device-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  background: rgba(11, 18, 39, 0.96);
}

.device-table thead {
  background: rgba(16, 24, 50, 0.98);
}

.device-table th,
.device-table td {
  padding: 13px 15px;
  border-bottom: 1px solid rgba(121, 146, 207, 0.12);
}

.device-table th {
  color: #9bb0da;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  text-align: left;
}

.device-table td {
  color: #edf5ff;
}

.device-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.03);
}

.table-device-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.table-device-info i {
  color: #59d2d7;
}

.device-name-table {
  font-weight: 600;
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

.recent-logs {
  margin-top: 14px;
  height: 42px;
  border-radius: 16px;
  padding: 0 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  background: linear-gradient(180deg, rgba(22, 31, 58, 0.9), rgba(14, 21, 41, 0.98));
  border: 1px solid rgba(121, 146, 207, 0.14);
  color: #eaf3ff;
}

.recent-logs__title {
  font-size: 13px;
  font-weight: 600;
}

.recent-avatars {
  display: flex;
  margin-left: auto;
}

.recent-avatars span {
  width: 20px;
  height: 20px;
  margin-left: -6px;
  border-radius: 999px;
  border: 2px solid rgba(14, 21, 41, 1);
  background: linear-gradient(180deg, #ffb39b, #ff7f63);
}

.recent-avatars span:nth-child(2) { background: linear-gradient(180deg, #7fd5ff, #3d88ff); }
.recent-avatars span:nth-child(3) { background: linear-gradient(180deg, #86f1c8, #2dbf92); }
.recent-avatars span:nth-child(4) { background: linear-gradient(180deg, #ffd48b, #f5a33b); }
.recent-avatars span:nth-child(5) { background: linear-gradient(180deg, #d1a4ff, #8a63ff); }

.recent-logs > i {
  color: #aabce3;
  font-size: 12px;
}

.footer-info {
  margin-top: 10px;
  padding-top: 0;
  border-top: 0;
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

@media (max-width: 768px) {
  .device-grid {
    grid-template-columns: 1fr;
  }

  .surface {
    padding: 12px;
  }

  .kpi-strip {
    width: 100%;
  }

  .kpi-card {
    flex: 1 1 140px;
  }

  .hero-title h2 {
    font-size: 24px;
  }
}
</style>
