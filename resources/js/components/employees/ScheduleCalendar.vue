<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import WorkScheduleForm from '../work-schedules/Form/Create.vue';

const props = defineProps({
  show:     { type: Boolean, default: false },
  employee: { type: Object,  default: null  },
});

const emit = defineEmits(['close']);

const workSchedules   = ref([]);
const loading         = ref(false);
const currentDate     = ref(new Date());
const showAddForm     = ref(false);
const editingSchedule = ref({});

const DAY_NAMES    = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const MONTH_NAMES  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const WEEK_HEADERS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

const COLORS = [
  { bg: 'rgba(31,191,184,0.18)',  text: '#52d3d0', border: 'rgba(31,191,184,0.4)'  },
  { bg: 'rgba(63,109,199,0.2)',   text: '#7db3f8', border: 'rgba(63,109,199,0.45)' },
  { bg: 'rgba(160,90,220,0.18)',  text: '#c589f5', border: 'rgba(160,90,220,0.4)'  },
  { bg: 'rgba(255,170,60,0.18)',  text: '#ffc46b', border: 'rgba(255,170,60,0.4)'  },
  { bg: 'rgba(80,200,100,0.18)', text: '#6dd98a', border: 'rgba(80,200,100,0.4)'  },
];

function fullName(emp) {
  if (!emp) return '';
  let n = `${emp.last_name}, ${emp.first_name}`;
  if (emp.middle_name) n += ` ${emp.middle_name.charAt(0)}.`;
  if (emp.name_ext)    n += ` ${emp.name_ext}`;
  return n;
}

function toDateStr(d) {
  return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
}

function fmtTime(t) {
  return t ? t.substring(0,5) : null;
}

