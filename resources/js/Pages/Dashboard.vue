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

// Format large numbers with k, m abbreviations
const formatNumber = (number) => {
    const num = parseFloat(number);
    
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    } else {
        return num.toLocaleString();
    }
};

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

// Homogenized KPI cards (unified design like warehouse)
const kpiCards = computed(() => [
    {
        key: 'orders',
        label: 'Total Orders',
        value: totalOrdersCount.value || 0,
        route: 'orders.index',
        iconPath: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        accent: 'text-indigo-600',
    },
    {
        key: 'transfers',
        label: 'Transfers',
        value: filteredTransferReceivedCard.value || 0,
        route: 'transfers.index',
        iconPath: 'M8 7h12m0 0l-4-4m4 4l-4 4M4 17h12m0 0l-4 4m4-4l-4-4',
        accent: 'text-purple-600',
    },
    {
        key: 'delayed',
        label: 'Delayed Orders',
        value: filteredOrdersDelayedCount.value || 0,
        route: 'orders.index',
        iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        accent: 'text-amber-600',
    },
    {
        key: 'low',
        label: 'Low Stock',
        value: lowStockCount.value || 0,
        route: 'inventories.index',
        iconPath: 'M12 9v2m0 4h.01M5 20h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v11a2 2 0 002 2z',
        accent: 'text-orange-600',
    },
    {
        key: 'out',
        label: 'Out of Stock',
        value: outOfStockCount.value || 0,
        route: 'inventories.index',
        iconPath: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636',
        accent: 'text-red-600',
    },
]);

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
            },
            formatter: (value) => {
                return formatNumber(value);
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.parsed.x);
                }
            }
        }
    },
    scales: {
        x: {
            beginAtZero: true,
            ticks: {
                callback: function(value) {
                    return formatNumber(value);
                }
            }
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
            },
            formatter: (value) => {
                return formatNumber(value);
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.parsed.y);
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: function(value) {
                    return formatNumber(value);
                }
            }
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
            },
            formatter: (value) => {
                return formatNumber(value);
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.parsed.y);
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: function(value) {
                    return formatNumber(value);
                }
            }
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

// ===============================
// TRACEABLE ITEMS FUNCTIONALITY
// ===============================

// Traceable items variables
const facilityDataType = ref('opening_balance');

// Chart data state  
const localFacilityChartData = ref([]);
const facilityChartCount = ref(0);
const facilityCategorizedData = ref([]);

// Computed property to group facility charts into rows of 4
const facilityChartRows = computed(() => {
    const rows = [];
    for (let i = 0; i < localFacilityChartData.value.length; i += 4) {
        rows.push(localFacilityChartData.value.slice(i, i + 4));
    }
    return rows;
});

const isLoadingFacilityChart = ref(false);
const facilityChartError = ref(null);

const months = Array.from({ length: 12 }, (_, i) =>
  dayjs().subtract(i, 'month').format('YYYY-MM')
);
const facilityMonth = ref(months[1]); // Use previous month as default to match backend

// Watch for changes in facility filters
watch(
    [
        () => facilityDataType.value,
        () => facilityMonth.value
    ],
    () => {
        handleFacilityTracertItems();
    }
);

// Get human-readable label for facility data type
function getFacilityTypeLabel(type) {
    const labels = {
        'opening_balance': 'Beginning Balance',
        'stock_received': 'QTY Received',
        'stock_issued': 'Issued Quantity', 
        'closing_balance': 'Closing Balance (Calculated)',
        'positive_adjustments': 'Positive Adjustments',
        'negative_adjustments': 'Negative Adjustments'
    };
    return labels[type] || 'Quantity';
}

async function handleFacilityTracertItems() {
    isLoadingFacilityChart.value = true;
    facilityChartError.value = null;
    
    const query = {};
    if (facilityDataType.value){
        query.type = facilityDataType.value;
    } else {
        query.type = 'opening_balance';
    }
    if (facilityMonth.value){
        query.month = facilityMonth.value;
    }
    // Note: No facility_id parameter needed as it will use auth()->user()->facility_id in backend

    try {
        const response = await axios.post(route('dashboard.facility.tracert-items'), query);
        console.log('Facility API Response:', response.data);
        
        if (response.data.success && response.data.chartData && response.data.chartData.charts) {
            // Handle successful response with multiple charts
            const charts = response.data.chartData.charts;
            localFacilityChartData.value = charts.map(chart => ({
                id: chart.id,
                category: chart.category,
                categoryDisplay: chart.categoryDisplay,
                labels: chart.labels || ['No Data'],
                datasets: [{
                    label: getFacilityTypeLabel(facilityDataType.value),
                    data: chart.data || [0],
                    backgroundColor: chart.backgroundColors || ['rgba(156, 163, 175, 0.8)'],
                    borderColor: chart.borderColors || ['rgba(156, 163, 175, 1)'],
                    borderWidth: 2
                }]
            }));
            facilityChartCount.value = response.data.chartData.totalCharts;
            facilityChartError.value = null;
            
            // Store items data if available
            if (response.data.items) {
                facilityCategorizedData.value = response.data.items;
            }
        } else {
            // Handle API success but no data
            facilityChartError.value = response.data.message || 'No facility data available for the selected period';
            localFacilityChartData.value = [{
                id: 1,
                category: 'No Data',
                categoryDisplay: 'No Data Available',
                labels: ['No Data'],
                datasets: [{
                    label: 'Quantity',
                    data: [0],
                    backgroundColor: ['rgba(156, 163, 175, 0.8)'],
                    borderColor: ['rgba(156, 163, 175, 1)'],
                    borderWidth: 2
                }]
            }];
            facilityChartCount.value = 1;
            facilityCategorizedData.value = [];
        }
    } catch (error) {
        console.error('Error fetching facility tracert items:', error);
        facilityChartError.value = error.response?.data?.message || 'Network error occurred while loading facility data';
        
        // Set empty chart data on error
        localFacilityChartData.value = [{
            id: 1,
            category: 'Error',
            categoryDisplay: 'Error Loading Data',
            labels: ['Error'],
            datasets: [{
                label: 'Quantity',
                data: [0],
                backgroundColor: ['rgba(239, 68, 68, 0.8)'],
                borderColor: ['rgba(239, 68, 68, 1)'],
                borderWidth: 2
            }]
        }];
        facilityChartCount.value = 1;
    } finally {
        isLoadingFacilityChart.value = false;
    }
}

