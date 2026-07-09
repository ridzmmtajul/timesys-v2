<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import ThemeSwal, { swalClass } from '../../utils/swal.js';

const emit = defineEmits(['device-action']);

const viewMode = ref('card');
const devices = ref([]);
const searchQuery = ref('');
const showForm = ref(false);
const loading = ref(false);
const saving = ref(false);
const openDeviceMenuId = ref(null);
const menuPosition = reactive({ top: 0, left: 0 });
const newDevice = reactive({ device_name: '', ip_address: '' });

const showUpdateModal = ref(false);
const updating = ref(false);
const updateTarget = ref(null);
const updateForm = reactive({ device_name: '', ip_address: '', product_name: '' });

const showDownloadModal = ref(false);
const downloadTarget = ref(null);
const downloadProgress = ref(0);
const downloadStatus = ref('connecting');
const downloadResult = ref(null);
const downloadErrorMsg = ref('');
let downloadProgressTimer = null;
let downloadStageTimer = null;

const totalUsers = computed(() => devices.value.reduce((sum, d) => sum + Number(d.user_count || 0), 0));
const totalLogs = computed(() => devices.value.reduce((sum, d) => sum + Number(d.log_count || 0), 0));

const filteredDevices = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return devices.value;
  return devices.value.filter((d) => (
    (d.device_name || '').toLowerCase().includes(query) ||
    (d.ip_address || '').toLowerCase().includes(query) ||
    (d.product_name || '').toLowerCase().includes(query) ||
    (d.serial_number || '').toLowerCase().includes(query)
  ));
});

const recentLogs = ref([]);
const showRecentLogs = ref(true);

const ACTION_LABELS = {
  'add-device': 'Device added',
  'update-device': 'Device updated',
  'delete-device': 'Device removed',
  connect: 'Connected',
  disconnect: 'Disconnected',
  'download-log': 'Logs downloaded',
  'sync-time': 'Time synced',
  refresh: 'Data refreshed',
};

const ACTION_ICONS = {
  'add-device': 'fa-plus',
  'update-device': 'fa-pen',
  'delete-device': 'fa-trash-alt',
  connect: 'fa-plug',
  disconnect: 'fa-power-off',
  'download-log': 'fa-download',
  'sync-time': 'fa-clock',
  refresh: 'fa-sync-alt',
};

function logIcon(log) {
  return ACTION_ICONS[log.action] || 'fa-circle-info';
}

function logLabel(log) {
  const label = ACTION_LABELS[log.action] || log.action;
  return log.status === 'failed' ? `${label} failed` : label;
}

function formatLogTime(timestamp) {
  if (!timestamp) return '';
  return new Date(timestamp).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function lastSyncedLabel(device) {
  const timestamp = device?.last_synced_at;
  if (!timestamp) return 'Never synced';

  const diffMs = Date.now() - new Date(timestamp).getTime();
  const minutes = Math.floor(diffMs / 60000);
  if (minutes < 1) return 'Synced just now';
  if (minutes < 60) return `Synced ${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `Synced ${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `Synced ${days}d ago`;
}

async function fetchRecentLogs() {
  try {
    const { data } = await axios.get('/api/biometrics/logs', { params: { limit: 3 } });
    recentLogs.value = data.data || [];
  } catch (error) {
    console.error(error);
  }
}

const CARDS_PER_PAGE = 4;
const carouselPage = ref(0);
const totalCarouselPages = computed(() => Math.max(1, Math.ceil(filteredDevices.value.length / CARDS_PER_PAGE)));
const pagedDevices = computed(() => {
  const start = carouselPage.value * CARDS_PER_PAGE;
  return filteredDevices.value.slice(start, start + CARDS_PER_PAGE);
});

function goToCarouselPage(page) {
  carouselPage.value = Math.min(Math.max(page, 0), totalCarouselPages.value - 1);
}

function prevCarouselPage() {
  goToCarouselPage(carouselPage.value - 1);
}

function nextCarouselPage() {
  goToCarouselPage(carouselPage.value + 1);
}

watch(() => filteredDevices.value.length, () => {
  if (carouselPage.value > totalCarouselPages.value - 1) {
    carouselPage.value = totalCarouselPages.value - 1;
  }
});

watch(searchQuery, () => {
  carouselPage.value = 0;
});

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
    ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: 'Unable to load biometric devices.' });
  } finally {
    loading.value = false;
  }
}

