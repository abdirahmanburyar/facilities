<template>
    <AuthenticatedLayout title="Order Details">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Order Details</h2>
                <Link
                    :href="route('orders.index')"
                    class="bg-gray-800 text-white rounded-full px-6 py-2.5 text-sm font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-700 transition-colors duration-200 ease-in-out flex items-center gap-2"
                >
                    <i class="fas fa-arrow-left"></i>
                    Back to Orders
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Order Header -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-sm font-semibold text-gray-600 mb-2">Order Information</h3>
                                <div class="space-y-2">
                                    <p class="text-sm">
                                        <span class="font-medium">Order Number:</span>
                                        <span class="ml-2">{{ order.order_number }}</span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Status:</span>
                                        <span :class="getStatusClass(order.status)" class="ml-2">
                                            {{ order.status }}
                                        </span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Order Date:</span>
                                        <span class="ml-2">{{ formatDate(order.order_date) }}</span>
                                    </p>
                                    <p class="text-sm" v-if="order.expected_date">
                                        <span class="font-medium">Expected Date:</span>
                                        <span class="ml-2">{{ formatDate(order.expected_date) }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-sm font-semibold text-gray-600 mb-2">Facility Information</h3>
                                <div class="space-y-2">
                                    <p class="text-sm">
                                        <span class="font-medium">Name:</span>
                                        <span class="ml-2">{{ order.facility.name }}</span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">City:</span>
                                        <span class="ml-2">{{ order.facility.city }}</span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Contact:</span>
                                        <span class="ml-2">{{ order.facility.contact_person }}</span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Phone:</span>
                                        <span class="ml-2">{{ order.facility.phone }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-sm font-semibold text-gray-600 mb-2">Order Summary</h3>
                                <div class="space-y-2">
                                    <p class="text-sm">
                                        <span class="font-medium">Total Items:</span>
                                        <span class="ml-2">{{ order.number_items }}</span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Created By:</span>
                                        <span class="ml-2">{{ order.user.name }}</span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Created At:</span>
                                        <span class="ml-2">{{ formatDate(order.created_at) }}</span>
                                    </p>
                                    <p class="text-sm">
                                        <span class="font-medium">Last Updated:</span>
                                        <span class="ml-2">{{ formatDate(order.updated_at) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Product Name
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Quantity
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in order.items" :key="item.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ item.product.name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ item.quantity }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mt-8" v-if="order.notes">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ order.notes }}</p>
                            </div>
                        </div>

                        <!-- Order Timeline -->
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Timeline</h3>
                            <div class="flow-root">
                                <ul role="list" class="-mb-8">
                                    <li v-if="order.approved_at">
                                        <div class="relative pb-8">
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-check text-white"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">Order Approved</p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time>{{ formatDate(order.approved_at) }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li v-if="order.processed_at">
                                        <div class="relative pb-8">
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-cog text-white"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">Order In Processing</p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time>{{ formatDate(order.processed_at) }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li v-if="order.dispatched_at">
                                        <div class="relative pb-8">
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-purple-500 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-truck text-white"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">Order Dispatched</p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time>{{ formatDate(order.dispatched_at) }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li v-if="order.delivered_at">
                                        <div class="relative pb-8">
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-gray-500 flex items-center justify-center ring-8 ring-white">
                                                        <i class="fas fa-flag-checkered text-white"></i>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">Order Delivered</p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time>{{ formatDate(order.delivered_at) }}</time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    order: {
        type: Object,
        required: true
    }
})

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusClass = (status) => {
    const classes = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full'
    const statusClasses = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'in processing': 'bg-blue-100 text-blue-800',
        'dispatched': 'bg-purple-100 text-purple-800',
        'delivered': 'bg-gray-100 text-gray-800'
    }
    return `${classes} ${statusClasses[status] || ''}`
}
</script>
