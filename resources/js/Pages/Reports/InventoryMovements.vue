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

                                <!-- Start Date Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Start Date</label>
                                    <input 
                                        v-model="filters.start_date"
                                        type="date"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    />
                                </div>

                                <!-- End Date Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">End Date</label>
                                    <input 
                                        v-model="filters.end_date"
                                        type="date"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    />
                                </div>

                                <!-- Per Page Filter -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Records per page</label>
                                    <select 
                                        v-model="filters.per_page"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    >
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Filter Actions -->
                            <div class="flex flex-wrap gap-2 justify-end">
                                <button 
                                    @click="clearFilters"
                                    class="inline-flex items-center px-3 py-1 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 focus:bg-gray-600 active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Clear
                                </button>
                                <button 
                                    @click="applyFilters"
                                    class="inline-flex items-center px-3 py-1 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Apply
                                </button>
                            </div>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 mx-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                <div class="text-green-600 text-xs font-medium">Total Received</div>
                                <div class="text-green-900 text-lg font-bold">{{ formatNumber(summary.total_received || 0) }}</div>
                            </div>
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <div class="text-red-600 text-xs font-medium">Total Issued</div>
                                <div class="text-red-900 text-lg font-bold">{{ formatNumber(summary.total_issued || 0) }}</div>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <div class="text-blue-600 text-xs font-medium">Received Count</div>
                                <div class="text-blue-900 text-lg font-bold">{{ formatNumber(summary.received_count || 0) }}</div>
                            </div>
                            <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                                <div class="text-purple-600 text-xs font-medium">Issued Count</div>
                                <div class="text-purple-900 text-lg font-bold">{{ formatNumber(summary.issued_count || 0) }}</div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto mx-4">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Movement</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received Qty</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued Qty</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Batch</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="movement in movements.data" :key="movement.id" class="hover:bg-gray-50">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ movement.product?.name || 'N/A' }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span :class="`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${movementTypeClass(movement.movement_type)}`">
                                                {{ formatMovementType(movement.movement_type) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <span :class="`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${sourceTypeClass(movement.source_type)}`">
                                                {{ movement.source_type?.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ movement.movement_type === 'facility_received' ? formatNumber(movement.quantity) : '-' }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ movement.movement_type === 'facility_issued' ? formatNumber(movement.quantity) : '-' }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ movement.batch_number || '-' }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ movement.expiry_date ? formatDate(movement.expiry_date) : '-' }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ movement.reference_number || '-' }}
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ formatDateTime(movement.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="!movements.data || movements.data.length === 0">
                                        <td colspan="9" class="px-3 py-8 text-center text-sm text-gray-500">
                                            No inventory movements found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="movements.data && movements.data.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <Link 
                                        v-if="movements.prev_page_url"
                                        :href="movements.prev_page_url" 
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                    <Link 
                                        v-if="movements.next_page_url"
                                        :href="movements.next_page_url" 
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing
                                            <span class="font-medium">{{ movements.from || 0 }}</span>
                                            to
                                            <span class="font-medium">{{ movements.to || 0 }}</span>
                                            of
                                            <span class="font-medium">{{ movements.total || 0 }}</span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <Link 
                                                v-if="movements.prev_page_url"
                                                :href="movements.prev_page_url"
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                            >
                                                <span class="sr-only">Previous</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </Link>
                                            
                                            <template v-for="link in movements.links" :key="link.label">
                                                <Link 
                                                    v-if="link.url && !link.label.includes('Previous') && !link.label.includes('Next')"
                                                    :href="link.url"
                                                    :class="[
                                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                        link.active 
                                                            ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
                                                            : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                    ]"
                                                    v-html="link.label"
                                                />
                                                <span 
                                                    v-else-if="!link.url && !link.label.includes('Previous') && !link.label.includes('Next')"
                                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
                                                    v-html="link.label"
                                                />
                                            </template>
                                            
                                            <Link 
                                                v-if="movements.next_page_url"
                                                :href="movements.next_page_url"
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                            >
                                                <span class="sr-only">Next</span>
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </Link>
                                        </nav>
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
            summary: {},
            filters: {
                product_id: [],
                movement_type: [],
                source_type: [],
                start_date: '',
                end_date: '',
                per_page: 25
            },
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
            
            router.get(route('reports.inventory-movements'), filterData, {
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
                
                window.open(`${route('reports.inventory-movements.export')}?${params}`, '_blank')
            } catch (error) {
                console.error('Export failed:', error)
            } finally {
                this.isExporting = false
            }
        },
        async loadSummary() {
            try {
                const response = await fetch(route('reports.inventory-movements.summary'))
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
