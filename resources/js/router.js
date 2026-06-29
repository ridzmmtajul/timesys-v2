import { createRouter, createWebHashHistory } from 'vue-router';
import LoginPage from './components/auth/Login.vue';
import SetPasswordPage from './components/auth/SetPassword.vue';
import BiometricList from './components/biometric/Index.vue';
import EmployeeList from './components/employees/Index.vue';
import RoleList from './components/libraries/roles/Index.vue';
import OfficeList from './components/libraries/offices/Index.vue';
import OfficeDivisionList from './components/libraries/office-divisions/Index.vue';
import TitleList from './components/libraries/titles/Index.vue';
import EmploymentTypeList from './components/libraries/employment-types/Index.vue';
import HolidayList from './components/libraries/holidays/Index.vue';
import PositionList from './components/libraries/positions/Index.vue';
import PostNumberList from './components/libraries/post-numbers/Index.vue';
import ScheduleTypeList from './components/libraries/schedule-types/Index.vue';
import ScheduleList from './components/libraries/schedules/Index.vue';
import WorkScheduleList from './components/work-schedules/Index.vue';
import WorkTimeRuleList from './components/settings/work-time-rules/Index.vue';
import UserList from './components/settings/users/Index.vue';
import EmployeeListReport from './components/reports/EmployeeList.vue';

const routes = [
  { path: '/login', component: LoginPage, meta: { guest: true } },
  { path: '/set-password', component: SetPasswordPage, meta: { requiresAuth: true } },
  { path: '/', redirect: '/biometric' },
  { path: '/biometric', component: BiometricList, meta: { requiresAuth: true } },
  { path: '/employees', component: EmployeeList, meta: { requiresAuth: true } },
  { path: '/libraries/roles', component: RoleList, meta: { requiresAuth: true } },
  { path: '/libraries/offices', component: OfficeList, meta: { requiresAuth: true } },
  { path: '/libraries/office-divisions', component: OfficeDivisionList, meta: { requiresAuth: true } },
  { path: '/libraries/titles', component: TitleList, meta: { requiresAuth: true } },
  { path: '/libraries/employment-types', component: EmploymentTypeList, meta: { requiresAuth: true } },
  { path: '/libraries/holidays', component: HolidayList, meta: { requiresAuth: true } },
  { path: '/libraries/positions', component: PositionList, meta: { requiresAuth: true } },
  { path: '/libraries/post-numbers', component: PostNumberList, meta: { requiresAuth: true } },
  { path: '/libraries/schedule-types', component: ScheduleTypeList, meta: { requiresAuth: true } },
  { path: '/libraries/schedules', component: ScheduleList, meta: { requiresAuth: true } },
  { path: '/work-schedules', component: WorkScheduleList, meta: { requiresAuth: true } },
  { path: '/settings/work-time-rules', component: WorkTimeRuleList, meta: { requiresAuth: true } },
  { path: '/settings/accounts', component: UserList, meta: { requiresAuth: true } },
  { path: '/reports/employees', component: EmployeeListReport, meta: { requiresAuth: true } },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('timesys_token');
  const stored = localStorage.getItem('timesys_user');
  const user = stored ? JSON.parse(stored) : null;

  if (to.meta.requiresAuth && !token) {
    return next('/login');
  }

  if (to.meta.guest && token) {
    return next(user?.isNew ? '/set-password' : '/biometric');
  }

  if (token && user?.isNew && to.path !== '/set-password') {
    return next('/set-password');
  }

  if (token && !user?.isNew && to.path === '/set-password') {
    return next('/biometric');
  }

  next();
});

export default router;
