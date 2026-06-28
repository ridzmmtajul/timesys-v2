<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const logoUrl = '/images/logo.png';

const navItems = [
  { path: '/biometric', label: 'Biometric list', icon: 'mdi mdi-chip' },
  { path: '/employees', label: 'Employees', icon: 'mdi mdi-account-group' },
];

const librariesItems = [
  { path: '/libraries/roles', label: 'Role', icon: 'mdi mdi-account-tag' },
  { path: '/libraries/offices', label: 'Office', icon: 'mdi mdi-office-building' },
  { path: '/libraries/office-divisions', label: 'Office Division', icon: 'mdi mdi-sitemap' },
  { path: '/libraries/employment-types', label: 'Employment Type', icon: 'mdi mdi-briefcase' },
  { path: '/libraries/titles', label: 'Title', icon: 'mdi mdi-badge-account' },
  { path: '/libraries/holidays', label: 'Holiday', icon: 'mdi mdi-calendar-today' },
  { path: '/libraries/positions', label: 'Position', icon: 'mdi mdi-account-tie' },
  { path: '/libraries/post-numbers', label: 'Post Number', icon: 'mdi mdi-pound' },
  { path: '/libraries/schedule-types', label: 'Schedule Type', icon: 'mdi mdi-calendar-clock' },
  { path: '/libraries/schedules', label: 'Schedule', icon: 'mdi mdi-clock-outline' },
];

const settingsItems = [
  { path: '/settings/accounts', label: 'Accounts', icon: 'mdi mdi-account-tag' },
  { path: '/settings/work-time-rules', label: 'Work Time Rules', icon: 'mdi mdi-timer-cog' },
];

const librariesOpen = ref(false);
const settingsOpen = ref(false);

const isLibrariesActive = computed(() => route.path.startsWith('/libraries'));
const isSettingsActive = computed(() => route.path.startsWith('/settings'));

watch(isLibrariesActive, (val) => {
  if (val) {
    librariesOpen.value = true;
    settingsOpen.value = false;
  }
});

watch(isSettingsActive, (val) => {
  if (val) {
    settingsOpen.value = true;
    librariesOpen.value = false;
  }
});

function toggleLibraries() {
  librariesOpen.value = !librariesOpen.value;
  if (librariesOpen.value) settingsOpen.value = false;
}

function toggleSettings() {
  settingsOpen.value = !settingsOpen.value;
  if (settingsOpen.value) librariesOpen.value = false;
}
</script>

<template>
  <aside class="sidebar">
    <div class="brand">
      <img :src="logoUrl" alt="Logo" class="brand-logo" />
      <div>
        <span>TimeSync</span>
        <small>Bio & Attendance System</small>
      </div>
    </div>

    <nav class="nav">
      <router-link
        v-for="item in navItems"
        :key="item.path"
        :to="item.path"
        class="nav-item"
        active-class="active"
      >
        <i :class="item.icon"></i>
        <span>{{ item.label }}</span>
      </router-link>

      <div
        class="nav-item"
        :class="{ active: isLibrariesActive }"
        @click="toggleLibraries"
      >
        <i class="mdi mdi-book-open-variant"></i>
        <span>Libraries</span>
        <i class="mdi mdi-chevron-down chevron" :class="{ rotated: librariesOpen }"></i>
      </div>

      <div class="libraries-dropdown" :class="{ open: librariesOpen }">
        <router-link
          v-for="item in librariesItems"
          :key="item.path"
          :to="item.path"
          class="nav-subitem"
          active-class="active"
        >
          <i :class="item.icon"></i>
          <span>{{ item.label }}</span>
        </router-link>
      </div>
      
      <div
        class="nav-item"
        :class="{ active: isSettingsActive }"
        @click="toggleSettings"
      >
        <i class="mdi mdi-cog"></i>
        <span>Settings</span>
        <i class="mdi mdi-chevron-down chevron" :class="{ rotated: settingsOpen }"></i>
      </div>

      <div class="settings-dropdown" :class="{ open: settingsOpen }">
        <router-link
          v-for="item in settingsItems"
          :key="item.path"
          :to="item.path"
          class="nav-subitem"
          active-class="active"
        >
          <i :class="item.icon"></i>
          <span>{{ item.label }}</span>
        </router-link>
      </div>
    </nav>

    <div class="sidebar-footer">
      <i class="mdi mdi-circle"></i> TimeSys · v2.1
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 292px;
  flex-shrink: 0;
  height: 100vh;
  position: sticky;
  top: 0;
  overflow: hidden;
  display: grid;
  grid-template-rows: auto 1fr auto;
  padding: 28px 18px;
  color: rgba(255, 255, 255, 0.8);
  background: linear-gradient(180deg, rgba(8, 18, 44, 0.96) 0%, rgba(6, 14, 33, 0.98) 100%);
  border-right: 1px solid rgba(108, 143, 214, 0.15);
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 8px 6px;
  margin-bottom: 42px;
}

