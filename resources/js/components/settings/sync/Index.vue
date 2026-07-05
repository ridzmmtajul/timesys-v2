<script setup>
import { onMounted } from 'vue';
import useSync, { SYNC_MODULES } from '../../../composables/sync.js';

const { pendingCounts, logs, pagination, is_loading, syncingAll, is_central, getPendingCounts, getLogs, pushAll } = useSync();

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString();
}

function formatCount(value) {
    return Number(value ?? 0).toLocaleString();
}

function statusLabel(status) {
    return { success: 'Success', partial: 'Partial', failed: 'Failed' }[status] || status;
}

function goToPage(page) {
    getLogs(page);
}

onMounted(() => {
    getPendingCounts();
    getLogs();
});
</script>

<template>
    <section class="sync-dashboard">
        <header class="hero-bar">
            <div class="hero-title">
                <div class="hero-kicker">
                    <v-icon icon="mdi-cloud-sync-outline" size="14" />
                    <span>Data Sync</span>
                </div>
                <h2>Data Sync Center</h2>
            </div>

            <div class="hero-actions">
                <div class="kpi-strip">
                    <div v-for="mod in SYNC_MODULES" :key="mod.key" class="kpi-card">
                        <span class="kpi-label">{{ mod.label }}</span>
                        <strong>{{ formatCount(pendingCounts[mod.key]) }}</strong>
                    </div>
                </div>

                <button v-if="!is_central" class="btn-primary" :disabled="syncingAll" @click="pushAll">
                    <v-icon :icon="syncingAll ? 'mdi-loading mdi-spin' : 'mdi-cloud-upload-outline'" size="15" />
                    {{ syncingAll ? 'Syncing...' : 'Sync All' }}
                </button>
            </div>
        </header>

        <div class="surface">
            <div v-if="is_loading" class="loading-state">
                <v-icon icon="mdi-loading mdi-spin" size="30" />
                <p>Loading sync logs...</p>
            </div>

            <div v-else class="table-container">
                <table class="sync-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Direction</th>
                            <th>Status</th>
                            <th>Synced</th>
                            <th>Existing</th>
                            <th>Skipped</th>
                            <th>Message</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs" :key="log.id">
                            <td><span class="module-name">{{ log.module }}</span></td>
                            <td class="muted">{{ log.direction }}</td>
                            <td>
                                <span class="status-badge" :class="`status-${log.status}`">
                                    <i class="fas fa-circle"></i>
                                    {{ statusLabel(log.status) }}
                                </span>
                            </td>
                            <td>{{ log.synced_count }}</td>
                            <td>{{ log.existing_count }}</td>
                            <td>{{ log.skipped_count }}</td>
                            <td class="muted message-cell">{{ log.message || '—' }}</td>
                            <td class="muted">{{ formatDate(log.created_at) }}</td>
                        </tr>
                        <tr v-if="!logs.length">
                            <td colspan="8" class="empty-table">
                                <v-icon icon="mdi-cloud-off-outline" size="30" />
                                <p>No sync activity yet</p>
                                <span v-if="!is_central">Click "Sync All" to send local records to the central server</span>
                                <span v-else>Waiting for local instances to push records</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="pagination.last_page > 1" class="pagination">
                <button
                    class="page-btn"
                    :disabled="pagination.current_page === 1"
                    @click="goToPage(pagination.current_page - 1)"
                >
                    <v-icon icon="mdi-chevron-left" size="16" />
                </button>
                <span class="page-info">
                    Page {{ pagination.current_page }} of {{ pagination.last_page }}
                    <small>({{ formatCount(pagination.total) }} total)</small>
                </span>
                <button
                    class="page-btn"
                    :disabled="pagination.current_page === pagination.last_page"
                    @click="goToPage(pagination.current_page + 1)"
                >
                    <v-icon icon="mdi-chevron-right" size="16" />
                </button>
            </div>

            <footer class="footer-info">
                <span><v-icon icon="mdi-arrow-right" size="12" /> Sync activity</span>
                <span v-if="pagination.total">
                    <v-icon icon="mdi-check-circle" size="12" /> {{ formatCount(pagination.total) }} log(s)
                </span>
            </footer>
        </div>
    </section>
</template>

<style scoped>
.sync-dashboard {
    min-height: calc(100vh - 40px);
    color: #eef6ff;
}

/* ── Hero bar ── */
.hero-bar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 12px;
}

.hero-title {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #c8d6f8;
    font-size: 14px;
}

.hero-kicker :deep(.v-icon) {
    color: #35d0d2;
}

