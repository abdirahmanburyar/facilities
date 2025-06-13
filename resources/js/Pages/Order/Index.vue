<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';
import axios from 'axios';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import moment from 'moment';

// No longer using bulk actions

const props = defineProps({
    orders: Object,
    filters: Object,
    stats: Object
});

// Track loading state for each order action
const loadingActions = ref({});

// Fixed order types
const orderTypes = [
    { id: 'quarterly', name: 'Quarterly' },
    { id: 'replenishment', name: 'Replenishment' }
];

// Compute total orders
const totalOrders = computed(() => {
    return props.stats.pending + props.stats.approved + props.stats.in_process +
        props.stats.dispatched + props.stats.delivered;
});

// Status configuration
const statusTabs = [
    { value: null, label: 'All Orders', color: 'blue' },
    { value: 'pending', label: 'Pending', color: 'yellow' },
    { value: 'approved', label: 'Approved', color: 'green' },
    { value: 'in_process', label: 'In Process', color: 'blue' },
    { value: 'dispatched', label: 'Dispatched', color: 'purple' },
    { value: 'received', label: 'Received', color: 'green' },
    { value: 'rejected', label: 'Rejected', color: 'red' }
];

// Filter states
const search = ref('');
const currentStatus = ref(props.filters.currentStatus || null);
const facility = ref(props.filters?.facility || null);
const orderType = ref(props.filters?.orderType || null);
const facilityLocation = ref(props.filters?.facilityLocation || null);
const dateFrom = ref(props.filters?.dateFrom || null);
const dateTo = ref(props.filters?.dateTo || null);

const changeStatus = (orderId, newStatus) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to change the order status to ${newStatus}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, change it!'
    }).then(async (result) => {
        if (result.isConfirmed) {
            // Set loading state for this order
            loadingActions.value[orderId] = true;
            
            try {
                // For rejection, use a different endpoint or pass different parameters
                if (newStatus === 'rejected') {
                    await axios.post('/orders/reject', {
                        order_id: orderId
                    })
                        .then(response => {
                            Swal.fire(
                                'Rejected!',
                                'Order has been rejected.',
                                'success'
                            ).then(() => {
                                reloadOrder();
                            });
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error!',
                                error.response?.data?.message || 'Failed to reject order',
                                'error'
                            );
                        });
                } else {
                    // For other status changes, use the existing endpoint
                    await axios.post(route('orders.change-status'), {
                        order_id: orderId,
                        status: newStatus
                    })
                        .then(response => {
                            Swal.fire(
                                'Updated!',
                                'Order status has been updated.',
                                'success'
                            ).then(() => {
                                reloadOrder();
                            });
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error!',
                                error.response?.data?.message || 'Failed to update order status',
                                'error'
                            );
                        });
                }
            } finally {
                // Clear loading state
                loadingActions.value[orderId] = false;
            }
        }
    });
};

// Bulk status change functionality removed

// Bulk selection functionality removed

// Initialize selected values with objects from the props arrays
const selectedFacility = ref(props.filters?.facility ?
    props.facilities.find(f => f.id === parseInt(props.filters.facility)) || null : null);
const selectedOrderType = ref(props.filters?.orderType ?
    orderTypes.find(t => t.id === props.filters.orderType) || null : null);

function handleSelect(selected) {
    selectedFacility.value = selected;
    facility.value = selected ? selected.id : null;
}

function handleOrderTypeSelect(selected) {
    selectedOrderType.value = selected;
    orderType.value = selected ? selected.id : null;
    console.log('Selected order type:', selected);
}

function handleFacilityLocationSelect(selected) {
    selectedFacilityLocation.value = selected;
    facilityLocation.value = selected ? selected.id : null;
}

// Handle deselection for any filter
function handleRemove(filterType) {
    if (filterType === 'facility') {
        facility.value = null;
        selectedFacility.value = null;
    } else if (filterType === 'orderType') {
        orderType.value = null;
        selectedOrderType.value = null;
    } else if (filterType === 'facilityLocation') {
        facilityLocation.value = null;
        selectedFacilityLocation.value = null;
    }
    // Reload with updated filters
    reloadOrder();
}

