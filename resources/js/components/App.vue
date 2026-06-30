<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useTheme as useVuetifyTheme } from 'vuetify';
import Sidebar from './Sidebar.vue';
import useTheme from '../composables/useTheme.js';

const route = useRoute();
const isAuthPage = computed(() => route.path === '/login' || route.path === '/set-password');

const vuetifyTheme = useVuetifyTheme();
const { initTheme } = useTheme();
onMounted(() => initTheme(vuetifyTheme));
</script>

<template>
  <div class="app-container">
    <Sidebar v-if="!isAuthPage" />

    <main class="main" :class="{ 'main--auth': isAuthPage }">
      <router-view />
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
  background: var(--app-bg);
}

.main {
  flex: 1;
  height: 100vh;
  overflow-y: auto;
  padding: 18px 22px 22px;
  background: var(--main-bg);
}

.main--auth {
  padding: 0;
  background: none;
}

.main::-webkit-scrollbar {
  width: 6px;
}

.main::-webkit-scrollbar-thumb {
  background: var(--scrollbar-thumb);
  border-radius: 12px;
}
</style>
