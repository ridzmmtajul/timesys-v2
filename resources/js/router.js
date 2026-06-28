import { createRouter, createWebHashHistory } from 'vue-router';
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
import WorkTimeRuleList from './components/libraries/work-time-rules/Index.vue';

const routes = [
  { path: '/', redirect: '/biometric' },
  { path: '/biometric', component: BiometricList },
  { path: '/employees', component: EmployeeList },
  { path: '/libraries/roles', component: RoleList },
  { path: '/libraries/offices', component: OfficeList },
  { path: '/libraries/office-divisions', component: OfficeDivisionList },
  { path: '/libraries/titles', component: TitleList },
  { path: '/libraries/employment-types', component: EmploymentTypeList },
  { path: '/libraries/holidays', component: HolidayList },
  { path: '/libraries/positions', component: PositionList },
  { path: '/libraries/post-numbers', component: PostNumberList },
  { path: '/libraries/schedule-types', component: ScheduleTypeList },
  { path: '/libraries/schedules', component: ScheduleList },
  { path: '/settings/work-time-rules', component: WorkTimeRuleList },
  { path: '/settings/accounts', component: WorkTimeRuleList },
];

export default createRouter({
  history: createWebHashHistory(),
  routes,
});