async function fetchSchedules() {
  if (!props.employee) return;
  loading.value = true;
  try {
    const { data } = await axios.get('/api/work-schedules', {
      params: { employee_id: props.employee.id, per_page: 999 },
    });
    workSchedules.value = data.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

watch(() => props.show, (val) => {
  if (val && props.employee) {
    currentDate.value = new Date();
    fetchSchedules();
  } else {
    workSchedules.value = [];
  }
});

function prevMonth() {
  const d = new Date(currentDate.value);
  d.setMonth(d.getMonth() - 1);
  currentDate.value = d;
}

function nextMonth() {
  const d = new Date(currentDate.value);
  d.setMonth(d.getMonth() + 1);
  currentDate.value = d;
}

function goToday() {
  currentDate.value = new Date();
}

const monthLabel = computed(() =>
  `${MONTH_NAMES[currentDate.value.getMonth()]} ${currentDate.value.getFullYear()}`
);

function getSchedulesForDate(dateStr, dow) {
  return workSchedules.value.filter(ws => {
    const inRange = (!ws.from_date || dateStr >= ws.from_date) &&
                    (!ws.to_date   || dateStr <= ws.to_date);
    if (!inRange) return false;
    if (!ws.days || ws.days.length === 0) return true;
    return ws.days.includes(DAY_NAMES[dow]);
  });
}

const calendarDays = computed(() => {
  const year  = currentDate.value.getFullYear();
  const month = currentDate.value.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay  = new Date(year, month + 1, 0);
  const todayStr = toDateStr(new Date());

  const start = new Date(firstDay);
  start.setDate(start.getDate() - start.getDay());

  const days = [];
  const cur  = new Date(start);
  while ((cur <= lastDay || days.length % 7 !== 0) && days.length < 42) {
    const ds = toDateStr(cur);
    days.push({
      day: cur.getDate(),
      dateStr: ds,
      isCurrentMonth: cur.getMonth() === month,
      isToday: ds === todayStr,
      schedules: getSchedulesForDate(ds, cur.getDay()),
    });
    cur.setDate(cur.getDate() + 1);
  }
  return days;
});

const colorMap = computed(() => {
  const m = {};
  workSchedules.value.forEach((ws, i) => { m[ws.id] = COLORS[i % COLORS.length]; });
  return m;
});

function openAddForm() {
  editingSchedule.value = { employee_id: props.employee?.id };
  showAddForm.value = true;
}

function onFormInput(val) {
  showAddForm.value = val;
  if (!val) editingSchedule.value = {};
}

async function onReload() {
  await fetchSchedules();
  editingSchedule.value = {};
}
</script>

<template>
  <Teleport to="body">
    <Transition name="sc-fade">
      <div v-if="show" class="sc-overlay" @mousedown.self="$emit('close')">
        <div class="sc-panel">

          <!-- Header -->
          <div class="sc-header">
            <div class="sc-header__left">
              <div class="sc-header__icon"><i class="fas fa-calendar-alt"></i></div>
              <div>
                <p class="sc-header__eyebrow">Work Schedule Calendar</p>
                <h5 class="sc-header__name">{{ fullName(employee) }}</h5>
                <span class="sc-header__empno">{{ employee?.employee_no }}</span>
              </div>
            </div>
            <div class="sc-header__actions">
              <button class="sc-add-btn" @click="openAddForm">
                <i class="fas fa-plus"></i> Add Schedule
              </button>
              <button class="sc-close-btn" @click="$emit('close')">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>

          <!-- Month nav -->
          <div class="sc-nav">
            <button class="sc-nav__arrow" @click="prevMonth">
              <i class="fas fa-chevron-left"></i>
            </button>
            <button class="sc-nav__today" @click="goToday">Today</button>
            <span class="sc-nav__month">{{ monthLabel }}</span>
            <button class="sc-nav__arrow" @click="nextMonth">
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="sc-loading">
            <i class="fas fa-circle-notch fa-spin"></i>
            <span>Loading schedules…</span>
          </div>

          <template v-else>
            <!-- Calendar -->
            <div class="sc-calendar">
              <div class="sc-week-row sc-week-row--header">
                <div v-for="h in WEEK_HEADERS" :key="h" class="sc-cell sc-cell--head">{{ h }}</div>
              </div>
              <div class="sc-days">
                <div
                  v-for="d in calendarDays"
                  :key="d.dateStr"
                  class="sc-cell sc-cell--day"
                  :class="{
                    'is-other':    !d.isCurrentMonth,
                    'is-today':     d.isToday,
                    'has-events':   d.schedules.length > 0,
                  }"
                >
                  <span class="sc-day-num">{{ d.day }}</span>
                  <div class="sc-events">
                    <div
                      v-for="ws in d.schedules.slice(0, 2)"
                      :key="ws.id"
                      class="sc-event"
                      :style="{
                        background:   colorMap[ws.id]?.bg,
                        color:        colorMap[ws.id]?.text,
                        borderColor:  colorMap[ws.id]?.border,
                      }"
                      :title="`${ws.schedule_for}${fmtTime(ws.timein_AM) ? '\n' + fmtTime(ws.timein_AM) + ' – ' + (fmtTime(ws.timeout_PM) || fmtTime(ws.timeout_AM) || '') : ''}`"
                    >
                      {{ ws.schedule_for }}
                    </div>
                    <div v-if="d.schedules.length > 2" class="sc-event sc-event--more">
                      +{{ d.schedules.length - 2 }} more
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Legend -->
            <div v-if="workSchedules.length > 0" class="sc-legend">
              <div
                v-for="ws in workSchedules"
                :key="ws.id"
                class="sc-legend-item"
                :style="{ borderColor: colorMap[ws.id]?.border }"
              >
                <span class="sc-legend-dot" :style="{ background: colorMap[ws.id]?.text }"></span>
                <span class="sc-legend-label">{{ ws.schedule_for }}</span>
                <span class="sc-legend-range">
                  {{ ws.from_date || 'open' }} → {{ ws.to_date || 'open' }}
                </span>
                <span v-if="ws.days?.length" class="sc-legend-days">
                  {{ ws.days.map(d => d.substring(0,3)).join(', ') }}
                </span>
              </div>
            </div>

            <!-- Empty -->
            <div v-if="workSchedules.length === 0" class="sc-empty">
              <i class="fas fa-calendar-times"></i>
              <p>No work schedules assigned</p>
              <span>Click "Add Schedule" to assign one</span>
            </div>
          </template>

        </div>
      </div>
    </Transition>
  </Teleport>

  <WorkScheduleForm
    :value="showAddForm"
    :work-schedule="editingSchedule"
    @input="onFormInput"
    @reloadWorkSchedules="onReload"
  />
</template>

<style scoped>
/* ── Overlay ── */
.sc-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(2, 8, 22, 0.72);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

/* ── Panel ── */
.sc-panel {
  width: 100%;
  max-width: 960px;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 20px;
  background: rgba(10, 18, 42, 0.98);
  border: 1px solid rgba(121, 146, 207, 0.18);
  box-shadow: 0 32px 80px rgba(1, 6, 20, 0.55);
  display: flex;
  flex-direction: column;
  gap: 0;
}

.sc-panel::-webkit-scrollbar { width: 5px; }
.sc-panel::-webkit-scrollbar-track { background: transparent; }
.sc-panel::-webkit-scrollbar-thumb { background: rgba(121,146,207,0.22); border-radius: 3px; }

/* ── Header ── */
.sc-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  padding: 20px 22px 16px;
  border-bottom: 1px solid rgba(121,146,207,0.12);
}

.sc-header__left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.sc-header__icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: rgba(31,191,184,0.14);
  border: 1px solid rgba(31,191,184,0.3);
  display: grid;
  place-items: center;
  color: #1fbfb8;
  font-size: 17px;
  flex-shrink: 0;
}

.sc-header__eyebrow {
  font-size: 11px;
  color: #6a84bf;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 3px;
}

.sc-header__name {
  font-size: 17px;
  font-weight: 700;
  color: #f3f7ff;
  line-height: 1.1;
  margin-bottom: 2px;
}

.sc-header__empno {
  font-size: 12px;
  color: #7dd4f8;
  font-family: monospace;
}

