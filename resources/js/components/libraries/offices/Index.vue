<script setup>
import { ref, onMounted, watch } from "vue";
import OfficeForm from "./Form/Create.vue";
import useOffices from "../../../composables/offices.js";

const { offices, pagination, query, is_loading, getOffices, destroyOffice } = useOffices();

const office = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Code", key: "code", sortable: true },
    { title: "Name", key: "name", sortable: true },
    { title: "Description", key: "description", sortable: false },
    { title: "Prefix", key: "prefix", sortable: true },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        office.value = {};
    }
};

onMounted(() => {
    getOffices();
});

const editItem = (value) => {
    office.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyOffice(value.id);
};

const reloadOffices = async () => {
    await getOffices();
    office.value = {};
};

watch(() => query.search, () => {
    query.page = 1;
    getOffices();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-office-building" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Office Management</h5>
                    <p class="lib-header__subtitle">Manage offices and departments</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Office
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
                    placeholder="Search offices..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="offices"
                :search="query.search"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading offices..."
                hide-default-footer
                item-value="id"
            >
                <!-- Code Column -->
                <template v-slot:item.code="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-office-building" size="14" />
                        </div>
                        <span class="lib-table__code">{{ item.code }}</span>
                    </div>
                </template>

                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <span class="lib-table__name">{{ item.name }}</span>
                </template>

                <!-- Description Column -->
                <template v-slot:item.description="{ item }">
                    <span class="lib-table__muted">{{ item.description || '—' }}</span>
                </template>

                <!-- Prefix Column -->
                <template v-slot:item.prefix="{ item }">
                    <span v-if="item.prefix" class="lib-table__badge">{{ item.prefix }}</span>
                    <span v-else class="lib-table__muted">—</span>
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
                        <p class="lib-empty__title">No offices found</p>
                        <p class="lib-empty__sub">Get started by creating your first office</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Office
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> offices
                </span>
                <v-pagination
                    v-model="query.page"
                    :length="pagination.last || 1"
                    :total-visible="5"
                    circle
                    @update:model-value="getOffices"
                    class="lib-pagination__control"
                    active-color="#1fbfb8"
                />
            </div>
        </div>
    </div>

    <!-- Office Form Modal -->
    <office-form
        :value="show_form_modal"
        :office="office"
        @input="showModalForm"
        @reloadOffices="reloadOffices"
    />
</template>
