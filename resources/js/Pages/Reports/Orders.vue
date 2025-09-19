<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Orders Report
                </h2>
                <button
                    @click="exportCSV"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                    Export CSV
                </button>
            </div>
        </template>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ summary.total_orders }}</div>
                        <div class="text-sm text-blue-800">Total Orders</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600">{{ summary.pending }}</div>
                        <div class="text-sm text-yellow-800">Pending</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ summary.approved }}</div>
                        <div class="text-sm text-green-800">Approved</div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600">{{ summary.completed }}</div>
                        <div class="text-sm text-purple-800">Completed</div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-red-600">{{ summary.cancelled }}</div>
                        <div class="text-sm text-red-800">Cancelled</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Filter Orders</h3>
                        <button 
                            @click="clearFilters"
                            class="text-gray-500 hover:text-gray-700 text-sm flex items-center space-x-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Clear All</span>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search Orders</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    placeholder="Search by Order Number or Notes..."
                                    class="pl-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    @input="debouncedFilter"
                                />
                            </div>
                        </div>

                        <!-- Per Page -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Items per page</label>
                            <select
                                v-model="filters.per_page"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="15">15 items</option>
                                <option value="25">25 items</option>
                                <option value="50">50 items</option>
                                <option value="100">100 items</option>
                            </select>
                        </div>
                    </div>

                    <!-- Second row of filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                        <!-- Order Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
                            <select
                                v-model="filters.order_type"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Types</option>
                                <option value="regular">📦 Regular</option>
                                <option value="emergency">🚨 Emergency</option>
                                <option value="routine">📅 Routine</option>
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select
                                v-model="filters.status"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="applyFilters"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">🕐 Pending</option>
                                <option value="approved">✅ Approved</option>
                                <option value="completed">📦 Completed</option>
                                <option value="cancelled">❌ Cancelled</option>
                                <option value="processing">⚙️ Processing</option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                            <input
                                v-model="filters.start_date"
                                type="date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="applyFilters"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                            <input
                                v-model="filters.end_date"
                                type="date"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                @change="applyFilters"
                            />
                        </div>
                    </div>

                    <!-- Quick Filter Buttons -->
                    <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-200">
                        <span class="text-sm text-gray-600 font-medium mr-2">Quick filters:</span>
                        <button 
                            @click="setQuickFilter('today')"
                            class="px-3 py-1 text-xs bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200 transition-colors"
                        >
                            Today
                        </button>
                        <button 
                            @click="setQuickFilter('this_week')"
                            class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full hover:bg-green-200 transition-colors"
                        >
                            This Week
                        </button>
                        <button 
                            @click="setQuickFilter('this_month')"
                            class="px-3 py-1 text-xs bg-purple-100 text-purple-800 rounded-full hover:bg-purple-200 transition-colors"
                        >
                            This Month
                        </button>
                        <button 
                            @click="setQuickFilter('pending_only')"
                            class="px-3 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full hover:bg-yellow-200 transition-colors"
                        >
                            Pending Only
                        </button>
                        <button 
                            @click="setQuickFilter('completed_only')"
                            class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full hover:bg-green-200 transition-colors"
                        >
                            Completed Only
                        </button>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <Link 
                                        :href="route('orders.show', order.id)"
                                        class="text-blue-600 hover:text-blue-800 hover:underline font-medium"
                                    >
                                        {{ order.order_number || 'N/A' }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(order.order_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span :class="getOrderTypeClass(order.order_type)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ order.order_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span :class="getStatusClass(order.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ order.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ order.items?.length || 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ order.user?.name || 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(order.expected_date) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    <TailwindPagination 
                        :data="orders" 
                        @pagination-change-page="goToPage"
                        :limit="3"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { TailwindPagination } from 'laravel-vue-pagination';

export default {
    name: 'OrdersReport',
    components: {
        AuthenticatedLayout,
        TailwindPagination,
        Link
    },
    props: {
        orders: Object,
        filters: Object,
        summary: Object,
    },
    data() {
        return {
            filters: {
                search: this.filters.search || '',
                status: this.filters.status || '',
                order_type: this.filters.order_type || '',
                start_date: this.filters.start_date || '',
                end_date: this.filters.end_date || '',
                per_page: this.filters.per_page || '15',
            },
            debouncedFilter: debounce(this.applyFilters, 300),
        };
    },
    methods: {
        applyFilters() {
            router.get(route('reports.orders'), this.filters, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        clearFilters() {
            this.filters.search = '';
            this.filters.status = '';
            this.filters.order_type = '';
            this.filters.start_date = '';
            this.filters.end_date = '';
            this.filters.per_page = '15';
            
            this.applyFilters();
        },

        setQuickFilter(type) {
            const today = new Date();
            const formatDate = (date) => date.toISOString().split('T')[0];
            
            // Clear existing filters first
            this.filters.search = '';
            this.filters.status = '';
            this.filters.order_type = '';
            this.filters.start_date = '';
            this.filters.end_date = '';
            
            switch (type) {
                case 'today':
                    this.filters.start_date = formatDate(today);
                    this.filters.end_date = formatDate(today);
                    break;
                    
                case 'this_week':
                    const startOfWeek = new Date(today.setDate(today.getDate() - today.getDay()));
                    const endOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 6));
                    this.filters.start_date = formatDate(startOfWeek);
                    this.filters.end_date = formatDate(endOfWeek);
                    break;
                    
                case 'this_month':
                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    this.filters.start_date = formatDate(startOfMonth);
                    this.filters.end_date = formatDate(endOfMonth);
                    break;
                    
                case 'pending_only':
                    this.filters.status = 'pending';
                    break;
                    
                case 'completed_only':
                    this.filters.status = 'completed';
                    break;
            }
            
            this.applyFilters();
        },

        goToPage(page) {
            const params = {
                ...this.filters,
                page: page
            };
            
            router.get(route('reports.orders'), params, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        exportCSV() {
            const params = new URLSearchParams(this.filters);
            const url = route('reports.orders.export') + '?' + params.toString();
            window.open(url, '_blank');
        },

        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString('en-GB', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        },

        getOrderTypeClass(type) {
            const classes = {
                'regular': 'bg-blue-100 text-blue-800',
                'emergency': 'bg-red-100 text-red-800',
                'routine': 'bg-green-100 text-green-800',
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        },

        getOrderTypeLabel(type) {
            const labels = {
                'regular': 'Regular',
                'emergency': 'Emergency',
                'routine': 'Routine',
            };
            return labels[type] || 'Unknown';
        },

        getStatusClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'approved': 'bg-blue-100 text-blue-800',
                'completed': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800',
                'processing': 'bg-purple-100 text-purple-800',
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getStatusLabel(status) {
            const labels = {
                'pending': 'Pending',
                'approved': 'Approved',
                'completed': 'Completed',
                'cancelled': 'Cancelled',
                'processing': 'Processing',
            };
            return labels[status] || 'Unknown';
        },

        getTotalQuantity(order) {
            if (!order.items || order.items.length === 0) return 0;
            return order.items.reduce((total, item) => total + (item.quantity || 0), 0);
        },
    },
};
</script>