.sc-header__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.sc-add-btn {
  border: 0;
  padding: 9px 16px;
  border-radius: 999px;
  background: linear-gradient(90deg, #1fbfb8 0%, #52d3d0 100%);
  color: #06162f;
  font-weight: 700;
  font-size: 13px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  cursor: pointer;
  box-shadow: 0 8px 22px rgba(31,191,184,0.25);
}

.sc-add-btn:hover {
  filter: brightness(1.08);
}

.sc-close-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid rgba(121,146,207,0.2);
  background: rgba(17,27,56,0.8);
  color: #9bb0da;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 13px;
  transition: background 0.15s, color 0.15s;
}

.sc-close-btn:hover {
  background: rgba(220,60,60,0.18);
  color: #f08080;
  border-color: rgba(220,60,60,0.3);
}

/* ── Month nav ── */
.sc-nav {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 22px;
  border-bottom: 1px solid rgba(121,146,207,0.1);
}

.sc-nav__arrow {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid rgba(121,146,207,0.18);
  background: rgba(17,27,56,0.7);
  color: #9bb0da;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 12px;
  transition: background 0.15s;
}

.sc-nav__arrow:hover {
  background: rgba(31,191,184,0.18);
  color: #52d3d0;
  border-color: rgba(31,191,184,0.3);
}

.sc-nav__today {
  border: 1px solid rgba(121,146,207,0.2);
  background: rgba(17,27,56,0.7);
  color: #9bb0da;
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
}

.sc-nav__today:hover {
  background: rgba(31,191,184,0.14);
  color: #52d3d0;
  border-color: rgba(31,191,184,0.3);
}

.sc-nav__month {
  font-size: 15px;
  font-weight: 700;
  color: #edf5ff;
  margin-left: 4px;
}

/* ── Loading ── */
.sc-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 60px 20px;
  color: #6a84bf;
  font-size: 14px;
}

.sc-loading i {
  color: #1fbfb8;
  font-size: 22px;
}

/* ── Calendar ── */
.sc-calendar {
  padding: 16px 22px 10px;
}

.sc-week-row--header {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  margin-bottom: 4px;
}

.sc-cell--head {
  text-align: center;
  font-size: 11px;
  font-weight: 700;
  color: #6a84bf;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 6px 0;
}

.sc-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.sc-cell--day {
  min-height: 82px;
  border-radius: 10px;
  border: 1px solid rgba(121,146,207,0.1);
  background: rgba(14,22,48,0.6);
  padding: 7px 7px 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  transition: border-color 0.15s, background 0.15s;
}

.sc-cell--day:hover {
  border-color: rgba(121,146,207,0.22);
  background: rgba(20,30,62,0.7);
}

.sc-cell--day.is-other {
  opacity: 0.35;
}

.sc-cell--day.is-today {
  border-color: rgba(31,191,184,0.45);
  background: rgba(31,191,184,0.06);
}

.sc-cell--day.is-today .sc-day-num {
  color: #1fbfb8;
  font-weight: 800;
}

.sc-day-num {
  font-size: 12px;
  font-weight: 600;
  color: #9bb0da;
  line-height: 1;
  margin-bottom: 2px;
}

/* ── Events ── */
.sc-events {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.sc-event {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 5px;
  border: 1px solid transparent;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  cursor: default;
  line-height: 1.4;
}

.sc-event--more {
  font-size: 10px;
  color: #6a84bf;
  padding: 1px 4px;
  border: none;
  background: none;
}

/* ── Legend ── */
.sc-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 10px 22px 16px;
  border-top: 1px solid rgba(121,146,207,0.1);
}

.sc-legend-item {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 5px 10px 5px 8px;
  border-radius: 8px;
  border: 1px solid rgba(121,146,207,0.18);
  background: rgba(14,22,48,0.5);
  font-size: 11px;
}

.sc-legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.sc-legend-label {
  color: #edf5ff;
  font-weight: 600;
}

.sc-legend-range {
  color: #6a84bf;
}

.sc-legend-days {
  color: #52d3d0;
  font-size: 10px;
  padding: 1px 5px;
  border-radius: 4px;
  background: rgba(31,191,184,0.1);
}

/* ── Empty ── */
.sc-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 50px 20px;
  color: #6a84bf;
  text-align: center;
}

.sc-empty i {
  font-size: 38px;
  color: rgba(31,191,184,0.4);
  margin-bottom: 6px;
}

.sc-empty p {
  font-size: 15px;
  font-weight: 600;
  color: #9bb0da;
}

.sc-empty span {
  font-size: 13px;
}

/* ── Transition ── */
.sc-fade-enter-active, .sc-fade-leave-active {
  transition: opacity 0.2s ease;
}
.sc-fade-enter-active .sc-panel, .sc-fade-leave-active .sc-panel {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.sc-fade-enter-from, .sc-fade-leave-to {
  opacity: 0;
}
.sc-fade-enter-from .sc-panel {
  transform: translateY(16px);
}
</style>