.brand span {
  display: block;
  font-weight: 700;
  font-size: 22px;
  letter-spacing: -0.3px;
  color: white;
}

.brand small {
  display: block;
  margin-top: 2px;
  font-size: 14px;
  color: #8aa0d7;
}

.brand-logo {
  width: 35px;
  height: 35px;
  object-fit: contain;
}

.nav {
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.nav::-webkit-scrollbar {
  width: 4px;
}

.nav::-webkit-scrollbar-thumb {
  background: rgba(108, 143, 214, 0.25);
  border-radius: 8px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 15px 16px;
  border-radius: 18px;
  font-weight: 500;
  font-size: 16px;
  color: #a9bce7;
  transition: all 0.2s;
  cursor: pointer;
  flex-shrink: 0;
  text-decoration: none;
}

.nav-item i {
  width: 24px;
  font-size: 18px;
  color: #728cc4;
}

.nav-item .chevron {
  margin-left: auto;
  width: auto;
  font-size: 13px;
  color: #728cc4;
  transition: transform 0.25s ease;
}

.nav-item .chevron.rotated {
  transform: rotate(180deg);
}

.nav-item.active {
  background: linear-gradient(90deg, rgba(31, 191, 184, 0.22), rgba(31, 191, 184, 0.08));
  color: white;
  box-shadow: 0 0 0 1px rgba(31, 191, 184, 0.3), 0 10px 24px rgba(31, 191, 184, 0.12);
  margin: 0px 1px;
}

.nav-item.active i {
  color: #1fbfb8;
}

.nav-item:not(.active):hover {
  background: rgba(255, 255, 255, 0.04);
  color: white;
}

.libraries-dropdown,
.settings-dropdown {
  display: flex;
  flex-direction: column;
  gap: 4px;
  overflow: auto;
  max-height: 0;
  transition: max-height 0.3s ease;
  margin-top: -4px;
  scrollbar-width: thin;
  scrollbar-color: rgba(108, 143, 214, 0.25) transparent;
}

.libraries-dropdown::-webkit-scrollbar,
.settings-dropdown::-webkit-scrollbar {
  width: 4px;
}

.libraries-dropdown::-webkit-scrollbar-track,
.settings-dropdown::-webkit-scrollbar-track {
  background: transparent;
}

.libraries-dropdown::-webkit-scrollbar-thumb,
.settings-dropdown::-webkit-scrollbar-thumb {
  background: rgba(108, 143, 214, 0.25);
  border-radius: 8px;
}

.libraries-dropdown.open,
.settings-dropdown.open {
  max-height: 100%;
}

.nav-subitem {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 11px 16px 11px 28px;
  border-radius: 14px;
  font-weight: 500;
  font-size: 14px;
  color: #8aa0d7;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
  text-decoration: none;
}

.nav-subitem i {
  width: 20px;
  font-size: 14px;
  color: #5a78b0;
}

.nav-subitem.active {
  background: rgba(31, 191, 184, 0.12);
  color: #1fbfb8;
}

.nav-subitem.active i {
  color: #1fbfb8;
}

.nav-subitem:not(.active):hover {
  background: rgba(255, 255, 255, 0.04);
  color: white;
}

.sidebar-footer {
  margin-top: 42px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 24px;
  display: flex;
  align-items: center;
  gap: 14px;
  color: #8aa0d7;
  font-size: 14px;
}

.sidebar-footer i {
  font-size: 20px;
  color: #4f72b3;
}
</style>
