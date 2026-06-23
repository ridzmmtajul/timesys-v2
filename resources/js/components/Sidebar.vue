<!-- Sidebar.vue -->
<template>
  <aside class="sidebar">
    <div class="logo">
      BioTrack
    </div>

    <nav>
      <RouterLink to="/">
        Dashboard
      </RouterLink>

      <RouterLink to="/devices">
        Biometric Devices
      </RouterLink>

      <RouterLink to="/employees">
        Employees
      </RouterLink>

      <RouterLink to="/logs">
        Attendance Logs
      </RouterLink>
    </nav>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 260px;
  height: 100vh;
  background: #031163;
  color: white;
  position: fixed;
}

.logo {
  padding: 20px;
  font-size: 22px;
  font-weight: bold;
  background: #05716c;
}

nav {
  display: flex;
  flex-direction: column;
}

nav a {
  color: white;
  padding: 16px 20px;
  text-decoration: none;
}

nav a.router-link-active {
  background: #1fbfb8;
}
</style>