<template>
    <AuthenticatedLayout>
        <Head title="Inventory Movements" />
        
            <div class="mb-[80px]">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="text-gray-900">
                        <!-- Header with Export Button -->
                        <div class="flex justify-between items-center mb-4 px-4 py-3">
                            <h2 class="text-lg font-bold text-gray-900">Inventory Movements</h2>
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
                        </div>

                        <!-- Filters -->
                        <div class="bg-gray-50 p-3 mx-4 rounded-lg mb-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 mb-3">
                                <!-- Item Filter -->
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
                                        :show-labels="false"
                                    />
                                </div>

                                <!-- Movement Type Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Movement Type</label>
                                    <Multiselect
                                        v-model="filters.movement_type"
                                        :options="movementTypeOptions"
                                        :multiple="true"
                                        :close-on-select="false"
                                        :clear-on-select="false"
                                        :preserve-search="true"
                                        placeholder="Select movement types"
                                        label="label"
                                        track-by="value"
                                        :preselect-first="false"
                                        :show-labels="false"
                                    />
                                </div>

                                <!-- Source Type Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Source Type</label>
                                    <Multiselect
                                        v-model="filters.source_type"
                                        :options="sourceTypeOptions"
                                        :multiple="true"
                                        :close-on-select="false"
                                        :clear-on-select="false"
                                        :preserve-search="true"
                                        placeholder="Select source types"
                                        label="label"
                                        track-by="value"
                                        :preselect-first="false"
                                        :show-labels="false"
                                    />
                                </div>

                                <!-- Start Date -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                                    <input 
                                        type="date" 
                                        v-model="filters.start_date"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <!-- End Date -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">End Date</label>
                                    <input 
                                        type="date" 
                                        v-model="filters.end_date"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </div>

                                <!-- Per Page -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Per Page</label>
                                    <select 
                                        v-model="filters.per_page"
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Filter Buttons -->
                            <div class="flex space-x-2">
                                <button 
                                    @click="applyFilters" 
                                    class="inline-flex items-center px-3 py-1 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Apply Filters
                                </button>
                                <button 
                                    @click="clearFilters" 
                                    class="inline-flex items-center px-3 py-1 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Clear Filters
                                </button>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4" v-if="summary">
                            <div class="bg-white rounded-lg shadow-sm border p-3">
                                <div class="flex items-center">
                                    <div class="p-1 bg-green-100 rounded-lg">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-2">
                                        <p class="text-xs font-medium text-gray-600">Total Received</p>
                                        <p class="text-lg font-bold text-gray-900">{{ formatNumber(summary.total_facility_received) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-sm border p-3">
                                <div class="flex items-center">
                                    <div class="p-1 bg-red-100 rounded-lg">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-2">
                                        <p class="text-xs font-medium text-gray-600">Total Issued</p>
                                        <p class="text-lg font-bold text-gray-900">{{ formatNumber(summary.total_facility_issued) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-sm border p-3">
                                <div class="flex items-center">
                                    <div class="p-1 bg-blue-100 rounded-lg">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-2">
                                        <p class="text-xs font-medium text-gray-600">Received Transactions</p>
                                        <p class="text-lg font-bold text-gray-900">{{ summary.facility_received_count }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow-sm border p-3">
                                <div class="flex items-center">
                                    <div class="p-1 bg-purple-100 rounded-lg">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-2">
                                        <p class="text-xs font-medium text-gray-600">Issued Transactions</p>
                                        <p class="text-lg font-bold text-gray-900">{{ summary.facility_issued_count }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Movements Table -->
                        <div class="bg-white rounded-lg shadow-sm border">
                            <div class="px-4 py-3 border-b border-gray-200">
                                <h2 class="text-lg font-semibold">Inventory Movements</h2>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Movement Type</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received/Issued Quantity</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="movement in movements.data" :key="movement.id" class="hover:bg-gray-50">
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                {{ formatDateTime(movement.movement_date) }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                {{ movement.product?.name || '-' }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', movementTypeClass(movement.movement_type)]">
                                                    {{ formatMovementType(movement.movement_type) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                <span v-if="movement.facility_received_quantity > 0" class="text-green-600 font-medium">
                                                    +{{ formatNumber(movement.facility_received_quantity) }}
                                                </span>
                                                <span v-else-if="movement.facility_issued_quantity > 0" class="text-red-600 font-medium">
                                                    -{{ formatNumber(movement.facility_issued_quantity) }}
                                                </span>
                                                <span v-else>-</span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', sourceTypeClass(movement.source_type)]">
                                                    {{ movement.source_type }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                {{ movement.batch_number || '-' }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                <span v-if="movement.expiry_date">
                                                    {{ formatDate(movement.expiry_date) }}
                                                </span>
                                                <span v-else>-</span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                {{ movement.reference_number || '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="px-4 py-3 border-t border-gray-200" v-if="movements.links">
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
                                                'px-3 py-1 text-sm rounded-md',
                                                link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                            ]"
                                            v-html="link.label"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </AuthenticatedLayout>
</template>

<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import Multiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.css'
import '@/Components/multiselect.css'

export default {
    components: {
        AuthenticatedLayout,
        Head,
        Link,
        Multiselect
    },
    props: {
        movements: Object,
        products: Array
    },
    data() {
        return {
            filters: {
                product_id: [],
                movement_type: [],
                source_type: [],
                start_date: '',
                end_date: '',
                per_page: 25
            },
            summary: null,
            movementTypeOptions: [
                { label: 'Received Quantity', value: 'facility_received' },
                { label: 'Issued Quantity', value: 'facility_issued' }
            ],
            sourceTypeOptions: [
                { label: 'Transfer', value: 'transfer' },
                { label: 'Order', value: 'order' },
                { label: 'Dispense', value: 'dispense' }
            ],
            isExporting: false
        }
    },
    mounted() {
        this.loadSummary()
    },
    methods: {
        applyFilters() {
            const filterData = {
                product_id: this.filters.product_id.map(p => p.id || p),
                movement_type: this.filters.movement_type.map(m => m.value || m),
                source_type: this.filters.source_type.map(s => s.value || s),
                start_date: this.filters.start_date,
                end_date: this.filters.end_date,
                per_page: this.filters.per_page
            }
            
            router.get(route('facility-inventory-movements.index'), filterData, {
                preserveState: true,
                preserveScroll: true
            })
        },
        clearFilters() {
            this.filters = {
                product_id: [],
                movement_type: [],
                source_type: [],
                start_date: '',
                end_date: '',
                per_page: 25
            }
            this.applyFilters()
        },
        async exportData() {
            this.isExporting = true
            try {
                const filterData = {
                    product_id: this.filters.product_id.map(p => p.id || p),
                    movement_type: this.filters.movement_type.map(m => m.value || m),
                    source_type: this.filters.source_type.map(s => s.value || s),
                    start_date: this.filters.start_date,
                    end_date: this.filters.end_date
                }
                
                const params = new URLSearchParams()
                Object.keys(filterData).forEach(key => {
                    if (Array.isArray(filterData[key])) {
                        filterData[key].forEach(value => {
                            if (value) params.append(`${key}[]`, value)
                        })
                    } else if (filterData[key]) {
                        params.append(key, filterData[key])
                    }
                })
                
                window.open(`${route('facility-inventory-movements.export')}?${params}`, '_blank')
            } catch (error) {
                console.error('Export failed:', error)
            } finally {
                this.isExporting = false
            }
        },
        async loadSummary() {
            try {
                const response = await fetch(route('facility-inventory-movements.summary'))
                this.summary = await response.json()
            } catch (error) {
                console.error('Failed to load summary:', error)
            }
        },
        formatNumber(number) {
            return new Intl.NumberFormat().format(number)
        },
        formatDate(date) {
            return new Date(date).toLocaleDateString()
        },
        formatDateTime(datetime) {
            return new Date(datetime).toLocaleString()
        },
        formatMovementType(type) {
            if (type === 'facility_received') return 'Received Quantity'
            if (type === 'facility_issued') return 'Issued Quantity'
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
    }
}
</script>
