<script setup>
import { ref, watch, computed, reactive, onMounted, onBeforeUnmount } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import { useToast } from 'vue-toastification';
import axios from 'axios';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import moment from 'moment';
import Swal from 'sweetalert2';
import { TailwindPagination } from 'laravel-vue-pagination';

const props = defineProps({
    inventories: Object,
    products: Array,
    dosage: Array,
    category: Array,
    filters: Object,
    inventoryStatusCounts: Array
});

const toast = useToast();



// Search and filter states
const search = ref(props.filters.search || '');
const location = ref(props.filters.location || '');
const dosage = ref(props.filters.dosage || '');
const category = ref(props.filters.category || '');
const perPage = ref(props.filters.per_page || 25);
const loadedLocation = ref([]);
const isSubmitting = ref(false);

// Create reactive copy of inventory data for real-time updates
const currentInventories = reactive({
    data: [...props.inventories.data],
    meta: { ...props.inventories.meta }
});

// Add a watcher to update currentInventories when props.inventories changes
watch(() => props.inventories, (newInventories) => {
    console.log('[PUSHER-DEBUG] Props updated, syncing with currentInventories');
    currentInventories.data = [...newInventories.data];
    currentInventories.meta = { ...newInventories.meta };
}, { deep: true });


// Apply filters
const applyFilters = () => {
    const query = {}
    if (search.value) query.search = search.value
    if (location.value) query.location = location.value
    if (perPage.value) query.per_page = perPage.value
    if (dosage.value) query.dosage = dosage.value
    if (category.value) query.category = category.value
    if (props.filters.page) query.page = props.filters.page

    router.get(
        route('inventories.index'), query,
        {
            preserveState: true,
            preserveScroll: true,
            only: ['inventories', 'products', 'filters', 'inventoryStatusCounts', 'locations','dosage', 'category'],
        }
    );
};

// Watch for changes in search input
watch([
    () => search.value,
    () => location.value,
    () => perPage.value,
    () => dosage.value,
    () => category.value,
    () => props.filters.page,
], () => {
    applyFilters();
});


// Format date
const formatDate = (date) => {
    if (!date) return 'N/A';
    return moment(date).format('DD/MM/YYYY');
};

// Check if inventory is low
const isLowStock = (inventory) => {
    return inventory.quantity > 0 && inventory.quantity <= inventory.reorder_level;
};

// Check if inventory is out of stock
const isOutOfStock = (inventory) => {
    return inventory.quantity === 0;
};

// Check if product is expiring soon (within 30 days)
const isExpiringSoon = (inventory) => {
    if (!inventory.expiry_date) return false;
    const expiryDate = moment(inventory.expiry_date);
    const today = moment();
    const diffDays = expiryDate.diff(today, 'days');
    return diffDays <= 30 && diffDays > 0;
};

// Check if product is expired
const isExpired = (inventory) => {
    if (!inventory.expiry_date) return false;
    const expiryDate = moment(inventory.expiry_date);
    const today = moment();
    return expiryDate.isBefore(today);
};

// Computed properties for inventory status counts
const inStockCount = computed(() => {
    const stat = props.inventoryStatusCounts.find(s => s.status === 'in_stock');
    return stat.count;
});

const lowStockCount = computed(() => {
    const stat = props.inventoryStatusCounts.find(s => s.status === 'low_stock');
    return stat.count;
});

const outOfStockCount = computed(() => {
    const stat = props.inventoryStatusCounts.find(s => s.status === 'out_of_stock');
    return stat.count;
});

const expiredCount = computed(() => {
    const stat = props.inventoryStatusCounts.find(s => s.status === 'expired');
    return stat.count;
});


function getResults(page = 1) {
    props.filters.page = page;
    applyFilters();
}

</script>

