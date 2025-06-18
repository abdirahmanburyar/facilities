<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import moment from 'moment';

// No longer using bulk actions

const props = defineProps({
    orders: Object,
    filters: Object,
    stats: Object,
    totalOrders: Number
});


// Fixed order types
const orderTypes = ['Quarterly', 'Replenishment'];

// Status configuration
const statusTabs = [
    { value: null, label: 'All Orders', color: 'blue' },
    { value: 'pending', label: 'Pending', color: 'yellow' },
    { value: 'reviewed', label: 'Reviewed', color: 'green' },
    { value: 'approved', label: 'Approved', color: 'green' },
    { value: 'in_process', label: 'In Process', color: 'blue' },
    { value: 'dispatched', label: 'Dispatched', color: 'purple' },
    { value: 'received', label: 'Received', color: 'green' },
    { value: 'delivered', label: 'Delivered', color: 'green' },
    { value: 'rejected', label: 'Rejected', color: 'red' }
];

// Filter states
const search = ref('');
const currentStatus = ref(props.filters.currentStatus);
const orderType = ref(props.filters?.orderType);
const dateFrom = ref(props.filters?.dateFrom);
const dateTo = ref(props.filters?.dateTo);

function reloadOrder() {
    const query = {}
    // Only add non-empty values to the query
    if (search.value) query.search = search.value;
    if (facility.value) query.facility = facility.value;
    if (currentStatus.value) query.currentStatus = currentStatus.value;
    if (orderType.value) query.orderType = orderType.value;
    if (dateFrom.value) query.dateFrom = dateFrom.value;
    if (dateTo.value) query.dateTo = dateTo.value;
    if (props.filters.page) query.page = props.filters.page;

    router.get(route('orders.index'), query, {
        preserveScroll: true,
        preserveState: true,
        only: ["orders", 'stats']
    })
}

function getResult(page = 1) {
    props.filters.page = page;
}

// Handle tab click
function handleTabClick(status) {
    currentStatus.value = status;
    reloadOrder();
}

// Watch for filter changes
watch([
    () => search.value,
    () => orderType.value,
    () => dateFrom.value,
    () => dateTo.value
], () => {
    reloadOrder();
});

const formatDate = (date) => {
    return moment(date).format('DD/MM/YYYY');
};


const statuses = {
  pending: 'Pending',
  reviewed: 'Reviewed',
  approved: 'Approved',
  rejected: 'Rejected',
  in_process: 'In Process',
  dispatched: 'Dispatched',
  received: 'Received',
};

const statusColors = {
  pending: '#eab308',
  reviewed: '#f59e0b',
  approved: '#22c55e',
  rejected: '#ef4444',
  in_process: '#3b82f6',
  dispatched: '#8b5cf6',
  received: '#6366f1',
};


const textColors = {
  pending: 'text-yellow-600',
  reviewed: 'text-amber-600',
  approved: 'text-green-600',
  rejected: 'text-red-600',
  in_process: 'text-blue-600',
  dispatched: 'text-purple-600',
  received: 'text-indigo-600',
};


const getPercentage = (key) => {
  if (props.totalOrders === 0) return 0;
  return Math.round((props.stats[key] / props.totalOrders) * 100);
};
</script>

