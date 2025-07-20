<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { Bar, Doughnut, Line, Pie } from 'vue-chartjs';
import dayjs from 'dayjs';
import axios from 'axios';
import Datepicker from 'vue-datepicker-next';
import 'vue-datepicker-next/index.css';

// Register the datalabels plugin
Chart.register(ChartDataLabels);

const props = defineProps({
    dashboardData: {
        type: Object,
        required: true,
        default: () => ({ summary: [] })
    },

    productCategoryCard: {
        type: Object,
        required: true,
        default: () => ({ Drugs: 0, Consumable: 0, Lab: 0 })
    },
    transferReceivedCard: {
        type: Number,
        required: true,
        default: 0
    },

    orderStats: {
        type: Object,
        required: true,
        default: () => ({
            pending: 0, reviewed: 0, approved: 0, in_process: 0, dispatched: 0, received: 0, rejected: 0
        })
    },

    ordersDelayedCount: {
        type: Number,
        required: true,
        default: 0
    },

    inventoryStatusCounts: {
        type: Array,
        required: false,
        default: () => []
    },
    expiredStats: {
        type: Object,
        required: false,
        default: () => ({
            expired: 0,
            expiring_within_6_months: 0,
            expiring_within_1_year: 0
        })
    },
});

function getCount(abbr) {
    const found = props.dashboardData.summary.find(item => item.label === abbr);
    return found ? found.value : 0;
}

// Date filters
const dateRange = ref([
    dayjs().startOf('month').toDate(),
    dayjs().endOf('month').toDate()
]);

// Date presets for the datepicker
const datePresets = [
    { label: 'Today', value: [new Date(), new Date()] },
    { label: 'Yesterday', value: [dayjs().subtract(1, 'day').toDate(), dayjs().subtract(1, 'day').toDate()] },
    { label: 'Last 7 days', value: [dayjs().subtract(7, 'day').toDate(), new Date()] },
    { label: 'Last 30 days', value: [dayjs().subtract(30, 'day').toDate(), new Date()] },
    { label: 'This month', value: [dayjs().startOf('month').toDate(), dayjs().endOf('month').toDate()] },
    { label: 'Last month', value: [dayjs().subtract(1, 'month').startOf('month').toDate(), dayjs().subtract(1, 'month').endOf('month').toDate()] },
    { label: 'This quarter', value: [dayjs().startOf('quarter').toDate(), dayjs().endOf('quarter').toDate()] },
    { label: 'This year', value: [dayjs().startOf('year').toDate(), dayjs().endOf('year').toDate()] }
];

// Order counts for facilities
const orderCounts = computed(() => ({
    'Orders': totalOrdersCount.value,
    'Transfers': props.transferReceivedCard,
    'Dispenses': 0 // Add dispense count if available
}));

const totalOrders = computed(() =>
    props.orderStats.pending +
    props.orderStats.reviewed +
    props.orderStats.approved +
    props.orderStats.in_process +
    props.orderStats.dispatched +
    props.orderStats.received +
    props.orderStats.rejected +
    props.orderStats.delivered
);

// Order Status Chart Filter
const selectedOrderStatus = ref([]);
const orderStatusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'reviewed', label: 'Reviewed' },
    { value: 'approved', label: 'Approved' },
    { value: 'in_process', label: 'In Process' },
    { value: 'dispatched', label: 'Dispatched' },
    { value: 'delivered', label: 'Delivered' },
    { value: 'received', label: 'Received' },
    { value: 'rejected', label: 'Rejected' }
];

// Computed properties for filtered data
const filteredTransferReceivedCard = computed(() => props.transferReceivedCard);
const filteredOrdersDelayedCount = computed(() => props.ordersDelayedCount);




// Chart data computed properties
const productCategoryChartData = computed(() => ({
    labels: Object.keys(props.productCategoryCard),
    datasets: [{
        data: Object.values(props.productCategoryCard),
        backgroundColor: [
            '#3B82F6', // blue
            '#10B981', // emerald
            '#F59E0B', // amber
            '#EF4444', // red
            '#8B5CF6', // violet
        ],
        borderWidth: 0,
    }]
}));

const facilitiesChartData = computed(() => ({
    labels: ['Facilities'],
    datasets: [{
        label: 'Count',
        data: [getCount('FAC')],
        backgroundColor: ['#10B981'],
        borderWidth: 0,
    }]
}));

// Computed properties for dashboard stats
const totalOrdersCount = computed(() => {
    return Object.values(props.orderStats || {}).reduce((sum, count) => sum + count, 0);
});

const lowStockCount = computed(() => {
    return props.inventoryStatusCounts?.find(item => item.status === 'low_stock')?.count || 0;
});

