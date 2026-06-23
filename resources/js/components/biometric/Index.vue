<template>
  <div>
    <div class="page-header">
      <h2><i class="fas fa-list-ul"></i> Biometric devices</h2>
      <div class="header-actions">
        <div class="view-toggle">
          <button class="view-btn" :class="{ active: viewMode === 'card' }" @click="viewMode = 'card'" title="Card view">
            <i class="fas fa-th-large"></i>
          </button>
          <button class="view-btn" :class="{ active: viewMode === 'table' }" @click="viewMode = 'table'" title="List view">
            <i class="fas fa-list"></i>
          </button>
        </div>
        <button class="btn-primary" @click="toggleForm">
          <i class="fas fa-plus-circle"></i> Add device
        </button>
      </div>
    </div>

    <div v-if="showForm" class="add-form">
      <div class="form-group">
        <label><i class="fas fa-tag"></i> Device name</label>
        <input v-model.trim="newDevice.device_name" placeholder="Enter device name" />
      </div>
      <div class="form-group">
        <label><i class="fas fa-network-wired"></i> IP address</label>
        <input v-model.trim="newDevice.ip_address" placeholder="Enter IP address" @keydown.enter="addDevice"/>
        <!-- <small class="form-hint">Enter the biometric device IP address.</small> -->
      </div>
      <button class="btn-add" :disabled="saving" @click="addDevice">
        <i class="fas fa-save"></i> {{ saving ? 'Connecting...' : 'Connect' }}
      </button>
      <button class="cancel-add" @click="cancelForm">Cancel</button>
    </div>

    <div v-if="loading" class="empty-state">
      <i class="fas fa-circle-notch fa-spin"></i>
      <p style="font-weight: 500; margin-top: 8px;">Loading biometric devices...</p>
    </div>

    <div v-else-if="viewMode === 'card'" class="device-grid">
      <div v-for="device in devices" :key="device.id || device.device_name" class="device-card">
        <div class="device-icon">
          <i class="fas fa-fingerprint"></i>
        </div>
        <div class="device-name">{{ device.device_name }}</div>
        <div class="device-meta">
          <span><i class="fas fa-network-wired"></i> {{ device.ip_address }}</span>
          <span><i class="fas fa-box"></i> {{ device.product_name || 'N/A' }}</span>
          <span><i class="fas fa-hashtag"></i> {{ device.serial_number || 'N/A' }}</span>
        </div>
        <div class="device-status">
          <span class="status-badge" :class="statusClass(device.status)">
            <i class="fas fa-circle" style="font-size: 8px; margin-right: 6px;"></i>
            {{ device.status }}
          </span>
          <span style="color: #1978a5; font-size: 13px;">
            <i class="fas fa-database"></i> {{ device.log_count || 0 }} logs
          </span>
        </div>
        <div class="device-stats">
          <span><strong>{{ device.user_count || 0 }}</strong> users</span>
          <span><strong>{{ device.admin_count || 0 }}</strong> admins</span>
        </div>

        <div class="device-card-actions" @click.stop>
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

      <div v-if="!devices.length" class="empty-state">
        <i class="fas fa-microchip"></i>
        <p style="font-weight: 500; margin-top: 8px;">No biometric devices added</p>
        <p style="font-size: 14px; opacity: 0.7;">Click "Add device" to get started</p>
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
                <i class="fas fa-fingerprint" style="color: #05716c;"></i>
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
            <td>{{ device.serial_number || 0 }}</td>
            <td>
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

                <div v-if="openDeviceMenuId === device.id" class="device-action-menu" @click.stop>
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
            <td colspan="11" class="empty-table">
              <i class="fas fa-microchip"></i>
              <p>No biometric devices added</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="footer-info">
      <span><i class="fas fa-arrow-right" style="color: #1fbfb8;"></i> Live data pull from biometric devices</span>
      <span><i class="fas fa-check-circle" style="color: #05716c;"></i> {{ devices.length }} device(s) connected</span>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'BiometricList',
  data() {
    return {
      viewMode: 'card',
      devices: [],
      showForm: false,
      loading: false,
      saving: false,
      openDeviceMenuId: null,
      newDevice: {
        device_name: '',
        ip_address: ''
      }
    };
  },
  mounted() {
    this.fetchDevices();
    document.addEventListener('click', this.closeDeviceMenu);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeDeviceMenu);
  },
  methods: {
    statusClass(status) {
      return String(status || '').toLowerCase() === 'connected' ? 'online' : 'offline';
    },
    toggleDeviceMenu(deviceId) {
      this.openDeviceMenuId = this.openDeviceMenuId === deviceId ? null : deviceId;
    },
    closeDeviceMenu() {
      this.openDeviceMenuId = null;
    },
    async fetchDevices() {
      this.loading = true;
      try {
        const { data } = await axios.get('/api/biometrics');
        this.devices = data.data || [];
      } catch (error) {
        console.error(error);
        alert('Unable to load biometric devices.');
      } finally {
        this.loading = false;
      }
    },
    toggleForm() {
      this.showForm = !this.showForm;
      if (!this.showForm) {
        this.resetForm();
      }
    },
    resetForm() {
      this.newDevice = {
        device_name: '',
        ip_address: ''
      };
    },
    cancelForm() {
      this.showForm = false;
      this.resetForm();
    },
    async addDevice() {
      if (!this.newDevice.device_name.trim()) {
        alert('Please enter a device name.');
        return;
      }

      if (!this.newDevice.ip_address.trim()) {
        alert('Please enter the IP address.');
        return;
      }

      this.saving = true;
      try {
        await axios.post('/api/biometrics', this.newDevice);
        await this.fetchDevices();
        this.resetForm();
        this.showForm = false;
      } catch (error) {
        console.error(error);
        const message = error?.response?.data?.message || 'Unable to connect to biometric device.';
        alert(message);
      } finally {
        this.saving = false;
      }
    },
    async removeDevice(device) {
      if (!confirm('Remove this biometric device?')) {
        return;
      }

      try {
        await axios.delete(`/api/biometrics/${device.id}`);
        await this.fetchDevices();
      } catch (error) {
        console.error(error);
        alert('Unable to remove biometric device.');
      }
    },
    async connectDevice(device) {
      try {
        const { data } = await axios.post(`/api/biometrics/${device.id}/connect`);
        this.syncDeviceFromResponse(data?.data);
      } catch (error) {
        console.error(error);
        alert(error?.response?.data?.message || 'Unable to connect biometric device.');
      }
    },
    async disconnectDevice(device) {
      try {
        const { data } = await axios.post(`/api/biometrics/${device.id}/disconnect`);
        this.syncDeviceFromResponse(data?.data);
      } catch (error) {
        console.error(error);
        alert(error?.response?.data?.message || 'Unable to disconnect biometric device.');
      }
    },
    async downloadLog(device) {
      try {
        const response = await axios.get(`/api/biometrics/${device.id}/download-log`, {
          responseType: 'blob'
        });

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
    },
    async updateDevice(device) {
      const deviceName = prompt('Update device name', device.device_name || '');
      if (deviceName === null) {
        return;
      }

      const ipAddress = prompt('Update IP address', device.ip_address || '');
      if (ipAddress === null) {
        return;
      }

      const productName = prompt('Update product name', device.product_name || '');
      if (productName === null) {
        return;
      }

      try {
        const { data } = await axios.put(`/api/biometrics/${device.id}`, {
          device_name: deviceName.trim(),
          ip_address: ipAddress.trim(),
          product_name: productName.trim()
        });

        this.syncDeviceFromResponse(data?.data);
      } catch (error) {
        console.error(error);
        alert(error?.response?.data?.message || 'Unable to update biometric device.');
      }
    },
    async syncTime(device) {
      try {
        const { data } = await axios.post(`/api/biometrics/${device.id}/sync-time`);
        alert(data?.message || 'Biometric device time synced.');
      } catch (error) {
        console.error(error);
        alert(error?.response?.data?.message || 'Unable to sync biometric device time.');
      }
    },
    async refreshDevice(device) {
      try {
        const { data } = await axios.post(`/api/biometrics/${device.id}/refresh`);
        this.syncDeviceFromResponse(data?.data);
      } catch (error) {
        console.error(error);
        alert(error?.response?.data?.message || 'Unable to refresh biometric device data.');
      }
    },
    syncDeviceFromResponse(updatedDevice) {
      if (!updatedDevice?.id) {
        this.fetchDevices();
        return;
      }

      const index = this.devices.findIndex((device) => device.id === updatedDevice.id);
      if (index !== -1) {
        this.devices.splice(index, 1, updatedDevice);
        return;
      }

      this.fetchDevices();
    },
    async handleDeviceAction(action, device) {
      this.openDeviceMenuId = null;
      this.$emit('device-action', { action, device });

      if (action === 'connect') {
        await this.connectDevice(device);
        return;
      }

      if (action === 'disconnect') {
        await this.disconnectDevice(device);
        return;
      }

      if (action === 'download-log') {
        await this.downloadLog(device);
        return;
      }

      if (action === 'update-device') {
        await this.updateDevice(device);
        return;
      }

      if (action === 'delete-device') {
        await this.removeDevice(device);
        return;
      }

      if (action === 'sync-time') {
        await this.syncTime(device);
        return;
      }

      if (action === 'refresh') {
        await this.refreshDevice(device);
        return;
      }

      console.info(`Device action "${action}" triggered for`, device);
    }
  }
};
</script>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.page-header h2 {
  font-size: 26px;
  font-weight: 700;
  color: #031163;
  letter-spacing: -0.3px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-header h2 i {
  color: #1fbfb8;
  font-size: 28px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 16px;
}

.view-toggle {
  display: flex;
  background: white;
  border-radius: 40px;
  padding: 4px;
  border: 1px solid #dde7f0;
  box-shadow: 0 2px 8px rgba(3, 17, 99, 0.04);
}

.view-btn {
  background: transparent;
  border: none;
  padding: 8px 16px;
  border-radius: 30px;
  cursor: pointer;
  color: #1978a5;
  transition: all 0.2s;
  font-size: 16px;
}

.view-btn:hover {
  background: rgba(31, 191, 184, 0.08);
}

.view-btn.active {
  background: #1fbfb8;
  color: #031163;
  box-shadow: 0 2px 8px rgba(31, 191, 184, 0.3);
}

.btn-primary {
  background: #1fbfb8;
  border: none;
  color: #031163;
  font-weight: 600;
  padding: 12px 28px;
  border-radius: 40px;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 8px 16px -6px rgba(31, 191, 184, 0.35);
  letter-spacing: 0.2px;
}

.btn-primary:hover {
  background: #1aaba5;
  transform: scale(1.01);
  box-shadow: 0 12px 20px -8px rgba(31, 191, 184, 0.45);
}

.add-form {
  background: white;
  border-radius: 32px;
  padding: 28px 32px;
  margin-bottom: 32px;
  border: 1px solid rgba(31, 191, 184, 0.2);
  box-shadow: 0 6px 16px rgba(3, 17, 99, 0.03);
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 18px 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1 0 160px;
}

.form-group label {
  font-size: 11px;
  font-weight: 600;
  color: #1978a5;
  letter-spacing: 0.2px;
  text-transform: uppercase;
}

.form-hint {
  color: #1978a5;
  font-size: 12px;
}

.connection-chip {
  padding: 12px 16px;
  border-radius: 20px;
  background: #f3fbfb;
  border: 1px solid #d4f3f0;
  color: #05716c;
  font-size: 13px;
  font-weight: 500;
}

.form-group input {
  padding: 12px 16px;
  border-radius: 20px;
  border: 1px solid #dde7f0;
  background: #fafcff;
  font-family: 'Inter', sans-serif;
  font-size: 14px;
  transition: 0.15s;
  color: #031163;
}

.form-group input:focus {
  outline: none;
  border-color: #1fbfb8;
  box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.15);
}

