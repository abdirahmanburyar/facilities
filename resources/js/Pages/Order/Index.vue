<template>

    <Head title="Orders" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-50 py-4">
            <div class="w-full px-4">
                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-6 gap-4">
                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden text-xs">
                            <!-- Order Selection -->
                            <select v-model="order_type"
                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Order Type</option>
                                <option value="Monthly">Monthly</option>
                                <option value="Amargency">Amargency</option>
                            </select>
                            <button @click="confirmCreateOrder" :disabled="order_type === ''"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create New Order
                            </button>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Order</label>
                                <select v-model="selectedOrderId" @change="handleOrderChange"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-xs focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Choose an order...</option>
                                    <option v-for="order in props.orders" :key="order.id" :value="order.id">
                                        {{ order.order_number }} ({{ order.status }})
                                    </option>
                                </select>
                            </div>

                            <!-- Current Order Info -->
                            <div v-if="currentOrder" class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 text-xs">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-3">
                                        Current Order
                                    </h3>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800': currentOrder.status === 'pending',
                                            'bg-green-100 text-green-800': currentOrder.status === 'completed'
                                        }">
                                        {{ currentOrder.status }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium text-gray-900">{{ currentOrder.order_number }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-900">{{ new
                                            Date(currentOrder.order_date).toLocaleDateString() }}</span>
                                        <button @click="handleOrderSubmit" :disabled="orderSubmitted"
                                            class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            {{ orderSubmitted ? 'Submitting...' : 'Submit Order' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Item Form -->
                            <div v-if="currentOrder" class="p-4 border-t border-gray-200">
                                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Add Item
                                </h3>
                                <form @submit.prevent="submitOrder" class="space-y-4">
                                    <div class="relative">
                                        <div class="relative">
                                            <textarea v-model="form.product_name" @keyup.enter="searchProduct"
                                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Scan or type barcode..." rows="2"></textarea>
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="relative rounded-md shadow-sm">
                                            <input type="number" v-model="form.quantity"
                                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Enter quantity" required min="1">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" :disabled="isAdded || !form.product_id"
                                        class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg v-if="isAdded" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        {{ isAdded ? 'Processing...' : 'Add to Order' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="lg:col-span-5">
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <!-- Header -->
                            <div class="border-b border-gray-200">
                                <div class="p-4">
                                    <h1 class="text-2xl font-semibold text-gray-900">Order Items</h1>
                                </div>
                                <!-- Tabs -->
                                <div class="border-b border-gray-200 mb-4">
                                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                        <button v-for="tab in tabs" 
                                            :key="tab.key" 
                                            @click="switchTab(tab.key)"
                                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                                            :class="[
                                                currentTab === tab.key
                                                    ? 'border-indigo-500 text-indigo-600'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            ]"
                                        >
                                            <span class="flex items-center">
                                                <i :class="['mr-2', tab.icon]"></i>
                                                {{ tab.name }}
                                                <span v-if="tab.count" 
                                                    class="ml-2 px-2 py-0.5 text-xs rounded-full"
                                                    :class="[
                                                        currentTab === tab.key
                                                            ? 'bg-indigo-100 text-indigo-600'
                                                            : 'bg-gray-100 text-gray-900'
                                                    ]"
                                                >
                                                    {{ tab.count }}
                                                </span>
                                            </span>
                                        </button>
                                    </nav>
                                </div>
                            </div>
                            <!-- Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Item
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Quantity
                                            </th>
                                            <th
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="order in props.currentOrder?.items" :key="order.id"
                                            class="hover:bg-gray-50 transition-colors duration-150">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ order.product.name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ order.quantity }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <button @click="removeItem(order)" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <button @click="editItem(order)" class="ml-2 text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="!props.currentOrder?.items?.length">
                                            <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">
                                                No items in the current order
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onBeforeUnmount, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';

const STORAGE_KEY = 'current_order';
const currentOrder = ref(null);
const isAdded = ref(false);
const searchQuery = ref('');
const selectedProduct = ref(null);
const selectedOrderId = ref('');

const props = defineProps({
    orders: {
        type: Array,
        required: true
    },
    products: {
        type: Array,
        required: true
    },
    currentOrder: {
        type: Object,
        default: null
    },
    tab: {
        type: String,
        default: 'items'
    }
});

currentOrder.value = props.currentOrder;

const form = ref({
    id: null,
    product_id: null,
    product_name: null,
    quantity: 0,
    order_id: null
});

watch(() => props.currentOrder, (newOrder) => {
    currentOrder.value = newOrder;
    if (newOrder) {
        selectedOrderId.value = newOrder.id;
        form.value.order_id = newOrder.id;  
        localStorage.setItem(STORAGE_KEY, JSON.stringify(newOrder));
    } else {
        resetForm();  
    }
}, { immediate: true });

// Load order from localStorage on mount
onMounted(() => {
    const savedOrder = localStorage.getItem(STORAGE_KEY);
    if (savedOrder) {
        currentOrder.value = JSON.parse(savedOrder);
    }

    if (props.orders.length > 0) {
        selectedOrderId.value = props.orders[0].id;
    }
});

// Clear localStorage on unmount
onBeforeUnmount(() => {
    localStorage.removeItem(STORAGE_KEY);
});

const tabs = [
    { key: 'items', name: 'Items', icon: 'fas fa-box', count: null }
];

const currentTab = computed(() => props.tab || 'items');

const orderSubmitted = ref(false);

const handleOrderSubmit = () => {
    orderSubmitted.value = true;
    axios.post(route('orders.submit'))
        .then((response) => {
            orderSubmitted.value = false;
            reloadOrder();
            Swal.fire({
                icon: 'success',
                title: response.data,
                showConfirmButton: false,
                timer: 1500
            });
        })
        .catch((error) => {
            orderSubmitted.value = false;
            console.error(error);
            Swal.fire({
                icon: 'error',
                title: 'Error submitting order',
                text: error.response.data
            });
        });
}

const order_type = ref(currentOrder.value?.order_type || 'Monthly');

const confirmCreateOrder = () => {
    if (!order_type.value) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select an order type'
        });
        return;
    }

    Swal.fire({
        title: 'Are you sure?',
        text: 'This will create a new order',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, create order'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('orders.create', { order_type: order_type.value }))
                .then(response => {
                    const newOrder = response.data.order;
                    
                    // Update all order-related states
                    currentOrder.value = newOrder;
                    selectedOrderId.value = newOrder.id;
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(newOrder));
                    
                    // Update form with new reactive object
                    form.value = Object.assign({}, form.value, {
                        id: null,
                        product_id: null,
                        product_name: null,
                        quantity: 0,
                        order_id: newOrder.id
                    });
                    
                    // Reload the page with new order
                    reloadOrder(newOrder.id);

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.response?.data || 'Failed to create order'
                    });
                });
        }
    });
};

