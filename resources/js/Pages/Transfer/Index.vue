<template>
    <AuthenticatedLayout title="Optimize Your Transfers" description="Moving Supplies, Bridging needs"
        img="/assets/images/transfer.png">
        
        <!-- Transfer Direction Tabs (Very Top Level) -->
        <div class="mb-8">
            <nav class="flex space-x-1 bg-gray-100 p-1 rounded-xl">
                <button v-for="tab in transferDirectionTabs" :key="tab.value" @click="currentDirectionTab = tab.value"
                    class="relative whitespace-nowrap py-3 px-6 font-semibold text-lg flex items-center gap-3 rounded-lg transition-all duration-300 ease-in-out" :class="[
                        currentDirectionTab === tab.value
                            ? 'bg-white text-blue-600 shadow-lg shadow-blue-100 ring-1 ring-blue-200'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-white/50',
                    ]">
                    <!-- Tab Icons -->
                    <div class="flex items-center justify-center w-6 h-6 rounded-full transition-all duration-300" :class="[
                        currentDirectionTab === tab.value 
                            ? 'bg-blue-100' 
                            : 'bg-gray-200 group-hover:bg-gray-300'
                    ]">

                        <svg v-if="tab.value === 'in'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                        </svg>
                        <svg v-else-if="tab.value === 'out'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </div>
                    
                    <span class="tracking-wide">{{ tab.label }}</span>
                    
                    <!-- Active indicator -->
                    <div v-if="currentDirectionTab === tab.value" 
                        class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-blue-600 rounded-full">
                    </div>
                </button>
            </nav>
        </div>

        <!-- Header Section -->
        <div class="flex flex-col space-y-6">
            <!-- Buttons First -->
            <div class="flex items-center justify-end">
                <!-- New Transfer -->
                <button @click="router.visit(route('transfers.create'))"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    New Transfer
                </button>
            </div>

            <!-- Filters Section -->
            <div class="mb-4">
                <div class="grid grid-cols-4 gap-3">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" v-model="search"
                            class="pl-10 pr-4 py-2 border border-gray-300 w-full focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Search a Transfer" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- warehouse or facility selection -->
                    <div>
                        <Multiselect v-model="transfer_type" :options="facilityType" :searchable="true"
                            :allow-empty="true" :show-labels="false" placeholder="All Transfer Type" class="">
                        </Multiselect>
                    </div>

                    <!-- Facility Selector -->
                    <div>
                        <Multiselect v-model="facility" :options="props.facilities" :searchable="true" :allow-empty="true"
                            :show-labels="false" placeholder="All Facilities" class="">
                        </Multiselect>
                    </div>

                    <!-- Warehouse Selector -->
                    <div>
                        <Multiselect v-model="warehouse" :options="props.warehouses" :close-on-select="true"
                            :clear-on-select="false" :preserve-search="true" :show-labels="false" placeholder="All Warehouses" class=""
                            :preselect-first="false">
                        </Multiselect>
                    </div>
                </div>

                <div class="grid grid-cols-4 gap-3 mt-2">
                    <div class="col-span-1">
                        <div class="flex items-center gap-2">
                            <input type="date" v-model="date_from"
                                class="border border-gray-300 w-full focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                @change="date_to = null"
                                placeholder="From Date" />
                            <span class="text-sm text-gray-600">to</span>
                            <input type="date" v-model="date_to"
                                class="border border-gray-300 w-full focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                :min="date_from"
                                placeholder="To Date" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-2">
                    <select class="rounded-3xl" name="per_page" id="per_page" @change="props.filters.page = 1"
                        v-model="per_page">
                        <option value="2">2 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>

            <!-- Status Tabs (Second Level) -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button v-for="tab in statusTabs" :key="tab.value" @click="currentTab = tab.value"
                        class="whitespace-nowrap py-4 px-3 border-b-4 font-bold text-xs" :class="[
                            currentTab === tab.value
                                ? 'border-green-500 text-green-600'
                                : 'border-transparent text-black hover:text-gray-700 hover:border-gray-300',
                        ]">
                        {{ tab.label }}
                    </button>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-12 mb-[40px] mt-2">
            <!-- Table Section (9 cols) -->
            <div class="md:col-span-9 sm:col-span-12">
                <div class="w-full overflow-x-auto">
                    <table class="w-full rounded-t-3xl overflow-hidden table-sm">
                        <thead>
                            <tr style="background-color: #F4F7FB;">
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-bold uppercase border-b rounded-tl-3xl"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;">
                                    ID
                                </th>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;">
                                    Date
                                </th>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;">
                                    To
                                </th>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;">
                                    Type
                                </th>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;">
                                    Items
                                </th>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-bold uppercase border-b rounded-tr-3xl"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="props.transfers.data.length === 0">
                                <td colspan="6" class="text-center py-8 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                            </path>
                                        </svg>
                                        <p class="mt-2 text-sm font-medium text-gray-900">
                                            No transfer data available
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Create a new transfer or adjust your
                                            filters to see results
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="transfer in props.transfers.data" :key="transfer.id"
                                class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-gray-900 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    <Link :href="route('transfers.show', transfer.id)"
                                        class="text-blue-600 hover:text-blue-800 hover:underline">
                                        {{ transfer.transferID }}
                                    </Link>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{ moment(transfer.transfer_date).format('DD/MM/YYYY') }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{ transfer.to_warehouse?.name || transfer.to_facility?.name }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{ transfer.transfer_type }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{ transfer.items_count }}
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-700 border-b rounded-tr-3xl" style="border-bottom: 1px solid #B7C6E6;">
                                    {{ transfer.status }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 flex justify-end items-center">
                    <TailwindPagination :data="props.transfers" :limit="2" @pagination-change-page="getResults" />
                </div>
            </div>

            <!-- Statistics Section (3 cols) -->
            <div class="md:col-span-3 sm:col-span-12">
                <div class="bg-white mb-4">
                    <h3 class="text-xs text-black mb-4">Transfer Statistics</h3>
                    <div class="flex justify-between gap-1">
                        <!-- Pending -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-amber-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute bottom-0 left-0 right-0 bg-amber-400 transition-all duration-500 ease-out"
                                    :style="{
                                        height: `${(pendingCount / totalCount) * 100}%`
                                    }">
                                </div>
                                <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs font-bold text-black">
                                    {{ pendingCount }}
                                </div>
                            </div>
                            <span class="text-xs text-black mt-2">Pending</span>
                        </div>

                        <!-- Approved -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-blue-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute bottom-0 left-0 right-0 bg-blue-400 transition-all duration-500 ease-out"
                                    :style="{
                                        height: `${(approvedCount / totalCount) * 100}%`
                                    }">
                                </div>
                                <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs font-bold text-black">
                                    {{ approvedCount }}
                                </div>
                            </div>
                            <span class="text-xs text-black mt-2">Approved</span>
                        </div>

                        <!-- In Process -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-yellow-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute bottom-0 left-0 right-0 bg-yellow-400 transition-all duration-500 ease-out"
                                    :style="{
                                        height: `${(inProcessCount / totalCount) * 100}%`
                                    }">
                                </div>
                                <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs font-bold text-black">
                                    {{ inProcessCount }}
                                </div>
                            </div>
                            <span class="text-xs text-black mt-2">In Process</span>
                        </div>

                        <!-- Dispatched -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-purple-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute bottom-0 left-0 right-0 bg-purple-400 transition-all duration-500 ease-out"
                                    :style="{
                                        height: `${(dispatchedCount / totalCount) * 100}%`
                                    }">
                                </div>
                                <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs font-bold text-black">
                                    {{ dispatchedCount }}
                                </div>
                            </div>
                            <span class="text-xs text-black mt-2">Dispatched</span>
                        </div>

                        <!-- Received -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-green-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute bottom-0 left-0 right-0 bg-green-400 transition-all duration-500 ease-out"
                                    :style="{
                                        height: `${(receivedCount / totalCount) * 100}%`
                                    }">
                                </div>
                                <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs font-bold text-black">
                                    {{ receivedCount }}
                                </div>
                            </div>
                            <span class="text-xs text-black mt-2">Received</span>
                        </div>

                        <!-- Rejected -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-red-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute bottom-0 left-0 right-0 bg-red-400 transition-all duration-500 ease-out"
                                    :style="{
                                        height: `${(rejectedCount / totalCount) * 100}%`
                                    }">
                                </div>
                                <div class="absolute -bottom-6 left-1/2 transform -translate-x-1/2 text-xs font-bold text-black">
                                    {{ rejectedCount }}
                                </div>
                            </div>
                            <span class="text-xs text-black mt-2">Rejected</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import {TailwindPagination} from "laravel-vue-pagination";