const bulkRefreshing = ref(false);
const bulkDownloading = ref(false);

async function refreshAllDevices() {
  if (!devices.value.length || bulkRefreshing.value) return;
  bulkRefreshing.value = true;
  try {
    const results = await Promise.allSettled(
      devices.value.map((device) => axios.post(`/api/biometrics/${device.id}/refresh`))
    );
    const failed = results.filter((r) => r.status === 'rejected').length;
    await fetchDevices();
    await fetchRecentLogs();
    ThemeSwal.fire({
      icon: failed ? 'warning' : 'success',
      title: failed ? 'Partially refreshed' : 'Refreshed',
      text: failed
        ? `${devices.value.length - failed} of ${devices.value.length} device(s) refreshed.`
        : 'All devices refreshed.',
    });
  } finally {
    bulkRefreshing.value = false;
  }
}

async function downloadAllDevices() {
  if (!devices.value.length || bulkDownloading.value) return;
  bulkDownloading.value = true;
  try {
    const results = await Promise.allSettled(
      devices.value.map((device) => axios.get(`/api/biometrics/${device.id}/download-log`))
    );
    const failed = results.filter((r) => r.status === 'rejected').length;
    await fetchDevices();
    await fetchRecentLogs();
    ThemeSwal.fire({
      icon: failed ? 'warning' : 'success',
      title: failed ? 'Partially downloaded' : 'Downloaded',
      text: failed
        ? `${devices.value.length - failed} of ${devices.value.length} device(s) downloaded.`
        : 'Logs downloaded from all devices.',
    });
  } finally {
    bulkDownloading.value = false;
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
    ThemeSwal.fire({ icon: 'warning', title: 'Missing information', text: 'Please enter a device name.' });
    return;
  }
  if (!newDevice.ip_address.trim()) {
    ThemeSwal.fire({ icon: 'warning', title: 'Missing information', text: 'Please enter the IP address.' });
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
    ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: error?.response?.data?.message || 'Unable to connect to biometric device.' });
  } finally {
    saving.value = false;
    fetchRecentLogs();
  }
}

async function removeDevice(device) {
  const result = await ThemeSwal.fire({
    title: 'Are you sure?',
    text: `Remove "${device.device_name}"? You won't be able to revert this!`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    customClass: {
      ...swalClass,
      confirmButton: 'ts-swal-btn ts-swal-btn--danger',
    },
  });
  if (!result.value) return;

  try {
    const { data } = await axios.delete(`/api/biometrics/${device.id}`);
    await fetchDevices();
    ThemeSwal.fire({
      title: 'Deleted',
      text: data?.message || 'Biometric device removed.',
      icon: 'success',
    });
  } catch (error) {
    console.error(error);
    ThemeSwal.fire({
      icon: 'error',
      title: 'Oops...',
      text: error?.response?.data?.message || 'Unable to remove biometric device.',
    });
  } finally {
    fetchRecentLogs();
  }
}

async function connectDevice(device) {
  try {
    const { data } = await axios.post(`/api/biometrics/${device.id}/connect`);
    syncDeviceFromResponse(data?.data);
  } catch (error) {
    console.error(error);
    ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: error?.response?.data?.message || 'Unable to connect biometric device.' });
  } finally {
    fetchRecentLogs();
    fetchDevices();
  }
}

