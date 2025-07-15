<template>
    <AuthenticatedLayout
        title="Create New Order"
        description="Create a replenishment order for your facility"
        img="/assets/images/orders.png"
    >
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create Order</h2>
                    <p class="text-sm text-gray-600 mt-1">Fill in the details below to create a new order</p>
                </div>
                <Link
                    :href="route('orders.index')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Orders
                </Link>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-8">
            <form @submit.prevent="submitOrder" class="space-y-8">
                <!-- Order Information Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center mb-6">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">Order Information</h3>
                            <p class="text-sm text-gray-500">Basic order details and scheduling</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Order Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Order Type
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    disabled
                                    value="Replenishment"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 cursor-not-allowed"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Order Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Order Date
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    disabled
                                    :value="form.order_date"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 cursor-not-allowed"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                                                 <!-- Expected Date -->
                         <div>
                             <label class="block text-sm font-medium text-gray-700 mb-2">
                                 Expected Delivery Date
                                 <span class="text-red-500">*</span>
                             </label>
                             <div class="relative">
                                 <input
                                     type="date"
                                     v-model="form.expected_date"
                                     :min="minExpectedDate"
                                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                     required
                                 />
                                 <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                     <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                     </svg>
                                 </div>
                             </div>
                         </div>
                    </div>

                    <!-- Notes -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Notes
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                            placeholder="Enter any additional notes, special instructions, or comments for this order..."
                        ></textarea>
                    </div>
                </div>

                <!-- Order Items Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Order Items</h3>
                                <p class="text-sm text-gray-500">Select products and specify quantities</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="addItem"
                            :disabled="isLoading"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add Item
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Product
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Required Qty
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Stock on Hand
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Order Qty
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Days of Stock
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Action
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="(item, index) in form.items"
                                        :key="index"
                                        class="hover:bg-gray-50 transition-colors duration-200"
                                    >
                                                                                 <!-- Product Selection -->
                                         <td class="px-6 py-4">
                                             <div class="relative">
                                                 <Multiselect
                                                     v-model="item.product"
                                                     :value="item.product_id"
                                                     :options="props.items"
                                                     :searchable="true"
                                                     :close-on-select="true"
                                                     :show-labels="false"
                                                     :allow-empty="true"
                                                     placeholder="Select a product..."
                                                     track-by="id"
                                                     label="name"
                                                     @select="checkInventory(index, $event)"
                                                     required
                                                     :class="{ 'opacity-50': isLoading }"
                                                     class="w-full"
                                                 >
                                                     <template #option="{ option }">
                                                         <div>
                                                             <div class="font-medium text-gray-900">{{ option.name }}</div>
                                                             <div class="text-sm text-gray-500">{{ option.code }}</div>
                                                         </div>
                                                     </template>
                                                 </Multiselect>
                                                 <div
                                                     v-if="isLoading"
                                                     class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75 rounded-lg"
                                                 >
                                                     <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                                 </div>
                                             </div>
                                         </td>

                                                                                 <!-- Required Quantity -->
                                         <td class="px-6 py-4">
                                             <input
                                                 type="number"
                                                 v-model="item.quantity"
                                                 min="1"
                                                 readonly
                                                 class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 text-center"
                                             />
                                         </td>

                                         <!-- Stock on Hand -->
                                         <td class="px-6 py-4">
                                             <input
                                                 type="number"
                                                 v-model="item.soh"
                                                 readonly
                                                 class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 text-center"
                                             />
                                         </td>

                                         <!-- Quantity on Order -->
                                         <td class="px-6 py-4">
                                             <input
                                                 type="number"
                                                 v-model="item.quantity_on_order"
                                                 min="0"
                                                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center"
                                             />
                                         </td>

                                         <!-- Days of Stock -->
                                         <td class="px-6 py-4">
                                             <input
                                                 type="number"
                                                 v-model="item.no_of_days"
                                                 readonly
                                                 class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-700 text-center"
                                             />
                                         </td>

                                        <!-- Action -->
                                        <td class="px-6 py-4">
                                            <button
                                                type="button"
                                                @click="removeItem(index)"
                                                class="text-red-600 hover:text-red-800 transition-colors duration-200"
                                                title="Remove item"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-if="form.items.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No items added</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by adding your first order item.</p>
                            <div class="mt-6">
                                <button
                                    type="button"
                                    @click="addItem"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <div class="text-sm text-gray-500">
                        <span class="font-medium">{{ form.items.length }}</span> items in order
                    </div>
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('orders.index')"
                            :disabled="isSubmitting || isLoading"
                            class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="isSubmitting || isLoading || form.items.length === 0"
                            class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isSubmitting ? "Creating Order..." : "Create Order" }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import axios from "axios";
import Swal from "sweetalert2";

const toast = useToast();
const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
});

const processing = ref(false);

const today = new Date();
const currentDate = today.toISOString().split("T")[0];
const minExpectedDate = computed(
    () => new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split("T")[0]
);

const form = ref({
    order_type: "Replenishment",
    order_date: currentDate,
    expected_date: "",
    notes: "",
    items: [],
});

const isLoading = ref(false);

const addItem = () => {
    form.value.items.push({
        product_id: "",
        product: null,
        quantity: 0,
        soh: 0,
        quantity_on_order: 0,
        no_of_days: 0,
    });
};

const removeItem = (index) => {
    form.value.items.splice(index, 1);
};

async function checkInventory(index, selected) {
    isLoading.value = true;
    form.value.items[index].product_id = selected.id;
    form.value.items[index].product = selected;
    form.value.items[index].soh = 0;
    form.value.items[index].quantity = 0;
    form.value.items[index].amc = 0;
    form.value.items[index].no_of_days = 0;

    await axios
        .post(route("orders.check-inventory"), {
            product_id: selected.id,
        })
        .then((response) => {
            console.log(response);
            form.value.items[index].soh = response.data.soh;
            form.value.items[index].quantity = response.data.quantity;
            form.value.items[index].amc = response.data.amc;
            form.value.items[index].no_of_days = parseInt(
                response.data.no_of_days
            );
            isLoading.value = false;
        })
        .catch((error) => {
            console.log(error);
            isLoading.value = false;
            Swal.fire({
                icon: "error",
                title: "Error",
                text: error.response.data,
                confirmButtonText: "OK",
            }).then((result) => {
                if (result.isConfirmed) {
                    isLoading.value = false;
                    form.value.items[index].product_id = "";
                    form.value.items[index].product = null;
                }
            });
        });
}

const isSubmitting = ref(false);

const submitOrder = async () => {
    isSubmitting.value = true;

    console.log(form.value);

    await axios
        .post(route("orders.store"), form.value)
        .then((response) => {
            toast.success("Order created successfully");
            isSubmitting.value = false;
            Swal.fire({
                icon: "success",
                title: "Order created successfully",
                showConfirmButton: false,
                timer: 1500,
            }).then(() => {
                form.value = {
                    order_type: "Replenishment",
                    order_date: currentDate,
                    expected_date: "",
                    notes: "",
                    items: [
                        {
                            product_id: "",
                            product: null,
                            quantity: 0,
                            soh: 0,
                            quantity_on_order: 0,
                            no_of_days: 0,
                        },
                    ],
                };
            });
        })
        .catch((error) => {
            toast.error(error.response.data);
            isSubmitting.value = false;
        });
};

// Initialize with one empty item
addItem();
</script>
