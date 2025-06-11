<template>
    <AuthenticatedLayout title="LMIS Monthly Report">
        <div class="mb-6">
            <h2 class="text-xs font-semibold leading-tight text-gray-800">
                LMIS Monthly Report: {{ monthName }}
            </h2>
        </div>

        <div class="p-2 mb-[80px]">
            <div class="mx-auto max-w-full">
                
                <!-- Facility Information Card -->
                <div class="mb-6 bg-white shadow-sm rounded-lg">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-xs font-medium text-gray-900 mb-3">Facility Information</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Facility Name</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.name || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Facility Code</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.code || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Facility Type</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.type || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Region</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.region || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">District</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.district || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Address</dt>
                                <dd class="text-xs text-gray-900">{{ facility?.address || 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Report Period</dt>
                                <dd class="text-xs text-gray-900">{{ monthName }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500">Status</dt>
                                <dd class="text-xs">
                                    <span :class="isApproved ? 'text-green-600 bg-green-100' : 'text-yellow-600 bg-yellow-100'" 
                                          class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ isApproved ? 'Approved' : 'Draft' }}
                                    </span>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Status and Period -->
                <div class="mb-6 bg-white shadow-sm rounded-lg">
                    <div class="p-2 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Report Period</label>
                                <p class="mt-1 text-xs text-gray-900">{{ monthName }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Report Status</label>
                                <span :class="getStatusClass(reports[0]?.status)" class="mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ reports[0]?.status || 'Draft' }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Last Updated</label>
                                <p class="mt-1 text-xs text-gray-900">{{ formatDate(reports[0]?.updated_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div v-if="reports && reports.length > 0" class="mb-6 bg-white shadow-sm rounded-lg">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-sm font-medium text-gray-900 mb-4">Actions</h3>
                        <div class="flex flex-wrap gap-2">
                            <!-- Save Changes Button -->
                            <button
                                v-if="!isApproved"
                                @click="saveChanges"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Save Changes
                            </button>

                            <!-- Submit for Review -->
                            <button
                                v-if="reports[0]?.status === 'draft'"
                                @click="submitForReview"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                Submit for Review
                            </button>

                            <!-- Approve Button -->
                            <button
                                v-if="reports[0]?.status === 'submitted' && canApprove"
                                @click="approveReport"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                            >
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Approve Report
                            </button>

                            <!-- Reject Button -->
                            <button
                                v-if="reports[0]?.status === 'submitted' && canApprove"
                                @click="rejectReport"
                                class="inline-flex items-center px-3 py-2 border border-red-300 text-xs font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            >
                                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Reject Report
                            </button>

                            <!-- Export Buttons -->
                            <div class="ml-auto flex gap-2">
                                <button
                                    @click="exportExcel"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Export Excel
                                </button>

                                <button
                                    @click="exportPdf"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                    Print Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Items Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <div class="border-b border-gray-200">
                        <div class="mt-2 text-xs text-gray-500">
                            <strong>Formula:</strong> Closing Balance = Beginning Balance + Qty Received - Qty Consumed + Positive Adjustments - Negative Adjustments
                        </div>
                    </div>

                    <div v-if="reports && reports.length > 0" class="overflow-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200">
                                        Item / Dose Strength /Dosage Form
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200">
                                        Beginning Balance
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200">
                                        Qty Received
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200">
                                        Qty Consumed
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200 bg-yellow-50">
                                        Positive Adjustment
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200 bg-yellow-50">
                                        Negative Adjustment
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize border-r border-gray-200 bg-blue-50">
                                        Closing Balance
                                    </th>
                                    <th class="p-1 text-left text-xs text-black capitalize bg-yellow-50">
                                        Stockout Days
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                <template v-for="report in reports" :key="report.id">
                                    <template v-for="(categoryItems, category) in report.items" :key="category">
                                        <!-- Items -->
                                        <tr 
                                            v-for="item in categoryItems" 
                                            :key="item.id"
                                            class="hover:bg-gray-50"
                                        >
                                            <td class="px-3 py-2 text-xs text-gray-900 border-r border-gray-200">
                                                {{ item.product_name }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right text-gray-900 border-r border-gray-200">
                                                {{ formatNumber(item.opening_balance) }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right text-green-600 border-r border-gray-200">
                                                {{ formatNumber(item.stock_received) }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right text-red-600 border-r border-gray-200">
                                                {{ formatNumber(item.stock_issued) }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right border-r border-gray-200 bg-yellow-50">
                                                <input
                                                    v-if="!isApproved"
                                                    v-model.number="item.positive_adjustments"
                                                    @input="updateClosingBalance(item)"
                                                    @blur="saveItemChanges(item)"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="w-full px-2 py-1 text-right border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                />
                                                <span v-else class="text-green-600">
                                                    {{ formatNumber(item.positive_adjustments) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right border-r border-gray-200 bg-yellow-50">
                                                <input
                                                    v-if="!isApproved"
                                                    v-model.number="item.negative_adjustments"
                                                    @input="updateClosingBalance(item)"
                                                    @blur="saveItemChanges(item)"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="w-full px-2 py-1 text-right border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                />
                                                <span v-else class="text-red-600">
                                                    {{ formatNumber(item.negative_adjustments) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right font-semibold border-r border-gray-200 bg-blue-50" 
                                                :class="getBalanceClass(item.closing_balance)">
                                                {{ formatNumber(item.closing_balance) }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right bg-yellow-50">
                                                <input
                                                    v-if="!isApproved"
                                                    v-model.number="item.stockout_days"
                                                    @blur="saveItemChanges(item)"
                                                    type="number"
                                                    min="0"
                                                    max="31"
                                                    class="w-full px-2 py-1 text-right border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                />
                                                <span v-else class="text-orange-600">
                                                    {{ item.stockout_days || 0 }}
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- No reports message -->
                    <div v-else-if="noReportsFound" class="p-12 text-center">
                        <div class="text-gray-400 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-medium text-gray-900 mb-2">{{ message }}</h3>
                        <p class="text-xs text-gray-500">No monthly inventory report found for the selected period.</p>
                        <Link 
                            to="/reports/monthly-inventory"
                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Generate New Report
                        </Link>
                    </div>
                    <div v-else class="p-12 text-center">
                        <div class="text-gray-400 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-medium text-gray-900 mb-2">No Report Data</h3>
                        <p class="text-xs text-gray-500">No monthly inventory report found for the selected period.</p>
                        <Link 
                            to="/reports/monthly-inventory"
                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Generate New Report
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import 'jspdf-autotable'
import Swal from 'sweetalert2'

const props = defineProps({
    reports: Array,
    facility: Object,
    reportPeriod: String,
    monthName: String,
    isApproved: Boolean,
    noReportsFound: Boolean,
    message: String,
    canApprove: Boolean
})

const formatNumber = (value) => {
    if (value === null || value === undefined || value === 0 || value === '0') return '-'
    return Number(value).toLocaleString()
}

const getBalanceClass = (balance) => {
    const bal = Number(balance) || 0
    if (bal === 0) return 'text-red-600 font-medium'
    if (bal < 10) return 'text-orange-600 font-medium'
    return 'text-gray-900'
}

const getStatusClass = (status) => {
    if (status === 'Approved') return 'bg-green-100 text-green-800'
    if (status === 'Draft') return 'bg-yellow-100 text-yellow-800'
    return 'bg-red-100 text-red-800'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString()
}

const updateClosingBalance = (item) => {
    item.closing_balance = item.opening_balance + item.stock_received - item.stock_issued + item.positive_adjustments - item.negative_adjustments
}

const saveItemChanges = async (item) => {
    try {
        const response = await axios.post(route('reports.monthly-inventory.update-item'), {
            item_id: item.id,
            positive_adjustments: item.positive_adjustments || 0,
            negative_adjustments: item.negative_adjustments || 0,
            stockout_days: item.stockout_days || 0,
        })
        
        if (response.data.success) {
            // Show a subtle success indicator
            console.log('Item updated successfully')
        }
    } catch (error) {
        console.error('Error updating item:', error)
        Swal.fire({
            title: 'Error Updating Item',
            text: 'An error occurred while updating the item.',
            icon: 'error',
            timer: 3000,
            showConfirmButton: false
        })
    }
}

const saveChanges = async () => {
    Swal.fire({
        title: 'Save Changes?',
        text: 'Are you sure you want to save all changes to this report?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, save changes'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('reports.monthly-inventory.save'), {
                report_period: props.reportPeriod,
                reports: props.reports
            })
            .then((response) => {
                if (response.data.success) {
                    Swal.fire({
                        title: 'Changes Saved!',
                        text: 'All changes have been successfully saved.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    })
                } else {
                    Swal.fire({
                        title: 'Error Saving Changes',
                        text: response.data.message || 'An error occurred while saving changes.',
                        icon: 'error',
                    })
                }
            })
            .catch((error) => {
                console.error('Error saving changes:', error)
                Swal.fire({
                    title: 'Error Saving Changes',
                    text: 'An error occurred while saving changes.',
                    icon: 'error',
                })
            })
        }
    })
}

const submitForReview = async () => {
    Swal.fire({
        title: 'Submit for Review?',
        text: 'Once submitted, you will not be able to make further changes until the report is reviewed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, submit for review'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('reports.monthly-inventory.submit'), {
                report_id: props.reports[0].id,
            })
            .then((response) => {
                if (response.data.success) {
                    Swal.fire({
                        title: 'Report Submitted!',
                        text: 'The report has been successfully submitted for review.',
                        icon: 'success',
                    }).then(() => {
                        // Reload the page to reflect status change
                        window.location.reload()
                    })
                } else {
                    Swal.fire({
                        title: 'Error Submitting Report',
                        text: response.data.message || 'An error occurred while submitting the report.',
                        icon: 'error',
                    })
                }
            })
            .catch((error) => {
                console.error('Error submitting report:', error)
                Swal.fire({
                    title: 'Error Submitting Report',
                    text: 'An error occurred while submitting the report.',
                    icon: 'error',
                })
            })
        }
    })
}

const goBack = () => {
    window.history.back()
}

const exportExcel = () => {
    if (!props.reports || props.reports.length === 0) {
        alert('No data to export')
        return
    }

    // Prepare data for Excel export
    const excelData = []
    
    // Add facility info header
    excelData.push([
        `LMIS Monthly Report - ${props.facility?.name || 'Unknown Facility'}`,
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ])
    excelData.push([
        `Report Period: ${props.monthName}`,
        '',
        '',
        '',
        '',
        '',
        '',
        ''
    ])
    excelData.push(['', '', '', '', '', '', '', '']) // Empty row

    // Add headers
    excelData.push([
        'Item / Dose Strength / Dosage Form',
        'Beginning Balance',
        'Qty Received',
        'Qty Consumed',
        'Positive Adjustment',
        'Negative Adjustment',
        'Closing Balance',
        'Stockout Days'
    ])

    // Add data rows
    props.reports.forEach(report => {
        if (report.items) {
            Object.entries(report.items).forEach(([category, categoryItems]) => {
                // Add category header
                excelData.push([category, '', '', '', '', '', '', ''])
                
                // Add items
                categoryItems.forEach(item => {
                    excelData.push([
                        item.product_name || '',
                        item.opening_balance || 0,
                        item.stock_received || 0,
                        item.stock_issued || 0,
                        item.positive_adjustments || 0,
                        item.negative_adjustments || 0,
                        calculateClosingBalance(item),
                        item.stockout_days || 0
                    ])
                })
            })
        }
    })

    // Create worksheet
    const ws = XLSX.utils.aoa_to_sheet(excelData)
    
    // Set column widths
    ws['!cols'] = [
        { width: 30 }, // Item name
        { width: 15 }, // Beginning Balance
        { width: 12 }, // Qty Received
        { width: 12 }, // Qty Consumed
        { width: 15 }, // Positive Adjustment
        { width: 15 }, // Negative Adjustment
        { width: 15 }, // Closing Balance
        { width: 12 }  // Stockout Days
    ]

    // Create workbook and add worksheet
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Monthly Report')

    // Generate filename
    const filename = `LMIS_Monthly_Report_${props.facility?.name || 'Facility'}_${props.reportPeriod}.xlsx`
    
    // Save file
    XLSX.writeFile(wb, filename)
}

const exportPdf = () => {
    if (!props.reports || props.reports.length === 0) {
        alert('No data to print')
        return
    }

    // Simple print functionality
    window.print()
}

const calculateClosingBalance = (item) => {
    return item.opening_balance + item.stock_received - item.stock_issued + item.positive_adjustments - item.negative_adjustments
}

const approveReport = async () => {
    Swal.fire({
        title: 'Approve Report?',
        text: 'Are you sure you want to approve this report?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, approve report'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('reports.monthly-inventory.approve'), {
                report_id: props.reports[0].id,
            })
            .then((response) => {
                if (response.data.success) {
                    Swal.fire({
                        title: 'Report Approved!',
                        text: 'The report has been successfully approved.',
                        icon: 'success',
                    })
                } else {
                    Swal.fire({
                        title: 'Error Approving Report',
                        text: 'An error occurred while approving the report.',
                        icon: 'error',
                    })
                }
            })
            .catch((error) => {
                console.error('Error approving report:', error)
                Swal.fire({
                    title: 'Error Approving Report',
                    text: 'An error occurred while approving the report.',
                    icon: 'error',
                })
            })
        }
    })
}

const rejectReport = async () => {
    Swal.fire({
        title: 'Reject Report?',
        text: 'Are you sure you want to reject this report?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, reject report'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('reports.monthly-inventory.reject'), {
                report_id: props.reports[0].id,
            })
            .then((response) => {
                if (response.data.success) {
                    Swal.fire({
                        title: 'Report Rejected!',
                        text: 'The report has been successfully rejected.',
                        icon: 'success',
                    })
                } else {
                    Swal.fire({
                        title: 'Error Rejecting Report',
                        text: 'An error occurred while rejecting the report.',
                        icon: 'error',
                    })
                }
            })
            .catch((error) => {
                console.error('Error rejecting report:', error)
                Swal.fire({
                    title: 'Error Rejecting Report',
                    text: 'An error occurred while rejecting the report.',
                    icon: 'error',
                })
            })
        }
    })
}
</script>

<style scoped>
/* Ensure proper table borders */
table {
    border-collapse: separate;
    border-spacing: 0;
}

th:first-child,
td:first-child {
    border-left: 1px solid #e5e7eb;
}

th:last-child,
td:last-child {
    border-right: 1px solid #e5e7eb;
}

thead th {
    border-top: 1px solid #e5e7eb;
    border-bottom: 2px solid #d1d5db;
}

tbody tr:last-child td {
    border-bottom: 1px solid #e5e7eb;
}
</style>