.form-group input[readonly] {
  background: #eef5fb;
  cursor: not-allowed;
}

.btn-add {
  background: #031163;
  color: white;
  border: none;
  padding: 12px 32px;
  border-radius: 40px;
  font-weight: 600;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: 0.15s;
  margin-left: auto;
  height: 48px;
}

.btn-add:disabled {
  opacity: 0.7;
  cursor: wait;
}

.btn-add:hover {
  background: #1f2a7a;
  box-shadow: 0 8px 20px rgba(3, 17, 99, 0.2);
}

.btn-add i {
  color: #1fbfb8;
}

.cancel-add {
  background: transparent;
  border: none;
  color: #1978a5;
  font-weight: 500;
  cursor: pointer;
  padding: 0 8px;
  font-size: 14px;
}

.cancel-add:hover {
  color: #031163;
}

.device-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 24px;
  margin-top: 12px;
}

.device-card {
  background: white;
  border-radius: 28px;
  padding: 24px 22px;
  box-shadow: 0 6px 18px rgba(3, 17, 99, 0.04);
  border: 1px solid rgba(3, 17, 99, 0.04);
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: relative;
}

.device-card:hover {
  border-color: #1fbfb8;
  box-shadow: 0 12px 24px -12px rgba(3, 17, 99, 0.12);
}

