<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useTheme as useVuetifyTheme } from 'vuetify';
import useAuth from '../composables/auth.js';
import useTheme from '../composables/useTheme.js';

const router = useRouter();
const { logout, getAuthUser } = useAuth();

const vuetifyTheme = useVuetifyTheme();
const { isDark, toggleTheme: _toggleTheme } = useTheme();
const handleToggleTheme = () => _toggleTheme(vuetifyTheme);

const authUser = computed(() => getAuthUser());

const handleLogout = async () => {
    await logout();
    router.push('/login');
};

const route = useRoute();

const logoUrl = '/images/logo.png';

const navItems = [
  { path: '/biometric', label: 'Biometrics', icon: 'mdi mdi-chip' },
  { path: '/employees', label: 'Employees', icon: 'mdi mdi-account-group' },
  { path: '/reports/dtr', label: 'Daily Time Record', icon: 'mdi mdi-calendar-account' },
  { path: '/reports/logbook', label: 'Logbook', icon: 'mdi mdi-notebook-outline' },
  { path: '/data-sync', label: 'Data Sync', icon: 'mdi mdi-cloud-sync-outline' },
];

const librariesItems = [
  { path: '/libraries/schedules', label: 'Schedule', icon: 'mdi mdi-clock-outline' },
  { path: '/libraries/holidays', label: 'Holiday', icon: 'mdi mdi-calendar-today' },
  { path: '/libraries/offices', label: 'Office', icon: 'mdi mdi-office-building' },
  { path: '/libraries/office-divisions', label: 'Office Division', icon: 'mdi mdi-sitemap' },
  { path: '/libraries/employment-types', label: 'Employment Type', icon: 'mdi mdi-briefcase' },
  { path: '/libraries/titles', label: 'Title', icon: 'mdi mdi-badge-account' },
  { path: '/libraries/positions', label: 'Position', icon: 'mdi mdi-account-tie' },
  { path: '/libraries/post-numbers', label: 'Post Number', icon: 'mdi mdi-pound' },
  { path: '/libraries/schedule-types', label: 'Schedule Type', icon: 'mdi mdi-calendar-clock' },
  { path: '/libraries/roles', label: 'Role', icon: 'mdi mdi-account-tag' },
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
      <div class="sidebar-user" v-if="authUser">
        <div class="sidebar-user__avatar">{{ authUser.username?.charAt(0).toUpperCase() }}</div>
        <div class="sidebar-user__info">
          <span class="sidebar-user__name">{{ authUser.username }}</span>
          <small class="sidebar-user__role">{{ authUser.role?.name ?? 'User' }}</small>
        </div>
        <button class="sidebar-theme" @click="handleToggleTheme" :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
          <i :class="isDark ? 'mdi mdi-weather-sunny' : 'mdi mdi-weather-night'"></i>
        </button>
        <button class="sidebar-logout" @click="handleLogout" title="Sign out">
          <i class="mdi mdi-logout"></i>
        </button>
      </div>
      <div v-else class="sidebar-version">
        <i class="mdi mdi-circle"></i> TimeSys · v2.1
      </div>
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
  color: var(--sidebar-color);
  background: var(--sidebar-bg);
  border-right: 1px solid var(--sidebar-border);
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
  color: var(--brand-title);
}

.brand small {
  display: block;
  margin-top: 2px;
  font-size: 14px;
  color: var(--brand-subtitle);
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
  color: var(--nav-text);
  transition: all 0.2s;
  cursor: pointer;
  flex-shrink: 0;
  text-decoration: none;
}

.nav-item i {
  width: 24px;
  font-size: 18px;
  color: var(--nav-icon);
}

.nav-item .chevron {
  margin-left: auto;
  width: auto;
  font-size: 13px;
  color: var(--nav-icon);
  transition: transform 0.25s ease;
}

.nav-item .chevron.rotated {
  transform: rotate(180deg);
}

.nav-item.active {
  background: linear-gradient(90deg, rgba(31, 191, 184, 0.22), rgba(31, 191, 184, 0.08));
  color: var(--nav-active-text);
  box-shadow: 0 0 0 1px rgba(31, 191, 184, 0.3), 0 10px 24px rgba(31, 191, 184, 0.12);
  margin: 1px 1px;
}

.nav-item.active i {
  color: #1fbfb8;
}

.nav-item:not(.active):hover {
  background: rgba(255, 255, 255, 0.04);
  color: var(--nav-hover-text);
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
  color: var(--nav-subtext);
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
  text-decoration: none;
}

.nav-subitem i {
  width: 20px;
  font-size: 14px;
  color: var(--nav-subicon);
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
  color: var(--nav-hover-text);
}

.sidebar-footer {
  border-top: 1px solid var(--sidebar-footer-border);
  padding-top: 20px;
}

.sidebar-version {
  display: flex;
  align-items: center;
  gap: 14px;
  color: var(--sidebar-version-color);
  font-size: 14px;
}

.sidebar-version i {
  font-size: 20px;
  color: var(--sidebar-version-icon);
}

.sidebar-user {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sidebar-user__avatar {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  border-radius: 10px;
  background: linear-gradient(135deg, #1fbfb8 0%, #05716c 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  color: white;
}

.sidebar-user__info {
  flex: 1;
  min-width: 0;
}

.sidebar-user__name {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--sidebar-user-name);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-user__role {
  display: block;
  font-size: 11px;
  color: var(--sidebar-user-role);
}

.sidebar-logout {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--logout-color);
  font-size: 16px;
  padding: 6px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  transition: color 0.2s, background 0.2s;
  flex-shrink: 0;
}

.sidebar-theme {
  background: none;
  border: none;
  cursor: pointer;
  color: #1fbfb8;
  font-size: 16px;
  padding: 6px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  transition: color 0.2s, background 0.2s;
  flex-shrink: 0;
}

.sidebar-theme:hover {
  background: rgba(31, 191, 184, 0.1);
}

.sidebar-logout:hover {
  color: #f87171;
  background: rgba(248, 113, 113, 0.08);
}
</style>
