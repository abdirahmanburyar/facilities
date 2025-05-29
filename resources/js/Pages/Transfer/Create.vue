<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import Swal from 'sweetalert2';
import moment from 'moment';

const toast = useToast();

const props = defineProps({
    inventory: Array,
    warehouses: {
        type: Array,
        default: () => []
    },
    facilities: {
        type: Array,
        default: () => []
    },
});

const destinationType = ref('warehouse');
const loading = ref(false);
const selectedDestination = ref(null);
const selectedInventory = ref(null);
const filteredInventories = ref([]);
const availableInventories = ref([]);
const searchQuery = ref('');
const loadingInventories = ref(false);

const form = ref({
    destination_type: 'warehouse',
    destination_id: null,
    items: [
        {
            id: null,
            product_id: '',
            product: null,
            quantity: 0,
            available_quantity: 0,
            batch_number: '',
            barcode: '',
            expire_date: null,
            uom: ''
        }
    ]
});

const errors = ref({});

const destinationOptions = computed(() => {
    return destinationType.value === 'warehouse' ? props.warehouses : props.facilities;
});

const handleDestinationSelect = (selected) => {
    form.value.destination_id = selected ? selected.id : null;
};

// Use inventory data from props directly
const loadInventoryData = () => {
    // Set loading state
    loadingInventories.value = true;
    filteredInventories.value = [];
    searchQuery.value = '';
    
    // Process inventory data from props
    if (props.inventory && Array.isArray(props.inventory)) {
        // Filter valid items
        const validItems = props.inventory.filter(item => {
            return item && item.product && item.product.name && item.quantity > 0;
        });
        
        // Map to the format expected by the form
        const mappedItems = validItems.map(item => ({
            id: item.id,
            product_id: item.product_id,
            product: item.product,
            quantity: 0,
            available_quantity: item.quantity,
            batch_number: item.batch_number,
            barcode: item.barcode,
            expire_date: item.expiry_date,
            uom: item.name || ''
        }));
        
        if (mappedItems.length > 0) {
            toast.success(`Loaded ${mappedItems.length} inventory items`);
        } else {
            toast.info('No inventory items available');
        }
    } else {
        toast.error('No inventory data available');
    }
    
    // Set loading state to false
    loadingInventories.value = false;
};

const validateForm = () => {
    errors.value = {};
    let isValid = true;

    // Validate destination
    if (!form.value.destination_id) {
        errors.value.destination_id = 'Please select a destination.';
        isValid = false;
    }

    // Validate that all items are properly filled
    let hasValidItems = false;

    form.value.items.forEach((item, index) => {
        // Check if inventory item is selected
        if (!item.id) {
            errors.value[`item_${index}_inventory`] = 'Please select an inventory item.';
            isValid = false;
        }

        // Check if quantity is valid (must be at least 1)
        if (item.id && (!item.quantity || item.quantity < 1)) {
            errors.value[`item_${index}_quantity`] = 'Quantity must be at least 1.';
            isValid = false;
        }

        // Check if quantity exceeds available quantity
        if (item.id && item.quantity > item.available_quantity) {
            errors.value[`item_${index}_quantity`] = `Maximum available quantity is ${item.available_quantity}.`;
            isValid = false;
        }

        // Track if we have at least one valid item
        if (item.id && item.quantity >= 1 && item.quantity <= item.available_quantity) {
            hasValidItems = true;
        }
    });

    // Ensure at least one valid item exists
    if (!hasValidItems) {
        errors.value.items = 'At least one item must be selected with a valid quantity.';
        isValid = false;
    }

    return isValid;
};

// Watch for changes in destination type and update form
watch(destinationType, (newValue) => {
    form.value.destination_type = newValue;
    form.value.destination_id = null;
    selectedDestination.value = null;
});

// Load inventory data when component is mounted
onMounted(() => {
    loadInventoryData();
});

const submit = async () => {

    loading.value = true;

    await axios.post(route('transfers.store'), form.value)
        .then((response) => {
            loading.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Transfer created successfully',
                text: response.data.message,
                showConfirmButton: false,
                timer: 1500
            })
            .then(() => {
                router.visit(route('transfers.index'));
            });
        })
        .catch((error) => {
            loading.value = false;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response.data,
                showConfirmButton: false,
                timer: 1500
            });
        });
};

function handleProductSelect(index, selected) {
    const item = form.value.items[index];

    if(!selected){
        item.id = null;
        item.product_id = null;
        item.product = null;
        item.product_name = '';
        item.uom = '';
        item.batch_number = '';
        item.barcode = '';
        item.expiry_date = null;
        item.available_quantity = 0;
        return;
    }

    if (form.value.items.some(item => item.id === selected.id)) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Item already added'
        });
        item.product = null;
        item.product_id = null;
        item.id = null;
        item.product_name = '';
        item.uom = '';
        item.batch_number = '';
        item.barcode = '';
        item.expiry_date = null;
        item.available_quantity = 0;
        return;
    }

    item.id = selected.id;
    item.product_id = selected.product_id;
    item.product = selected;
    item.product_name = selected.product_name;
    item.uom = selected.uom;
    item.batch_number = selected.batch_number;
    item.barcode = selected.barcode;
    item.expiry_date = selected.expiry_date;
    item.available_quantity = selected.quantity;

}

