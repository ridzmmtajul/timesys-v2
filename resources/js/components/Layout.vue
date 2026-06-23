<!-- resources/js/components/Layout.vue -->
<template>
    <div class="min-h-screen bg-[#dfe9f6] text-slate-900">
        <div class="mx-auto flex min-h-screen w-full max-w-[1600px] flex-col border-x border-slate-300 bg-[#eef4fb] shadow-[0_24px_60px_rgba(15,23,42,0.18)]">
            <!-- Header -->
            <header class="border-b border-[#7da0c9] bg-[linear-gradient(to_bottom,#f8fbff_0%,#cfe0f5_48%,#93b4df_100%)] px-4 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button class="toolbar-button">
                        <span class="toolbar-icon toolbar-icon--device">
                            <span class="toolbar-icon__screen"></span>
                            <span class="toolbar-icon__base"></span>
                        </span>
                        <span>Device</span>
                    </button>
                    <button class="toolbar-button">
                        <span class="toolbar-icon toolbar-icon--delete">X</span>
                        <span>Del Device</span>
                    </button>
                    <div class="toolbar-divider"></div>
                    <button class="toolbar-button">
                        <span class="toolbar-icon toolbar-icon--play">▶</span>
                        <span>Connect</span>
                    </button>
                    <button class="toolbar-button">
                        <span class="toolbar-icon toolbar-icon--stop">■</span>
                        <span>Disconnect</span>
                    </button>
                    <div class="toolbar-divider"></div>
                    <button class="toolbar-button">
                        <span class="toolbar-icon toolbar-icon--power">⏻</span>
                        <span>Exit system</span>
                    </button>
                </div>
            </header>

            <!-- Main Content Area with Sidebar -->
            <div class="flex flex-1 overflow-hidden">
                <!-- Sidebar -->
                <aside class="w-64 border-r border-[#c4d3e4] bg-[#f5f4ea] overflow-y-auto flex-shrink-0">
                    <div class="p-4">
                        <h2 class="text-sm font-semibold text-[#2f62c4] uppercase tracking-wider mb-3">Devices</h2>
                        <div class="space-y-1">
                            <div 
                                v-for="device in devices" 
                                :key="device.name"
                                class="sidebar-item"
                                :class="{ 'sidebar-item--active': device.selected }"
                                @click="setSelected(devices.indexOf(device))"
                            >
                                <span class="status-dot-small" :class="device.status === 'Connected' ? 'status-dot-small--connected' : 'status-dot-small--disconnected'"></span>
                                <span class="truncate text-sm">{{ device.name }}</span>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Content -->
                <main class="flex-1 flex flex-col overflow-hidden">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    devices: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['update:selected'])

const setSelected = (index) => {
    emit('update:selected', index)
}
</script>

<style scoped>
.sidebar-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s;
    color: #1e293b;
}

.sidebar-item:hover {
    background: rgba(42, 107, 187, 0.08);
}

.sidebar-item--active {
    background: #0a73d8;
    color: white;
}

.sidebar-item--active:hover {
    background: #0a73d8;
}

.status-dot-small {
    width: 10px;
    height: 10px;
    border-radius: 9999px;
    flex-shrink: 0;
}

.status-dot-small--connected {
    background: #2faa35;
}

.status-dot-small--disconnected {
    background: #f27a2a;
}

/* Keep existing toolbar styles */
.toolbar-button {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    min-height: 48px;
    padding: 0.5rem 0.9rem;
    border: 1px solid transparent;
    border-radius: 0.55rem;
    background: transparent;
    color: #18324d;
    font-size: 0.95rem;
    cursor: default;
}

.toolbar-button:hover {
    border-color: rgba(74, 117, 172, 0.25);
    background: rgba(255, 255, 255, 0.35);
}

.toolbar-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 9999px;
    font-size: 1rem;
    font-weight: 700;
}

.toolbar-icon--device {
    position: relative;
    border-radius: 0.35rem;
    background: linear-gradient(180deg, #3e6ea9 0%, #1b355c 100%);
    box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.2);
}

.toolbar-icon__screen {
    position: absolute;
    inset: 5px 8px 11px 5px;
    border-radius: 0.15rem;
    background: linear-gradient(180deg, #a8d0ff 0%, #5a86c4 100%);
}

.toolbar-icon__base {
    position: absolute;
    right: 5px;
    bottom: 4px;
    width: 8px;
    height: 8px;
    border-radius: 2px;
    background: #d6a85c;
}

.toolbar-icon--delete {
    color: #1490e3;
    font-size: 1.7rem;
    font-weight: 400;
    text-shadow: 0 1px 0 rgba(255, 255, 255, 0.8);
}

.toolbar-icon--play {
    color: #0c9f3d;
    background: linear-gradient(180deg, #bbf0cd 0%, #59c36a 100%);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
}

.toolbar-icon--stop {
    color: #c22a2a;
    background: linear-gradient(180deg, #ffd3d3 0%, #ef6464 100%);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
}

.toolbar-icon--power {
    color: #fff;
    background: linear-gradient(180deg, #5a9cf0 0%, #2159c9 100%);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
}

.toolbar-divider {
    width: 1px;
    align-self: stretch;
    margin: 0 0.35rem;
    background: rgba(88, 110, 142, 0.35);
}
</style>