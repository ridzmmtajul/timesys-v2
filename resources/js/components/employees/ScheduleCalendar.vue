<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import WorkScheduleForm from '../work-schedules/Form/Create.vue';
import useTheme from '../../composables/useTheme.js';
import ThemeSwal, { swalClass } from '../../utils/swal.js';

const props = defineProps({
  show:     { type: Boolean, default: false },
  employee: { type: Object,  default: null  },
});

const emit = defineEmits(['close']);

const workSchedules   = ref([]);
const loading         = ref(false);
const currentDate     = ref(new Date());
const showAddForm     = ref(false);
const editingSchedule   = ref({});
const selectedSchedule  = ref(null);
const activeTab         = ref('calendar');

const DAY_NAMES    = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const MONTH_NAMES  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const WEEK_HEADERS = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

const { isDark } = useTheme();

const DARK_COLORS = [
  { bg: 'rgba(31,191,184,0.18)',  text: '#52d3d0', border: 'rgba(31,191,184,0.4)'  },
  { bg: 'rgba(63,109,199,0.2)',   text: '#7db3f8', border: 'rgba(63,109,199,0.45)' },
  { bg: 'rgba(160,90,220,0.18)',  text: '#c589f5', border: 'rgba(160,90,220,0.4)'  },
  { bg: 'rgba(255,170,60,0.18)',  text: '#ffc46b', border: 'rgba(255,170,60,0.4)'  },
  { bg: 'rgba(80,200,100,0.18)',  text: '#6dd98a', border: 'rgba(80,200,100,0.4)'  },
];

