<template>
    <AuthenticatedLayout>
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
                                :disabled="isExporting"
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
                                :disabled="isGenerating"
                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            >
                                <svg class="-ml-0.5 mr-1.5 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ isGenerating ? 'Generating...' : 'Generate Reports' }}
                            </button>
                            <Link 
                                :href="route('monthly-reports.create')"
                                class="inline-flex items-center px-3 py-1 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
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
                                <select v-model="filters.year" @change="applyFilters" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Years</option>
                                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                </select>
                            </div>

                            <!-- Month Filter -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Month</label>
                                <select v-model="filters.month" @change="applyFilters" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Months</option>
                                    <option v-for="(name, num) in months" :key="num" :value="num">{{ name }}</option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                                <select v-model="filters.status" @change="applyFilters" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                    v-model="filters.product_id"
                                    :options="products"
                                    :multiple="true"
                                    :close-on-select="false"
                                    :clear-on-select="false"
                                    :preserve-search="true"
                                    placeholder="Select items"
                                    label="name"
                                    track-by="id"
                                    :preselect-first="false"
                                    @input="applyFilters"
                                    class="text-xs"
                                />
                            </div>

                            <!-- Per Page -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Per Page</label>
                                <select v-model="filters.per_page" @change="applyFilters" class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="15">15</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>

                            <!-- Clear Filters -->
                            <div class="flex items-end">
                                <button @click="clearFilters" class="w-full px-3 py-1 bg-gray-500 text-white text-xs rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Reports Table -->
                    <div class="overflow-x-auto mx-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opening Balance</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adjustments</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closing Balance</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stockout Days</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="report in reports.data" :key="report.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-xs text-gray-900">
                                        {{ months[report.report_month] }} {{ report.report_year }}
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-900">
                                        <div class="font-medium">{{ report.product.name }}</div>
                                        <div class="text-gray-500" v-if="report.product.strength">{{ report.product.strength }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-900">{{ formatNumber(report.opening_balance) }}</td>
                                    <td class="px-4 py-2 text-xs text-green-600">{{ formatNumber(report.stock_received) }}</td>
                                    <td class="px-4 py-2 text-xs text-red-600">{{ formatNumber(report.stock_issued) }}</td>
                                    <td class="px-4 py-2 text-xs text-gray-900">
                                        <div v-if="report.positive_adjustments > 0" class="text-green-600">+{{ formatNumber(report.positive_adjustments) }}</div>
                                        <div v-if="report.negative_adjustments > 0" class="text-red-600">-{{ formatNumber(report.negative_adjustments) }}</div>
                                        <div v-if="report.positive_adjustments == 0 && report.negative_adjustments == 0">-</div>
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-900 font-medium">{{ formatNumber(report.closing_balance) }}</td>
                                    <td class="px-4 py-2 text-xs text-gray-900">
                                        <span v-if="report.stockout_days > 0" class="text-red-600">{{ report.stockout_days }} days</span>
                                        <span v-else class="text-green-600">0 days</span>
                                    </td>
                                    <td class="px-4 py-2 text-xs">
                                        <span :class="getStatusClass(report.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                            {{ formatStatus(report.status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="reports.data.length === 0">
                                    <td colspan="9" class="px-4 py-8 text-center text-xs text-gray-500">
                                        No monthly reports found. 
                                        <Link :href="route('monthly-reports.create')" class="text-blue-600 hover:text-blue-800">Create your first report</Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-4 py-3 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-xs text-gray-700">
                                Showing {{ reports.from || 0 }} to {{ reports.to || 0 }} of {{ reports.total }} results
                            </div>
                            <div class="flex space-x-1">
                                <Link 
                                    v-for="link in reports.links" 
                                    :key="link.label"
                                    :href="link.url"
                                    :class="[
                                        'px-2 py-1 text-xs border rounded',
                                        link.active 
                                            ? 'bg-blue-500 text-white border-blue-500' 
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                    :disabled="!link.url"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Multiselect from 'vue-multiselect';

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        Multiselect,
    },
    
    props: {
        reports: Object,
        filters: Object,
        products: Array,
        years: Array,
        months: Object,
    },
    
    data() {
        return {
            isExporting: false,
            isGenerating: false,
            filters: {
                year: this.filters?.year || '',
                month: this.filters?.month || '',
                status: this.filters?.status || '',
                product_id: this.filters?.product_id || [],
                per_page: this.filters?.per_page || 15,
            }
        };
    },
    
    methods: {
        applyFilters() {
            const params = {};
            
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key] !== '' && this.filters[key] !== null && this.filters[key] !== undefined) {
                    if (Array.isArray(this.filters[key]) && this.filters[key].length > 0) {
                        params[key] = this.filters[key].map(item => typeof item === 'object' ? item.id : item);
                    } else if (!Array.isArray(this.filters[key])) {
                        params[key] = this.filters[key];
                    }
                }
            });
            
            router.get(route('monthly-reports.index'), params, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        
        clearFilters() {
            this.filters = {
                year: '',
                month: '',
                status: '',
                product_id: [],
                per_page: 15,
            };
            this.applyFilters();
        },
        
        exportData() {
            this.isExporting = true;
            
            const params = new URLSearchParams();
            if (this.filters.year) params.append('year', this.filters.year);
            if (this.filters.month) params.append('month', this.filters.month);
            
            window.location.href = route('monthly-reports.export') + '?' + params.toString();
            
            setTimeout(() => {
                this.isExporting = false;
            }, 2000);
        },
        
        generateReports() {
            this.isGenerating = true;
            
            router.post(route('monthly-reports.generate'), {}, {
                preserveState: true,
                preserveScroll: true,
            });
            
            setTimeout(() => {
                this.isGenerating = false;
            }, 2000);
        },
        
        formatNumber(value) {
            return parseFloat(value || 0).toLocaleString();
        },
        
        formatStatus(status) {
            const statusMap = {
                'draft': 'Draft',
                'submitted': 'Submitted',
                'approved': 'Approved'
            };
            return statusMap[status] || status;
        },
        
        getStatusClass(status) {
            const classMap = {
                'draft': 'bg-gray-100 text-gray-800',
                'submitted': 'bg-yellow-100 text-yellow-800',
                'approved': 'bg-green-100 text-green-800'
            };
            return classMap[status] || 'bg-gray-100 text-gray-800';
        },
    }
};
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