<template>

    <Head title="All Orders" />
    <AuthenticatedLayout title="Tracks Your Orders" description="Keeping Essenticals Ready, Every Time"
        img="/assets/images/orders.png">
        <div class="mb-6">
            <!-- Filters Section -->
            <div class="flex items-center justify-between mb-[30px]">
                <h1 class="text-xl font-bold text-gray-900">Facility Orders</h1>
                <Link :href="route('orders.create')"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
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
                        <Multiselect v-model="orderType" :options="orderTypes" :searchable="true"
                            :close-on-select="true" :show-labels="false" :allow-empty="true"
                            placeholder="Select Order Type">
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
                <div class="lg:col-span-10 text-xs">
                    <div>
                        <div class="overflow-auto">
                            <table class="w-full table-sm">
                                <thead style="background-color: #eef1f8" class="rounded-t-xl">
                                    <tr>
                                        <!-- Checkbox column removed -->
                                        <th
                                            class="px-2 py-2 text-left text-xs font-medium text-black capitalize tracking-wider">
                                            Order Number
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-medium text-black capitalize tracking-wider">
                                            Facility
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-medium text-black capitalize tracking-wider">
                                            Order Type
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-medium text-black capitalize tracking-wider">
                                            Order Date
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-medium text-black capitalize tracking-wider">
                                            Expected Date
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-medium text-black capitalize tracking-wider">
                                            Handled By
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-medium text-black capitalize tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <tr v-if="orders.data?.length === 0">
                                        <td colspan="7"
                                            class="px-2 py-2 text-center text-sm text-black border-b border-grey-500">
                                            No orders found
                                        </td>
                                    </tr>
                                    <tr v-for="order in orders.data" :key="order.id" class="border-b border-grey-500"
                                        :class="{
                                            'hover:bg-gray-50': true,
                                            'text-red-500':
                                                order.status === 'rejected',
                                        }">
                                        <!-- Checkbox cell removed -->
                                        <td class="px-2 py-2 whitespace-nowrap">
                                            <div class="text-xs text-gray-900">
                                                <Link :href="route(
                                                    'orders.show',
                                                    order.id
                                                )
                                                    ">{{ order.order_number }}</Link>
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap">
                                            <div class="text-xs text-gray-900">
                                                {{ order.facility?.name }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-black">
                                            {{ order.order_type }}
                                        </td>

                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-black">
                                            {{ formatDate(order.order_date) }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-black">
                                            {{ formatDate(order.expected_date) }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-black">
                                            {{
                                                order.handledby?.name ||
                                                "Not assigned"
                                            }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <!-- Status Progress Icons - Only show actions taken -->
                                                <div class="flex items-center gap-1">
                                                    <!-- Always show pending as it's the initial state -->
                                                    <img src="/assets/images/pending.png" class="w-6 h-6" alt="pending"
                                                        title="Pending" />

                                                    <!-- Only show approved if status is approved or further -->
                                                    <img v-if="
                                                        [
                                                            'reviewed',
                                                            'approved',
                                                            'in_process',
                                                            'dispatched',
                                                            'delivered',
                                                            'received',
                                                        ].includes(order.status)
                                                    " src="/assets/images/review.png" class="w-6 h-6" alt="Reviewed"
                                                        title="Reviewed" />
                                                    <!-- Only show approved if status is approved or further -->
                                                    <img v-if="
                                                        [
                                                            'approved',
                                                            'in_process',
                                                            'dispatched',
                                                            'delivered',
                                                            'received',
                                                        ].includes(order.status)
                                                    " src="/assets/images/approved.png" class="w-6 h-6" alt="Approved"
                                                        title="Approved" />
                                                    <!-- Only show rejected if status is rejected -->
                                                    <img v-if="
                                                        order.status ===
                                                        'rejected'
                                                    " src="/assets/images/rejected.svg" class="w-6 h-6" alt="Rejected"
                                                        title="Rejected" />

                                                    <!-- Only show in_process if status is in_process or further -->
                                                    <img v-if="
                                                        [
                                                            'in_process',
                                                            'dispatched',
                                                            'delivered',
                                                            'received',
                                                        ].includes(order.status)
                                                    " src="/assets/images/inprocess.png" class="w-6 h-6"
                                                        alt="In Process" title="In Process" />

                                                    <!-- Only show dispatched if status is dispatched or further -->
                                                    <img v-if="
                                                        [
                                                            'dispatched',
                                                            'delivered',
                                                            'received',
                                                        ].includes(order.status)
                                                    " src="/assets/images/dispatch.png" class="w-6 h-6"
                                                        alt="Dispatched" title="Dispatched" />

                                                    <img v-if="
                                                        [
                                                            'delivered',
                                                            'received',
                                                        ].includes(order.status)
                                                    " src="/assets/images/delivery.png" class="w-6 h-6"
                                                        alt="Dispatched" title="Dispatched" />


                                                    <!-- Only show received if status is received -->
                                                    <img v-if="
                                                        [
                                                            'received',
                                                        ].includes(order.status)
                                                    " src="/assets/images/received.png" class="w-6 h-6" alt="Received"
                                                        title="Received" />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <TailwindPagination :data="props.orders" :limit="2" @pagination-change-page="getResult" />
                    </div>
                </div>
                <!-- Status Statistics -->
                <div class="lg:col-span-1">
                    <div class="sticky text-xs top-4 sticky:mt-5">
                        <div class="space-y-8">
                            <!-- Pending -->
                            <div class="relative">
                                <div class="flex items-center mb-2">
                                    <div class="w-16 h-16 relative mr-4">
                                        <svg class="w-16 h-16 transform -rotate-90">
                                            <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0"
                                                stroke-width="4" />
                                            <circle cx="32" cy="32" r="28" fill="none" stroke="#eab308" stroke-width="4"
                                                :stroke-dasharray="`${(stats.pending / props.totalOrders) *
                                                    125.6
                                                    } 125.6`" />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-xs font-bold text-yellow-600">{{
                                                totalOrders > 0
                                                    ? Math.round(
                                                        (stats.pending /
                                                            props.totalOrders) *
                                                        100
                                                    )
                                                    : 0
                                            }}%</span>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-xs font-bold text-gray-900">
                                            {{ stats.pending }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            Pending
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <!-- Pending -->
                            <div class="relative">
                                <div class="flex items-center mb-2">
                                    <div class="w-16 h-16 relative mr-4">
                                        <svg class="w-16 h-16 transform -rotate-90">
                                            <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0"
                                                stroke-width="4" />
                                            <circle cx="32" cy="32" r="28" fill="none" stroke="#eab308" stroke-width="4"
                                                :stroke-dasharray="`${(stats.reviewed / props.totalOrders) *
                                                    125.6
                                                    } 125.6`" />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-xs font-bold text-yellow-600">{{
                                                totalOrders > 0
                                                    ? Math.round(
                                                        (stats.reviewed /
                                                            props.totalOrders) *
                                                        100
                                                    )
                                                    : 0
                                            }}%</span>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="text-xs font-bold text-gray-900">
                                            {{ stats.reviewed }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            Reviewed
                                        </div>
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
                                            :stroke-dasharray="`${(stats.approved / totalOrders) *
                                                125.6
                                                } 125.6`" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-bold text-green-600">{{
                                            totalOrders > 0
                                                ? Math.round(
                                                    (stats.approved /
                                                        totalOrders) *
                                                    100
                                                )
                                                : 0
                                        }}%</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900">
                                        {{ stats.approved }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Approved
                                    </div>
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
                                            :stroke-dasharray="`${(stats.rejected / totalOrders) *
                                                125.6
                                                } 125.6`" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-bold text-red-600">{{
                                            totalOrders > 0
                                                ? Math.round(
                                                    (stats.rejected /
                                                        totalOrders) *
                                                    100
                                                )
                                                : 0
                                        }}%</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900">
                                        {{ stats.rejected }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Rejected
                                    </div>
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
                                            :stroke-dasharray="`${(stats.in_process / totalOrders) *
                                                125.6
                                                } 125.6`" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-bold text-blue-600">{{
                                            totalOrders > 0
                                                ? Math.round(
                                                    (stats.in_process /
                                                        totalOrders) *
                                                    100
                                                )
                                                : 0
                                        }}%</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900">
                                        {{ stats.in_process }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        In Process
                                    </div>
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
                                            :stroke-dasharray="`${(stats.dispatched / props.totalOrders) *
                                                125.6
                                                } 125.6`" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-bold text-purple-600">{{
                                            totalOrders > 0
                                                ? Math.round(
                                                    (stats.dispatched / props.totalOrders) * 100
                                                )
                                                : 0
                                        }}%</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900">
                                        {{ stats.dispatched }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Dispatched
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Received -->
                        <div class="relative">
                            <div class="flex items-center mb-2">
                                <div class="w-16 h-16 relative mr-4">
                                    <svg class="w-16 h-16 transform -rotate-90">
                                        <circle cx="32" cy="32" r="28" fill="none" stroke="#e2e8f0" stroke-width="4" />
                                        <circle cx="32" cy="32" r="28" fill="none" stroke="#6366f1" stroke-width="4"
                                            :stroke-dasharray="`${(stats.received / totalOrders) *
                                                125.6
                                                } 125.6`" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xs font-bold text-indigo-600">{{
                                            props.totalOrders > 0
                                                ? Math.round(
                                                    (stats.received /
                                                        props.totalOrders) *
                                                    100
                                                )
                                                : 0
                                        }}%</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900">
                                        {{ stats.received }}
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Received
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