const getStatusActions = (order) => {
    const actions = [];

    switch (order.status) {
        case 'pending':
            actions.push({ label: 'Approve', status: 'approved', color: 'green', icon: '/assets/images/approved.png' });
            actions.push({ label: 'Reject', status: 'rejected', color: 'red', icon: 'svg' });
            break;
        case 'approved':
            actions.push({ label: 'Process', status: 'in_process', color: 'blue', icon: '/assets/images/inprocess.png' });
            // Reject action removed for approved orders
            break;
        case 'in_process':
            actions.push({ label: 'Dispatch', status: 'dispatched', color: 'purple', icon: '/assets/images/dispatch.png' });
            // Reject action removed for in_process orders
            break;
        case 'dispatched':
            actions.push({ label: 'Deliver', status: 'delivered', color: 'indigo', icon: '/assets/images/delivery.png' });
            // Reject action removed for dispatched orders
            break;
        case 'rejected':
            actions.push({ label: 'Approve', status: 'approved', color: 'green', icon: '/assets/images/approved.png' });
            break;
    }

    return actions
};

function reloadOrder() {
    const query = {}

    // Only add non-empty values to the query
    if (search.value) query.search = search.value;
    if (facility.value) query.facility = facility.value;
    if (currentStatus.value) query.currentStatus = currentStatus.value;
    if (orderType.value) query.orderType = orderType.value;
    if (facilityLocation.value) query.facilityLocation = facilityLocation.value;
    if (dateFrom.value) query.dateFrom = dateFrom.value;
    if (dateTo.value) query.dateTo = dateTo.value;

    console.log('Applying filters:', query);

    router.get(route('orders.index'), query, {
        preserveScroll: true,
        preserveState: true,
        only: ["orders", 'filters', 'stats']
    })
}

// Handle tab click
function handleTabClick(status) {
    currentStatus.value = status;
    reloadOrder();
}

// Watch for filter changes
watch([
    () => search.value,
    () => facility.value,
    () => orderType.value,
    () => facilityLocation.value,
    () => dateFrom.value,
    () => dateTo.value
], () => {
    reloadOrder();
});

const formatDate = (date) => {
    return moment(date).format('DD/MM/YYYY');
};
</script>

