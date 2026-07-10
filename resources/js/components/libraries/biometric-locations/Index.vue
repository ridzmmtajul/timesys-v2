<script setup>
import { ref, onMounted, watch } from "vue";
import BiometricLocationForm from "./Form/Create.vue";
import useBiometricLocations from "../../../composables/biometric-locations.js";

const { biometricLocations, pagination, query, is_loading, getBiometricLocations, destroyBiometricLocation } = useBiometricLocations();

const biometricLocation = ref({});
const show_form_modal = ref(false);

const headers = [
    { title: "Name", key: "name", sortable: true },
    { title: "Actions", key: "actions", sortable: false, align: "center" },
];

const showModalForm = (val) => {
    show_form_modal.value = val;
    if (val == false) {
        biometricLocation.value = {};
    }
};

onMounted(() => {
    getBiometricLocations();
});

const editItem = (value) => {
    biometricLocation.value = value;
    showModalForm(true);
};

const deleteItem = async (value) => {
    await destroyBiometricLocation(value.id);
};

const reloadBiometricLocations = async () => {
    await getBiometricLocations();
    biometricLocation.value = {};
};

watch(() => query.value.search, () => {
    query.value.page = 1;
    getBiometricLocations();
});
</script>

<template>
    <div class="lib-page">
        <!-- Page Header -->
        <div class="lib-header">
            <div class="lib-header__left">
                <div class="lib-header__icon">
                    <v-icon icon="mdi-map-marker-radius-outline" size="20" />
                </div>
                <div>
                    <h5 class="lib-header__title">Biometric Location Management</h5>
                    <p class="lib-header__subtitle">Manage biometric device locations</p>
                </div>
            </div>
            <button class="lib-btn-new" @click="showModalForm(true)">
                <v-icon icon="mdi-plus" size="16" />
                New Location
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
                    placeholder="Search locations..."
                    class="lib-search__input"
                />
            </div>

            <!-- Data Table -->
            <v-data-table
                :headers="headers"
                :items="biometricLocations"
                class="lib-table"
                :loading="is_loading"
                loading-text="Loading locations..."
                hide-default-footer
                item-value="id"
            >
                <!-- Name Column -->
                <template v-slot:item.name="{ item }">
                    <div class="lib-table__cell">
                        <div class="lib-table__avatar">
                            <v-icon icon="mdi-map-marker-radius-outline" size="14" />
                        </div>
                        <span class="lib-table__name">{{ item.name }}</span>
                    </div>
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
                            <v-icon icon="mdi-map-marker-off-outline" size="32" />
                        </div>
                        <p class="lib-empty__title">No biometric locations found</p>
                        <p class="lib-empty__sub">Get started by creating your first location</p>
                        <button class="lib-btn-new" style="margin-top:8px" @click="showModalForm(true)">
                            <v-icon icon="mdi-plus" size="15" />
                            Create Location
                        </button>
                    </div>
                </template>
            </v-data-table>

            <!-- Pagination -->
            <div class="lib-pagination" v-if="pagination && pagination.total > 0">
                <span class="lib-pagination__info">
                    Showing
                    <strong>{{ pagination.from || 0 }}</strong>–<strong>{{ pagination.to || 0 }}</strong>
                    of <strong>{{ pagination.total }}</strong> locations
                </span>
                <div class="lib-pager">
                    <button class="lib-pager__btn" :disabled="query.page <= 1" @click="query.page--; getBiometricLocations()">
                        <v-icon icon="mdi-chevron-left" size="18" />
                    </button>
                    <span class="lib-pager__current">{{ query.page }}</span>
                    <button class="lib-pager__btn" :disabled="query.page >= (pagination.last_page || 1)" @click="query.page++; getBiometricLocations()">
                        <v-icon icon="mdi-chevron-right" size="18" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Biometric Location Form Modal -->
    <biometric-location-form
        :value="show_form_modal"
        :biometric-location="biometricLocation"
        @input="showModalForm"
        @reloadBiometricLocations="reloadBiometricLocations"
    />
</template>