import moment from "moment";

const props = defineProps({
    transfers: Object,
    filters: Object,
    facilities: Array,
    warehouses: Array,
});

// Transfer direction tabs
const transferDirectionTabs = ref([
    { value: 'in', label: 'Incoming' },
    { value: 'out', label: 'Outgoing' },
]);

const currentDirectionTab = ref('in');

// Status tabs
const statusTabs = ref([
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'reviewed', label: 'Reviewed' },
    { value: 'approved', label: 'Approved' },
    { value: 'in_process', label: 'In Process' },
    { value: 'dispatched', label: 'Dispatched' },
    { value: 'received', label: 'Received' },
    { value: 'rejected', label: 'Rejected' },
]);

const currentTab = ref('all');

// Filter states
const search = ref(props.filters.search || '');
const transfer_type = ref(props.filters.transfer_type || '');
const facility = ref(props.filters.facility || '');
const warehouse = ref(props.filters.warehouse || '');
const date_from = ref(props.filters.date_from || '');
const date_to = ref(props.filters.date_to || '');
const per_page = ref(props.filters.per_page || '25');

// Transfer type options
const facilityType = ref([
    'Facility to Warehouse',
    'Warehouse to Facility',
    'Facility to Facility',
    'Warehouse to Warehouse'
]);

// Computed properties for statistics
const totalCount = computed(() => props.transfers.total || 0);
const pendingCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'pending').length || 0;
});
const approvedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'approved').length || 0;
});
const inProcessCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'in_process').length || 0;
});
const dispatchedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'dispatched').length || 0;
});
const receivedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'received').length || 0;
});
const rejectedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'rejected').length || 0;
});
const thisMonthCount = computed(() => {
    const thisMonth = moment().format('YYYY-MM');
    return props.transfers.data?.filter(t => 
        moment(t.transfer_date).format('YYYY-MM') === thisMonth
    ).length || 0;
});
const completionRate = computed(() => {
    if (totalCount.value === 0) return 0;
    const completed = receivedCount.value;
    return Math.round((completed / totalCount.value) * 100);
});

// Watch for filter changes
watch([search, transfer_type, facility, warehouse, date_from, date_to, per_page, currentTab, currentDirectionTab], () => {
    getResults();
}, { deep: true });

const getResults = () => {
    router.get(route('transfers.index'), {
        search: search.value,
        transfer_type: transfer_type.value,
        facility: facility.value,
        warehouse: warehouse.value,
        date_from: date_from.value,
        date_to: date_to.value,
        per_page: per_page.value,
        status: currentTab.value,
        direction: currentDirectionTab.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>