function addNewItem() {
    form.value.items.push({
        id: null,
        product_id: null,
        product: null,
        product_name: '',
        quantity: 0,
        batch_number: '',
        barcode: '',
        expiry_date: null,
        uom: '',
        available_quantity: 0
    });
}

function removeItem(index) {
    // Check if we have more than one item before removing
    if (form.value.items.length > 1) {
        const itemToRemove = form.value.items[index];
        const itemName = itemToRemove.product_name || 'this item';

        // Show confirmation dialog
        Swal.fire({
            title: 'Confirm Deletion',
            text: `Are you sure you want to remove ${itemName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Remove the item from the local array
                form.value.items.splice(index, 1);

                // Show success message
                toast.success('Item removed successfully');
            }
        });
    }
}

const message = ref([]);

function checkQuantity(index) {
    message.value[index] = '';
    const item = form.value.items[index];

    // Ensure quantity is at least 1
    if (item.quantity < 1) {
        item.quantity = 1;
        message.value[index] = 'Minimum quantity is 1';
    }

    // Ensure quantity doesn't exceed available quantity
    if (item.quantity > item.available_quantity) {
        // Reset to available quantity if exceeded
        item.quantity = item.available_quantity;
        message.value[index] = `Quantity reset to maximum available (${item.available_quantity})`;
    }
}
</script>

<template>
    <AuthenticatedLayout title="Transfer Item">
        <div class="mb-5">
            <div class="p-6 text-gray-900">
                <h2 class="text-2xl font-semibold mb-6">Transfer Item</h2>

                <div class="mb-4">
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    Select destination location, then choose inventory items to transfer.
                                    The quantity must not exceed available quantity.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Destination Type Selection -->
                        <div>
                            <InputLabel value="Transfer To" />
                            <div class="mt-2 space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="destinationType" value="warehouse"
                                        class="form-radio text-indigo-600" />
                                    <span class="ml-2">Warehouse</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" v-model="destinationType" value="facility"
                                        class="form-radio text-indigo-600" />
                                    <span class="ml-2">Facility</span>
                                </label>
                            </div>
                        </div>

                        <!-- Destination Selection -->
                        <div>
                            <InputLabel
                                :value="`Select Destination ${destinationType === 'warehouse' ? 'Warehouse' : 'Facility'}`" />
                            <Multiselect v-model="selectedDestination" :options="destinationOptions"
                                :searchable="true" :close-on-select="true" :show-labels="false"
                                placeholder="Select destination" label="name" track-by="id"
                                @select="handleDestinationSelect" class="mt-1 block w-full multiselect-custom" />
                            <InputError :message="errors.destination_id" class="mt-2" />
                        </div>
                        <!-- here for items -->
                    </div>

                    <div class="mb-4">
                        <table class="min-w-full">
                            <thead class="w-full bg-gray-50">
                                <tr>
                                    <th
                                        class="w-auto px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase border border-black">
                                        Item</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border border-black tracking-wider">
                                        UoM</th>
                                    <th
                                        class="w-[300px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border border-black tracking-wider">
                                        Item Information
                                    </th>
                                    <th
                                        class="w-[100px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border border-black tracking-wider">
                                        Available quantity</th>
                                    <th
                                        class="w-[200px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border border-black tracking-wider">
                                        Quantity to release</th>
                                    <th
                                        class="w-[70px] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase border border-black tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Inventory Items rows -->
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50">
                                    <td
                                        class="px-6 py-4 whitespace-nowrap border border-black text-sm font-medium text-gray-900">
                                        <Multiselect v-model="item.product"
                                            :options="props.inventory" placeholder="Search for an item..."
                                            track-by="product_id"
                                            label="product_name"
                                            required :searchable="true" :allow-empty="true"
                                            @select="handleProductSelect(index, $event)"></Multiselect>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap border border-black text-sm text-black">
                                        {{ item.uom }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap border border-black text-sm text-gray-500">
                                        <div>Batch Number: {{ item.batch_number }}</div>
                                        <div>Barcode: {{ item.barcode }}</div>
                                        <div>Expire Date: {{ item.expire_date ?
                                            moment(item.expire_date).format('DD/MM/YYYY') : 'N/A' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap border border-black text-sm text-black">
                                        {{ item.available_quantity }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap border border-black text-sm text-black">
                                        <input type="text" v-model.number="item.quantity" required :class="[
                                            'w-full text-sm',
                                            errors[`item_${index}_quantity`] ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''
                                        ]" min="1" :max="item.available_quantity" :disabled="!item.product_id"
                                            placeholder="0"
                                            @input="checkQuantity(index); errors[`item_${index}_quantity`] = null" />
                                        <p v-if="message[index]" class="text-sm text-red-500 mt-1">{{ message[index] }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap border border-black text-sm text-gray-500">
                                        <button type="button" @click="removeItem(index)"
                                            class="text-red-600 hover:text-red-900" :disabled="form.items.length <= 1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>


                    <div class="flex items-center justify-between space-x-4 mb-4">
                        <button type="button" @click="addNewItem"
                            class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Add Another Item
                        </button>
                        <div>
                            <SecondaryButton :href="route('transfers.index')" as="a" :disabled="loading"
                                class="opacity-75" :class="{ 'opacity-50 cursor-not-allowed': loading }">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton :disabled="loading" class="relative">
                                <span v-if="loading" class="absolute left-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </span>
                                <span :class="{ 'pl-7': loading }">{{ loading ? 'Processing...' : 'Transfer Item'
                                }}</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>