function reloadOrder(id = null) {
    form.value.order_id = id || currentOrder.value?.id;

    console.log('Reloading order with ID:', id);

    router.visit(route('orders.index', {
        id: id || currentOrder.value?.id,
        tab: currentTab.value
    }), {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}

function handleOrderChange() {
    console.log('Order changed to:', selectedOrderId.value);
    if (selectedOrderId.value) {
        reloadOrder(selectedOrderId.value);
    }
}

const switchTab = (tab) => {
    const query = { tab };
    if (currentOrder.value?.id) {
        query.id = currentOrder.value?.id;
    }
    console.log('Switching tab to:', query);
    router.get(route('orders.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

function resetForm() {
    form.value = {
        id: null,
        product_id: null,
        product_name: null,
        quantity: 0,
        order_id: currentOrder.value?.id || null
    };
    searchQuery.value = '';
    selectedProduct.value = null;
}

async function removeItem(order) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, remove'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.get(route('orders.remove', { id: order.id }))
                .then((response) => {
                    reloadOrder();
                    Swal.fire({
                        icon: 'success',
                        title: response.data,
                        showConfirmButton: false,
                        timer: 1500
                    });
                })
                .catch((error) => {
                    console.error(error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error removing item',
                        text: error.response.data
                    });
                });
        }
    });
}

async function searchProduct(event) {
    axios.get(route('orders.search', { barcode: event.target.value }))
        .then(response => {
            form.value.product_id = response.data.id;
            form.value.product_name = response.data.name;

        })
        .catch(error => {
            console.error(error);
            Swal.fire({
                icon: 'error',
                title: 'Error searching product',
                text: error.response.data
            });
        });
}

function editItem(order){
    form.value = {
        id: order.id,
        product_id: order.product_id,
        product_name: order.product.name,
        quantity: order.quantity,
        order_id: order.order_id
    };
}

async function submitOrder() {
    isAdded.value = true;
    await axios.post(route('orders.store'), form.value)
        .then(response => {
            isAdded.value = false;
            Swal.fire({
                icon: 'success',
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500
            });
            reloadOrder();
            resetForm();
        })
        .catch(error => {
            console.error(error);
            isAdded.value = false;
            const errors = error.response.data;
            Swal.fire({
                icon: 'error',
                title: 'Error creating order',
                text: errors
            });
        });
}
</script>