async function disconnectDevice(device) {
  try {
    const { data } = await axios.post(`/api/biometrics/${device.id}/disconnect`);
    syncDeviceFromResponse(data?.data);
  } catch (error) {
    console.error(error);
    ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: error?.response?.data?.message || 'Unable to disconnect biometric device.' });
  } finally {
    fetchRecentLogs();
  }
}

function startDownloadProgress() {
  clearInterval(downloadProgressTimer);
  downloadProgress.value = 0;
  downloadProgressTimer = setInterval(() => {
    if (downloadProgress.value >= 90) return;
    const step = downloadProgress.value < 45 ? 5 : downloadProgress.value < 75 ? 2 : 1;
    downloadProgress.value = Math.min(90, downloadProgress.value + step);
  }, 200);
}

function stopDownloadProgress() {
  clearInterval(downloadProgressTimer);
  downloadProgressTimer = null;
  clearTimeout(downloadStageTimer);
  downloadStageTimer = null;
}

async function downloadLog(device) {
  downloadTarget.value = device;
  downloadStatus.value = 'connecting';
  downloadResult.value = null;
  downloadErrorMsg.value = '';
  showDownloadModal.value = true;
  startDownloadProgress();

  downloadStageTimer = setTimeout(() => {
    if (showDownloadModal.value) downloadStatus.value = 'transferring';
  }, 600);

  try {
    const { data } = await axios.get(`/api/biometrics/${device.id}/download-log`);
    const summary = data?.data || {};
    downloadStatus.value = 'saving';
    await new Promise((resolve) => setTimeout(resolve, 450));
    stopDownloadProgress();
    downloadProgress.value = 100;
    downloadResult.value = summary;
    downloadStatus.value = 'success';
  } catch (error) {
    console.error(error);
    stopDownloadProgress();
    downloadStatus.value = 'error';
    downloadErrorMsg.value = error?.response?.data?.message || 'Unable to download biometric logs.';
  } finally {
    fetchRecentLogs();
    fetchDevices();
  }
}

function closeDownloadModal() {
  if (downloadStatus.value !== 'success' && downloadStatus.value !== 'error') return;
  showDownloadModal.value = false;
  stopDownloadProgress();
  downloadTarget.value = null;
}

function updateDevice(device) {
  updateTarget.value = device;
  updateForm.device_name = device.device_name || '';
  updateForm.ip_address = device.ip_address || '';
  updateForm.product_name = device.product_name || '';
  showUpdateModal.value = true;
}

function closeUpdateModal() {
  if (updating.value) return;
  showUpdateModal.value = false;
  updateTarget.value = null;
}

async function submitUpdateDevice() {
  if (!updateTarget.value) return;
  if (!updateForm.device_name.trim()) {
    ThemeSwal.fire({ icon: 'warning', title: 'Missing information', text: 'Please enter a device name.' });
    return;
  }
  if (!updateForm.ip_address.trim()) {
    ThemeSwal.fire({ icon: 'warning', title: 'Missing information', text: 'Please enter the IP address.' });
    return;
  }
  updating.value = true;
  try {
    const { data } = await axios.put(`/api/biometrics/${updateTarget.value.id}`, {
      device_name: updateForm.device_name.trim(),
      ip_address: updateForm.ip_address.trim(),
      product_name: updateForm.product_name.trim()
    });
    syncDeviceFromResponse(data?.data);
    showUpdateModal.value = false;
    updateTarget.value = null;
  } catch (error) {
    console.error(error);
    ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: error?.response?.data?.message || 'Unable to update biometric device.' });
  } finally {
    updating.value = false;
    fetchRecentLogs();
    fetchDevices();
  }
}

async function syncTime(device) {
  try {
    const { data } = await axios.post(`/api/biometrics/${device.id}/sync-time`);
    ThemeSwal.fire({ icon: 'success', title: 'Synced', text: data?.message || 'Biometric device time synced.' });
  } catch (error) {
    console.error(error);
    ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: error?.response?.data?.message || 'Unable to sync biometric device time.' });
  } finally {
    fetchRecentLogs();
    fetchDevices();
  }
}

