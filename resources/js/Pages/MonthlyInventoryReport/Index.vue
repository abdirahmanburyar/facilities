<template>
    <AuthenticatedLayout title="Monthly Inventory Reports">
        <Head title="Monthly Inventory Reports" />
        
        <div class="mb-[80px]">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="text-gray-900">
                    <!-- Header with Actions -->
                    <div class="flex justify-between items-center mb-4 px-4 py-3">
                        <h2 class="text-lg font-bold text-gray-900">Monthly Inventory Reports</h2>
                        <div class="flex space-x-2">
                            <button 
                                @click="exportData" 
                                :disabled="isExporting || isLoading"
                                class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                <svg v-if="!isExporting" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <svg v-else class="animate-spin w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isExporting ? 'Exporting...' : 'Export CSV' }}
                            </button>
                            <button
                                @click="generateReports"
                                :disabled="isGenerating || isLoading"
                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            >
                                <svg v-if="!isGenerating" class="-ml-0.5 mr-1.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg v-else class="animate-spin -ml-0.5 mr-1.5 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isGenerating ? 'Generating...' : 'Generate Reports' }}
                            </button>
                            <Link 
                                :href="route('reports.monthly-reports.create')"
                                :class="[
                                    'inline-flex items-center px-3 py-1 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
                                    isLoading ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                :disabled="isLoading"
                            >
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Create Report
                            </Link>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-gray-50 p-3 mx-4 rounded-lg mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 mb-3">
                            <!-- Year Filter -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Year</label>
                                <input 
                                    v-model="month_year" 
                                    type="month"
                                    class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                <select 
                                    v-model="status"
                                    class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <option value="">All Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="approved">Approved</option>
                                </select>
                            </div>

                            <!-- Product Filter -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Items</label>
                                <Multiselect
                                    v-model="product_id"
                                    :options="products"
                                    :multiple="true"
                                    :close-on-select="false"
                                    :clear-on-select="false"
                                    :preserve-search="true"
                                    placeholder="Select items"
                                    label="name"
                                    track-by="id"
                                    :preselect-first="false"
                                    :disabled="isLoading"
                                    class="text-xs"
                                />
                            </div>
                        </div>
                    </div>

                    {{ reports }}
                </div>

                <!-- Report Header -->
                <div v-if="reports && reports.id" class="mx-4 mb-6">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-2">
                                    Monthly Inventory Report
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-600">Facility:</span>
                                        <p class="font-medium text-gray-900">{{ reports.facility?.name }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Report Period:</span>
                                        <p class="font-medium text-gray-900">{{ formatReportPeriod(reports.report_period) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Status:</span>
                                        <span :class="`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(reports.status)}`">
                                            {{ formatStatus(reports.status) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Total Items:</span>
                                        <p class="font-medium text-gray-900">{{ reports.items?.length || 0 }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button 
                                    @click="exportToExcel" 
                                    :disabled="isExporting"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    <svg v-if="!isExporting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <svg v-else class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isExporting ? 'Exporting...' : 'Export Excel' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Table -->
                <div v-if="reports && reports.items && reports.items.length > 0" class="mx-4">
                    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product Details</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opening Balance</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Received</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Issued</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adjustments</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closing Balance</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stockout Days</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in reports.items" :key="item.id" class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <div class="text-sm font-medium text-gray-900">{{ item.product?.name || 'N/A' }}</div>
                                                <div class="text-xs text-gray-500">
                                                    <span class="font-medium">ID:</span> {{ item.product?.productID || 'N/A' }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <span class="font-medium">Category:</span> {{ item.product?.category?.name || 'N/A' }}
                                                </div>
                                                <div v-if="item.product?.dosage" class="text-xs text-gray-500">
                                                    <span class="font-medium">Dosage:</span> {{ item.product.dosage.name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ formatNumber(item.opening_balance) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-green-600">{{ formatNumber(item.stock_received) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-red-600">{{ formatNumber(item.stock_issued) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col space-y-1">
                                                <div class="text-xs">
                                                    <span class="text-green-600 font-medium">+{{ formatNumber(item.positive_adjustments) }}</span>
                                                </div>
                                                <div class="text-xs">
                                                    <span class="text-red-600 font-medium">-{{ formatNumber(item.negative_adjustments) }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold text-blue-600">{{ formatNumber(item.closing_balance) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span :class="`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${item.stockout_days > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`">
                                                    {{ item.stockout_days || 0 }} days
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- No Data Message -->
                <div v-else-if="month_year" class="mx-4 py-12">
                    <div class="text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <p class="text-lg font-medium text-gray-900 mb-2">No report found for {{ formatReportPeriod(month_year) }}</p>
                        <p class="text-sm text-gray-600">Try generating a new report for this period</p>
                    </div>
                </div>

                <!-- No Month/Year Selected Message -->
                <div v-else class="mx-4 py-12">
                    <div class="text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Select Month and Year</h3>
                        <p class="text-gray-600 mb-4">Please select month and year to view monthly inventory reports.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";

import axios from 'axios';
import Swal from 'sweetalert2';
import { ref, reactive, computed, onMounted, watch } from 'vue'
import * as XLSX from 'xlsx'

const props = defineProps({
    reports: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    products: {
        type: Array,
        default: () => []
    },
    months: {
        type: Object,
        default: () => ({})
    },
    facilityType: {
        type: String,
        required: false
    }
})
    
const isLoading = ref(false)
const isGenerating = ref(false)
const isExporting = ref(false)
const isFiltering = ref(false)

const month_year = ref(props.filters?.month_year || '')
const status = ref(props.filters?.status || '')
const product_id = ref(props.filters?.product_id || [])


watch([
    () => month_year.value,
    () => status.value,
    () => product_id.value
], () => {
    applyFilters()
});

function applyFilters() {
    const query = {};
    if (month_year.value) query.month_year = month_year.value;
    if (status.value) query.status = status.value;
    if (product_id.value.length > 0) query.product_id = product_id.value;
    router.get(route('reports.monthly-reports.index'), query, {
        preserveState: true,
        preserveScroll: true,
        only: ['reports']
    });
}

function formatNumber(value) {
    return parseFloat(value || 0).toLocaleString();
}

function formatStatus(status) {
    const statusMap = {
        'draft': 'Draft',
        'submitted': 'Submitted',
        'approved': 'Approved'
    };
    return statusMap[status] || status;
}

function getStatusClass(status) {
    const classMap = {
        'draft': 'bg-gray-100 text-gray-800',
        'submitted': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800'
    };
    return classMap[status] || 'bg-gray-100 text-gray-800';
}

function getMonthName(month) {
    const monthNames = {
        1: 'January', 2: 'February', 3: 'March', 4: 'April',
        5: 'May', 6: 'June', 7: 'July', 8: 'August',
        9: 'September', 10: 'October', 11: 'November', 12: 'December'
    };
    return monthNames[month] || month;
}

function formatReportPeriod(period) {
    if (!period) return 'N/A';
    const [year, month] = period.split('-');
    const monthNames = {
        '01': 'January', '02': 'February', '03': 'March', '04': 'April',
        '05': 'May', '06': 'June', '07': 'July', '08': 'August',
        '09': 'September', '10': 'October', '11': 'November', '12': 'December'
    };
    return `${monthNames[month] || month} ${year}`;
}

async function exportToExcel() {
    if (!props.reports || !props.reports.items) {
        Swal.fire({
            title: 'No Data',
            text: 'No report data available to export',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }

    isExporting.value = true;

    try {
        // Prepare data for Excel
        const excelData = props.reports.items.map(item => ({
            'Product ID': item.product?.productID || 'N/A',
            'Product Name': item.product?.name || 'N/A',
            'Category': item.product?.category?.name || 'N/A',
            'Dosage Form': item.product?.dosage?.name || 'N/A',
            'Opening Balance': parseFloat(item.opening_balance || 0),
            'Stock Received': parseFloat(item.stock_received || 0),
            'Stock Issued': parseFloat(item.stock_issued || 0),
            'Positive Adjustments': parseFloat(item.positive_adjustments || 0),
            'Negative Adjustments': parseFloat(item.negative_adjustments || 0),
            'Closing Balance': parseFloat(item.closing_balance || 0),
            'Stockout Days': parseInt(item.stockout_days || 0)
        }));

        // Create workbook and worksheet
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.json_to_sheet(excelData);

        // Add header information
        const headerInfo = [
            ['Monthly Inventory Report'],
            [''],
            ['Facility:', props.reports.facility?.name || 'N/A'],
            ['Report Period:', formatReportPeriod(props.reports.report_period)],
            ['Status:', formatStatus(props.reports.status)],
            ['Generated Date:', new Date().toLocaleDateString()],
            ['Total Items:', props.reports.items?.length || 0],
            ['']
        ];

        // Insert header information at the top
        XLSX.utils.sheet_add_aoa(ws, headerInfo, { origin: 'A1' });

        // Style the header
        ws['A1'] = { v: 'Monthly Inventory Report', s: { font: { bold: true, size: 16 } } };

        // Set column widths
        const colWidths = [
            { wch: 15 }, // Product ID
            { wch: 40 }, // Product Name
            { wch: 15 }, // Category
            { wch: 15 }, // Dosage Form
            { wch: 15 }, // Opening Balance
            { wch: 15 }, // Stock Received
            { wch: 15 }, // Stock Issued
            { wch: 20 }, // Positive Adjustments
            { wch: 20 }, // Negative Adjustments
            { wch: 15 }, // Closing Balance
            { wch: 15 }  // Stockout Days
        ];
        ws['!cols'] = colWidths;

        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Monthly Report');

        // Generate filename
        const facilityName = props.reports.facility?.name?.replace(/[^a-zA-Z0-9]/g, '_') || 'Facility';
        const period = props.reports.report_period?.replace('-', '_') || 'Report';
        const filename = `Monthly_Inventory_Report_${facilityName}_${period}.xlsx`;

        // Save the file
        XLSX.writeFile(wb, filename);

        Swal.fire({
            title: 'Export Successful!',
            text: `Report exported as ${filename}`,
            icon: 'success',
            confirmButtonText: 'OK'
        });

    } catch (error) {
        console.error('Export error:', error);
        Swal.fire({
            title: 'Export Failed',
            text: 'Failed to export report. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    } finally {
        isExporting.value = false;
    }
}

</script>