const outOfStockCount = computed(() => {
    return props.inventoryStatusCounts?.find(item => item.status === 'out_of_stock')?.count || 0;
});

const orderChartData = computed(() => ({
    labels: Object.keys(orderCounts.value),
    datasets: [{
        label: 'Count',
        data: Object.values(orderCounts.value),
        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'],
        borderWidth: 0,
    }]
}));

const orderStatusChartData = computed(() => {
    const filteredStats = selectedOrderStatus.value.length > 0
        ? Object.fromEntries(
            Object.entries(props.orderStats).filter(([key]) => 
                selectedOrderStatus.value.some(status => status.value === key)
            )
        )
        : props.orderStats;

    return {
        labels: Object.keys(filteredStats).map(key => key.replace('_', ' ').toUpperCase()),
        datasets: [{
            label: 'Orders',
            data: Object.values(filteredStats),
            backgroundColor: [
                '#F59E0B', // amber - pending
                '#3B82F6', // blue - reviewed
                '#10B981', // emerald - approved
                '#8B5CF6', // violet - in_process
                '#EC4899', // pink - dispatched
                '#F97316', // orange - delivered
                '#059669', // emerald - received
                '#EF4444', // red - rejected
            ],
            borderWidth: 0,
        }]
    };
});

const expiredChartData = computed(() => ({
    labels: ['Expired', 'Expiring in 6 Months', 'Expiring in 1 Year'],
    datasets: [{
        data: [
            props.expiredStats.expired || 0,
            props.expiredStats.expiring_within_6_months || 0,
            props.expiredStats.expiring_within_1_year || 0
        ],
        backgroundColor: [
            '#EF4444', // red - expired
            '#F59E0B', // amber - expiring soon
            '#3B82F6', // blue - expiring later
        ],
        borderWidth: 0,
    }]
}));

// Chart options
const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
        datalabels: {
            color: '#fff',
            font: {
                weight: 'bold',
                size: 12
            },
            formatter: (value, ctx) => {
                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = ((value / total) * 100).toFixed(1);
                return `${percentage}%`;
            }
        }
    }
};

const horizontalBarChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: 'y',
    plugins: {
        legend: {
            display: false
        },
        datalabels: {
            color: '#fff',
            font: {
                weight: 'bold'
            }
        }
    },
    scales: {
        x: {
            beginAtZero: true
        }
    }
};

const orderChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        datalabels: {
            color: '#fff',
            font: {
                weight: 'bold'
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
};

const orderStatusChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        datalabels: {
            color: '#fff',
            font: {
                weight: 'bold'
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true
        }
    }
};

// Methods
const clearAllStatuses = () => {
    selectedOrderStatus.value = [];
};

// Watch for date range changes
watch(dateRange, (newRange) => {
    if (newRange && newRange.length === 2) {
        // Handle date range changes if needed
        console.log('Date range changed:', newRange);
    }
});


