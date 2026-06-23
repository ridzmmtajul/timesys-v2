<template>
  <div class="modal">
    <div class="card">
      <h2>Add Biometric Device</h2>

      <input
        v-model="form.name"
        placeholder="Device Name"
      />

      <input
        v-model="form.ip"
        placeholder="IP Address"
      />

      <input
        v-model="form.port"
        placeholder="Port"
      />

      <button @click="save">
        Save
      </button>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { reactive } from 'vue'

const form = reactive({
  name: '',
  ip: '',
  port: 4370
})

const save = async () => {
  await axios.post('/api/devices', form)
}
</script>