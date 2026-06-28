<script setup>
import Sidebar from './Sidebar.vue';
</script>

<template>
  <div class="app-container">
    <Sidebar />

    <main class="main">
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
  background:
    radial-gradient(circle at top left, rgba(31, 191, 184, 0.16), transparent 28%),
    radial-gradient(circle at top right, rgba(63, 109, 199, 0.2), transparent 32%),
    linear-gradient(135deg, #071029 0%, #091737 45%, #0c1730 100%);
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
