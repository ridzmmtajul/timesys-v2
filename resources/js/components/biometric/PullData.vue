<!-- resources/js/components/PullDate.vue -->
<template>
    <Layout :devices="devices" @update:selected="handleDeviceSelect">
        <template #default>
            <!-- Content Section Header -->
            <section class="border-b border-[#7da0c9] bg-[linear-gradient(to_bottom,#edf4fe_0%,#d8e6f8_100%)] px-4 py-3 flex-shrink-0">
                <h1 class="text-[18px] font-semibold text-[#2f62c4]">Machine List</h1>
            </section>

            <!-- Stats Bar -->
            <section class="flex items-center justify-between gap-4 border-b border-[#c4d3e4] bg-[#f5f4ea] px-3 py-2 text-sm flex-shrink-0">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded border border-[#c4d3e4] bg-white px-3 py-1 font-semibold text-slate-700">Selected: {{ selectedDevice.name }}</span>
                    <span class="rounded border border-[#c4d3e4] bg-white px-3 py-1 text-slate-600">Connected: {{ connectedCount }}</span>
                    <span class="rounded border border-[#c4d3e4] bg-white px-3 py-1 text-slate-600">Disconnected: {{ disconnectedCount }}</span>
                </div>
                <span class="text-xs text-slate-500">Desktop device console mockup</span>
            </section>

            <!-- Table Content -->
            <section class="flex-1 overflow-auto bg-white">
                <div class="min-w-[1500px]">
                    <div class="grid machine-grid border-b border-[#c8d4e3] bg-[#f2f1e7] text-[14px] font-normal text-[#222]">
                        <div class="header-cell">Device Name</div>
                        <div class="header-cell">Status</div>
                        <div class="header-cell">MachineNo.</div>
                        <div class="header-cell">Comm type</div>
                        <div class="header-cell">Baud Rate</div>
                        <div class="header-cell">IP Address</div>
                        <div class="header-cell">Port</div>
                        <div class="header-cell">ProductName</div>
                        <div class="header-cell">UserCount</div>
                        <div class="header-cell">Admin Count</div>
                        <div class="header-cell">Password</div>
                        <div class="header-cell">Log Count</div>
                    </div>

                    <button
                        v-for="(device, index) in devices"
                        :key="device.name"
                        type="button"
                        class="grid machine-grid items-stretch border-b border-[#e6ebf1] text-[14px] text-[#2c57ff] transition hover:bg-[#eef5ff]"
                        :class="device.selected ? 'bg-[#0a73d8] text-white hover:bg-[#0a73d8]' : 'bg-white'"
                        @click="setSelected(index)"
                    >
                        <div class="row-cell row-cell--name">
                            <span class="status-dot" :class="device.status === 'Connected' ? 'status-dot--connected' : 'status-dot--disconnected'"></span>
                            <span class="truncate">{{ device.name }}</span>
                        </div>
                        <div class="row-cell" :class="device.selected ? 'text-white' : device.status === 'Connected' ? 'text-[#2457ff]' : 'text-[#e05b2f]'">
                            {{ device.status }}
                        </div>
                        <div class="row-cell">{{ device.machineNo }}</div>
                        <div class="row-cell">{{ device.commType }}</div>
                        <div class="row-cell">{{ device.baudRate }}</div>
                        <div class="row-cell">{{ device.ipAddress }}</div>
                        <div class="row-cell">{{ device.port }}</div>
                        <div class="row-cell">{{ device.productName }}</div>
                        <div class="row-cell">{{ device.userCount }}</div>
                        <div class="row-cell">{{ device.adminCount }}</div>
                        <div class="row-cell">{{ device.fpCount }}</div>
                        <div class="row-cell">{{ device.fcCount }}</div>
                        <div class="row-cell">{{ device.password }}</div>
                        <div class="row-cell">{{ device.logCount }}</div>
                    </button>
                </div>
            </section>
        </template>
    </Layout>
</template>

<script setup>
import { computed, ref } from 'vue'
import Layout from './../Layout.vue'

const devices = ref([
    {
        name: 'BD1_secretary',
        status: 'Connected',
        machineNo: '163',
        commType: 'Ethernet',
        baudRate: '',
        ipAddress: '192.168.1.163',
        port: '4370',
        productName: 'K30',
        userCount: '439',
        adminCount: '9',
        fpCount: '881',
        fcCount: '0',
        password: '0',
        logCount: '59',
    },
    {
        name: 'BD2_citytreasurer',
        status: 'Connected',
        machineNo: '164',
        commType: 'Ethernet',
        baudRate: '',
        ipAddress: '192.168.1.164',
        port: '4370',
        productName: 'K30',
        userCount: '347',
        adminCount: '9',
        fpCount: '692',
        fcCount: '0',
        password: '1',
        logCount: '26',
    },
    {
        name: 'BD3_citybudget',
        status: 'Connected',
        machineNo: '165',
        commType: 'Ethernet',
        baudRate: '',
        ipAddress: '192.168.1.165',
        port: '4370',
        productName: 'K30',
        userCount: '333',
        adminCount: '9',
        fpCount: '669',
        fcCount: '0',
        password: '1',
        logCount: '13',
    },
    {
        name: 'BD4_BOSS_3rd_floor',
        status: 'Connected',
        machineNo: '204',
        commType: 'Ethernet',
        baudRate: '',
        ipAddress: '192.168.39.2',
        port: '4370',
        productName: 'MB20/ID',
        userCount: '181',
        adminCount: '5',
        fpCount: '325',
        fcCount: '1',
        password: '1',
        logCount: '0',
    },
    {
        name: 'BD7_cityaccountant',
        status: 'Disconnected',
        machineNo: '162',
        commType: 'Ethernet',
        baudRate: '',
        ipAddress: '192.168.1.162',
        port: '4370',
        productName: '',
        userCount: '126',
        adminCount: '8',
        fpCount: '251',
        fcCount: '0',
        password: '0',
        logCount: '0',
    },
    {
        name: 'BD16_BOSS_2nd_floor',
        status: 'Connected',
        machineNo: '116',
        commType: 'Ethernet',
        baudRate: '',
        ipAddress: '192.168.39.252',
        port: '4370',
        productName: 'K40',
        userCount: '18',
        adminCount: '1',
        fpCount: '21',
        fcCount: '0',
        password: '0',
        logCount: '0',
        selected: true,
    },
])

const selectedDevice = computed(() => devices.value.find((device) => device.selected) ?? devices.value[devices.value.length - 1])

const setSelected = (index) => {
    devices.value = devices.value.map((device, deviceIndex) => ({
        ...device,
        selected: deviceIndex === index,
    }))
}

const handleDeviceSelect = (index) => {
    setSelected(index)
}

const connectedCount = computed(() => devices.value.filter((device) => device.status === 'Connected').length)
const disconnectedCount = computed(() => devices.value.length - connectedCount.value)
</script>

<style scoped>
.machine-grid {
    grid-template-columns: 2.4fr 1.1fr 0.9fr 1fr 0.95fr 1.5fr 0.75fr 1fr 0.95fr 1fr 0.85fr 0.85fr 0.75fr 0.9fr;
}

.header-cell,
.row-cell {
    padding: 0.45rem 0.5rem;
    border-right: 1px solid rgba(200, 212, 227, 0.75);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-cell:last-child,
.row-cell:last-child {
    border-right: 0;
}

.header-cell {
    color: #313131;
}

.row-cell {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
}

.row-cell--name {
    justify-content: flex-start;
    gap: 0.5rem;
}

.status-dot {
    width: 14px;
    height: 14px;
    border-radius: 9999px;
    flex: none;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
}

.status-dot--connected {
    background: linear-gradient(180deg, #8ce174 0%, #2faa35 100%);
}

.status-dot--disconnected {
    background: linear-gradient(180deg, #ffd7b1 0%, #f27a2a 100%);
}

button.machine-grid {
    width: 100%;
    text-align: left;
}
</style>