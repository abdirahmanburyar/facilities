<template>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Facility Inventory Movements</h1>
            <p class="text-gray-600">Track all facility received and issued inventory movements</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Filters</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Facility</label>
                    <select v-model="filters.facility_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All Facilities</option>
                        <option v-for="facility in facilities" :key="facility.id" :value="facility.id">
                            {{ facility.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                    <select v-model="filters.product_id" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All Products</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Movement Type</label>
                    <select v-model="filters.movement_type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All Types</option>
                        <option value="facility_received">Facility Received</option>
                        <option value="facility_issued">Facility Issued</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Source Type</label>
                    <select v-model="filters.source_type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All Sources</option>
                        <option value="transfer">Transfer</option>
                        <option value="order">Order</option>
                        <option value="dispense">Dispense</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input 
                        type="date" 
                        v-model="filters.start_date" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input 
                        type="date" 
                        v-model="filters.end_date" 
                        class="w-full border border-gray-300 rounded-md px-3 py-2"
                    >
                </div>

                <div class="flex items-end space-x-2">
                    <button 
                        @click="applyFilters" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                    >
                        Apply Filters
                    </button>
                    <button 
                        @click="clearFilters" 
                        class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition-colors"
                    >
                        Clear
                    </button>
                </div>

                <div class="flex items-end">
                    <button 
                        @click="exportData" 
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
                    >
                        Export CSV
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6" v-if="summary">
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Facility Received</p>
                        <p class="text-2xl font-bold text-gray-900">{{ formatNumber(summary.total_facility_received) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Facility Issued</p>
                        <p class="text-2xl font-bold text-gray-900">{{ formatNumber(summary.total_facility_issued) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Received Transactions</p>
                        <p class="text-2xl font-bold text-gray-900">{{ summary.facility_received_count }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Issued Transactions</p>
                        <p class="text-2xl font-bold text-gray-900">{{ summary.facility_issued_count }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Movements Table -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Facility Inventory Movements</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Movement Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility Received</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility Issued</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch/Expiry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="movement in movements.data" :key="movement.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatDateTime(movement.movement_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ movement.facility?.name || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ movement.product?.name || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="movementTypeClass(movement.movement_type)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ formatMovementType(movement.movement_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="sourceTypeClass(movement.source_type)" class="px-2 py-1 text-xs font-semibold rounded-full">
                                    {{ movement.source_type.charAt(0).toUpperCase() + movement.source_type.slice(1) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span v-if="movement.facility_received_quantity > 0" class="text-green-600 font-semibold">
                                    +{{ formatNumber(movement.facility_received_quantity) }}
                                </span>
                                <span v-else>-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span v-if="movement.facility_issued_quantity > 0" class="text-red-600 font-semibold">
                                    -{{ formatNumber(movement.facility_issued_quantity) }}
                                </span>
                                <span v-else>-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div v-if="movement.batch_number || movement.expiry_date">
                                    <div v-if="movement.batch_number">Batch: {{ movement.batch_number }}</div>
                                    <div v-if="movement.expiry_date">Exp: {{ formatDate(movement.expiry_date) }}</div>
                                </div>
                                <span v-else>-</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ movement.reference_number || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200" v-if="movements.links">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ movements.from }} to {{ movements.to }} of {{ movements.total }} results
                    </div>
                    <div class="flex space-x-2">
                        <Link 
                            v-for="link in movements.links" 
                            :key="link.label"
                            :href="link.url"
                            :class="[
                                'px-3 py-2 text-sm rounded-md',
                                link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'

export default {
    components: {
        Link
    },
    props: {
        movements: Object,
        facilities: Array,
        products: Array,
        filters: Object
    },
    data() {
        return {
            summary: null
        }
    },
    mounted() {
        this.loadSummary()
    },
    methods: {
        applyFilters() {
            router.get(route('facility-inventory-movements.index'), this.filters, {
                preserveState: true,
                preserveScroll: true
            })
        },
        clearFilters() {
            this.filters = {
                facility_id: '',
                product_id: '',
                movement_type: '',
                source_type: '',
                start_date: '',
                end_date: ''
            }
            this.applyFilters()
        },
        exportData() {
            const params = new URLSearchParams(this.filters)
            window.open(`${route('facility-inventory-movements.export')}?${params}`)
        },
        async loadSummary() {
            try {
                const params = new URLSearchParams(this.filters)
                const response = await fetch(`${route('facility-inventory-movements.summary')}?${params}`)
                const data = await response.json()
                this.summary = data.summary
            } catch (error) {
                console.error('Error loading summary:', error)
            }
        },
        formatNumber(number) {
            return new Intl.NumberFormat().format(number || 0)
        },
        formatDateTime(dateTime) {
            return new Date(dateTime).toLocaleString()
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString()
        },
        formatMovementType(type) {
            return type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
        },
        movementTypeClass(type) {
            return type === 'facility_received' 
                ? 'bg-green-100 text-green-800' 
                : 'bg-red-100 text-red-800'
        },
        sourceTypeClass(type) {
            const classes = {
                transfer: 'bg-blue-100 text-blue-800',
                order: 'bg-purple-100 text-purple-800',
                dispense: 'bg-orange-100 text-orange-800'
            }
            return classes[type] || 'bg-gray-100 text-gray-800'
        }
    },
    watch: {
        filters: {
            handler() {
                this.loadSummary()
            },
            deep: true
        }
    }
}
</script>
