<script setup>
import { ref, onMounted, watch } from "vue";
import WorkTimeRuleForm from "../work-time-rules/Form/Create.vue";
import useWorkTimeRules from "../../../composables/work-time-rules.js";

const { workTimeRules, pagination, query, is_loading, getWorkTimeRules, destroyWorkTimeRule } = useWorkTimeRules();

const workTimeRule = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Rule", key: "rule", sortable: true },
    { title: "Time", key: "time", sortable: false },
    { title: "Offices", key: "offices", sortable: false },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        workTimeRule.value = {};
    }
};

onMounted(() => {
    getWorkTimeRules();
});

const editItem = (value) => {
    workTimeRule.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyWorkTimeRule(value.id);
};

const reloadWorkTimeRules = async () => {
    await getWorkTimeRules();
    workTimeRule.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getWorkTimeRules();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-clock-check-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Work Time Rules</h5>
                    <p class="lib-header__subtitle">Manage work time rules and office assignments</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Rule
            </button>
        </div>

        <!-- Main Card -->
        <div class="lib-card">
            <!-- Search Bar -->
            <div class="lib-search">
                <v-icon icon="mdi-magnify" size="17" class="lib-search__icon" />
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Search work time rules..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="workTimeRules"
                :search="query.search"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading work time rules..."
                hide-default-footer
                item-value="id"
            >
                <!-- Rule Column -->
                <template v-slot:item.rule="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-clock-check-outline" size="14" />
                        </div>
                        <div>
                            <span class="lib-table__name">{{ item.rule }}</span>
                            <span v-if="item.description" class="lib-table__muted" style="display:block;font-size:11px;">{{ item.description }}</span>
                        </div>
                    </div>
                </template>

                <!-- Time Column -->
                <template v-slot:item.time="{ item }">
                    <span class="lib-table__muted">{{ item.time != null ? item.time + ' min' : '—' }}</span>
                </template>

                <!-- Offices Column -->
                <template v-slot:item.offices="{ item }">
                    <span v-if="item.offices && item.offices.length" class="lib-table__badge">
                        {{ item.offices.length }} office{{ item.offices.length > 1 ? 's' : '' }}
                    </span>
                    <span v-else class="lib-table__muted">All offices</span>
                </template>

                <!-- Actions Column -->
                <template v-slot:item.actions="{ item }">
                    <div class="lib-table__actions">
                        <button class="lib-action-btn lib-action-btn--edit" @click="editItem(item)">
                            <v-icon icon="mdi-pencil-outline" size="14" />
                            Edit
                        </button>
                        <button class="lib-action-btn lib-action-btn--delete" @click="deleteItem(item)">
                            <v-icon icon="mdi-delete-outline" size="14" />
                            Delete
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template v-slot:no-data>
                    <div class="lib-empty">
                        <div class="lib-empty__icon">
                            <v-icon icon="mdi-clock-remove-outline" size="32" />
                        </div>
                        <p class="lib-empty__title">No work time rules found</p>
                        <p class="lib-empty__sub">Get started by creating your first work time rule</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Rule
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> rules
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getWorkTimeRules"
                    class="lib-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Work Time Rule Form Modal -->
    <work-time-rule-form
        :value="show_form_modal"
        :work-time-rule="workTimeRule"
        @input="showModalForm"
        @reloadWorkTimeRules="reloadWorkTimeRules"
    />
</template>