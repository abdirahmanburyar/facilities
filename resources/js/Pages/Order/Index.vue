<template>
    <Head title="Orders" />
    <AuthenticatedLayout>
        <div class="min-h-screen py-4 bg-gray-50">
            <div class="w-full px-4">
                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-6">
                    <!-- Sidebar -->
                    <div class="lg:col-span-2">
                        <div
                            class="overflow-hidden text-xs bg-white rounded-lg shadow-sm"
                        >
                            <!-- Order Selection -->
                            <label
                                class="block mb-2 text-sm font-medium text-gray-700"
                                >Select Order Type</label
                            >
                            <select
                                v-model="order_type"
                                class="w-full py-2 pl-10 pr-3 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="">Select Order Type</option>
                                <option value="Quaterly">
                                    {{ currentQuarterLabel }}
                                </option>
                                <option value="Monthly">Monthly</option>
                                <option value="Amargency">Amargency</option>
                            </select>
                            <button
                                @click="confirmCreateOrder"
                                :disabled="order_type === ''"
                                class="flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Create New Order
                            </button>

                            <div>
                                <label
                                    class="block mb-2 text-sm font-medium text-gray-700"
                                    >Select Order</label
                                >
                                <select
                                    v-model="selectedOrderId"
                                    @change="handleOrderChange"
                                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="">Choose an order...</option>
                                    <option
                                        v-for="order in props.orders"
                                        :key="order.id"
                                        :value="order.id"
                                    >
                                        {{ order.order_number }} ({{
                                            order.status
                                        }})
                                    </option>
                                </select>
                            </div>

                            <!-- Current Order Info -->
                            <div
                                v-if="currentOrder"
                                class="p-4 text-xs bg-gradient-to-br from-blue-50 to-indigo-50"
                            >
                                <div class="flex items-center justify-between">
                                    <h3
                                        class="mb-3 text-sm font-semibold tracking-wider text-gray-900 uppercase"
                                    >
                                        Current Order
                                    </h3>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800':
                                                currentOrder.status ===
                                                'pending',
                                            'bg-green-100 text-green-800':
                                                currentOrder.status ===
                                                'completed',
                                        }"
                                    >
                                        {{ currentOrder.status }}
                                    </span>
                                </div>
                                <div class="space-y-3">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="font-medium text-gray-900"
                                            >{{
                                                currentOrder.order_number
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm text-gray-900">{{
                                            new Date(
                                                currentOrder.order_date
                                            ).toLocaleDateString()
                                        }}</span>
                                        <button
                                            @click="handleOrderSubmit"
                                            :disabled="orderSubmitted"
                                            class="font-medium text-indigo-600 hover:text-indigo-900"
                                        >
                                            {{
                                                orderSubmitted
                                                    ? "Submitting..."
                                                    : "Submit Order"
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Item Form -->
                            <div
                                v-if="currentOrder"
                                class="p-3 text-xs border-t border-gray-200"
                            >
                                <h3
                                    class="mb-2 text-xs font-semibold tracking-wider text-gray-900 uppercase"
                                >
                                    Add Item
                                </h3>
                                <form
                                    @submit.prevent="submitOrder"
                                    class="space-y-3"
                                >
                                    <div class="relative">
                                        <div class="relative">
                                            <div class="relative">
                                                <input
                                                    v-model="form.product_name"
                                                    @input="onProductSearch"
                                                    class="w-full pl-8 pr-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                    placeholder="Scan or type barcode/product name..."
                                                />
                                                <div
                                                    v-if="
                                                        productSuggestions.length >
                                                        0
                                                    "
                                                    class="absolute z-10 w-full py-1 mt-1 text-sm bg-white rounded-md shadow-lg"
                                                >
                                                    <div
                                                        v-for="suggestion in productSuggestions"
                                                        :key="suggestion.id"
                                                        @click="
                                                            selectProduct(
                                                                suggestion
                                                            )
                                                        "
                                                        class="px-4 py-2 cursor-pointer hover:bg-gray-100"
                                                    >
                                                        <div
                                                            class="flex items-center"
                                                        >
                                                            <span>{{
                                                                suggestion.name
                                                            }}</span>
                                                            <span
                                                                v-if="
                                                                    suggestion.barcode
                                                                "
                                                                class="ml-2 text-xs text-gray-500"
                                                                >({{
                                                                    suggestion.barcode
                                                                }})</span
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                                <div
                                                    class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                                                >
                                                    <svg
                                                        class="w-4 h-4 text-gray-400"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path
                                                            fill-rule="evenodd"
                                                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                                            clip-rule="evenodd"
                                                        />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div
                                            class="relative rounded-md shadow-sm"
                                        >
                                            <input
                                                type="number"
                                                v-model="form.quantity"
                                                class="w-full pl-8 pr-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                placeholder="Enter quantity"
                                                required
                                                min="1"
                                            />
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-gray-400"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                                                    />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    <button
                                        type="submit"
                                        :disabled="isAdded || !form.product_id"
                                        class="inline-flex justify-center items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 w-full"
                                    >
                                        <svg
                                            class="h-3 w-3 mr-1.5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                            />
                                        </svg>
                                        {{
                                            isAdded
                                                ? "Processing..."
                                                : "Add to Order"
                                        }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="lg:col-span-4">
                        <div
                            class="overflow-hidden bg-white rounded-lg shadow-sm"
                        >
                            <!-- Header -->
                            <div class="border-b border-gray-200">
                                <div class="p-4">
                                    <h1
                                        class="text-2xl font-semibold text-gray-900"
                                    >
                                        Order Items
                                    </h1>
                                </div>
                                <!-- Tabs -->
                                <div class="mb-4 border-b border-gray-200">
                                    <nav
                                        class="flex -mb-px space-x-8"
                                        aria-label="Tabs"
                                    >
                                        <button
                                            v-for="tab in tabs"
                                            :key="tab.key"
                                            @click="switchTab(tab.key)"
                                            class="px-1 py-4 text-sm font-medium border-b-2 whitespace-nowrap"
                                            :class="[
                                                currentTab === tab.key
                                                    ? 'border-indigo-500 text-indigo-600'
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                            ]"
                                        >
                                            <span class="flex items-center">
                                                <i
                                                    :class="['mr-2', tab.icon]"
                                                ></i>
                                                {{ tab.name }}
                                                <span
                                                    v-if="tab.count"
                                                    class="ml-2 px-2 py-0.5 text-xs rounded-full"
                                                    :class="[
                                                        currentTab === tab.key
                                                            ? 'bg-indigo-100 text-indigo-600'
                                                            : 'bg-gray-100 text-gray-900',
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
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                            >
                                                Item
                                            </th>
                                            <th
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                            >
                                                Quantity
                                            </th>
                                            <th
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                            >
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="order in props.currentOrder
                                                ?.items"
                                            :key="order.id"
                                            class="transition-colors duration-150 hover:bg-gray-50"
                                        >
                                            <td
                                                class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap"
                                            >
                                                {{ order.product.name }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap"
                                            >
                                                {{ order.quantity }}
                                            </td>
                                            <td
                                                class="px-6 py-4 text-sm whitespace-nowrap"
                                            >
                                                <button
                                                    @click="removeItem(order)"
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <button
                                                    @click="editItem(order)"
                                                    class="ml-2 text-blue-600 hover:text-blue-900"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr
                                            v-if="
                                                !props.currentOrder?.items
                                                    ?.length
                                            "
                                        >
                                            <td
                                                colspan="3"
                                                class="px-6 py-8 text-sm text-center text-gray-500"
                                            >
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
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed, onBeforeUnmount, onMounted, watch } from "vue";
import { router } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import axios from "axios";

const STORAGE_KEY = "current_order";
const currentOrder = ref(null);
const isAdded = ref(false);
const searchQuery = ref("");
const selectedProduct = ref(null);
const selectedOrderId = ref("");

// Calculate current quarter
const currentDate = new Date();
const currentYear = currentDate.getFullYear();
const currentMonth = currentDate.getMonth() + 1; // JavaScript months are 0-indexed
const currentQuarter = Math.ceil(currentMonth / 3);

// Get the first month of the current quarter
const quarterStartMonth = (currentQuarter - 1) * 3 + 1;
const currentQuarterLabel = `Quarter ${quarterStartMonth
    .toString()
    .padStart(2, "0")}-${currentYear}`;

const props = defineProps({
    orders: {
        type: Array,
        required: true,
    },
    products: {
        type: Array,
        required: true,
    },
    currentOrder: {
        type: Object,
        default: null,
    },
    tab: {
        type: String,
        default: "items",
    },
});

currentOrder.value = props.currentOrder;

const form = ref({
    id: null,
    product_id: null,
    product_name: null,
    quantity: 0,
    order_id: null,
});

const productSuggestions = ref([]);

watch(
    () => props.currentOrder,
    (newOrder) => {
        currentOrder.value = newOrder;
        if (newOrder) {
            selectedOrderId.value = newOrder.id;
            form.value.order_id = newOrder.id;
            localStorage.setItem(STORAGE_KEY, JSON.stringify(newOrder));
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

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

const tabs = [{ key: "items", name: "Items", icon: "fas fa-box", count: null }];

const currentTab = computed(() => props.tab || "items");

const orderSubmitted = ref(false);

const handleOrderSubmit = () => {
    orderSubmitted.value = true;
    axios
        .post(route("orders.submit"))
        .then((response) => {
            orderSubmitted.value = false;
            reloadOrder();
            Swal.fire({
                icon: "success",
                title: response.data,
                showConfirmButton: false,
                timer: 1500,
            });
        })
        .catch((error) => {
            orderSubmitted.value = false;
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Error submitting order",
                text: error.response.data,
            });
        });
};

const order_type = ref(currentOrder.value?.order_type || "Monthly");

const confirmCreateOrder = () => {
    if (!order_type.value) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Please select an order type",
        });
        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: "This will create a new order",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, create order",
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .post(route("orders.create", { order_type: order_type.value }))
                .then((response) => {
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
                        order_id: newOrder.id,
                    });

                    // Reload the page with new order
                    reloadOrder(newOrder.id);

                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: response.data.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });
                })
                .catch((error) => {
                    console.error(error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: error.response?.data || "Failed to create order",
                    });
                });
        }
    });
};