</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout title="Dashboard" description="Welcome to the dashboard">
        <!-- Quick Stats Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <!-- Total Orders Card -->
            <Link :href="route('orders.index')" class="block">
                <div class="relative overflow-hidden rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-green-400"></div>
                    <div class="relative p-6 flex items-center justify-between">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Total Orders</h3>
                            <div class="text-2xl font-bold text-white">{{ totalOrdersCount || 0 }}</div>
                </div>
                        <div class="flex-shrink-0">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                        </div>
            </Link>

            <!-- Transfers Card -->
            <Link :href="route('transfers.index')" class="block">
                <div class="relative overflow-hidden rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-pink-500 to-orange-400"></div>
                    <div class="relative p-6 flex items-center justify-between">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Transfers</h3>
                            <div class="text-2xl font-bold text-white">{{ filteredTransferReceivedCard || 0 }}</div>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                    </div>
                    </div>
            </Link>

            <!-- Delayed Orders Card -->
            <Link :href="route('orders.index')" class="block">
                <div class="relative overflow-hidden rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-yellow-400"></div>
                    <div class="relative p-6 flex items-center justify-between">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Delayed Orders</h3>
                            <div class="text-2xl font-bold text-white">{{ filteredOrdersDelayedCount || 0 }}</div>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    </div>
            </Link>

            <!-- Low Stock Card -->
            <Link :href="route('inventories.index')" class="block">
                <div class="relative overflow-hidden rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-orange-500 to-yellow-400"></div>
                    <div class="relative p-6 flex items-center justify-between">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Low Stock</h3>
                            <div class="text-2xl font-bold text-white">{{ lowStockCount || 0 }}</div>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    </div>
            </Link>

            <!-- Out of Stock Card -->
            <Link :href="route('inventories.index')" class="block">
                <div class="relative overflow-hidden rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer transform hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-br from-red-500 to-pink-400"></div>
                    <div class="relative p-6 flex items-center justify-between">
                        <div class="flex flex-col">
                            <h3 class="text-sm font-medium text-white mb-1">Out of Stock</h3>
                            <div class="text-2xl font-bold text-white">{{ outOfStockCount || 0 }}</div>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                            </svg>
                        </div>
                    </div>
                    </div>
            </Link>
            </div>

        <!-- Date Range Filter -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Select Date Range</span>
                </div>
                <Datepicker
                    v-model="dateRange"
                    range
                    :enable-time-picker="false"
                    :format="{ year: 'numeric', month: 'short', day: 'numeric' }"
                    :placeholder="'Select date range'"
                    :preview-format="'MMM DD, YYYY'"
                    :teleport="true"
                    :auto-apply="true"
                    :min-date="new Date('2020-01-01')"
                    :max-date="new Date('2030-12-31')"
                    :presets="datePresets"
                    class="w-full max-w-md"
                />
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Product Categories Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Product Categories</h3>
                      </div>
                <div class="h-64">
                    <Doughnut :data="productCategoryChartData" :options="doughnutChartOptions" />
                    </div>
                </div>

            <!-- Facilities Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Facilities Overview</h3>
                            </div>
                <div class="h-64">
                    <Bar :data="facilitiesChartData" :options="horizontalBarChartOptions" />
                        </div>
                            </div>

            <!-- Orders Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Supplies</h3>
                        </div>
                <div class="h-64">
                    <Bar :data="orderChartData" :options="orderChartOptions" />
                            </div>
                        </div>
                    </div>

        <!-- Expired Statistics Chart Row -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Expired Chart - Takes 8 columns -->
            <div class="lg:col-span-8 bg-white rounded-xl shadow-lg border border-gray-200 p-3">
                <div class="mb-2">
                    <h3 class="text-xl font-bold text-gray-900">Expiry Status Overview</h3>
                    <p class="text-sm text-gray-600 mt-1">Items by expiry status and timeline</p>
                </div>
                <div class="h-64">
                    <Doughnut :data="expiredChartData" :options="doughnutChartOptions" />
                    </div>
                </div>

            <!-- Summary Stats - Takes 4 columns -->
            <div class="lg:col-span-4 space-y-3">
                <!-- Expired Items Card -->
                <div class="bg-gray-600 rounded-xl shadow-sm border border-gray-200 p-3">
                        <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-white">Expired Items</h3>
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                            </div>
                    <div class="text-xl font-bold text-white">{{ props.expiredStats?.expired || 0 }}</div>
                    <div class="text-xs text-white mt-1">Items past expiry date</div>
                </div>
                
                <!-- Expiring in 6 Months Card -->
                <div class="bg-pink-500 rounded-xl shadow-sm border border-gray-200 p-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-white">Expiring in 6 Months</h3>
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                    <div class="text-xl font-bold text-white">{{ props.expiredStats.expiring_within_6_months || 0 }}</div>
                    <div class="text-xs text-white mt-1">Items expiring soon</div>
                        </div>
                
                <!-- Expiring in 1 Year Card -->
                <div class="bg-orange-400 rounded-xl shadow-sm border border-gray-200 p-3">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-semibold text-white">Expiring in 1 Year</h3>
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="text-xl font-bold text-white">{{ props.expiredStats.expiring_within_1_year || 0 }}</div>
                    <div class="text-xs text-white mt-1">Items to monitor</div>
                        </div>
                    </div>
                </div>

        <!-- Order Status Chart Section -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Order Status Overview</h3>
                    <p class="text-sm text-gray-600 mt-1">Track orders across different statuses</p>
                            </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Status Filter -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Filter by Status:</label>
                        <div class="flex gap-2">
                            <Multiselect
                                v-model="selectedOrderStatus"
                                :options="orderStatusOptions"
                                :searchable="true"
                                :close-on-select="false"
                                :multiple="true"
                                :show-labels="false"
                                label="label"
                                track-by="value"
                                placeholder="Select statuses..."
                                class="w-full sm:w-48"
                            />
                            <button
                                @click="clearAllStatuses"
                                class="px-3 py-2 text-xs bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors mt-2"
                            >
                                Clear
                            </button>
                            </div>
                        </div>
                        </div>
                            </div>
            <div class="h-80">
                <Bar :data="orderStatusChartData" :options="orderStatusChartOptions" />
                    </div>
                </div>
    </AuthenticatedLayout>
</template>