.hero-title h2 {
    font-size: 20px;
    line-height: 1.05;
    letter-spacing: -0.5px;
    color: #f3f7ff;
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

/* ── KPI strip ── */
.kpi-strip {
    display: flex;
    gap: 10px;
}

.kpi-card {
    min-width: 128px;
    padding: 9px 12px;
    border-radius: 12px;
    background: rgba(24, 35, 70, 0.82);
    border: 1px solid rgba(126, 153, 210, 0.16);
    box-shadow: 0 8px 18px rgba(1, 8, 24, 0.16);
}

.kpi-label {
    display: block;
    font-size: 11px;
    color: #99b2df;
    margin-bottom: 4px;
    white-space: nowrap;
}

.kpi-card strong {
    font-size: 15px;
    color: #f5fbff;
}

/* ── Primary button ── */
.btn-primary {
    border: 0;
    padding: 11px 16px;
    border-radius: 999px;
    background: linear-gradient(90deg, #1fbfb8 0%, #52d3d0 100%);
    color: #06162f;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    box-shadow: 0 16px 30px rgba(31, 191, 184, 0.22);
    white-space: nowrap;
}

.btn-primary:disabled {
    opacity: 0.7;
    cursor: default;
}

/* ── Surface ── */
.surface {
    border-radius: 18px;
    background: rgba(10, 18, 40, 0.58);
    border: 1px solid rgba(121, 146, 207, 0.16);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02), 0 24px 60px rgba(2, 7, 20, 0.28);
    padding: 14px;
}

/* ── Loading state ── */
.loading-state {
    border-radius: 24px;
    background: rgba(17, 27, 56, 0.6);
    border: 1px solid rgba(121, 146, 207, 0.14);
    min-height: 220px;
    display: grid;
    place-items: center;
    text-align: center;
    color: #9bb0da;
    gap: 10px;
}

.loading-state :deep(.v-icon) {
    color: #1fbfb8;
}

.loading-state p {
    font-size: 15px;
    font-weight: 600;
}

/* ── Table ── */
.table-container {
    overflow: auto;
    border-radius: 18px;
    border: 1px solid rgba(121, 146, 207, 0.16);
}

.sync-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    background: rgba(11, 18, 39, 0.96);
}

.sync-table thead {
    background: rgba(16, 24, 50, 0.98);
}

.sync-table th,
.sync-table td {
    padding: 13px 15px;
    border-bottom: 1px solid rgba(121, 146, 207, 0.12);
}

.sync-table th {
    color: #9bb0da;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    text-align: left;
    white-space: nowrap;
}

.sync-table td {
    color: #edf5ff;
}

.sync-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.03);
}

.module-name {
    font-weight: 600;
    text-transform: capitalize;
}

.muted {
    color: #9bb0da;
    text-transform: capitalize;
}

.message-cell {
    max-width: 320px;
    white-space: normal;
    text-transform: none;
}

/* ── Status badges ── */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
    text-transform: capitalize;
}

.status-badge i {
    font-size: 7px;
}

.status-success {
    background: rgba(31, 191, 184, 0.18);
    color: #75e7d7;
}

.status-partial {
    background: rgba(240, 180, 41, 0.18);
    color: #f0c040;
}

.status-failed {
    background: rgba(220, 60, 60, 0.16);
    color: #f08080;
}

/* ── Empty state ── */
.empty-table {
    text-align: center;
    padding: 60px 20px !important;
    color: #9bb0da;
}

.empty-table :deep(.v-icon) {
    color: #1fbfb8;
    display: block;
    margin: 0 auto 12px;
}

.empty-table p {
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 6px;
}

.empty-table span {
    font-size: 13px;
}

/* ── Pagination ── */
.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 14px;
}

.page-btn {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    border: 1px solid rgba(121, 146, 207, 0.2);
    background: rgba(17, 27, 56, 0.8);
    color: #9bb0da;
    cursor: pointer;
    display: grid;
    place-items: center;
}

.page-btn:disabled {
    opacity: 0.35;
    cursor: default;
}

.page-btn:not(:disabled):hover {
    background: rgba(31, 191, 184, 0.18);
    color: #75e7d7;
    border-color: rgba(31, 191, 184, 0.3);
}

.page-info {
    font-size: 13px;
    color: #9bb0da;
}

.page-info small {
    opacity: 0.7;
}

/* ── Footer ── */
.footer-info {
    margin-top: 10px;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    color: #9bb0da;
    font-size: 13px;
}

.footer-info :deep(.v-icon) {
    color: #1fbfb8;
    margin-right: 6px;
}

@media (max-width: 1024px) {
    .hero-bar {
        flex-direction: column;
    }

    .hero-actions {
        justify-content: flex-start;
    }
}
</style>