function reloadOrder(id = null) {
    form.value.order_id = id || currentOrder.value?.id;

    console.log("Reloading order with ID:", id);

    router.visit(
        route("orders.index", {
            id: id || currentOrder.value?.id,
            tab: currentTab.value,
        }),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
}

function handleOrderChange() {
    console.log("Order changed to:", selectedOrderId.value);
    if (selectedOrderId.value) {
        reloadOrder(selectedOrderId.value);
    }
}

const switchTab = (tab) => {
    const query = { tab };
    if (currentOrder.value?.id) {
        query.id = currentOrder.value?.id;
    }
    console.log("Switching tab to:", query);
    router.get(route("orders.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

function resetForm() {
    form.value = {
        id: null,
        product_id: null,
        product_name: null,
        quantity: 0,
        order_id: currentOrder.value?.id || null,
    };
    searchQuery.value = "";
    selectedProduct.value = null;
}

async function removeItem(order) {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, remove",
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .get(route("orders.remove", { id: order.id }))
                .then((response) => {
                    reloadOrder();
                    Swal.fire({
                        icon: "success",
                        title: response.data,
                        showConfirmButton: false,
                        timer: 1500,
                    });
                })
                .catch((error) => {
                    console.error(error);
                    Swal.fire({
                        icon: "error",
                        title: "Error removing item",
                        text: error.response.data,
                    });
                });
        }
    });
}

async function searchProduct(event) {
    const query = event.target.value;
    console.log(query);

    axios
        .get(route("orders.search", { barcode: query, tag: "all" }))
        .then((response) => {
            if (response.data.product) {
                form.value.product_id = response.data.product.id;
                form.value.product_name = response.data.product.name;
                productSuggestions.value = [];
            } else {
                Swal.fire({
                    icon: "question",
                    title: "Product not found",
                    text: `Do you want to add ${query} as a new product?`,
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes",
                    cancelButtonText: "No",
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        axios
                            .get(
                                route("orders.search", {
                                    barcode: query,
                                    tag: "all",
                                })
                            )
                            .then((response) => {
                                if (response.data.product) {
                                    form.value.product_id =
                                        response.data.product.id;
                                    form.value.product_name =
                                        response.data.product.name;
                                }
                            })
                            .catch((error) => {
                                console.error(error);
                                Swal.fire({
                                    icon: "error",
                                    title: "Error searching product",
                                    text: error.response.data,
                                });
                            });
                    }
                });
            }
        })
        .catch((error) => {
            console.error(error);
            Swal.fire({
                icon: "error",
                title: "Error searching product",
                text: error.response.data,
            });
        });
}

