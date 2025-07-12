<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch, computed, onMounted, onBeforeUnmount } from "vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import moment from "moment";
import { TailwindPagination } from "laravel-vue-pagination";

const props = defineProps({
    orders: Object,
    filters: Object,
    stats: Object,
    totalOrders: Number,
});

// Fixed order types
const orderTypes = ["Quarterly", "Replenishment"];

// Status configuration
const statusTabs = [
    { value: null, label: "All Orders", color: "blue" },
    { value: "pending", label: "Pending", color: "yellow" },
    { value: "reviewed", label: "Reviewed", color: "green" },
    { value: "approved", label: "Approved", color: "green" },
    { value: "in_process", label: "In Process", color: "blue" },
    { value: "dispatched", label: "Dispatched", color: "purple" },
    { value: "received", label: "Received", color: "green" },
    { value: "delivered", label: "Delivered", color: "green" },
    { value: "rejected", label: "Rejected", color: "red" },
];

// Filter states
const search = ref(props.filters.search || "");
const currentStatus = ref(props.filters.currentStatus || null);
const orderType = ref(props.filters?.orderType);
const dateFrom = ref(props.filters?.dateFrom);
const dateTo = ref(props.filters?.dateTo);
const per_page = ref(props.filters.per_page || 25);

// Debounce setup
let searchTimeout = null;

function reloadOrder() {
    const query = {};
    // Only add non-empty values to the query
    if (search.value) query.search = search.value;
    if (currentStatus.value) query.currentStatus = currentStatus.value;
    if (orderType.value) query.orderType = orderType.value;
    if (dateFrom.value) query.dateFrom = dateFrom.value;
    if (dateTo.value) query.dateTo = dateTo.value;
    if (props.filters.page) query.page = props.filters.page;
    if (per_page.value) query.per_page = per_page.value;

    router.get(route("orders.index"), query, {
        preserveScroll: true,
        preserveState: true,
        only: ["orders", "stats"],
    });
}

function getResult(page = 1) {
    props.filters.page = page;
}

// Handle tab click
function handleTabClick(status) {
    currentStatus.value = status;
    reloadOrder();
}

// Watch for filter changes with debouncing for search
watch(
    () => search.value,
    () => {
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        searchTimeout = setTimeout(() => {
            reloadOrder();
        }, 500);
    }
);

// Watch for other filter changes (no debouncing needed)
watch(
    [
        () => currentStatus.value,
        () => orderType.value,
        () => dateFrom.value,
        () => dateTo.value,
        () => per_page.value,
    ],
    () => {
        reloadOrder();
    }
);

// Cleanup on unmount
onBeforeUnmount(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});

const formatDate = (date) => {
    return moment(date).format("DD/MM/YYYY");
};

const statuses = {
    pending: "Pending",
    reviewed: "Reviewed",
    approved: "Approved",
    rejected: "Rejected",
    in_process: "In Process",
    dispatched: "Dispatched",
    received: "Received",
};

const statusColors = {
    pending: "#eab308",
    reviewed: "#f59e0b",
    approved: "#22c55e",
    rejected: "#ef4444",
    in_process: "#3b82f6",
    dispatched: "#8b5cf6",
    received: "#6366f1",
};

const textColors = {
    pending: "text-yellow-600",
    reviewed: "text-amber-600",
    approved: "text-green-600",
    rejected: "text-red-600",
    in_process: "text-blue-600",
    dispatched: "text-purple-600",
    received: "text-indigo-600",
};

const getPercentage = (key) => {
    if (props.totalOrders === 0) return 0;
    return Math.round((props.stats[key] / props.totalOrders) * 100);
};

const showLegend = ref(false);
</script>

