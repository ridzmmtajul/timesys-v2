<script setup>
import { ref, onMounted, watch } from "vue";
import OfficeDivisionForm from "./Form/Create.vue";
import useOfficeDivisions from "../../../composables/officeDivisions.js";

const { divisions, pagination, query, is_loading, getDivisions, destroyDivision } = useOfficeDivisions();

const division = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Code", key: "code", sortable: true },
    { title: "Name", key: "name", sortable: true },
    { title: "Office", key: "office_name", sortable: true },
    { title: "Description", key: "description", sortable: false },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        division.value = {};
    }
};

onMounted(() => {
    getDivisions();
});

const editItem = (value) => {
    division.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyDivision(value.id);
};

const reloadDivisions = async () => {
    await getDivisions();
    division.value = {};
};

watch(() => query.value.search, () => {
    query.value.page = 1;
    getDivisions();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-office-building-marker" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Office Division Management</h5>
                    <p class="lib-header__subtitle">Manage divisions and departments</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Division
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
                    placeholder="Search divisions..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="divisions"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading divisions..."
                hide-default-footer
                item-value="id"
            >
                <!-- Code Column -->
                <template v-slot:item.code="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-office-building-marker" size="14" />
                        </div>
                        <span class="lib-table__code">{{ item.code }}</span>
                    </div>
                </template>

                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <span class="lib-table__name">{{ item.name }}</span>
                </template>

                <!-- Office Column -->
                <template v-slot:item.office_name="{ item }">
                    <span v-if="item.office_name" class="lib-table__badge">
                        {{ item.office_code }} — {{ item.office_name }}
                    </span>
                    <span v-else class="lib-table__muted">—</span>
                </template>

                <!-- Description Column -->
                <template v-slot:item.description="{ item }">
                    <span class="lib-table__muted">{{ item.description || '—' }}</span>
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
                            <v-icon icon="mdi-office-building-remove" size="32" />
                        </div>
                        <p class="lib-empty__title">No divisions found</p>
                        <p class="lib-empty__sub">Get started by creating your first division</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Division
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> divisions
                </span>
                <div class="lib-pager">
                    <button class="lib-pager__btn" :disabled="query.page <= 1" @click="query.page--; getDivisions()">
                        <v-icon icon="mdi-chevron-left" size="18" />
                    </button>
                    <span class="lib-pager__current">{{ query.page }}</span>
                    <button class="lib-pager__btn" :disabled="query.page >= (pagination.last_page || 1)" @click="query.page++; getDivisions()">
                        <v-icon icon="mdi-chevron-right" size="18" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Division Form Modal -->
    <office-division-form
        :value="show_form_modal"
        :division="division"
        @input="showModalForm"
        @reloadDivisions="reloadDivisions"
    />
</template>