// Chart options for traceable items
const issuedChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        datalabels: {
            color: '#374151',
            font: {
                weight: 'bold',
                size: 10
            },
            formatter: (value) => {
                return formatNumber(value);
            }
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    return context.dataset.label + ': ' + formatNumber(context.parsed.y);
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0,
                callback: function(value) {
                    return formatNumber(value);
                }
            }
        }
    }
};

// Load traceable items on mount
onMounted(() => {
    handleFacilityTracertItems();
});


</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout title="Dashboard" description="Welcome to the dashboard">
        <!-- Quick Stats Cards Row (homogenized) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
            <template v-for="card in kpiCards" :key="card.key">
                <Link :href="route(card.route)" class="block">
                    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition-all duration-200">
                        <div class="p-4 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-medium text-gray-500">{{ card.label }}</div>
                                <div class="text-2xl font-bold text-gray-900">{{ card.value }}</div>
                            </div>
                            <svg class="w-10 h-10" :class="card.accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.iconPath" />
                            </svg>
                        </div>
                    </div>
                </Link>
            </template>
        </div>

        <!-- Tracert Items Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Tracert Items</h3>
                    <p class="text-sm text-gray-600 mt-1">Track inventory data by category and time period</p>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-4">
                <div class="flex gap-4">
                    <!-- Note: No facility selector needed - uses auth()->user()->facility_id -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <input type="month" v-model="facilityMonth" class="border border-gray-300 rounded-md px-3 py-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Type</label>
                        <select v-model="facilityDataType" class="border border-gray-300 rounded-md px-3 py-2 min-w-[180px]">
                            <option value="opening_balance">Beginning Balance</option>
                            <option value="stock_received">QTY Received</option>
                            <option value="stock_issued">Monthly Consumption</option>
                            <option value="closing_balance">Closing Balance</option>
                        </select>
                    </div>
                </div>
            </div>
            <!-- Chart Container -->
            <div class="relative" :class="facilityChartCount > 1 ? 'min-h-96' : 'h-80'">
                <!-- Loading State -->
                <div v-if="isLoadingFacilityChart" class="absolute inset-0 flex items-center justify-center bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-2">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <span class="text-gray-600">Loading facility chart data...</span>
                    </div>
                </div>
                <!-- Error State -->
                <div v-else-if="facilityChartError" class="absolute inset-0 flex items-center justify-center bg-red-50 rounded-lg">
                    <div class="text-center">
                        <div class="text-red-600 font-medium">{{ facilityChartError }}</div>
                        <button @click="handleFacilityTracertItems" class="mt-2 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Retry
                        </button>
                    </div>
                </div>
                <!-- Charts Grid -->
                <div v-else class="h-full">
                    <!-- Single Chart -->
                    <div v-if="facilityChartCount === 1" class="h-full">
                        <div class="mb-4 text-center">
                            <h3 class="text-lg font-semibold text-gray-800 bg-gray-50 px-4 py-2 rounded-md border inline-block">
                                {{ localFacilityChartData[0]?.categoryDisplay || localFacilityChartData[0]?.category || 'Unknown Category' }}
                            </h3>
                        </div>
                        <div class="h-full">
                            <Bar :data="localFacilityChartData[0]" :options="issuedChartOptions" />
                        </div>
                    </div>
                    <!-- Multiple Charts Grid - 4 charts per row -->
                    <div v-else class="space-y-6">
                        <div v-for="(chartRow, rowIndex) in facilityChartRows" :key="'facility-row-' + rowIndex" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div v-for="chart in chartRow" :key="'facility-' + chart.id" class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
                                <!-- Category Title -->
                                <div class="mb-3 flex items-start">
                                    <span class="text-sm font-semibold text-gray-700">
                                        {{ chart.categoryDisplay || chart.category || 'Unknown Category' }}
                                    </span>
                                </div>
                                <div class="h-64">
                                    <Bar :data="chart" :options="issuedChartOptions" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Product Categories Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-gray-900">Product Categories</h3>
                      </div>
                <div class="h-64">
                    <Doughnut :data="productCategoryChartData" :options="doughnutChartOptions" />
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