<template>
    <Head title="All Orders" />
    <AuthenticatedLayout
        title="Tracks Your Orders"
        description="Keeping Essentials Ready, Every Time"
        img="/assets/images/orders.png"
    >
        <div class="mb-6">
            <!-- Filters Section -->
            <div class="flex items-center justify-between mb-[30px]">
                <h1 class="text-xl font-bold text-gray-900">Facility Orders</h1>
                <div class="flex gap-2">
                    <Link
                        :href="route('orders.create')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        Create New Order
                    </Link>
                    <div class="flex justify-end items-center gap-2">
                        <select
                            v-model="per_page"
                            @change="props.filters.page = 1"
                            class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="10">10 Per page</option>
                            <option value="25">25 Per page</option>
                            <option value="50">50 Per page</option>
                            <option value="100">100 Per page</option>
                        </select>
                        <button
                            @click="showLegend = true"
                            class="flex items-center justify-center w-10 h-10 bg-blue-50 text-blue-700 rounded-full hover:bg-blue-100 transition-colors duration-200 shadow"
                            aria-label="Show Icon Legend"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Status Tabs -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button
                            v-for="tab in statusTabs"
                            :key="tab.value"
                            @click="handleTabClick(tab.value)"
                            class="whitespace-nowrap py-4 px-1 border-b-4 font-bold text-xs"
                            :class="[
                                currentStatus === tab.value
                                    ? `border-${tab.color}-500 text-${tab.color}-600`
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            ]"
                        >
                            {{ tab.label }}
                            <span
                                v-if="
                                    props.orders.meta?.counts &&
                                    props.orders.meta.counts[tab.value || 'all']
                                "
                                class="ml-2 px-2 py-0.5 text-xs rounded-full"
                                :class="`bg-${tab.color}-100 text-${tab.color}-800`"
                            >
                                {{
                                    props.orders.meta.counts[tab.value || "all"]
                                }}
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
                                <thead style="background-color: #F4F7FB;">
                                    <tr>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-bold uppercase border-b rounded-tl-lg"
                                            style="color: #4F6FCB; border-bottom: 2px solid #F4F7FB;"
                                        >
                                            Order Number
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                            style="color: #4F6FCB; border-bottom: 2px solid #F4F7FB;"
                                        >
                                            Facility
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                            style="color: #4F6FCB; border-bottom: 2px solid #F4F7FB;"
                                        >
                                            Order Type
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                            style="color: #4F6FCB; border-bottom: 2px solid #F4F7FB;"
                                        >
                                            Order Date
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                            style="color: #4F6FCB; border-bottom: 2px solid #F4F7FB;"
                                        >
                                            Expected Date
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                            style="color: #4F6FCB; border-bottom: 2px solid #F4F7FB;"
                                        >
                                            Handled By
                                        </th>
                                        <th
                                            class="px-2 py-2 text-left text-xs font-bold uppercase border-b rounded-tr-lg"
                                            style="color: #4F6FCB; border-bottom: 2px solid #F4F7FB;"
                                        >
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <tr v-if="orders.data?.length === 0">
                                        <td
                                            colspan="7"
                                            class="px-2 py-2 text-center text-sm text-gray-600"
                                            style="border-bottom: 1px solid #F4F7FB;"
                                        >
                                            No orders found
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="order in orders.data"
                                        :key="order.id"
                                        class="hover:bg-gray-50 transition-colors duration-150"
                                        style="border-bottom: 1px solid #F4F7FB;"
                                    >
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                            <Link :href="route('orders.show', order.id)">{{ order.order_number }}</Link>
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">
                                            {{ order.facility?.name }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-600">
                                            {{ order.order_type }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-600">
                                            {{ formatDate(order.order_date) }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-600">
                                            {{ formatDate(order.expected_date) }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-600">
                                            {{ order.facility?.handledby?.name || "Not assigned" }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <!-- Status Progress Icons - Only show actions taken -->
                                                <div class="flex items-center gap-1">
                                                    <!-- Always show pending as it's the initial state -->
                                                    <img src="/assets/images/pending.png" class="w-6 h-6" alt="pending" title="Pending" />
                                                    <!-- Only show reviewed if status is reviewed or further -->
                                                    <img v-if="['reviewed','approved','in_process','dispatched','delivered','received'].includes(order.status)" src="/assets/images/review.png" class="w-6 h-6" alt="Reviewed" title="Reviewed" />
                                                    <!-- Only show approved if status is approved or further -->
                                                    <img v-if="['approved','in_process','dispatched','delivered','received'].includes(order.status)" src="/assets/images/approved.png" class="w-6 h-6" alt="Approved" title="Approved" />
                                                    <!-- Only show rejected if status is rejected -->
                                                    <img v-if="order.status === 'rejected'" src="/assets/images/rejected.png" class="w-6 h-6" alt="Rejected" title="Rejected" />
                                                    <!-- Only show in_process if status is in_process or further -->
                                                    <img v-if="['in_process','dispatched','delivered','received'].includes(order.status)" src="/assets/images/inprocess.png" class="w-6 h-6" alt="In Process" title="In Process" />
                                                    <!-- Only show dispatched if status is dispatched or further -->
                                                    <img v-if="['dispatched','delivered','received'].includes(order.status)" src="/assets/images/dispatch.png" class="w-6 h-6" alt="Dispatched" title="Dispatched" />
                                                    <img v-if="['delivered','received'].includes(order.status)" src="/assets/images/delivery.png" class="w-6 h-6" alt="Delivered" title="Delivered" />
                                                    <!-- Only show received if status is received -->
                                                    <img v-if="['received'].includes(order.status)" src="/assets/images/received.png" class="w-6 h-6" alt="Received" title="Received" />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex justify-end">
                            <TailwindPagination
                                :data="props.orders"
                                :limit="2"
                                @pagination-change-page="getResult"
                            />
                        </div>
                    </div>
                </div>
                <!-- Status Statistics -->
                <div class="lg:col-span-2">
                    <div class="sticky text-xs top-4 sticky:mt-5">
                        <div class="space-y-8">
                            <!-- Pending -->
                            <div class="relative">
                                <div class="flex items-center mb-2">
                                    <div class="w-16 h-16 relative mr-4">
                                        <svg class="w-16 h-16 transform -rotate-90">
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#e2e8f0"
                                                stroke-width="4"
                                            />
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#eab308"
                                                stroke-width="4"
                                                :stroke-dasharray="(stats.pending === totalOrders && totalOrders > 0) ? '175.93 175.93' : `${(stats.pending / totalOrders) * 175.93} 175.93`"
                                            />
                                        </svg>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold text-yellow-600"
                                                >{{
                                                    totalOrders > 0
                                                        ? Math.round(
                                                              (stats.pending /
                                                                  totalOrders) *
                                                                  100
                                                          )
                                                        : 0
                                                }}%</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-lg font-bold text-gray-900"
                                        >
                                            {{ stats.pending }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            Pending
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reviewed -->
                            <div class="relative">
                                <div class="flex items-center mb-2">
                                    <div class="w-16 h-16 relative mr-4">
                                        <svg class="w-16 h-16 transform -rotate-90">
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#e2e8f0"
                                                stroke-width="4"
                                            />
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#f59e0b"
                                                stroke-width="4"
                                                :stroke-dasharray="(stats.reviewed === totalOrders && totalOrders > 0) ? '175.93 175.93' : `${(stats.reviewed / totalOrders) * 175.93} 175.93`"
                                            />
                                        </svg>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold text-amber-600"
                                                >{{
                                                    totalOrders > 0
                                                        ? Math.round(
                                                              (stats.reviewed /
                                                                  totalOrders) *
                                                                  100
                                                          )
                                                        : 0
                                                }}%</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-lg font-bold text-gray-900"
                                        >
                                            {{ stats.reviewed }}
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            Reviewed
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Approved -->
                            <div class="relative">
                                <div class="flex items-center mb-2">
                                    <div class="w-16 h-16 relative mr-4">
                                        <svg class="w-16 h-16 transform -rotate-90">
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#e2e8f0"
                                                stroke-width="4"
                                            />
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#22c55e"
                                                stroke-width="4"
                                                :stroke-dasharray="(stats.approved === totalOrders && totalOrders > 0) ? '175.93 175.93' : `${(stats.approved / totalOrders) * 175.93} 175.93`"
                                            />
                                        </svg>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold text-green-600"
                                                >{{
                                                    totalOrders > 0
                                                        ? Math.round(
                                                              (stats.approved /
                                                                  totalOrders) *
                                                                  100
                                                          )
                                                        : 0
                                                }}%</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-lg font-bold text-gray-900"
                                        >
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
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#e2e8f0"
                                                stroke-width="4"
                                            />
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#ef4444"
                                                stroke-width="4"
                                                :stroke-dasharray="(stats.rejected === totalOrders && totalOrders > 0) ? '175.93 175.93' : `${(stats.rejected / totalOrders) * 175.93} 175.93`"
                                            />
                                        </svg>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold text-red-600"
                                                >{{
                                                    totalOrders > 0
                                                        ? Math.round(
                                                              (stats.rejected /
                                                                  totalOrders) *
                                                                  100
                                                          )
                                                        : 0
                                                }}%</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-lg font-bold text-gray-900"
                                        >
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
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#e2e8f0"
                                                stroke-width="4"
                                            />
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#3b82f6"
                                                stroke-width="4"
                                                :stroke-dasharray="(stats.in_process === totalOrders && totalOrders > 0) ? '175.93 175.93' : `${(stats.in_process / totalOrders) * 175.93} 175.93`"
                                            />
                                        </svg>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold text-blue-600"
                                                >{{
                                                    totalOrders > 0
                                                        ? Math.round(
                                                              (stats.in_process /
                                                                  totalOrders) *
                                                                  100
                                                          )
                                                        : 0
                                                }}%</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-lg font-bold text-gray-900"
                                        >
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
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#e2e8f0"
                                                stroke-width="4"
                                            />
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#8b5cf6"
                                                stroke-width="4"
                                                :stroke-dasharray="(stats.dispatched === totalOrders && totalOrders > 0) ? '175.93 175.93' : `${(stats.dispatched / totalOrders) * 175.93} 175.93`"
                                            />
                                        </svg>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold text-purple-600"
                                                >{{
                                                    totalOrders > 0
                                                        ? Math.round(
                                                              (stats.dispatched /
                                                                  totalOrders) *
                                                                  100
                                                          )
                                                        : 0
                                                }}%</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-lg font-bold text-gray-900"
                                        >
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
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#e2e8f0"
                                                stroke-width="4"
                                            />
                                            <circle
                                                cx="32"
                                                cy="32"
                                                r="28"
                                                fill="none"
                                                stroke="#6366f1"
                                                stroke-width="4"
                                                :stroke-dasharray="(stats.received === totalOrders && totalOrders > 0) ? '175.93 175.93' : `${(stats.received / totalOrders) * 175.93} 175.93`"
                                            />
                                        </svg>
                                        <div
                                            class="absolute inset-0 flex items-center justify-center"
                                        >
                                            <span
                                                class="text-xs font-bold text-indigo-600"
                                                >{{
                                                    totalOrders > 0
                                                        ? Math.round(
                                                              (stats.received /
                                                                  totalOrders) *
                                                                  100
                                                          )
                                                        : 0
                                                }}%</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-lg font-bold text-gray-900"
                                        >
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
        </div>
    </AuthenticatedLayout>

    <!-- Slideover for Icon Legend -->
    <transition name="slide">
      <div v-if="showLegend" class="fixed inset-0 z-50 flex justify-end">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-30 transition-opacity" @click="showLegend = false"></div>
        <!-- Slideover Panel -->
        <div class="relative w-full max-w-sm bg-white shadow-xl h-full flex flex-col p-6 overflow-y-auto">
          <button @click="showLegend = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
          <h2 class="text-lg font-bold text-blue-700 mb-6 mt-2">Icon Legend</h2>
          <ul class="space-y-5">
            <li class="flex items-center gap-4">
              <img src="/assets/images/pending.png" class="w-10 h-10" alt="Pending" />
              <div>
                <div class="font-semibold text-yellow-600">Pending</div>
                <div class="text-xs text-gray-500">Order is awaiting review or action.</div>
              </div>
            </li>
            <li class="flex items-center gap-4">
              <img src="/assets/images/review.png" class="w-10 h-10" alt="Reviewed" />
              <div>
                <div class="font-semibold text-amber-600">Reviewed</div>
                <div class="text-xs text-gray-500">Order has been reviewed by an authorized user.</div>
              </div>
            </li>
            <li class="flex items-center gap-4">
              <img src="/assets/images/approved.png" class="w-10 h-10" alt="Approved" />
              <div>
                <div class="font-semibold text-green-600">Approved</div>
                <div class="text-xs text-gray-500">Order has been approved and is ready for processing.</div>
              </div>
            </li>
            <li class="flex items-center gap-4">
              <img src="/assets/images/rejected.png" class="w-10 h-10" alt="Rejected" />
              <div>
                <div class="font-semibold text-red-600">Rejected</div>
                <div class="text-xs text-gray-500">Order has been rejected and will not be processed.</div>
              </div>
            </li>
            <li class="flex items-center gap-4">
              <img src="/assets/images/inprocess.png" class="w-10 h-10" alt="In Process" />
              <div>
                <div class="font-semibold text-blue-600">In Process</div>
                <div class="text-xs text-gray-500">Order is currently being processed.</div>
              </div>
            </li>
            <li class="flex items-center gap-4">
              <img src="/assets/images/dispatch.png" class="w-10 h-10" alt="Dispatched" />
              <div>
                <div class="font-semibold text-purple-600">Dispatched</div>
                <div class="text-xs text-gray-500">Order has been dispatched for delivery.</div>
              </div>
            </li>
            <li class="flex items-center gap-4">
              <img src="/assets/images/delivery.png" class="w-10 h-10" alt="Delivered" />
              <div>
                <div class="font-semibold text-indigo-600">Delivered</div>
                <div class="text-xs text-gray-500">Order has been delivered to the destination.</div>
              </div>
            </li>
            <li class="flex items-center gap-4">
              <img src="/assets/images/received.png" class="w-10 h-10" alt="Received" />
              <div>
                <div class="font-semibold text-green-700">Received</div>
                <div class="text-xs text-gray-500">Order has been received and confirmed by the facility.</div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </transition>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
}
.slide-enter-to, .slide-leave-from {
  transform: translateX(0);
}
</style>