async function refreshDevice(device) {
  try {
    const { data } = await axios.post(`/api/biometrics/${device.id}/refresh`);
    syncDeviceFromResponse(data?.data);
  } catch (error) {
    console.error(error);
    ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: error?.response?.data?.message || 'Unable to refresh biometric device data.' });
  } finally {
    fetchRecentLogs();
    fetchDevices();
  }
}

function syncDeviceFromResponse(updatedDevice) {
  if (!updatedDevice?.id) {
    fetchDevices();
    return;
  }
  const index = devices.value.findIndex((d) => d.id === updatedDevice.id);
  if (index !== -1) {
    if (updatedDevice.last_synced_at === undefined) {
      updatedDevice.last_synced_at = devices.value[index].last_synced_at ?? null;
    }
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
  fetchRecentLogs();
  document.addEventListener('click', closeDeviceMenu);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', closeDeviceMenu);
  stopDownloadProgress();
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

        <div class="icon-toolbar">
          <button
            class="icon-toolbar-btn"
            type="button"
            title="Refresh all"
            aria-label="Refresh all"
            :disabled="bulkRefreshing || !devices.length"
            @click="refreshAllDevices"
          >
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': bulkRefreshing }"></i>
          </button>
          <button
            class="icon-toolbar-btn"
            type="button"
            title="Download all"
            aria-label="Download all"
            :disabled="bulkDownloading || !devices.length"
            @click="downloadAllDevices"
          >
            <i class="fas fa-download" :class="{ 'fa-spin': bulkDownloading }"></i>
          </button>
          <button class="icon-toolbar-btn icon-toolbar-btn--primary" type="button" title="Add device" aria-label="Add device" @click="toggleForm">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      </div>
    </header>

    <div class="surface">
      <div class="search-bar">
        <i class="fas fa-search search-bar__icon"></i>
        <input
          v-model.trim="searchQuery"
          type="text"
          class="search-bar__input"
          placeholder="Search devices by name, IP, product or serial number..."
        />
        <button v-if="searchQuery" class="search-bar__clear" type="button" title="Clear search" aria-label="Clear search" @click="searchQuery = ''">
          <i class="fas fa-times"></i>
        </button>
      </div>

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

      <div v-else-if="viewMode === 'card'" class="device-carousel">
        <button
          v-if="filteredDevices.length"
          class="carousel-nav carousel-nav--prev"
          type="button"
          title="Previous"
          aria-label="Previous page"
          :disabled="carouselPage === 0"
          @click="prevCarouselPage"
        >
          <i class="fas fa-chevron-left"></i>
        </button>

        <div class="device-grid">
        <article v-for="device in pagedDevices" :key="device.id || device.device_name" class="device-card">
          <div class="card-top">
            <div class="device-icon">
              <i class="fas fa-fingerprint"></i>
            </div>
            <span class="card-period" :title="device.last_synced_at ? formatLogTime(device.last_synced_at) : ''">{{ lastSyncedLabel(device) }}</span>
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

        <div v-if="!filteredDevices.length" class="empty-state">
          <i class="fas" :class="searchQuery ? 'fa-magnifying-glass' : 'fa-microchip'"></i>
          <p>{{ searchQuery ? 'No devices match your search' : 'No biometric devices added' }}</p>
          <span>{{ searchQuery ? 'Try a different search term' : 'Click "Add device" to get started' }}</span>
        </div>
        </div>

        <button
          v-if="filteredDevices.length"
          class="carousel-nav carousel-nav--next"
          type="button"
          title="Next"
          aria-label="Next page"
          :disabled="carouselPage >= totalCarouselPages - 1"
          @click="nextCarouselPage"
        >
          <i class="fas fa-chevron-right"></i>
        </button>

        <div v-if="filteredDevices.length && totalCarouselPages > 1" class="carousel-dots">
          <button
            v-for="page in totalCarouselPages"
            :key="page"
            type="button"
            class="carousel-dot"
            :class="{ active: carouselPage === page - 1 }"
            :aria-label="`Go to page ${page}`"
            @click="goToCarouselPage(page - 1)"
          ></button>
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
            <tr v-for="device in filteredDevices" :key="device.id || device.device_name">
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
            <tr v-if="!filteredDevices.length">
              <td colspan="9" class="empty-table">
                <i class="fas" :class="searchQuery ? 'fa-magnifying-glass' : 'fa-microchip'"></i>
                <p>{{ searchQuery ? 'No devices match your search' : 'No biometric devices added' }}</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="recent-logs">
        <button type="button" class="recent-logs__header" @click="showRecentLogs = !showRecentLogs">
          <span class="recent-logs__title">Recent Sync Logs</span>
          <i class="fas" :class="showRecentLogs ? 'fa-chevron-down' : 'fa-chevron-up'"></i>
        </button>

        <div v-if="showRecentLogs" class="recent-logs__list">
          <div v-if="!recentLogs.length" class="recent-log-item recent-log-item--empty">
            No sync activity yet
          </div>
          <div v-for="log in recentLogs" :key="log.id" class="recent-log-item">
            <i class="fas recent-log-item__icon" :class="[logIcon(log), log.status === 'failed' ? 'is-failed' : 'is-success']"></i>
            <div class="recent-log-item__body">
              <span class="recent-log-item__text">{{ logLabel(log) }}</span>
              <span class="recent-log-item__meta">{{ log.device_name || 'Unknown device' }} &middot; {{ formatLogTime(log.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <footer class="footer-info">
        <span><i class="fas fa-check-circle"></i> {{ devices.length }} device(s) connected</span>
      </footer>
    </div>

    <v-dialog v-model="showUpdateModal" max-width="480px" persistent>
      <div class="lib-modal">
        <div class="lib-modal__header">
          <div class="lib-modal__header-left">
            <div class="lib-modal__icon">
              <v-icon icon="mdi-fingerprint" size="18" />
            </div>
            <div>
              <p class="lib-modal__eyebrow">Biometric Devices</p>
              <h6 class="lib-modal__title">Update Device</h6>
            </div>
          </div>
          <button class="lib-modal__close" :disabled="updating" @click="closeUpdateModal">
            <v-icon icon="mdi-close" size="16" />
          </button>
        </div>

        <div class="lib-modal__body">
          <div class="lib-modal__field">
            <label class="lib-modal__label">
              Device Name
              <span class="lib-modal__required">*</span>
            </label>
            <div class="lib-modal__input-wrap">
              <v-icon icon="mdi-fingerprint" size="16" class="lib-modal__input-icon" />
              <input
                v-model.trim="updateForm.device_name"
                type="text"
                placeholder="Enter device name"
                class="lib-modal__input"
                @keyup.enter="submitUpdateDevice"
                autofocus
              />
            </div>
          </div>

          <div class="lib-modal__field">
            <label class="lib-modal__label">
              IP Address
              <span class="lib-modal__required">*</span>
            </label>
            <div class="lib-modal__input-wrap">
              <v-icon icon="mdi-network-outline" size="16" class="lib-modal__input-icon" />
              <input
                v-model.trim="updateForm.ip_address"
                type="text"
                placeholder="e.g. 192.168.1.10"
                class="lib-modal__input"
                @keyup.enter="submitUpdateDevice"
              />
            </div>
          </div>

          <div class="lib-modal__field">
            <label class="lib-modal__label">Product Name</label>
            <div class="lib-modal__input-wrap">
              <v-icon icon="mdi-package-variant-closed" size="16" class="lib-modal__input-icon" />
              <input
                v-model.trim="updateForm.product_name"
                type="text"
                placeholder="Optional product name"
                class="lib-modal__input"
                @keyup.enter="submitUpdateDevice"
              />
            </div>
          </div>
        </div>

        <div class="lib-modal__footer">
          <button class="lib-modal__btn lib-modal__btn--cancel" :disabled="updating" @click="closeUpdateModal">
            Cancel
          </button>
          <button
            class="lib-modal__btn lib-modal__btn--save"
            :disabled="!updateForm.device_name?.trim() || !updateForm.ip_address?.trim() || updating"
            @click="submitUpdateDevice"
          >
            <v-icon v-if="updating" icon="mdi-loading" size="14" class="lib-modal__spinner" />
            <v-icon v-else icon="mdi-content-save-outline" size="14" />
            {{ updating ? 'Saving...' : 'Update' }}
          </button>
        </div>
      </div>
    </v-dialog>

    <v-dialog v-model="showDownloadModal" max-width="440px" persistent>
      <div class="lib-modal download-modal">
        <div class="lib-modal__body">
          <p class="download-device-name">{{ downloadTarget?.device_name }}</p>

          <div class="transfer-track">
            <div class="transfer-node transfer-node--device">
              <i class="fas fa-calculator"></i>
              <span>Biometric</span>
            </div>

            <div class="transfer-path">
              <div class="transfer-path__line"></div>
              <div
                v-for="n in 3"
                :key="n"
                class="transfer-packet"
                :class="{ 'transfer-packet--paused': downloadStatus === 'success' || downloadStatus === 'error' }"
                :style="{ animationDelay: (n - 1) * 0.45 + 's' }"
              ></div>
            </div>

            <div class="transfer-node transfer-node--server">
              <i class="fas fa-server"></i>
              <span>Server</span>
            </div>
          </div>

          <div class="download-progress">
            <div class="download-progress__bar">
              <div
                class="download-progress__fill"
                :class="{ 'download-progress__fill--error': downloadStatus === 'error' }"
                :style="{ width: downloadProgress + '%' }"
              ></div>
            </div>
            <div class="download-progress__meta">
              <span class="download-progress__label">
                <i v-if="downloadStatus === 'success'" class="fas fa-check-circle"></i>
                <i v-else-if="downloadStatus === 'error'" class="fas fa-triangle-exclamation"></i>
                <i v-else class="fas fa-circle-notch fa-spin"></i>
                {{
                  downloadStatus === 'connecting' ? 'Connecting to device...'
                  : downloadStatus === 'transferring' ? 'Transferring logs...'
                  : downloadStatus === 'saving' ? 'Saving to server...'
                  : downloadStatus === 'success' ? 'Complete'
                  : 'Failed'
                }}
              </span>
              <span class="download-progress__pct">{{ Math.round(downloadProgress) }}%</span>
            </div>
          </div>

          <div v-if="downloadStatus === 'success'" class="download-summary">
            <div class="download-summary__row">
              <span>New logs saved</span>
              <strong>{{ downloadResult?.total_downloaded ?? 0 }}</strong>
            </div>
            <div class="download-summary__row">
              <span>Already existed</span>
              <strong>{{ downloadResult?.already_exists ?? 0 }}</strong>
            </div>
            <div class="download-summary__row">
              <span>Total on device</span>
              <strong>{{ downloadResult?.total_logs ?? 0 }}</strong>
            </div>
          </div>

          <div v-if="downloadStatus === 'error'" class="download-error">
            {{ downloadErrorMsg }}
          </div>
        </div>

        <div v-if="downloadStatus === 'success' || downloadStatus === 'error'" class="lib-modal__footer">
          <button class="lib-modal__btn lib-modal__btn--save" @click="closeDownloadModal">
            Done
          </button>
        </div>
      </div>
    </v-dialog>
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

.icon-toolbar {
  display: flex;
  gap: 8px;
}

.icon-toolbar-btn {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  border: 1px solid rgba(121, 146, 207, 0.18);
  background: rgba(24, 35, 70, 0.82);
  color: #d6e2ff;
  display: grid;
  place-items: center;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(1, 8, 24, 0.16);
}

.icon-toolbar-btn:hover:not(:disabled) {
  border-color: rgba(31, 191, 184, 0.5);
  color: #75e7d7;
}

.icon-toolbar-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.icon-toolbar-btn--primary {
  border: 0;
  background: linear-gradient(90deg, #1fbfb8 0%, #52d3d0 100%);
  color: #06162f;
  box-shadow: 0 16px 30px rgba(31, 191, 184, 0.22);
}

.icon-toolbar-btn--primary:hover:not(:disabled) {
  color: #06162f;
  opacity: 0.9;
}

.surface {
  border-radius: 18px;
  background: rgba(10, 18, 40, 0.58);
  border: 1px solid rgba(121, 146, 207, 0.16);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 24px 60px rgba(2, 7, 20, 0.28);
  padding: 14px;
}

.search-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 44px;
  margin-bottom: 14px;
  padding: 0 14px;
  border-radius: 14px;
  background: rgba(7, 13, 28, 0.7);
  border: 1px solid rgba(121, 146, 207, 0.18);
}

.search-bar:focus-within {
  border-color: rgba(31, 191, 184, 0.6);
  box-shadow: 0 0 0 3px rgba(31, 191, 184, 0.14);
}

.search-bar__icon {
  color: #59d2d7;
  font-size: 13px;
}

.search-bar__input {
  flex: 1;
  height: 100%;
  border: 0;
  background: transparent;
  color: #edf5ff;
  outline: none;
  font-size: 14px;
}

.search-bar__input::placeholder {
  color: #7488ae;
}

.search-bar__clear {
  border: 0;
  background: transparent;
  color: #9bb0da;
  cursor: pointer;
  padding: 4px;
}

.search-bar__clear:hover {
  color: #ff8080;
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

.device-carousel {
  display: flex;
  align-items: center;
  gap: 10px;
  position: relative;
  margin-bottom: 26px;
}

.device-carousel .device-grid {
  flex: 1;
  min-width: 0;
}

.carousel-nav {
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  border-radius: 999px;
  border: 1px solid rgba(121, 146, 207, 0.18);
  background: rgba(24, 35, 70, 0.82);
  color: #d6e2ff;
  display: grid;
  place-items: center;
  cursor: pointer;
  box-shadow: 0 8px 18px rgba(1, 8, 24, 0.16);
}

.carousel-nav:hover:not(:disabled) {
  border-color: rgba(31, 191, 184, 0.5);
  color: #75e7d7;
}

.carousel-nav:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.carousel-dots {
  position: absolute;
  left: 50%;
  bottom: -22px;
  transform: translateX(-50%);
  display: flex;
  gap: 8px;
}

.carousel-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  border: 0;
  padding: 0;
  background: rgba(121, 146, 207, 0.35);
  cursor: pointer;
}

.carousel-dot.active {
  background: #1fbfb8;
  width: 20px;
}

.device-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
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
  background: rgba(255, 79, 79, 0.18);
  color: #ff8080;
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
  justify-content: flex-end;
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
  border-radius: 16px;
  background: linear-gradient(180deg, rgba(22, 31, 58, 0.9), rgba(14, 21, 41, 0.98));
  border: 1px solid rgba(121, 146, 207, 0.14);
  color: #eaf3ff;
}

.recent-logs__header {
  width: 100%;
  height: 42px;
  padding: 0 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

.recent-logs__title {
  font-size: 13px;
  font-weight: 600;
}

.recent-logs__header i {
  margin-left: auto;
  color: #aabce3;
  font-size: 12px;
}

.recent-logs__list {
  padding: 4px 14px 12px;
  display: grid;
  gap: 4px;
  max-height: 420px;
  overflow-y: auto;
  border-top: 1px solid rgba(121, 146, 207, 0.12);
}

.recent-log-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 4px;
}

.recent-log-item--empty {
  color: #8ea3d4;
  font-size: 13px;
  justify-content: center;
  padding: 16px 4px;
}

.recent-log-item__icon {
  width: 30px;
  height: 30px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 9px;
  font-size: 12px;
  background: rgba(31, 191, 184, 0.15);
  color: #59d2d7;
}

.recent-log-item__icon.is-failed {
  background: rgba(255, 79, 79, 0.14);
  color: #ff9b9b;
}

.recent-log-item__body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.recent-log-item__text {
  font-size: 13px;
  font-weight: 600;
  color: #edf5ff;
}

.recent-log-item__meta {
  font-size: 11px;
  color: #8ea3d4;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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

  .device-grid {
    grid-template-columns: repeat(2, 1fr);
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

.download-device-name {
  text-align: center;
  font-size: 13px;
  color: #9bb0da;
  margin-bottom: 20px;
}

.transfer-track {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 22px;
}

.transfer-node {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.transfer-node i {
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  font-size: 18px;
  background: rgba(31, 191, 184, 0.15);
  color: #59d2d7;
  box-shadow: inset 0 0 0 1px rgba(31, 191, 184, 0.25);
}

.transfer-node span {
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: #8ea3d4;
}

.transfer-node--server i {
  background: rgba(75, 135, 255, 0.15);
  color: #8ed5ff;
  box-shadow: inset 0 0 0 1px rgba(75, 135, 255, 0.25);
}

.transfer-path {
  position: relative;
  flex: 1;
  height: 46px;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.transfer-path__line {
  width: 100%;
  height: 2px;
  border-radius: 999px;
  background: repeating-linear-gradient(90deg, rgba(121, 146, 207, 0.35) 0 6px, transparent 6px 12px);
}

.transfer-packet {
  position: absolute;
  top: 50%;
  left: 0;
  width: 9px;
  height: 9px;
  margin-top: -4.5px;
  border-radius: 999px;
  background: #1fbfb8;
  box-shadow: 0 0 10px 2px rgba(31, 191, 184, 0.75);
  animation: transfer-move 1.6s linear infinite;
}

.transfer-packet--paused {
  animation-play-state: paused;
  opacity: 0;
}

@keyframes transfer-move {
  0% {
    left: 0%;
    opacity: 0;
    transform: scale(0.8);
  }
  10% {
    opacity: 1;
    transform: scale(1);
  }
  85% {
    opacity: 1;
  }
  100% {
    left: 100%;
    opacity: 0;
    transform: scale(0.8);
  }
}

.download-progress__bar {
  height: 8px;
  border-radius: 999px;
  background: rgba(121, 146, 207, 0.14);
  overflow: hidden;
}

.download-progress__fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #1fbfb8 0%, #52d3d0 100%);
  transition: width 0.25s ease;
}

.download-progress__fill--error {
  background: linear-gradient(90deg, #ff7f63, #ff4d4d);
}

.download-progress__meta {
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  color: #9bb0da;
}

.download-progress__label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.download-progress__pct {
  font-weight: 700;
  color: #edf5ff;
}

.download-summary {
  margin-top: 18px;
  padding: 12px 14px;
  border-radius: 14px;
  background: rgba(31, 191, 184, 0.08);
  border: 1px solid rgba(31, 191, 184, 0.2);
  display: grid;
  gap: 8px;
}

.download-summary__row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #c8d6f8;
}

.download-summary__row strong {
  color: #f5fbff;
}

.download-error {
  margin-top: 16px;
  padding: 12px 14px;
  border-radius: 14px;
  background: rgba(255, 79, 79, 0.1);
  border: 1px solid rgba(255, 79, 79, 0.25);
  color: #ffb3b3;
  font-size: 13px;
}
</style>