.device-icon {
  background: rgba(31, 191, 184, 0.08);
  width: 52px;
  height: 52px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #05716c;
  font-size: 26px;
}

.device-name {
  font-weight: 700;
  font-size: 20px;
  color: #031163;
  letter-spacing: -0.2px;
}

.device-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 18px;
  font-size: 14px;
  color: #1978a5;
  margin-top: 4px;
}

.device-meta i {
  color: #05716c;
  width: 18px;
  margin-right: 4px;
}

.device-status {
  margin-top: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  font-weight: 500;
}

.device-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 14px;
  font-size: 13px;
  color: #1978a5;
}

.device-stats strong {
  color: #031163;
}

.device-card-actions {
  position: absolute;
  right: 22px;
  bottom: 18px;
  display: inline-block;
}

.status-badge {
  background: #e2f0f2;
  color: #05716c;
  padding: 6px 14px;
  border-radius: 40px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.2px;
  display: inline-flex;
  align-items: center;
}

.status-badge.online {
  background: #d4f3f0;
  color: #05716c;
}

.status-badge.offline {
  background: #f0eef9;
  color: #4f4f8b;
}

.action-menu-btn {
  background: #ffffff;
  border: 1px solid #ffffff;
  color: #35507a;
  width: 46px;
  height: 46px;
  border-radius: 11px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: 0.15s ease;
  padding: 0;
}

