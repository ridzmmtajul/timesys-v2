<template>
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="brand">
        <!-- <i class="fas fa-fingerprint"></i> -->
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
          <i :class="item.icon"></i> {{ item.label }}
        </div>
      </nav>
      <div class="sidebar-footer">
        <i class="fas fa-circle"></i> system · v2.1
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main">
      <BiometricList @data-pulled="handleDataPulled" />
    </main>
  </div>
</template>

<script>
import BiometricList from './biometric/Index.vue';

export default {
  name: 'App',
  components: {
    BiometricList
  },
  data() {
    return {
      logoUrl: '/images/logo.png',
      currentPage: 'biometric-list',
      navItems: [
        { name: 'biometric-list', label: 'Biometric list', icon: 'fas fa-microchip' },
        { name: 'analytics', label: 'Analytics', icon: 'fas fa-chart-pie' },
        { name: 'logs', label: 'Logs', icon: 'fas fa-clock' },
        { name: 'settings', label: 'Settings', icon: 'fas fa-cog' }
      ]
    };
  },
  methods: {
    handleDataPulled(device) {
      console.log('Data pulled from device:', device);
    }
  }
};
</script>

<style scoped>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.app-container {
  width: 100vw;
  height: 100vh;
  background: white;
  display: flex;
  overflow: hidden;
  margin: 0;
  border-radius: 0;
  box-shadow: none;
}

.sidebar {
  width: 280px;
  background: #031163;
  color: rgba(255,255,255,0.8);
  padding: 32px 20px;
  display: flex;
  flex-direction: column;
  gap: 48px;
  flex-shrink: 0;
  height: 100vh;
  position: sticky;
  top: 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: 14px;
  padding-left: 6px;
}

.brand i {
  font-size: 28px;
  color: #1fbfb8;
  background: rgba(31, 191, 184, 0.12);
  padding: 10px;
  border-radius: 18px;
}

.brand span {
  font-weight: 700;
  font-size: 22px;
  letter-spacing: -0.3px;
  color: white;
}

.brand small {
  font-weight: 400;
  font-size: 14px;
  color: #a0b9e6;
  display: block;
  margin-top: 2px;
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
  padding: 14px 18px;
  border-radius: 18px;
  font-weight: 500;
  font-size: 16px;
  color: #c8d9f5;
  transition: all 0.2s;
  cursor: pointer;
}

.nav-item i {
  width: 24px;
  font-size: 18px;
  color: #6d89c7;
}

.nav-item.active {
  background: rgba(31, 191, 184, 0.18);
  color: white;
  box-shadow: 0 0 0 1px rgba(31, 191, 184, 0.3);
}

.nav-item.active i {
  color: #1fbfb8;
}

.nav-item:not(.active):hover {
  background: rgba(255,255,255,0.04);
  color: white;
}

.nav-item:not(.active):hover i {
  color: #a6c0f0;
}

.sidebar-footer {
  margin-top: auto;
  border-top: 1px solid rgba(255,255,255,0.06);
  padding-top: 24px;
  display: flex;
  align-items: center;
  gap: 14px;
  color: #a0b9e6;
  font-size: 14px;
}

.sidebar-footer i {
  font-size: 20px;
  color: #4f72b3;
}

.main {
  flex: 1;
  background: #f8fafd;
  padding: 32px 40px;
  overflow-y: auto;
  height: 100vh;
}

.main::-webkit-scrollbar {
  width: 6px;
}
.main::-webkit-scrollbar-thumb {
  background: #b9d2e8;
  border-radius: 12px;
}

.brand-logo {
  width: 35px;
  height: 35px;
  object-fit: contain;
}
</style>
