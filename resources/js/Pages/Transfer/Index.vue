<template>
    <AuthenticatedLayout title="Optimize Your Transfers" description="Moving Supplies, Bridging needs"
        img="/assets/images/transfer.png">

        <!-- Header Section -->
        <div class="flex flex-col space-y-6">
            <!-- Buttons First -->
            <div class="flex items-center justify-end">
                <!-- New Transfer -->
                <Link :href="route('transfers.create')"
                    class="inline-flex items-center rounded-2xl px-4 py-2 border border-transparent text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    New Transfer
                </Link>
            </div>

            <!-- Filters Section -->
            <div class="mb-4">
                <!-- First Row: Search, Facility, Warehouse -->
                <div class="grid grid-cols-3 gap-4 mb-3">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" v-model="filters.search"
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-2xl w-full focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Search a Transfer">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Facility Selector -->
                    <div>
                        <Multiselect v-model="filters.selected_facility" :options="props.facilities" :searchable="true"
                            :allow-empty="true" :show-labels="false" placeholder="All Facilities" label="name"
                            track-by="id" class="rounded-2xl" @input="updateFacilityFilter">
                            <template #singleLabel="{ option }">
                                <span class="multiselect__single">
                                    {{ option ? option.name : 'All Facilities' }}
                                </span>
                            </template>
                        </Multiselect>
                    </div>

                    <!-- Warehouse Selector -->
                    <div>
                        <Multiselect v-model="filters.selected_warehouses" :options="props.warehouses" :multiple="true"
                            :close-on-select="false" :clear-on-select="false" :preserve-search="true" 
                            placeholder="All Warehouses" label="name" track-by="id" class="rounded-2xl" 
                            :preselect-first="false" @input="updateWarehouseFilter">
                            <template #selection="{ values, search, isOpen }">
                                <span class="multiselect__single" v-if="values.length && !isOpen">
                                    {{ values.length === 1 ? values[0].name : `${values.length} warehouses selected` }}
                                </span>
                            </template>
                        </Multiselect>
                    </div>
                </div>

                <!-- Second Row: Date Filters and Per Page -->
                <div class="flex items-center justify-between">
                    <div class="flex space-x-4">
                        <!-- From Date -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" v-model="filters.date_from"
                                class="pl-10 pr-2 py-2 border border-gray-300 rounded-2xl focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="From Date">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" style="top: 24px;">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <!-- To Date -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" v-model="filters.date_to"
                                class="pl-10 pr-2 py-2 border border-gray-300 rounded-2xl focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="To Date">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none" style="top: 24px;">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Per Page Selector -->
                    <div style="width: 200px;">
                        <select class="rounded-3xl border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 w-full" 
                            v-model="filters.per_page">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Status Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button v-for="tab in statusTabs" :key="tab.value" @click="currentTab = tab.value"
                        class="whitespace-nowrap py-4 px-3 border-b-4 font-bold text-xs" :class="[
                            currentTab === tab.value ?
                                'border-green-500 text-green-600' :
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                        {{ tab.label }}
                    </button>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-12 gap-1 mb-[40px]">
            <!-- Table Section (9 cols) -->
            <div class="md:col-span-9 sm:col-span-12">
                <div class="max-w-full overflow-auto">
                    <table class="min-w-full rounded-3xl">
                        <thead style="background-color: #EEF1F8;">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-[#6C75B8] uppercase tracking-wider">
                                    Transfer ID
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-[#6C75B8] uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-[#6C75B8] uppercase tracking-wider">
                                    To
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-[#6C75B8] uppercase tracking-wider">
                                    Items
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-[#6C75B8] uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="">
                            <tr v-if="filteredTransfers.length === 0">
                                <td colspan="5" class="text-center text-gray-500 py-4">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                            </path>
                                        </svg>
                                        <p class="mt-2 text-xs font-medium">No transfer data available</p>
                                        <p class="mt-1 text-xs">Create a new transfer or adjust your filters to see results</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="transfer in filteredTransfers" :key="transfer.id"
                                class="hover:bg-gray-50 border-b border-gray-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                                    <Link :href="route('transfers.show', transfer.id)" class="hover:underline">
                                    {{ transfer.transferID }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                    {{ new Date(transfer.transfer_date).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                    {{ transfer.to_warehouse?.name || transfer.to_facility?.name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                    {{ transfer.items_count }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    <div class="flex items-center gap-2">
                                        <!-- Status Progress Icons - Only show actions taken -->
                                        <div class="flex items-center gap-2">
                                            <!-- Show status progression up to current status - icons with labels -->
                                            <!-- Always show pending as it's the initial state -->
                                            <div class="flex items-center gap-1">
                                                <img src="/assets/images/pending.png" class="w-6 h-6" alt="Pending"
                                                    title="Pending" />
                                            </div>

                                            <!-- Show approved if status is approved or further -->
                                            <template
                                                v-if="['approved', 'in_process', 'dispatched', 'transferred', 'delivered', 'received'].includes(transfer.status?.toLowerCase())">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/approved.png" class="w-6 h-6"
                                                        alt="Approved" title="Approved" />
                                                </div>
                                            </template>

                                            <!-- Show processed if status is in_process or further -->
                                            <template
                                                v-if="['in_process', 'dispatched', 'transferred', 'delivered', 'received'].includes(transfer.status?.toLowerCase())">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/inprocess.png" class="w-6 h-6"
                                                        alt="Processed" title="Processed" />
                                                </div>
                                            </template>

                                            <!-- Show dispatched if status is dispatched or further -->
                                            <template
                                                v-if="['dispatched', 'transferred', 'delivered', 'received'].includes(transfer.status?.toLowerCase())">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/dispatch.png" class="w-6 h-6"
                                                        alt="Dispatched" title="Dispatched" />
                                                </div>
                                            </template>

                                            <!-- Show received if status is received -->
                                            <template v-if="['received'].includes(transfer.status?.toLowerCase())">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/received.png" class="w-6 h-6"
                                                        alt="Received" title="Received" />
                                                </div>
                                            </template>

                                            <!-- Show rejected if status is rejected (special case) -->
                                            <template v-if="transfer.status?.toLowerCase() === 'rejected'">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/rejected.png" class="w-6 h-6"
                                                        alt="Rejected" title="Rejected" />
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div class="flex justify-between items-center px-6 py-3 bg-gray-50 border-t border-gray-200" v-if="filteredTransfers.length > 0">
                    <div class="text-xs text-gray-700">
                        Showing {{ (currentPage - 1) * filters.per_page + 1 }} to 
                        {{ Math.min(currentPage * filters.per_page, filteredTransfers.length) }} 
                        of {{ filteredTransfers.length }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Previous Button -->
                        <button v-if="currentPage > 1" @click="previousPage"
                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-700 rounded">
                            Previous
                        </button>
                        
                        <!-- Page Numbers -->
                        <div class="flex space-x-1">
                            <template v-for="page in visiblePages" :key="page">
                                <button v-if="page !== '...'" @click="goToPage(page)"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium border rounded',
                                        page === currentPage 
                                            ? 'bg-blue-600 text-white border-blue-600' 
                                            : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50 hover:text-gray-700'
                                    ]">
                                    {{ page }}
                                </button>
                                <span v-else class="px-3 py-2 text-sm text-gray-500">...</span>
                            </template>
                        </div>
                        
                        <!-- Next Button -->
                        <button v-if="currentPage < totalPages" @click="nextPage"
                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-700 rounded">
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Section (3 cols) -->
            <div class="md:col-span-3 sm:col-span-12">
                <div class="bg-white mb-4">
                    <h3 class="text-xs text-black mb-4">Transfer Statistics</h3>
                    <div class="flex justify-between gap-3">
                        <!-- Pending -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-amber-50 rounded-2xl relative overflow-hidden shadow-md">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/pending_small.png" class="h-6 w-6 object-contain"
                                        alt="Pending" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-amber-500 to-amber-400 transition-all duration-500"
                                    :style="{ height: (props.statistics?.pending?.percentage || 0) + '%' }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ props.statistics?.pending?.percentage || 0 }}%
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <span class="font-medium text-gray-900"
                                    style="font-size: 10px; font-weight: bold">Pending</span>
                            </div>
                        </div>

                        <!-- Approved -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-blue-50 rounded-2xl relative overflow-hidden shadow-md">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/approved_small.png" class="h-6 w-6 object-contain"
                                        alt="Approved" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-blue-600 to-blue-400 transition-all duration-500"
                                    :style="{ height: (props.statistics?.approved?.percentage || 0) + '%' }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ props.statistics?.approved?.percentage || 0 }}%
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <span class="font-medium text-gray-900"
                                    style="font-size: 10px; font-weight: bold">Approved</span>
                            </div>
                        </div>

                        <!-- In Process -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-slate-50 rounded-2xl relative overflow-hidden shadow-md">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/inprocess.png" class="h-6 w-6 object-contain"
                                        alt="In Process" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-slate-600 to-slate-400 transition-all duration-500"
                                    :style="{ height: (props.statistics?.in_process?.percentage || 0) + '%' }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ props.statistics?.in_process?.percentage || 0 }}%
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <span class="font-medium text-gray-900" style="font-size: 10px; font-weight: bold">In
                                    Process</span>
                            </div>
                        </div>

                        <!-- Dispatched -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-purple-50 rounded-2xl relative overflow-hidden shadow-md">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/dispatch.png" class="h-6 w-6 object-contain"
                                        alt="Dispatched" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-purple-600 to-purple-400 transition-all duration-500"
                                    :style="{ height: (props.statistics?.transferred?.percentage || 0) + '%' }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ props.statistics?.transferred?.percentage || 0 }}%
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <span class="font-medium text-gray-900"
                                    style="font-size: 10px; font-weight: bold">Dispatched</span>
                            </div>
                        </div>

                        <!-- Received -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-emerald-50 rounded-2xl relative overflow-hidden shadow-md">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/received.png" class="h-6 w-6 object-contain"
                                        alt="Received" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-emerald-600 to-emerald-400 transition-all duration-500"
                                    :style="{ height: (props.statistics?.received?.percentage || 0) + '%' }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ props.statistics?.received?.percentage || 0 }}%
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <span class="font-medium text-gray-900"
                                    style="font-size: 10px; font-weight: bold">Received</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { router, Link, usePage } from '@inertiajs/vue3';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';

// In Vue 3 with script setup, components are automatically registered when imported

const props = defineProps({
    transfers: {
        type: Array,
        default: () => []
    },
    statistics: {
        type: Object,
        default: () => ({
            approved: { count: 0, percentage: 0 },
            pending: { count: 0, percentage: 0 },
            in_process: { count: 0, percentage: 0 },
            transferred: { count: 0, percentage: 0 },
            received: { count: 0, percentage: 0 },
            rejected: { count: 0, percentage: 0 }
        })
    },
    facilities: {
        type: Array,
        default: () => []
    },
    warehouses: {
        type: Array,
        default: () => []
    }
});

const currentTab = ref('all');
const selectedTransfers = ref([]);
const isAllSelected = computed(() => selectedTransfers.value.length === filteredTransfers.value.length && filteredTransfers.value.length > 0);

// Filter state
const filters = ref({
    ...props.filters,
    selected_facility: null,
    selected_warehouses: [],
    per_page: 10
});

// Initialize multiselect values if filters exist
onMounted(() => {
    // Initialize facility filter
    if (props.filters && props.filters.facility_id) {
        const facilityId = props.filters.facility_id.toString();
        filters.value.selected_facility = props.facilities.find(facility =>
            facility.id.toString() === facilityId);
    }

    // Initialize warehouse filter
    if (props.filters && props.filters.warehouse_id) {
        const warehouseIds = props.filters.warehouse_id.toString().split(',');
        filters.value.selected_warehouses = props.warehouses.filter(warehouse =>
            warehouseIds.includes(warehouse.id.toString()));
    }

    // Initialize per page filter
    if (props.filters && props.filters.per_page) {
        filters.value.per_page = props.filters.per_page;
    }
});

// Methods to handle filter updates
const updateFacilityFilter = () => {
    // Update the facility_id based on the selected facility object
    if (filters.value.selected_facility) {
        filters.value.facility_id = filters.value.selected_facility.id.toString();
    } else {
        filters.value.facility_id = '';
    }
    
    // Force immediate URL update
    // Create a params object with only non-empty values
    const params = {};
    
    // Add all non-empty filter values
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.facility_id) params.facility_id = filters.value.facility_id;
    if (filters.value.warehouse_id) params.warehouse_id = filters.value.warehouse_id;
    if (filters.value.date_from) params.date_from = filters.value.date_from;
    if (filters.value.date_to) params.date_to = filters.value.date_to;
    if (filters.value.per_page) params.per_page = filters.value.per_page;
    
    // Include tab if not 'all'
    if (currentTab.value !== 'all') params.tab = currentTab.value;
    
    // Update the URL immediately
    router.get(
        route('transfers.index'),
        params,
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            only: ['transfers']
        }
    );
};

const updateWarehouseFilter = () => {
    if (filters.value.selected_warehouses && filters.value.selected_warehouses.length > 0) {
        filters.value.warehouse_id = filters.value.selected_warehouses.map(w => w.id).join(',');
    } else {
        filters.value.warehouse_id = '';
    }
};

// Watch for filter changes and update URL
watch(filters, (newFilters) => {
    // Create a params object with only non-empty values
    const params = {};
    
    // Only add filter values that are not empty
    if (newFilters.search) params.search = newFilters.search;
    if (newFilters.facility_id) params.facility_id = newFilters.facility_id;
    if (newFilters.warehouse_id) params.warehouse_id = newFilters.warehouse_id;
    if (newFilters.date_from) params.date_from = newFilters.date_from;
    if (newFilters.date_to) params.date_to = newFilters.date_to;
    if (newFilters.per_page) params.per_page = newFilters.per_page;
    
    // Only include the tab parameter if it's not 'all'
    if (currentTab.value !== 'all') params.tab = currentTab.value;
    
    router.get(
        route('transfers.index'),
        params,
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            only: ['transfers']
        }
    );
}, { deep: true });

// Watch for tab changes
watch(currentTab, (newTab) => {
    // Create a params object with only non-empty values
    const params = {};
    
    // Only add filter values that are not empty
    if (filters.value.search) params.search = filters.value.search;
    if (filters.value.facility_id) params.facility_id = filters.value.facility_id;
    if (filters.value.warehouse_id) params.warehouse_id = filters.value.warehouse_id;
    if (filters.value.date_from) params.date_from = filters.value.date_from;
    if (filters.value.date_to) params.date_to = filters.value.date_to;
    if (filters.value.per_page) params.per_page = filters.value.per_page;
    
    // Only add tab if it's not 'all'
    if (newTab !== 'all') params.tab = newTab;
    
    router.get(
        route('transfers.index'),
        params,
        {
            preserveState: true,
            replace: true,
            preserveScroll: true,
            only: ['transfers']
        }
    );
});

// Status configuration
const statusTabs = [
    { value: 'all', label: 'All Transfers', color: 'blue' },
    { value: 'pending', label: 'Pending Approval', color: 'yellow' },
    { value: 'approved', label: 'Approved', color: 'green' },
    { value: 'in_process', label: 'In Process', color: 'blue' },
    { value: 'dispatched', label: 'Dispatched', color: 'purple' },
    { value: 'received', label: 'Received', color: 'gray' },
    { value: 'rejected', label: 'Rejected', color: 'red' },
];

const currentPage = ref(1);

const totalPages = computed(() => {
    return Math.ceil(filteredTransfers.value.length / filters.value.per_page);
});

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const pages = [];
    
    if (total <= 7) {
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
        if (current <= 4) {
            for (let i = 1; i <= 5; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(total);
        } else if (current >= total - 3) {
            pages.push(1);
            pages.push('...');
            for (let i = total - 4; i <= total; i++) {
                pages.push(i);
            }
        } else {
            pages.push(1);
            pages.push('...');
            for (let i = current - 1; i <= current + 1; i++) {
                pages.push(i);
            }
            pages.push('...');
            pages.push(total);
        }
    }
    
    return pages;
});

// Watch for changes that should reset pagination
watch([() => filters.value.search, () => filters.value.facility_id, () => filters.value.warehouse_id, 
       () => filters.value.date_from, () => filters.value.date_to, () => currentTab.value], () => {
    currentPage.value = 1;
});

const filteredTransfers = computed(() => {
    let filtered = props.transfers;

    if (currentTab.value !== 'all') {
        filtered = filtered.filter(transfer => transfer.status?.toLowerCase() === currentTab.value);
    }

    if (filters.value.search) {
        const searchTerm = filters.value.search.toLowerCase();
        filtered = filtered.filter(transfer => 
            transfer.transferID?.toLowerCase().includes(searchTerm) ||
            transfer.from_facility?.name?.toLowerCase().includes(searchTerm) ||
            transfer.to_facility?.name?.toLowerCase().includes(searchTerm) ||
            transfer.from_warehouse?.name?.toLowerCase().includes(searchTerm) ||
            transfer.to_warehouse?.name?.toLowerCase().includes(searchTerm)
        );
    }

    if (filters.value.facility_id) {
        const facilityIds = filters.value.facility_id.split(',').map(id => parseInt(id));
        filtered = filtered.filter(transfer => {
            return facilityIds.includes(transfer.to_facility_id);
        });
    }

    if (filters.value.warehouse_id) {
        const warehouseIds = filters.value.warehouse_id.split(',').map(id => parseInt(id));
        filtered = filtered.filter(transfer => {
            return (
                warehouseIds.includes(transfer.to_warehouse_id) ||
                warehouseIds.includes(transfer.from_warehouse_id)
            );
        });
    }

    if (filters.value.date_from) {
        const fromDate = new Date(filters.value.date_from);
        filtered = filtered.filter(transfer => {
            const transferDate = new Date(transfer.transfer_date);
            return transferDate >= fromDate;
        });
    }

    if (filters.value.date_to) {
        const toDate = new Date(filters.value.date_to);
        toDate.setHours(23, 59, 59, 999);
        filtered = filtered.filter(transfer => {
            const transferDate = new Date(transfer.transfer_date);
            return transferDate <= toDate;
        });
    }

    // Apply pagination
    const start = (currentPage.value - 1) * parseInt(filters.value.per_page);
    const end = start + parseInt(filters.value.per_page);
    
    return filtered.slice(start, end);
});

const getTabCount = (tabName) => {
    if (tabName === 'all') {
        return props.transfers.length;
    }
    return props.transfers.filter(transfer => transfer.status === tabName).length;
};

const getStatusPercentage = (status) => {
    if (!props.transfers.length) return 0;
    const count = props.transfers.filter(transfer => transfer.status === status).length;
    return Math.round((count / props.transfers.length) * 100);
};

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

const getStatusActions = (transfer) => {
    // Debug the transfer status
    console.log('Transfer status:', transfer.status);

    const actions = [];
    
    // Get current user's facility ID from the shared data
    const currentUserFacilityId = usePage().props.facility?.id;

    // Ensure we're handling the status correctly
    const status = transfer.status?.toLowerCase() || '';
    
    // Check if this transfer is to the current user's facility
    const isToCurrentUserFacility = transfer.to_facility_id === currentUserFacilityId;
    // Check if this transfer is from the current user's facility
    const isFromCurrentUserFacility = transfer.from_facility_id === currentUserFacilityId;

    // Debug facility IDs to help troubleshoot
    console.log('Current user facility ID:', currentUserFacilityId);
    console.log('Transfer from facility ID:', transfer.from_facility_id);
    console.log('Transfer to facility ID:', transfer.to_facility_id);
    console.log('Is from current user facility:', isFromCurrentUserFacility);
    console.log('Is to current user facility:', isToCurrentUserFacility);

    // Check if the user has approval permissions
    const canApprove = usePage().props.auth.user.can_approve_transfers;
    
    // Only show actions for specific statuses: pending, approved, in_process, dispatched
    // Exclude actions for: rejected, received (these are terminal states)
    if (['rejected', 'received'].includes(status)) {
        return actions; // Return empty actions array for terminal states
    }

    switch (status) {
        case 'pending':
            // Only users with approval permissions can approve/reject
            if (canApprove) {
                actions.push({ label: 'Approve', status: 'approved', color: 'green', icon: '/assets/images/approved.png' });
                actions.push({ label: 'Reject', status: 'rejected', color: 'red', icon: '/assets/images/rejected.png' });
            }
            break;
        case 'approved':
            // Only the source facility can process an approved transfer
            if (isFromCurrentUserFacility) {
                actions.push({ label: 'Process', status: 'in_process', color: 'blue', icon: '/assets/images/inprocess.png' });
            }
            break;
        case 'in_process':
            // Only the source facility can dispatch an in-process transfer
            if (isFromCurrentUserFacility) {
                actions.push({ label: 'Dispatch', status: 'dispatched', color: 'purple', icon: '/assets/images/dispatch.png' });
            }
            break;
        case 'dispatched':
            // Only the destination facility can receive a dispatched transfer
            if (isToCurrentUserFacility) {
                actions.push({ label: 'Receive', status: 'received', color: 'green', icon: '/assets/images/received.png' });
            }
            break;
    }

    // Debug the actions being returned
    console.log('Actions for transfer:', actions);

    return actions;
};

const getBulkStatusActions = () => {
    // Get unique current statuses of selected transfers
    const currentStatuses = [...new Set(props.transfers
        .filter(transfer => selectedTransfers.value.includes(transfer.id))
        .map(transfer => transfer.status))];

    // If there are no selected transfers or multiple different statuses, return empty actions
    if (currentStatuses.length !== 1) {
        return [];
    }

    // Return actions based on the current status
    const currentStatus = currentStatuses[0];
    const actions = [];

    switch (currentStatus) {
        case 'pending':
            actions.push({ label: 'Approve Selected', status: 'approved', color: 'green' });
            actions.push({ label: 'Reject Selected', status: 'rejected', color: 'red' });
            break;
        case 'approved':
            actions.push({ label: 'Process Selected', status: 'in_process', color: 'blue' });
            break;
        case 'in_process':
            actions.push({ label: 'Dispatch Selected', status: 'dispatched', color: 'purple' });
            break;
        case 'dispatched':
            actions.push({ label: 'Deliver Selected', status: 'delivered', color: 'indigo' });
            break;
    }

    return actions;
};

const toggleAllTransfers = () => {
    if (isAllSelected.value) {
        selectedTransfers.value = [];
    } else {
        selectedTransfers.value = filteredTransfers.value.map(t => t.id);
    }
};

const clearSelection = () => {
    selectedTransfers.value = [];
};

// Track loading state for each transfer action
const loadingActions = ref({});

const changeStatus = (transferId, newStatus) => {
    console.log('Changing status for transfer:', transferId, 'to:', newStatus);

    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to change the transfer status to ${newStatus}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, change it!'
    }).then(async (result) => {
        if (result.isConfirmed) {
            let routeName;
            switch (newStatus) {
                case 'approved':
                    routeName = 'transfers.approve';
                    break;
                case 'rejected':
                    routeName = 'transfers.reject';
                    break;
                case 'in_process':
                    routeName = 'transfers.inProcess';
                    break;
                case 'dispatched':
                    routeName = 'transfers.dispatch';
                    break;
                default:
                    Toast.fire({
                        icon: 'error',
                        title: 'Invalid status transition'
                    });
                    return;
            }

            console.log('Using route:', routeName, 'for transferId:', transferId);

            // Set loading state for this specific action
            loadingActions.value[`${transferId}_${newStatus}`] = true;

            await axios.post(route(routeName, transferId))
                .then(response => {
                    Toast.fire({
                        icon: 'success',
                        title: response.data.message || 'Transfer status has been updated.'
                    });
                    router.reload();
                })
                .catch(error => {
                    console.error('Error updating status:', error);
                    Toast.fire({
                        icon: 'error',
                        title: error.response?.data?.message || 'Failed to update transfer status'
                    });
                    // Clear loading state on error
                    loadingActions.value[`${transferId}_${newStatus}`] = false;
                });
        }
    });
};

// Add missing functions for approve and reject actions
const approveTransfer = (transferId) => {
    changeStatus(transferId, 'approved');
};

const rejectTransfer = (transferId) => {
    changeStatus(transferId, 'rejected');
};

const markInProcess = (transferId) => {
    changeStatus(transferId, 'in_process');
};

// Pagination navigation methods
const goToPage = (page) => {
    if (page !== '...' && page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};
</script>