<template>

    <Head title="All Orders" />
    <AuthenticatedLayout title="Tracks Your Orders" description="Keeping Essenticals Ready, Every Time" img="/assets/images/orders.png">
       <div class="mb-6">
         <!-- Filters Section -->
         <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold text-gray-900">Facility Orders</h1>
            <Link :href="route('orders.create')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                Create New Order
            </Link>
        </div>

        <div class="bg-white mb-6">
            <div class="flex flex-wrap gap-4 items-center mb-5">
                <!-- Search -->
                <div class="relative w-full sm:w-auto flex-grow min-w-[250px]">
                    <input type="text" v-model="search" placeholder="Search orders..."
                        class="w-full px-4 py-2 pl-10 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Order Type Filter -->
                <div class="w-full sm:w-auto flex-none min-w-[200px] w-[220px]">
                    <Multiselect v-model="selectedOrderType" :options="orderTypes" :searchable="true"
                        :close-on-select="true" :show-labels="false" :allow-empty="true" placeholder="Select Order Type"
                        track-by="id" label="name" @select="handleOrderTypeSelect" @remove="handleRemove('orderType')">
                    </Multiselect>
                </div>

                <!-- Date From -->
                <div class="w-full sm:w-auto flex-none min-w-[150px] w-[180px]">
                    <div class="relative">
                        <input type="date" v-model="dateFrom"
                            class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <label class="absolute -top-5 left-0 text-xs text-gray-500">From Date</label>
                    </div>
                </div>

                <!-- Date To -->
                <div class="w-full sm:w-auto flex-none min-w-[150px] w-[180px]">
                    <div class="relative">
                        <input type="date" v-model="dateTo"
                            class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <label class="absolute -top-5 left-0 text-xs text-gray-500">To Date</label>
                    </div>
                </div>

            </div>
            <!-- Status Tabs -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button v-for="tab in statusTabs" :key="tab.value" @click="handleTabClick(tab.value)"
                        class="whitespace-nowrap py-4 px-1 border-b-4 font-bold text-xs" :class="[
                            currentStatus === tab.value ?
                                `border-${tab.color}-500 text-${tab.color}-600` :
                                'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                        {{ tab.label }}
                        <span v-if="props.orders.meta?.counts && props.orders.meta.counts[tab.value || 'all']"
                            class="ml-2 px-2 py-0.5 text-xs rounded-full"
                            :class="`bg-${tab.color}-100 text-${tab.color}-800`">
                            {{ props.orders.meta.counts[tab.value || 'all'] }}
                        </span>
                    </button>
                </nav>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Orders Table -->
            <div class="lg:col-span-10">
                <div class="bg-white overflow-auto">
                    <div class="shadow overflow-x-auto">
                        <table class="min-w-full divide-y">

                            <thead>
                                <tr>
                                    <!-- Checkbox column removed -->
                                    <th
                                        class="px-6 py-3 text-left text-xs text-black uppercase">
                                        Order Number
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs text-black uppercase">
                                        Facility
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs text-black uppercase">
                                        Order Type
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs text-black uppercase">
                                        Date
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs text-black uppercase">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white border-b border-grey-500">
                                <tr v-if="orders.data?.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <img src="/assets/images/empty-order.svg" alt="No orders" class="w-32 h-32 mb-4" 
                                                onerror="this.onerror=null; this.src='/assets/images/empty-box.png'">
                                            <h3 class="text-lg font-medium text-gray-900 mb-1">No Orders Found</h3>
                                            <p class="text-xs text-gray-500 max-w-md">
                                                {{ currentStatus ? 
                                                    `There are no orders with '${statusTabs.find(t => t.value === currentStatus)?.label || currentStatus}' status.` : 
                                                    'There are no orders matching your current filters.' }}
                                            </p>
                                            <div class="mt-6">
                                                <Link :href="route('orders.create')" 
                                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    Create New Order
                                                </Link>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-for="order in orders.data" :key="order.id" class="border-b" :class="{
                                    'hover:bg-gray-50': true,
                                    'text-red-500 border border-red-500': order.status === 'rejected'
                                }">
                                    <!-- Checkbox cell removed -->
                                    <td class="px-6 text-xs py-4">
                                        <Link :href="route('orders.show', order.id)">{{ order.order_number }}</Link>
                                    </td>
                                    <td class="px-6 text-xs py-4">
                                        {{ order.facility?.name }}
                                    </td>
                                    <td class="px-6 text-xs py-4">
                                        {{ order.order_type }}
                                    </td>

                                    <td class="px-6 text-xs py-4">
                                        <div class="text-xs text-gray-900">{{ formatDate(order.created_at) }}</div>
                                        <div class="text-xs text-gray-500">Expected: {{ formatDate(order.expected_date)
                                            }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <!-- Status Progress Icons - Only show actions taken -->
                                            <div class="flex items-center gap-1">
                                                <!-- Always show pending as it's the initial state -->
                                                <img src="/assets/images/pending.png" class="w-10 h-10" alt="pending" />

                                                <!-- Only show approved if status is approved or further -->
                                                <img v-if="['approved', 'in_process', 'dispatched', 'delivered', 'received'].includes(order.status)"
                                                    src="/assets/images/approved.png" class="w-10 h-10"
                                                    alt="Approved" />
                                                <!-- Only show rejected if status is rejected -->
                                                <img v-if="order.status === 'rejected'"
                                                    src="/assets/images/rejected.svg" class="w-10 h-10"
                                                    alt="Rejected" />

                                                <!-- Only show in_process if status is in_process or further -->
                                                <img v-if="['in_process', 'dispatched', 'delivered', 'received'].includes(order.status)"
                                                    src="/assets/images/inprocess.png" class="w-10 h-10"
                                                    alt="In Process" />

                                                <!-- Only show dispatched if status is dispatched or further -->
                                                <img v-if="['dispatched', 'delivered', 'received'].includes(order.status)"
                                                    src="/assets/images/dispatch.png" class="w-10 h-10"
                                                    alt="Dispatched" />

                                                <!-- Only show received if status is received -->
                                                <img v-if="order.status === 'received'"
                                                    src="/assets/images/received.png" class="w-10 h-10"
                                                    alt="Received" />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Status Statistics -->
            <div class="lg:col-span-1">
                <div class="sticky top-4 sticky:mt-5">
                    <div class="space-y-5">
                        <!-- Pending -->
                        <div class="relative">
                            <div class="flex items-center mb-2">
                                <div class="w-16 h-16 relative mr-4">
                                    <svg class="w-16 h-16 transform -rotate-90">
                                        <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="4" />
                                        <circle cx="32" cy="32" r="28" fill="none" stroke="#eab308" stroke-width="4"
                                            :stroke-dasharray="`${(stats.pending / totalOrders) * 125.6} 125.6`" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-base font-bold text-yellow-600">{{ totalOrders > 0 ?
                                            Math.round((stats.pending / totalOrders) * 100) : 0 }}%</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900">{{ stats.pending }}</div>
                                    <div class="text-base text-gray-600">Pending</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approved -->
                    <div class="relative">
                        <div class="flex items-center mb-2">
                            <div class="w-16 h-16 relative mr-4">
                                <svg class="w-16 h-16 transform -rotate-90">
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="4" />
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#22c55e" stroke-width="4"
                                        :stroke-dasharray="`${(stats.approved / totalOrders) * 125.6} 125.6`" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-base font-bold text-green-600">{{ totalOrders > 0 ?
                                        Math.round((stats.approved
                                        / totalOrders) * 100) : 0 }}%</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900">{{ stats.approved }}</div>
                                <div class="text-base text-gray-600">Approved</div>
                            </div>
                        </div>
                    </div>

                    <!-- Rejected -->
                    <div class="relative">
                        <div class="flex items-center mb-2">
                            <div class="w-16 h-16 relative mr-4">
                                <svg class="w-16 h-16 transform -rotate-90">
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="4" />
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#ef4444" stroke-width="4"
                                        :stroke-dasharray="`${(stats.rejected / totalOrders) * 125.6} 125.6`" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-base font-bold text-red-600">{{ totalOrders > 0 ?
                                        Math.round((stats.rejected /
                                        totalOrders) * 100) : 0 }}%</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900">{{ stats.rejected }}</div>
                                <div class="text-base text-gray-600">Rejected</div>
                            </div>
                        </div>
                    </div>

                    <!-- In Process -->
                    <div class="relative">
                        <div class="flex items-center mb-2">
                            <div class="w-16 h-16 relative mr-4">
                                <svg class="w-16 h-16 transform -rotate-90">
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="4" />
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#3b82f6" stroke-width="4"
                                        :stroke-dasharray="`${(stats.in_process / totalOrders) * 125.6} 125.6`" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-base font-bold text-blue-600">{{ totalOrders > 0 ?
                                        Math.round((stats.in_process / totalOrders) * 100) : 0 }}%</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900">{{ stats.in_process }}</div>
                                <div class="text-base text-gray-600">In Process</div>
                            </div>
                        </div>
                    </div>

                    <!-- Dispatched -->
                    <div class="relative">
                        <div class="flex items-center mb-2">
                            <div class="w-16 h-16 relative mr-4">
                                <svg class="w-16 h-16 transform -rotate-90">
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="4" />
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#8b5cf6" stroke-width="4"
                                        :stroke-dasharray="`${(stats.dispatched / totalOrders) * 125.6} 125.6`" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-base font-bold text-purple-600">{{ totalOrders > 0 ?
                                        Math.round((stats.dispatched / totalOrders) * 100) : 0 }}%</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900">{{ stats.dispatched }}</div>
                                <div class="text-base text-gray-600">Dispatched</div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivered -->
                    <div class="relative">
                        <div class="flex items-center mb-2">
                            <div class="w-16 h-16 relative mr-4">
                                <svg class="w-16 h-16 transform -rotate-90">
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="4" />
                                    <circle cx="32" cy="32" r="28" fill="none" stroke="#6366f1" stroke-width="4"
                                        :stroke-dasharray="`${(stats.delivered / totalOrders) * 125.6} 125.6`" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-base font-bold text-indigo-600">{{ totalOrders > 0 ?
                                        Math.round((stats.delivered / totalOrders) * 100) : 0 }}%</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900">{{ stats.delivered }}</div>
                                <div class="text-base text-gray-600">Delivered</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       </div>
    </AuthenticatedLayout>
</template>
