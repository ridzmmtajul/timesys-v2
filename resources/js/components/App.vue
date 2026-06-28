<script>
import BiometricList from './biometric/Index.vue';
import EmployeeList from './employees/Index.vue';
import RoleList from './libraries/roles/Index.vue';
import OfficeList from './libraries/offices/Index.vue';
import OfficeDivisionList from './libraries/office-divisions/Index.vue';
import TitleList from './libraries/titles/Index.vue';
import EmploymentTypeList from './libraries/employment-types/Index.vue';
import HolidayList from './libraries/holidays/Index.vue';

export default {
  name: 'App',
  components: { BiometricList, EmployeeList, RoleList, OfficeList, OfficeDivisionList, TitleList, EmploymentTypeList, HolidayList },
  data() {
    return {
      logoUrl: '/images/logo.png',
      currentPage: 'biometric-list',
      settingsOpen: false,
      navItems: [
        { name: 'biometric-list', label: 'Biometric list', icon: 'fas fa-microchip' },
        { name: 'employees', label: 'Employees', icon: 'fas fa-users' },
      ],
      settingsItems: [
        { name: 'settings-role', label: 'Role', icon: 'fas fa-user-tag' },
        { name: 'settings-office', label: 'Office', icon: 'fas fa-building' },
        { name: 'settings-office-division', label: 'Office Division', icon: 'fas fa-sitemap' },
        { name: 'settings-employment-type', label: 'Employment Type', icon: 'fas fa-briefcase' },
        { name: 'settings-title', label: 'Title', icon: 'fas fa-id-badge' },
        { name: 'settings-holiday', label: 'Holiday', icon: 'fas fa-calendar-day' },
      ]
    };
  },
  computed: {
    isSettingsActive() {
      return this.currentPage.startsWith('settings-');
    }
  },
  methods: {
    handleDataPulled(device) {
      console.log('Data pulled from device:', device);
    },
    toggleSettings() {
      this.settingsOpen = !this.settingsOpen;
    },
    navigateToSettings(name) {
      this.currentPage = name;
      this.settingsOpen = true;
    }
  }
};
</script>

<template>
  <div class="app-container">
    <aside class="sidebar">
      <div class="brand">
        <img :src="logoUrl" alt="Logo" class="brand-logo" />
        <div>
          <span>TimeSync</span>
          <small>Bio & Attendance System</small>
        </div>
      </div>

      <nav class="nav">
        <div
          v-for="item in navItems"
          :key="item.name"
          class="nav-item"
          :class="{ active: currentPage === item.name }"
          @click="currentPage = item.name"
        >
          <i :class="item.icon"></i>
          <span>{{ item.label }}</span>
        </div>

        <!-- Settings dropdown -->
        <div
          class="nav-item"
          :class="{ active: isSettingsActive }"
          @click="toggleSettings"
        >
          <i class="fas fa-cog"></i>
          <span>Settings</span>
          <i class="fas fa-chevron-down chevron" :class="{ rotated: settingsOpen }"></i>
        </div>

        <div class="settings-dropdown" :class="{ open: settingsOpen }">
          <div
            v-for="item in settingsItems"
            :key="item.name"
            class="nav-subitem"
            :class="{ active: currentPage === item.name }"
            @click="navigateToSettings(item.name)"
          >
            <i :class="item.icon"></i>
            <span>{{ item.label }}</span>
          </div>
        </div>
      </nav>

      <div class="sidebar-footer">
        <i class="fas fa-circle"></i> system · v2.1
      </div>
    </aside>

    <main class="main">
      <BiometricList v-if="currentPage === 'biometric-list'" @data-pulled="handleDataPulled" />
      <EmployeeList v-if="currentPage === 'employees'" />
      <RoleList v-if="currentPage === 'settings-role'" />
      <OfficeList v-if="currentPage === 'settings-office'" />
      <OfficeDivisionList v-if="currentPage === 'settings-office-division'" />
      <TitleList v-if="currentPage === 'settings-title'" />
      <EmploymentTypeList v-if="currentPage === 'settings-employment-type'" />
      <HolidayList v-if="currentPage === 'settings-holiday'" />
    </main>
  </div>
</template>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.app-container {
  width: 100vw;
  height: 100vh;
  display: flex;
  overflow: hidden;
  background:
    radial-gradient(circle at top left, rgba(31, 191, 184, 0.16), transparent 28%),
    radial-gradient(circle at top right, rgba(63, 109, 199, 0.2), transparent 32%),
    linear-gradient(135deg, #071029 0%, #091737 45%, #0c1730 100%);
}

.sidebar {
  width: 292px;
  flex-shrink: 0;
  height: 100vh;
  position: sticky;
  top: 0;
  display: flex;
  flex-direction: column;
  gap: 42px;
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
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 8px;
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
}

.nav-item.active i {
  color: #1fbfb8;
}

.nav-item:not(.active):hover {
  background: rgba(255, 255, 255, 0.04);
  color: white;
}

.settings-dropdown {
  display: flex;
  flex-direction: column;
  gap: 4px;
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.3s ease;
  margin-top: -4px;
}

.settings-dropdown.open {
  max-height: 450px;
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
  margin-top: auto;
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

.main {
  flex: 1;
  height: 100vh;
  overflow-y: auto;
  padding: 18px 22px 22px;
  background:
    radial-gradient(circle at top center, rgba(66, 113, 216, 0.14), transparent 35%),
    linear-gradient(180deg, rgba(12, 21, 43, 0.72), rgba(8, 16, 34, 0.88));
}

.main::-webkit-scrollbar {
  width: 6px;
}

.main::-webkit-scrollbar-thumb {
  background: #b9d2e8;
  border-radius: 12px;
}
</style>
