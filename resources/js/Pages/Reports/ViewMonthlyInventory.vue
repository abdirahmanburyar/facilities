<template>
    <AuthenticatedLayout title="Monthly Inventory Report">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Monthly Summary Report Form: {{ monthName }}
                </h2>
                <div class="flex items-center space-x-4">
                    <button
                        @click="exportExcel"
                        class="px-4 py-2 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                        DOWNLOAD AS XLS
                    </button>
                    <button
                        @click="exportPdf"
                        class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    >
                        DOWNLOAD AS PDF
                    </button>
                    <button
                        @click="goBack"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    >
                        Back to Reports
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- Report Header Info -->
                <div class="mb-6 bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Report Period</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ monthName }}</p>
                            </div>
                            <div v-if="facility">
                                <label class="block text-sm font-medium text-gray-700">Facility</label>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ facility.full_name || facility.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Report Type</label>
                                <p class="mt-1 text-lg font-semibold text-blue-600">Logistic Data Hospitals & HCs (MF-11A)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Display -->
                <div v-if="reports && reports.length > 0" class="space-y-8">
                    <div v-for="report in reports" :key="report.id" class="bg-white shadow-sm sm:rounded-lg">
                        
                        <!-- Facility Header for Multi-facility Reports -->
                        <div v-if="reports.length > 1" class="px-6 py-4 bg-blue-50 border-b border-blue-200">
                            <h3 class="text-lg font-semibold text-blue-900">
                                {{ report.facility.full_name || report.facility.name }}
                            </h3>
                            <p class="text-sm text-blue-700">{{ report.facility.type }}</p>
                        </div>
                        
                        <!-- Report Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200" style="min-width: 300px;">
                                            Description / Dosage / Form
                                        </th>
                                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200" style="width: 120px;">
                                            Opening<br>Balance
                                        </th>
                                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200" style="width: 120px;">
                                            Stock<br>Received
                                        </th>
                                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200" style="width: 120px;">
                                            Stock<br>Issued
                                        </th>
                                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200" style="width: 150px;">
                                            Losses(-)/Adjustments(+)
                                        </th>
                                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200" style="width: 120px;">
                                            Stock<br>on Hand
                                        </th>
                                        <th class="px-4 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 100px;">
                                            Stock-<br>out Days
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <!-- Group items by category -->
                                    <template v-for="(categoryItems, category) in report.items" :key="category">
                                        <!-- Category Header -->
                                        <tr class="bg-gray-100">
                                            <td colspan="7" class="px-6 py-3 text-sm font-bold text-gray-800 uppercase tracking-wide">
                                                {{ category }}
                                            </td>
                                        </tr>
                                        
                                        <!-- Category Items -->
                                        <tr v-for="item in categoryItems" :key="item.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200">
                                                <div class="font-medium">{{ item.product.name }}</div>
                                                <div v-if="item.product.strength || item.product.dosage_form" class="text-xs text-gray-500 mt-1">
                                                    <span v-if="item.product.dosage_form">{{ item.product.dosage_form }}</span>
                                                    <span v-if="item.product.strength && item.product.dosage_form">; </span>
                                                    <span v-if="item.product.strength">{{ item.product.strength }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-900 text-center border-r border-gray-200 font-mono">
                                                {{ formatNumber(item.opening_balance) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-900 text-center border-r border-gray-200 font-mono">
                                                {{ formatNumber(item.stock_received) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-900 text-center border-r border-gray-200 font-mono">
                                                {{ formatNumber(item.stock_issued) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-gray-900 text-center border-r border-gray-200 font-mono">
                                                {{ formatAdjustments(item.positive_adjustments, item.negative_adjustments) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm font-semibold text-center border-r border-gray-200 font-mono" :class="getStockBalanceClass(item.closing_balance)">
                                                {{ formatNumber(item.closing_balance) }}
                                            </td>
                                            <td class="px-4 py-4 text-sm text-center font-mono" :class="getStockoutClass(item.stockout_days)">
                                                {{ item.stockout_days }}
                                            </td>
                                        </tr>
                                        
                                        <!-- Empty row if no items in category -->
                                        <tr v-if="categoryItems.length === 0">
                                            <td colspan="7" class="px-6 py-4 text-sm text-gray-500 text-center italic">
                                                No movement data available for this category
                                            </td>
                                        </tr>
                                    </template>
                                    
                                    <!-- No data row -->
                                    <tr v-if="Object.keys(report.items).length === 0">
                                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                            <div class="text-lg font-medium mb-2">No movement data found</div>
                                            <p class="text-sm">No inventory movements were recorded for this facility during {{ monthName }}.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Report Summary -->
                        <div v-if="Object.keys(report.items).length > 0" class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700">Total Products:</span>
                                    <span class="ml-2 font-semibold text-blue-600">{{ getTotalProducts(report.items) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Total Received:</span>
                                    <span class="ml-2 font-semibold text-green-600">{{ formatNumber(getTotalReceived(report.items)) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Total Issued:</span>
                                    <span class="ml-2 font-semibold text-red-600">{{ formatNumber(getTotalIssued(report.items)) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Products with Stockouts:</span>
                                    <span class="ml-2 font-semibold text-orange-600">{{ getStockoutProducts(report.items) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- No Reports Found -->
                <div v-else class="bg-white shadow-sm sm:rounded-lg">
                    <div class="px-6 py-12 text-center">
                        <div class="text-gray-400 text-6xl mb-4">📊</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Reports Found</h3>
                        <p class="text-gray-500 mb-6">
                            No monthly inventory reports were found for the specified criteria.
                            Please generate the report first.
                        </p>
                        <button
                            @click="goBack"
                            class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Generate Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    reports: Array,
    facility: Object,
    year: Number,
    month: Number,
    reportPeriod: String,
    monthName: String
})

const formatNumber = (value) => {
    if (value === 0 || value === '0') return ''
    return Number(value).toLocaleString()
}

const formatAdjustments = (positive, negative) => {
    const pos = Number(positive) || 0
    const neg = Number(negative) || 0
    
    if (pos === 0 && neg === 0) return ''
    
    let result = ''
    if (pos > 0) result += `+${pos.toLocaleString()}`
    if (neg > 0) {
        if (result) result += ' / '
        result += `-${neg.toLocaleString()}`
    }
    return result
}

const getStockBalanceClass = (balance) => {
    const bal = Number(balance) || 0
    if (bal === 0) return 'text-red-600'
    if (bal < 100) return 'text-yellow-600'
    return 'text-green-600'
}

const getStockoutClass = (days) => {
    const stockoutDays = Number(days) || 0
    if (stockoutDays === 0) return 'text-green-600'
    if (stockoutDays < 7) return 'text-yellow-600'
    return 'text-red-600'
}

const getTotalProducts = (items) => {
    return Object.values(items).reduce((total, categoryItems) => total + categoryItems.length, 0)
}

const getTotalReceived = (items) => {
    return Object.values(items).reduce((total, categoryItems) => {
        return total + categoryItems.reduce((catTotal, item) => catTotal + (Number(item.stock_received) || 0), 0)
    }, 0)
}

const getTotalIssued = (items) => {
    return Object.values(items).reduce((total, categoryItems) => {
        return total + categoryItems.reduce((catTotal, item) => catTotal + (Number(item.stock_issued) || 0), 0)
    }, 0)
}

const getStockoutProducts = (items) => {
    return Object.values(items).reduce((total, categoryItems) => {
        return total + categoryItems.filter(item => (Number(item.stockout_days) || 0) > 0).length
    }, 0)
}

const exportExcel = async () => {
    try {
        const params = new URLSearchParams({
            year: props.year,
            month: props.month,
            format: 'excel'
        })
        
        if (props.facility) {
            params.append('facility_id', props.facility.id)
        }
        
        window.open(`/reports/monthly-inventory/export/excel?${params}`, '_blank')
    } catch (error) {
        console.error('Error exporting Excel:', error)
        alert('Excel export feature coming soon!')
    }
}

const exportPdf = async () => {
    try {
        const params = new URLSearchParams({
            year: props.year,
            month: props.month,
            format: 'pdf'
        })
        
        if (props.facility) {
            params.append('facility_id', props.facility.id)
        }
        
        window.open(`/reports/monthly-inventory/export/pdf?${params}`, '_blank')
    } catch (error) {
        console.error('Error exporting PDF:', error)
        alert('PDF export feature coming soon!')
    }
}

const goBack = () => {
    router.get('/reports/monthly-inventory')
}
</script>

<style scoped>
/* Custom table styling for report format */
.table-fixed {
    table-layout: fixed;
}

/* Print styles */
@media print {
    .no-print {
        display: none;
    }
    
    table {
        font-size: 12px;
    }
    
    .bg-gray-50 {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact;
    }
    
    .bg-gray-100 {
        background-color: #f3f4f6 !important;
        -webkit-print-color-adjust: exact;
    }
}
</style>
