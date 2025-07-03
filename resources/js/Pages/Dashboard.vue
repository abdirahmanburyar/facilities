<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    inventoryStatus: Object,
    recentActivity: Object,
    statistics: Object,
    lowStockAlerts: Array,
    monthlyReportStatus: Object,
    inventoryMovements: Object,
});

// Computed properties for formatting
const formatNumber = (number) => {
    return new Intl.NumberFormat().format(number || 0);
};

const formatDateTime = (dateTime) => {
    return new Date(dateTime).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusBadgeClass = (status) => {
    const statusClasses = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'in_process': 'bg-blue-100 text-blue-800',
        'dispatched': 'bg-purple-100 text-purple-800',
        'received': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'draft': 'bg-gray-100 text-gray-800',
        'submitted': 'bg-blue-100 text-blue-800',
        'not_generated': 'bg-red-100 text-red-800',
    };
    return statusClasses[status] || 'bg-gray-100 text-gray-800';
};

const getAlertClass = (alertLevel) => {
    return alertLevel === 'critical' 
        ? 'bg-red-50 border-red-200 text-red-700' 
        : 'bg-yellow-50 border-yellow-200 text-yellow-700';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout 
        title="Supply Insight Hub" 
        description="All Key Metrics, One Place"
        img="/assets/images/dashboard.png"
    >
        <div class="p-4 space-y-6">
            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Inventory Status Cards -->
                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <img src="/assets/images/in_stock.png" alt="In Stock" class="w-8 h-8">
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ formatNumber(inventoryStatus.in_stock) }}
                            </div>
                            <div class="text-sm text-gray-500">In Stock</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                            <img src="/assets/images/low_stock.png" alt="Low Stock" class="w-8 h-8">
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ formatNumber(inventoryStatus.low_stock) }}
                            </div>
                            <div class="text-sm text-gray-500">Low Stock</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                            <img src="/assets/images/out_stock.png" alt="Out of Stock" class="w-8 h-8">
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ formatNumber(inventoryStatus.out_of_stock) }}
                            </div>
                            <div class="text-sm text-gray-500">Out of Stock</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                            <img src="/assets/images/expired_stock.png" alt="Expired" class="w-8 h-8">
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ formatNumber(inventoryStatus.expired) }}
                            </div>
                            <div class="text-sm text-gray-500">Expired</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-clock text-orange-600 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">
                                {{ formatNumber(inventoryStatus.soon_expiring) }}
                            </div>
                            <div class="text-sm text-gray-500">Soon Expiring</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Row -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Total Products</div>
                            <div class="text-xl font-bold text-gray-900">
                                {{ formatNumber(statistics.total_products) }}
                            </div>
                        </div>
                        <i class="fas fa-boxes text-blue-500 text-2xl"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Received This Month</div>
                            <div class="text-xl font-bold text-green-600">
                                {{ formatNumber(statistics.total_received_this_month) }}
                            </div>
                        </div>
                        <i class="fas fa-arrow-down text-green-500 text-2xl"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Issued This Month</div>
                            <div class="text-xl font-bold text-red-600">
                                {{ formatNumber(statistics.total_issued_this_month) }}
                            </div>
                        </div>
                        <i class="fas fa-arrow-up text-red-500 text-2xl"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Active Orders</div>
                            <div class="text-xl font-bold text-blue-600">
                                {{ formatNumber(statistics.active_orders) }}
                            </div>
                        </div>
                        <i class="fas fa-shopping-cart text-blue-500 text-2xl"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-4 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Active Transfers</div>
                            <div class="text-xl font-bold text-purple-600">
                                {{ formatNumber(statistics.active_transfers) }}
                            </div>
                        </div>
                        <i class="fas fa-exchange-alt text-purple-500 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Recent Activity Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Low Stock Alerts -->
                    <div v-if="lowStockAlerts.length > 0" class="bg-white rounded-lg shadow-md border border-gray-100">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                Low Stock Alerts
                            </h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <div 
                                v-for="alert in lowStockAlerts" 
                                :key="alert.product_name"
                                class="p-3 rounded-lg border"
                                :class="getAlertClass(alert.alert_level)"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-medium">{{ alert.product_name }}</div>
                                        <div class="text-sm">
                                            Stock: {{ alert.current_stock }} | Reorder Level: {{ alert.reorder_level }}
                                        </div>
                                        <div class="text-xs">AMC: {{ alert.amc }}</div>
                                    </div>
                                    <span 
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="alert.alert_level === 'critical' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'"
                                    >
                                        {{ alert.alert_level.toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="bg-white rounded-lg shadow-md border border-gray-100">
                        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Orders</h3>
                            <Link :href="route('orders.index')" class="text-blue-600 hover:text-blue-800 text-sm">
                                View All
                            </Link>
                        </div>
                        <div class="p-4">
                            <div v-if="recentActivity.orders.length === 0" class="text-gray-500 text-center py-4">
                                No recent orders
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="order in recentActivity.orders" 
                                    :key="order.id"
                                    class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
                                >
                                    <div>
                                        <div class="font-medium">{{ order.order_number }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ order.items_count }} items • {{ formatDateTime(order.created_at) }}
                                        </div>
                                    </div>
                                    <span 
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="getStatusBadgeClass(order.status)"
                                    >
                                        {{ order.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transfers -->
                    <div class="bg-white rounded-lg shadow-md border border-gray-100">
                        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Transfers</h3>
                            <Link :href="route('transfers.index')" class="text-blue-600 hover:text-blue-800 text-sm">
                                View All
                            </Link>
                        </div>
                        <div class="p-4">
                            <div v-if="recentActivity.transfers.length === 0" class="text-gray-500 text-center py-4">
                                No recent transfers
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="transfer in recentActivity.transfers" 
                                    :key="transfer.id"
                                    class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
                                >
                                    <div>
                                        <div class="font-medium flex items-center">
                                            {{ transfer.transferID }}
                                            <span 
                                                class="ml-2 px-2 py-0.5 rounded text-xs"
                                                :class="transfer.type === 'incoming' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                                            >
                                                {{ transfer.type.toUpperCase() }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ transfer.items_count }} items • {{ formatDateTime(transfer.created_at) }}
                                        </div>
                                    </div>
                                    <span 
                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                        :class="getStatusBadgeClass(transfer.status)"
                                    >
                                        {{ transfer.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-md border border-gray-100">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <Link 
                                :href="route('orders.create')" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center block transition-colors"
                                v-if="$page.props.auth.can.order_create"
                            >
                                <i class="fas fa-plus mr-2"></i>Create Order
                            </Link>
                            <Link 
                                :href="route('transfers.create')" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-center block transition-colors"
                                v-if="$page.props.auth.can.transfer_create"
                            >
                                <i class="fas fa-exchange-alt mr-2"></i>Create Transfer
                            </Link>
                            <Link 
                                :href="route('dispence.create')" 
                                class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-center block transition-colors"
                                v-if="$page.props.auth.can.dispence_create"
                            >
                                <i class="fas fa-pills mr-2"></i>Dispense Medicine
                            </Link>
                            <Link 
                                :href="route('reports.index')" 
                                class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-center block transition-colors"
                            >
                                <i class="fas fa-chart-bar mr-2"></i>View Reports
                            </Link>
                        </div>
                    </div>

                    <!-- Monthly Report Status -->
                    <div class="bg-white rounded-lg shadow-md border border-gray-100">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Monthly Reports</h3>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="border-l-4 border-blue-500 pl-3">
                                <div class="font-medium">{{ monthlyReportStatus.current_month.period }}</div>
                                <div class="text-sm text-gray-500">
                                    Status: 
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-xs font-medium ml-1"
                                        :class="getStatusBadgeClass(monthlyReportStatus.current_month.status)"
                                    >
                                        {{ monthlyReportStatus.current_month.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div class="border-l-4 border-gray-300 pl-3">
                                <div class="font-medium">{{ monthlyReportStatus.last_month.period }}</div>
                                <div class="text-sm text-gray-500">
                                    Status: 
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-xs font-medium ml-1"
                                        :class="getStatusBadgeClass(monthlyReportStatus.last_month.status)"
                                    >
                                        {{ monthlyReportStatus.last_month.status.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <Link 
                                :href="route('reports.monthly-inventory')" 
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-center block transition-colors text-sm"
                            >
                                Manage Reports
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Dispenses -->
                    <div class="bg-white rounded-lg shadow-md border border-gray-100">
                        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Dispenses</h3>
                            <Link :href="route('dispence.index')" class="text-blue-600 hover:text-blue-800 text-sm">
                                View All
                            </Link>
                        </div>
                        <div class="p-4">
                            <div v-if="recentActivity.dispenses.length === 0" class="text-gray-500 text-center py-4">
                                No recent dispenses
                            </div>
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="dispense in recentActivity.dispenses" 
                                    :key="dispense.id"
                                    class="p-3 bg-gray-50 rounded-lg"
                                >
                                    <div class="font-medium">{{ dispense.dispence_number }}</div>
                                    <div class="text-sm text-gray-500">{{ dispense.patient_name }}</div>
                                    <div class="text-xs text-gray-400">
                                        {{ dispense.items_count }} items • {{ formatDateTime(dispense.created_at) }}
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