function editItem(order) {
    form.value = {
        id: order.id,
        product_id: order.product_id,
        product_name: order.product.name,
        quantity: order.quantity,
        order_id: order.order_id,
    };
}

async function submitOrder() {
    isAdded.value = true;
    await axios
        .post(route("orders.store"), form.value)
        .then((response) => {
            isAdded.value = false;
            Swal.fire({
                icon: "success",
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500,
            });
            reloadOrder();
            resetForm();
        })
        .catch((error) => {
            console.error(error);
            isAdded.value = false;
            const errors = error.response.data;
            Swal.fire({
                icon: "error",
                title: "Error creating order",
                text: errors,
            });
        });
}

function onProductSearch(event) {
    const query = event.target.value;

    // Search even with short queries to allow barcode scanning
    if (query && query.length > 0) {
        axios
            .get(route("orders.search", { barcode: query, tag: "eligible" }))
            .then((response) => {
                if (response.data.product) {
                    console.log(response.data);
                    // Single product found
                    productSuggestions.value = [response.data.product];
                } else if (
                    response.data.products &&
                    response.data.products.length
                ) {
                    // Multiple products found
                    productSuggestions.value = response.data.products;
                } else {
                    productSuggestions.value = [];
                }
            })
            .catch((error) => {
                console.error(error);
                productSuggestions.value = [];
            });
    } else {
        productSuggestions.value = [];
    }
}

function selectProduct(product) {
    form.value.product_id = product.id;
    form.value.product_name = product.name;
    productSuggestions.value = [];
}

onMounted(() => {
    const savedOrderId = localStorage.getItem(STORAGE_KEY);
    if (savedOrderId && props.orders.data) {
        loadItems(parseInt(savedOrderId));
    }

    // Initialize Echo listener for OrderEvent
    window.Echo.channel("orders").listen(".order-received", (e) => {
        console.log("Order event received:", e);
        reloadOrder();
    });
});
</script>