<template>

    <Head title="Inventory Management" />

    <AuthenticatedLayout img="/assets/images/inventory.png" title="Management Your Inventory" description="Keeping Essentials Ready, Every Time">
        <div class="text-gray-900 mb-[100px]">
            <!-- Search and Filters -->
            <div class="mb-1 flex flex-wrap items-center gap-4">
                <div class="flex-grow relative">
                    <label>Search</label>
                    <TextInput v-model="search" type="text" class="w-full"
                        placeholder="Search by item name, barcode" />
                </div>

                <div class="flex flex-wrap items-center gap-4">                       
                    <div class="w-[300px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <Multiselect
                            v-model="category"
                            :options="props.category"
                            :searchable="true"
                            :close-on-select="true"
                            :show-labels="false"
                            placeholder="Select a category"
                            :allow-empty="true"
                            @input="applyFilters"
                        > </Multiselect>
                    </div>
                    <div class="w-[300px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dosage Form</label>
                        <Multiselect
                            v-model="dosage"
                            :options="props.dosage"
                            :searchable="true"
                            :close-on-select="true"
                            :show-labels="false"
                            placeholder="Select a dosage form"
                            :allow-empty="true"
                        > </Multiselect>
                    </div>
                </div>
            </div>
           <div class="flex justify-end mt-3">
            <select v-model="perPage"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-[200px] mb-3"
                >
                <option :value="25">25 per page</option>
                <option :value="50">50 per page</option>
                <option :value="100">100 per page</option>
            </select>
           </div>

            <!-- Add Button -->
            
            <div class="flex justify-between">
                <!-- Inventory Table -->
                <div class="overflow-auto w-full">
                    <table class="min-w-full border border-gray-200 divide-y divide-gray-200">
                        <thead class="border-b border-gray-200">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-900 uppercase tracking-wider border border-black">
                                    Item
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-900 uppercase tracking-wider border border-black">
                                    Category
                                </th>
                                <th class="cursor-pointer px-3 py-2 text-left text-xs font-medium text-gray-900 uppercase tracking-wider border border-black">
                                    In Stock
                                </th>
                                <th class="cursor-pointer px-3 py-2 text-left text-xs font-medium text-gray-900 uppercase tracking-wider border border-black">
                                    Batch Number
                                </th>
                                <th class="cursor-pointer px-3 py-2 text-left text-xs font-medium text-gray-900 uppercase tracking-wider border border-black">
                                    Expiry Date
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-900 uppercase tracking-wider border border-black">
                                    Status
                                </th>
                                <th
                                    class="px-3 py-2 text-left text-xs font-medium text-gray-900 uppercase tracking-wider border border-black">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="!currentInventories.data || currentInventories.data.length === 0" class=" border border-black">
                                <td colspan="10" class="px-3 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="1"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        <span class="text-lg font-medium">No inventory items found</span>
                                        <p class="text-sm text-gray-400 mt-1">Try adjusting your search or
                                            filters</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else v-for="inventory in currentInventories.data" :key="inventory.id"
                                class="hover:bg-gray-50">
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-sm text-gray-900  border border-black">
                                    <div v-if="inventory.product">
                                        <div class="font-medium text-gray-900 relative group cursor-help">
                                            {{ inventory.product.name }}
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-gray-500">Product not found</div>
                                </td>
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-sm text-gray-900  border border-black">
                                    <div class="flex flex-col">
                                        {{ inventory.product?.category?.name || 'N/A' }}
                                        <span class="text-xs">Dosage Form: {{ inventory.product?.dosage?.name || 'N/A' }}</span>
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-sm text-gray-900  border border-black">
                                    <div :class="{
                                        'font-medium': true,
                                        'text-red-600': isLowStock(inventory),
                                        'text-gray-900': !isLowStock(inventory),
                                    }">
                                        {{ inventory.quantity }}
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-sm text-gray-900  border border-black">
                                    {{ inventory.batch_number }}
                                </td>
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-sm text-gray-900  border border-black">
                                    <div :class="{
                                        'text-sm': true,
                                        'text-red-600': isExpired(inventory),
                                        'text-orange-500': isExpiringSoon(inventory) && !isExpired(inventory),
                                        'text-gray-900': !isExpiringSoon(inventory) && !isExpired(inventory),
                                    }">
                                        {{ formatDate(inventory.expiry_date) }}
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-2 whitespace-nowrap text-sm text-gray-900  border border-black">
                                    <div class="flex items-center space-x-2">
                                        <div v-if="isLowStock(inventory)" class="flex items-center">
                                            <img src="/assets/images/low_stock.png" title="Low Stock"
                                                class="w-6 h-6" alt="Low Stock" />
                                        </div>
                                        <div v-if="!isOutOfStock(inventory) && isExpiringSoon(inventory)" class="flex items-center">
                                            <img src="/assets/images/soon_expire.png" title="Expire soon"
                                                class="w-6 h-6" alt="Expire soon" />
                                        </div>
                                        <div v-if="isExpired(inventory)" class="flex items-center">
                                            <img src="/assets/images/expired_stock.png" title="Expired"
                                                class="w-6 h-6" alt="Expired" />
                                        </div>
                                        <div v-if="isOutOfStock(inventory)" class="flex items-center">
                                            <img src="/assets/images/out_stock.png" title="Out of Stock"
                                                class="w-6 h-6" alt="Out of Stock" />
                                        </div>
                                        <div v-if="!isLowStock(inventory) && !isExpiringSoon(inventory) && !isExpired(inventory) && !isOutOfStock(inventory)"
                                            class="flex items-center">
                                            <img src="/assets/images/in_stock.png" title="In Stock"
                                                class="w-6 h-6" alt="In Stock" />
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium  border border-black">
                                    <div class="flex items-center space-x-3">
                                        <Link :href="route('orders.create')" class="rounded-full w-[34px] cursor-pointer">
                                            <img src="/assets/images/ReorderAlert.png" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- Pagination - Only show if we have data -->
                    <div class="mt-3 flex justify-end items-center">
                        <TailwindPagination
                            :data="props.inventories"
                            @pagination-change-page="getResults"
                        />
                    </div>                        
                    
                </div>
                <div class="sticky top-0 z-10 shadow-sm p-2">
                    <div class="space-y-4">
                        <div class="flex items-center p-3 rounded-lg bg-green-50">
                            <img src="/assets/images/in_stock.png" class="w-[60px] h-[60px]" alt="In Stock" />
                            <div class="ml-4 flex flex-col">
                                <span class="text-xl font-bold text-green-600">{{ inStockCount }}</span>
                                <span class="ml-2 text-xs text-green-600">In Stock</span>
                            </div>
                        </div>
                        <div class="flex items-center p-3 rounded-lg bg-orange-50">
                            <img src="/assets/images/low_stock.png" class="w-[60px] h-[60px]" alt="Low Stock" />
                            <div class="ml-4 flex flex-col">
                                <span class="text-xl font-bold text-orange-600">{{ lowStockCount }}</span>
                                <span class="ml-2 text-xs text-orange-600">Low Stock</span>
                            </div>
                        </div>
                        <div class="flex items-center p-3 rounded-lg bg-red-50">
                            <img src="/assets/images/out_stock.png" class="w-[60px] h-[60px]" alt="Out of Stock" />
                            <div class="ml-4 flex flex-col">
                                <span class="text-xl font-bold text-red-600">{{ outOfStockCount }}</span>
                                <span class="ml-2 text-xs text-red-600">Out of Stock</span>
                            </div>
                        </div>
                        <div class="flex items-center p-3 rounded-lg bg-gray-50">
                            <img src="/assets/images/expired_stock.png" class="w-[60px] h-[60px]" alt="Expired" />
                            <div class="ml-4 flex flex-col">
                                <span class="text-xl font-bold text-gray-600">{{ expiredCount }}</span>
                                <span class="ml-2 text-xs text-gray-600">Expired</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>