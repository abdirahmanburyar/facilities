<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Order
            </h2>
        </template>

        <div class="py-12">
            <form @submit.prevent="submitOrder">
                <!-- Order Details -->
                <div class="grid grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Order Type</label>
                        <input type="text" disabled value="Replenishment" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Order Date</label>
                        <input type="text" disabled :value="form.order_date" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expected Date</label>
                        <input 
                            type="date" 
                            v-model="form.expected_date"
                            :min="minExpectedDate.value"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required
                        />
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea 
                            v-model="form.notes" 
                            rows="3" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Enter any additional notes or instructions..."
                        ></textarea>
                    </div>
                </div>

                <!-- Product Selection -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Order Items</h3>                        
                    </div>

                    <!-- Item List -->
                    <table class="w-full mt-4 border border-black">
                        <thead>
                            <tr>
                                <th class="w-full px-3 text-left text-sm leading-4 font-medium text-black border border-black">Item</th>
                                <th class="w-[150px] px-3 text-left text-sm leading-4 font-medium text-black border border-black">Required Quantity</th>
                                <th class="w-[200px] px-3 text-left text-sm leading-4 font-medium text-black border border-black">SoH</th>
                                <th class="w-[150px] px-3 text-left text-sm leading-4 font-medium text-black border border-black">Quantity on Order</th>
                                <th class="w-[150px] px-3 text-left text-sm leading-4 font-medium text-black border border-black">No of Days</th>
                                <th class="w-[150px] px-3 text-left text-sm leading-4 font-medium text-black border border-black">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in form.items" :key="index">
                                <td class="px-3 border border-black">
                                    <div class="relative">
                                        <Multiselect v-model="item.product" :value="item.product_id"
                                        :options="props.items"
                                        :searchable="true" :close-on-select="true" :show-labels="false"
                                        :allow-empty="true" placeholder="Select item" track-by="id" label="name"
                                        @select="checkInventory(index, $event)"
                                        required
                                        :class="{'opacity-50': isLoading}">
                                        </Multiselect>
                                        <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-50">
                                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 border border-black">
                                    <input 
                                        type="number" 
                                        v-model="item.quantity"
                                        readonly
                                        min="1"
                                        required
                                        class="mt-1 block w-[180px] rounded-md  shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </td>
                                <td class="px-3 border border-black"> <input type="number" v-model="item.soh" readonly class="mt-1 block w-[180px] rounded-md  shadow-sm focus:border-indigo-500 focus:ring-indigo-500"> </td>
                                <td class="px-3 border border-black">
                                    <input 
                                        type="number" 
                                        v-model="item.quantity_on_order"
                                        min="0"
                                        class="mt-1 block w-[180px] rounded-md  shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </td>
                                <td class="px-3 border border-black">
                                    <input 
                                        type="number" 
                                        v-model="item.no_of_days"
                                        readonly
                                        class="mt-1 block w-[180px] rounded-md  shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                </td>
                                <td class="px-3 text-left border border-black">
                                    <button 
                                        type="button" 
                                        @click="removeItem(index)"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" @click="addItem" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 mt-4">
                        Add Item
                    </button>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end mt-6">
                    <Link :href="route('orders.index')" :disabled="isSubmitting" class="mr-4">Exit</Link>
                    <button 
                        type="submit" 
                        :disabled="isSubmitting"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ isSubmitting ? 'Processing...' : 'Submit Order' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'vue-toastification';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import axios from 'axios';
import Swal from 'sweetalert2';

const toast = useToast();
const props = defineProps({
    items: {
        type: Array,
        required: true
    }
});

const processing = ref(false);

const today = new Date();
const currentDate = today.toISOString().split('T')[0];
const minExpectedDate = computed(() => new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split('T')[0]);

const form = ref({
    order_type: 'Replenishment',
    order_date: currentDate,
    expected_date: '',
    notes: '',
    items: []
});

const isLoading = ref(false);

const addItem = () => {
    form.value.items.push({
        product_id: '',
        product: null,
        quantity: 1,
        soh: 0,
        quantity_on_order: 0,
        no_of_days: 0
    });
};

const removeItem = (index) => {
    form.value.items.splice(index, 1);
};


async function checkInventory(index, selected){
    isLoading.value = true;
    form.value.items[index].product_id = selected.id;
    form.value.items[index].product = selected;
   
    await axios.post(route('orders.check-inventory'), {
        product_id: selected.id
    })
        .then(response => {
            console.log(response.data);
            form.value.items[index].soh = response.data.soh;
            form.value.items[index].quantity = response.data.quantity;
            form.value.items[index].amc = response.data.amc;
            form.value.items[index].no_of_days = parseInt(response.data.no_of_days);
            isLoading.value = false;
        })
        .catch(error => {
            isLoading.value = false;
            console.error('Error checking inventory:', error.response.data);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response.data,
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    isLoading.value = false;
                    form.value.items[index].product_id = "";
                    form.value.items[index].product = null;
                }
            });
        });
};

const isSubmitting = ref(false);

const submitOrder = async () => {
    console.log(form.value);
  isSubmitting.value = true;

  await axios.post(route('orders.store'), form.value)
    .then(response => {
      toast.success('Order created successfully');
      isSubmitting.value = false;
      Swal.fire({
        icon: 'success',
        title: 'Order created successfully',
        showConfirmButton: false,
        timer: 1500
      })
      .then(() => {
        form.value = {
            order_type: 'Replenishment',
            order_date: currentDate,
            expected_date: '',
            notes: '',
            items: [{
                product_id: '',
                product: null,
                quantity: 1,
                soh: 0,
                quantity_on_order: 0,
                no_of_days: 0
            }]
        }
      });
    })
    .catch(error => {
      console.error('Error creating order:', error);
      toast.error('Failed to create order');
      isSubmitting.value = false;
    });
};

// Initialize with one empty item
addItem();
</script>