.action-menu-btn:hover {
  background: #dee5ee;
  color: #20395e;
  transform: translateY(-1px);
}

.device-action-wrap {
  position: relative;
  display: inline-block;
}

.device-action-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 10px);
  min-width: 228px;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 12px 28px rgba(18, 28, 45, 0.22);
  padding: 8px 0;
  z-index: 20;
}

.device-action-menu--card {
  bottom: calc(100% + 10px);
  top: auto;
}

.device-action-item {
  width: 100%;
  border: 0;
  background: transparent;
  padding: 13px 18px;
  display: flex;
  align-items: center;
  gap: 16px;
  font: inherit;
  color: #2b3553;
  text-align: left;
  cursor: pointer;
}

.device-action-item i {
  color: #6b7588;
  font-size: 16px;
  width: 18px;
  flex: 0 0 18px;
  text-align: center;
}

.device-action-item span {
  font-size: 15px;
  font-weight: 500;
  line-height: 1.2;
}

.device-action-item:hover {
  background: #f6f8fb;
}

.table-container {
  background: white;
  border-radius: 28px;
  overflow: auto;
  box-shadow: 0 6px 18px rgba(3, 17, 99, 0.04);
  border: 1px solid rgba(3, 17, 99, 0.04);
  margin-top: 12px;
}

.device-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.device-table thead {
  background: #f8fafd;
  border-bottom: 1px solid #e6edf6;
}

.device-table th {
  text-align: left;
  padding: 16px 20px;
  font-weight: 600;
  color: #1978a5;
  text-transform: uppercase;
  font-size: 12px;
  letter-spacing: 0.3px;
}

.device-table td {
  padding: 16px 20px;
  border-bottom: 1px solid #f0f4f9;
  color: #031163;
}

.device-table tbody tr:hover {
  background: #f8fafd;
}

.device-table tbody tr:last-child td {
  border-bottom: none;
}

.table-device-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.device-name-table {
  font-weight: 600;
  color: #031163;
}

.empty-table {
  text-align: center;
  padding: 60px 20px !important;
  color: #1978a5;
}

.empty-table i {
  font-size: 44px;
  color: #1fbfb8;
  opacity: 0.5;
  display: block;
  margin-bottom: 16px;
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 60px 20px;
  color: #1978a5;
  background: white;
  border-radius: 40px;
  border: 1px dashed #b2d4e6;
}

.empty-state i {
  font-size: 44px;
  color: #1fbfb8;
  opacity: 0.5;
  margin-bottom: 16px;
}

.footer-info {
  margin-top: 32px;
  font-size: 13px;
  color: #1978a5;
  display: flex;
  gap: 20px;
  border-top: 1px solid #e6edf6;
  padding-top: 20px;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    flex-wrap: wrap;
  }

  .device-grid {
    grid-template-columns: 1fr;
  }

  .device-table {
    font-size: 12px;
  }

  .device-table th,
  .device-table td {
    padding: 12px 14px;
  }
}
</style>