const LIGHT_COLORS = [
  { bg: 'rgba(31,191,184,0.22)',  text: '#0a6b66', border: 'rgba(31,191,184,0.5)'  },
  { bg: 'rgba(63,109,199,0.2)',   text: '#1a4fa8', border: 'rgba(63,109,199,0.48)' },
  { bg: 'rgba(140,60,210,0.18)',  text: '#5a0faa', border: 'rgba(140,60,210,0.42)' },
  { bg: 'rgba(200,120,20,0.18)',  text: '#8a4a00', border: 'rgba(200,120,20,0.42)' },
  { bg: 'rgba(40,160,70,0.18)',   text: '#145a28', border: 'rgba(40,160,70,0.42)'  },
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

function fmtScheduleTimes(ws) {
  const parts = [];
  if (ws.timein_AM && ws.timeout_AM) parts.push(`${fmtTime(ws.timein_AM)}–${fmtTime(ws.timeout_AM)}`);
  if (ws.timein_PM && ws.timeout_PM) parts.push(`${fmtTime(ws.timein_PM)}–${fmtTime(ws.timeout_PM)}`);
  return parts.join(' / ');
}

function scheduleName(ws) {
  if (!ws.is_others && ws.schedule?.name) return ws.schedule.name;
  if (ws.schedule_type?.name) return ws.schedule_type.name;
  return ws.schedule_for.replace(/_/g, ' ');
}

const SCHEDULE_FOR_LABEL = {
  day_in_week:      'Day in a Week',
  'inclusive date': 'Inclusive Date',
  everyday:         'Everyday',
};

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
    activeTab.value = 'calendar';
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
  const all = workSchedules.value;
  const inRange = ws => (!ws.from_date || dateStr >= ws.from_date) &&
                        (!ws.to_date   || dateStr <= ws.to_date);

  // Priority 1: day_in_week
  const dayInWeek = all.filter(ws =>
    ws.schedule_for === 'day_in_week' && inRange(ws) &&
    ws.days && ws.days.includes(DAY_NAMES[dow])
  );
  if (dayInWeek.length) return [dayInWeek[0]];

  // Priority 2: inclusive date
  const inclusiveDate = all.filter(ws =>
    ws.schedule_for === 'inclusive date' && inRange(ws)
  );
  if (inclusiveDate.length) return [inclusiveDate[0]];

  // Priority 3: everyday
  const everyday = all.filter(ws =>
    ws.schedule_for === 'everyday' && inRange(ws)
  );
  return everyday.length ? [everyday[0]] : [];
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
  const palette = isDark.value ? DARK_COLORS : LIGHT_COLORS;
  workSchedules.value.forEach((ws, i) => { m[ws.id] = palette[i % palette.length]; });
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

function deleteSchedule(ws) {
  ThemeSwal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    customClass: {
      ...swalClass,
      confirmButton: 'ts-swal-btn ts-swal-btn--danger',
    },
  }).then((result) => {
    if (result.value) {
      axios
        .delete(`/api/work-schedules/${ws.id}`)
        .then(async (response) => {
          selectedSchedule.value = null;
          await fetchSchedules();
          ThemeSwal.fire({ title: 'Deleted', text: response.data.message, icon: 'success' });
        })
        .catch(() => {
          ThemeSwal.fire({ icon: 'error', title: 'Oops...', text: 'Something went wrong!' });
        });
    }
  });
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

          <!-- Tabs -->
          <div class="sc-tabs">
            <button
              class="sc-tab"
              :class="{ 'is-active': activeTab === 'calendar' }"
              @click="activeTab = 'calendar'"
            >
              <i class="fas fa-calendar-alt"></i> Calendar
            </button>
            <button
              class="sc-tab"
              :class="{ 'is-active': activeTab === 'list' }"
              @click="activeTab = 'list'"
            >
              <i class="fas fa-list"></i> List
            </button>
          </div>

          <!-- Month nav -->
          <div v-if="activeTab === 'calendar'" class="sc-nav">
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
            <!-- Calendar tab -->
            <div v-if="activeTab === 'calendar'" class="sc-calendar">
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
                      v-for="ws in d.schedules"
                      :key="ws.id"
                      class="sc-event"
                      :style="{
                        background:   colorMap[ws.id]?.bg,
                        color:        colorMap[ws.id]?.text,
                        borderColor:  colorMap[ws.id]?.border,
                        cursor:       'pointer',
                      }"
                      @click.stop="selectedSchedule = ws"
                    >
                      <span class="sc-event__name">{{ scheduleName(ws) }}</span>
                      <span v-if="fmtScheduleTimes(ws)" class="sc-event__time">{{ fmtScheduleTimes(ws) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- List tab -->
            <div v-else class="sc-table-wrap">
              <div class="sc-trow sc-trow--head">
                <span class="sc-tcell sc-tcell--dot"></span>
                <span class="sc-tcell">Schedule</span>
                <span class="sc-tcell">Type</span>
                <span class="sc-tcell">Date Range</span>
                <span class="sc-tcell">Days</span>
                <span class="sc-tcell">Time</span>
              </div>
              <div class="sc-tbody">
                <div
                  v-for="ws in workSchedules"
                  :key="ws.id"
                  class="sc-trow"
                  @click="selectedSchedule = ws"
                >
                  <span class="sc-tcell sc-tcell--dot">
                    <span class="sc-table-dot" :style="{ background: colorMap[ws.id]?.text }"></span>
                  </span>
                  <span class="sc-tcell sc-tcell--name">{{ scheduleName(ws) }}</span>
                  <span class="sc-tcell">{{ SCHEDULE_FOR_LABEL[ws.schedule_for] ?? ws.schedule_for }}</span>
                  <span class="sc-tcell">{{ ws.from_date || 'open' }} → {{ ws.to_date || 'open' }}</span>
                  <span class="sc-tcell">{{ ws.days?.length ? ws.days.map(d => d.substring(0,3)).join(', ') : '—' }}</span>
                  <span class="sc-tcell">{{ fmtScheduleTimes(ws) || '—' }}</span>
                </div>
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

  <!-- Schedule Detail Modal -->
  <Teleport to="body">
    <Transition name="sd-fade">
      <div v-if="selectedSchedule" class="sd-overlay" @mousedown.self="selectedSchedule = null">
        <div class="sd-modal">
          <div class="sd-modal__header">
            <div class="sd-modal__icon" :style="{ background: colorMap[selectedSchedule.id]?.bg, borderColor: colorMap[selectedSchedule.id]?.border }">
              <i class="fas fa-clock" :style="{ color: colorMap[selectedSchedule.id]?.text }"></i>
            </div>
            <div>
              <p class="sd-modal__type">{{ SCHEDULE_FOR_LABEL[selectedSchedule.schedule_for] ?? selectedSchedule.schedule_for }}</p>
              <h5 class="sd-modal__name">{{ scheduleName(selectedSchedule) }}</h5>
            </div>
            <button class="sd-modal__delete" @click="deleteSchedule(selectedSchedule)" title="Delete Schedule">
              <i class="fas fa-trash-alt"></i>
            </button>
            <button class="sd-modal__close" @click="selectedSchedule = null"><i class="fas fa-times"></i></button>
          </div>

          <div class="sd-modal__body">
            <!-- Date / Days coverage -->
            <div class="sd-section">
              <p class="sd-section__title">Coverage</p>
              <div class="sd-grid">
                <div class="sd-field" v-if="selectedSchedule.schedule_for === 'day_in_week'">
                  <span class="sd-field__label">Days</span>
                  <div class="sd-chips">
                    <span v-for="d in selectedSchedule.days" :key="d" class="sd-chip">{{ d }}</span>
                  </div>
                </div>
                <div class="sd-field" v-if="selectedSchedule.from_date || selectedSchedule.to_date">
                  <span class="sd-field__label">Date Range</span>
                  <span class="sd-field__value">{{ selectedSchedule.from_date || '—' }} → {{ selectedSchedule.to_date || 'ongoing' }}</span>
                </div>
                <div class="sd-field" v-if="selectedSchedule.schedule_for === 'everyday' && !selectedSchedule.from_date && !selectedSchedule.to_date">
                  <span class="sd-field__label">Applies to</span>
                  <span class="sd-field__value">All days</span>
                </div>
              </div>
            </div>

            <!-- Times -->
            <div class="sd-section">
              <p class="sd-section__title">Time</p>
              <div class="sd-grid sd-grid--4">
                <div class="sd-field">
                  <span class="sd-field__label">Time In AM</span>
                  <span class="sd-field__value">{{ fmtTime(selectedSchedule.timein_AM) || '—' }}</span>
                </div>
                <div class="sd-field">
                  <span class="sd-field__label">Time Out AM</span>
                  <span class="sd-field__value">{{ fmtTime(selectedSchedule.timeout_AM) || '—' }}</span>
                </div>
                <div class="sd-field">
                  <span class="sd-field__label">Time In PM</span>
                  <span class="sd-field__value">{{ fmtTime(selectedSchedule.timein_PM) || '—' }}</span>
                </div>
                <div class="sd-field">
                  <span class="sd-field__label">Time Out PM</span>
                  <span class="sd-field__value">{{ fmtTime(selectedSchedule.timeout_PM) || '—' }}</span>
                </div>
              </div>
              <div v-if="selectedSchedule.no_lunch_gap" class="sd-badge">
                <i class="fas fa-check-circle"></i> No Lunch Break
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
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

/* ── Tabs ── */
.sc-tabs {
  display: flex;
  gap: 6px;
  padding: 14px 22px 0;
}

.sc-tab {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 16px;
  border-radius: 9px 9px 0 0;
  border: 1px solid transparent;
  background: transparent;
  color: #6a84bf;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}

.sc-tab:hover {
  color: #9bb0da;
}

.sc-tab.is-active {
  background: rgba(31,191,184,0.1);
  border-color: rgba(31,191,184,0.28);
  border-bottom-color: transparent;
  color: #52d3d0;
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
  padding: 3px 6px;
  border-radius: 5px;
  border: 1px solid transparent;
  overflow: hidden;
  cursor: default;
  line-height: 1.4;
  display: flex;
  flex-direction: column;
  gap: 1px;
}

.sc-event__name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sc-event__time {
  font-size: 9px;
  font-weight: 500;
  opacity: 0.8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: 0.01em;
}

.sc-event--more {
  font-size: 10px;
  color: #6a84bf;
  padding: 1px 4px;
  border: none;
  background: none;
}

/* ── List (table) ── */
.sc-table-wrap {
  display: flex;
  flex-direction: column;
  min-height: 0;
  padding: 16px 22px 10px;
  font-size: 12.5px;
}

.sc-trow {
  display: grid;
  grid-template-columns: 18px minmax(120px, 1.3fr) 100px 190px 90px 150px;
  align-items: center;
  gap: 10px;
  padding: 10px;
}

.sc-trow--head {
  font-size: 11px;
  font-weight: 700;
  color: #6a84bf;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 0 10px 10px;
  border-bottom: 1px solid rgba(121,146,207,0.16);
  flex-shrink: 0;
}

.sc-tbody {
  max-height: 380px;
  min-height: 0;
  overflow-y: auto;
}

.sc-tbody::-webkit-scrollbar { width: 5px; }
.sc-tbody::-webkit-scrollbar-track { background: transparent; }
.sc-tbody::-webkit-scrollbar-thumb { background: rgba(121,146,207,0.22); border-radius: 3px; }

.sc-tbody .sc-trow {
  cursor: pointer;
  border-bottom: 1px solid rgba(121,146,207,0.08);
  transition: background 0.15s;
}

.sc-tbody .sc-trow:hover {
  background: rgba(31,191,184,0.08);
}

.sc-tcell {
  color: #cdd9f5;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sc-tcell--name {
  color: #edf5ff;
  font-weight: 600;
}

.sc-table-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
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

/* ── Schedule Detail Modal ── */
.sd-overlay {
  position: fixed;
  inset: 0;
  z-index: 1300;
  background: rgba(2,8,22,0.7);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.sd-modal {
  width: 100%;
  max-width: 480px;
  border-radius: 18px;
  background: rgba(10,18,42,0.99);
  border: 1px solid rgba(121,146,207,0.2);
  box-shadow: 0 28px 70px rgba(1,6,20,0.6);
  overflow: hidden;
}

.sd-modal__header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 20px 20px 16px;
  border-bottom: 1px solid rgba(121,146,207,0.12);
  position: relative;
}

.sd-modal__icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  border: 1px solid;
  display: grid;
  place-items: center;
  font-size: 16px;
  flex-shrink: 0;
}

.sd-modal__type {
  font-size: 11px;
  color: #6a84bf;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 3px;
}

.sd-modal__name {
  font-size: 17px;
  font-weight: 700;
  color: #f3f7ff;
  line-height: 1.2;
  margin: 0;
}

.sd-modal__delete {
  margin-left: auto;
  width: 32px;
  height: 32px;
  border-radius: 9px;
  border: 1px solid rgba(220,60,60,0.25);
  background: rgba(220,60,60,0.1);
  color: #f08080;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 13px;
  flex-shrink: 0;
  transition: background 0.15s, color 0.15s;
}

.sd-modal__delete:hover {
  background: rgba(220,60,60,0.22);
  color: #ff9a9a;
  border-color: rgba(220,60,60,0.4);
}

.sd-modal__close {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  border: 1px solid rgba(121,146,207,0.2);
  background: rgba(17,27,56,0.8);
  color: #9bb0da;
  cursor: pointer;
  display: grid;
  place-items: center;
  font-size: 13px;
  flex-shrink: 0;
  transition: background 0.15s, color 0.15s;
}

.sd-modal__close:hover {
  background: rgba(220,60,60,0.18);
  color: #f08080;
  border-color: rgba(220,60,60,0.3);
}

.sd-modal__body {
  padding: 16px 20px 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.sd-section__title {
  font-size: 11px;
  font-weight: 700;
  color: #6a84bf;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 10px;
}

.sd-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.sd-grid--4 {
  grid-template-columns: repeat(4, 1fr);
}

.sd-field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.sd-field__label {
  font-size: 10px;
  color: #6a84bf;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.sd-field__value {
  font-size: 13px;
  font-weight: 600;
  color: #edf5ff;
}

.sd-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  grid-column: 1 / -1;
}

.sd-chip {
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  background: rgba(31,191,184,0.12);
  border: 1px solid rgba(31,191,184,0.28);
  color: #52d3d0;
}

.sd-badge {
  margin-top: 10px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #6dd98a;
  background: rgba(80,200,100,0.1);
  border: 1px solid rgba(80,200,100,0.25);
  padding: 4px 10px;
  border-radius: 8px;
}

.sd-fade-enter-active, .sd-fade-leave-active {
  transition: opacity 0.18s ease;
}
.sd-fade-enter-active .sd-modal, .sd-fade-leave-active .sd-modal {
  transition: transform 0.18s ease, opacity 0.18s ease;
}
.sd-fade-enter-from, .sd-fade-leave-to {
  opacity: 0;
}
.sd-fade-enter-from .sd-modal {
  transform: scale(0.96) translateY(10px);
}
</style>
