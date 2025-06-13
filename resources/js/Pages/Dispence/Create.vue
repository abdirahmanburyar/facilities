<template>
    <AuthenticatedLayout :title="'Create New Dispense'">
        <!-- Modern Header Section -->
        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Breadcrumb Navigation -->
                <nav class="flex items-center space-x-2 text-sm text-gray-600 mb-8">
                    <Link :href="route('dispence.index')"
                        class="flex items-center hover:text-blue-600 transition-colors duration-200">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 5a2 2 0 012-2h4a2 2 0 012 2v3H8V5z" />
                    </svg>
                    Dispenses
                    </Link>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-900 font-medium">Create New</span>
                </nav>

                <!-- Page Header -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="bg-white/20 rounded-xl p-3">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-white">Create New Dispense</h1>
                                    <p class="text-blue-100 mt-1">Issue medication to patients with proper documentation
                                    </p>
                                </div>
                            </div>
                            <div class="bg-white/10 rounded-xl px-4 py-2">
                                <div class="flex items-center text-white text-sm">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ new Date().toLocaleDateString('en-US', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submit" novalidate class="divide-y divide-gray-100">
                        <!-- Patient Information -->
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Patient Name -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Patient Name</label>
                                    <input type="text" v-model="form.patient_name"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Patient full name" required />
                                    <InputError :message="errors.patient_name" class="mt-1 text-sm text-red-600" />
                                </div>

                                <!-- Age -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Age</label>
                                    <div class="flex rounded-md shadow-sm">
                                        <input type="number" v-model="form.patient_age" min="0" max="120"
                                            class="block w-full rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            placeholder="Age" required />
                                        <span
                                            class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                                            Years
                                        </span>
                                    </div>
                                </div>

                                <!-- Gender -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Gender</label>
                                    <select v-model="form.patient_gender"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>

                                <!-- Phone Number -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input type="tel" v-model="form.phone_number"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        placeholder="Phone number" required />
                                    <InputError :message="errors.phone_number" class="mt-1 text-sm text-red-600" />
                                </div>

                                <!-- Prescription Date -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Prescription Date</label>
                                    <input type="date" v-model="form.prescription_date" :max="today"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                        required />
                                </div>
                            </div>

                            <!-- Diagnosis -->
                            <div class="mt-4 space-y-1">
                                <label class="block text-sm font-medium text-gray-700">Diagnosis</label>
                                <textarea v-model="form.diagnosis" rows="2"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    placeholder="Enter diagnosis"></textarea>
                                <InputError :message="errors.diagnosis" class="mt-1 text-sm text-red-600" />
                            </div>
                        </div>

                        <!-- Prescription Items -->
                        <div class="p-6 bg-gray-50">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Prescription Items</h3>
                                <button type="button" @click="addItem"
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    :disabled="isProcessing">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Add Item
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 table-fixed">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="w-full px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Product</th>
                                            <th
                                                class="w-24 px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Dose</th>
                                            <th class="w-16 px-2 py-3">
                                                <span class="sr-only">Actions</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div class="w-full">
                                                    <Multiselect v-model="item.product" :options="props.inventories"
                                                        :searchable="true" :close-on-select="true" :allow-empty="true"
                                                        placeholder="Select product" track-by="id" label="name"
                                                        @select="checkInventory(index, $event)" class="text-sm w-full">
                                                        <template v-slot:option="{ option }">
                                                            <div class="flex justify-between items-center w-full">
                                                                <span class="truncate">{{ option.name }}</span>
                                                                <span
                                                                    class="ml-2 text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                                                    Stock: {{ option.quantity }}
                                                                </span>
                                                            </div>
                                                        </template>
                                                    </Multiselect>
                                                </div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <label for="">Dose</label>
                                                <input type="number"
                                                    class="block md:w-[150px] w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                    v-model="item.dose" placeholder="Dose" min="1"
                                                    @input="calculateItemQuantity(index)" required />
                                                <label for="">Frequency</label>
                                                <input type="number"
                                                        class="pl-8 block md:w-[150px] w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                        v-model="item.frequency" placeholder="Per day" min="1"
                                                        @input="calculateItemQuantity(index)" required />
                                                <label for="">Duration</label>
                                                <input type="number"
                                                    class="pl-8 block md:w-[150px] w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs"
                                                    v-model="item.duration" placeholder="Days" min="1"
                                                    @input="calculateItemQuantity(index)" required />
                                                <div class="md:w-[150px] w-full">
                                                    <label for="">Quantity</label>
                                                    <input type="number"
                                                        class="w-full rounded-md border-gray-200 bg-gray-50 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                                        v-model="item.quantity" readonly required />
                                                    <div v-if="item.max_quantity" class="mt-1">
                                                        <span :class="{
                                                            'text-green-600': item.quantity <= item.max_quantity,
                                                            'text-red-600': item.quantity > item.max_quantity
                                                        }" class="text-xs font-medium">
                                                            {{ item.quantity > item.max_quantity ? 'Insufficient' :
                                                            'Available' }}: {{ item.max_quantity }}
                                                        </span>
                                                        <span v-if="item.quantity > item.max_quantity"
                                                            class="block text-xs font-medium text-red-600">
                                                            (Short by {{ item.quantity - item.max_quantity }})
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-2 py-3 text-center">
                                                <button type="button" @click="removeItem(index)"
                                                    class="text-red-600 hover:text-red-900 p-1"
                                                    :disabled="form.items.length <= 1">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div
                            class="bg-gray-50 px-6 py-4 border-t border-gray-200 rounded-b-lg flex justify-end space-x-3">
                            <Link :href="route('dispence.index')"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                :disabled="isProcessing">
                            Cancel
                            </Link>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                :disabled="isProcessing">
                                <svg v-if="isProcessing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                {{ isProcessing ? 'Processing...' : 'Save Dispense' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import { useToast } from 'vue-toastification';
import Swal from 'sweetalert2';
import axios from 'axios';

const toast = useToast();
const today = new Date().toISOString().split('T')[0];

const props = defineProps({
    inventories: {
        type: Array,
        required: true
    },
});

const errors = ref({});
const isProcessing = ref(false);
const form = ref({
    patient_name: '',
    diagnosis: '',
    phone_number: '',
    items: [{
        product_id: '',
        product: null,
        dose: '',
        frequency: '',
        duration: '',
        quantity: 1,
        max_quantity: null
    }]
});

const addItem = () => {
    form.value.items.push({
        product_id: '',
        product: null,
        dose: '',
        frequency: '',
        duration: '',
        quantity: 1
    });
};

const removeItem = (index) => {
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
};

const calculateItemQuantity = (index) => {
    const item = form.value.items[index];
    if (item.dose && item.frequency && item.duration) {
        const calculatedQty = item.dose * item.frequency * item.duration;
        item.quantity = calculatedQty;

        // Check if quantity exceeds available stock
        if (item.max_quantity && calculatedQty > item.max_quantity) {
            Swal.fire({
                title: 'Warning',
                text: `Required quantity (${calculatedQty}) exceeds available stock (${item.max_quantity})`,
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        }
    }
};

async function checkInventory(index, selected) {
    // Clear row data first
    const item = form.value.items[index];
    item.product = null;
    item.product_id = '';
    item.dose = '';
    item.frequency = '';
    item.duration = '';
    item.quantity = 1;
    item.max_quantity = null;

    if (selected) {
        // Check if this product is already selected in another row
        const isDuplicate = form.value.items.some((item, idx) =>
            idx !== index && item.product_id === selected.id
        );

        if (isDuplicate) {
            Swal.fire({
                title: 'Duplicate Item',
                text: 'This product is already added to the list',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Set the new product data
        item.product = selected;
        item.product_id = selected.id;
        item.max_quantity = parseInt(selected.quantity);
        calculateItemQuantity(index);
        addItem();
        return;
    }
}

const isLoading = ref(false);
// Watch for changes in dose, frequency, and duration
watch(() => form.value.items, (items) => {
    items.forEach((item, index) => {
        if (item.dose && item.frequency && item.duration) {
            calculateItemQuantity(index);
        }
    });
}, { deep: true });

const submit = async () => {
    isProcessing.value = true;
    await axios.post(route('dispence.store'), form.value)
        .then((response) => {
            isProcessing.value = false;
            toast.success(response.data);
            reloadDispences();
        })
        .catch((error) => {
            isProcessing.value = false;
            console.error('Error creating dispense:', error);
            toast.error('Error creating dispense');
        });
};

function reloadDispences() {
    form.value = {
        patient_name: '',
        phone_number: '',
        diagnosis: '',
        items: [{
            product_id: '',
            product: null,
            dose: '',
            frequency: '',
            duration: '',
            quantity: 1
        }]
    }
    router.get(route('dispence.create'), {}, {
        preserveState: true,
        preserveScroll: true,
        only: ['inventories']
    })
}

</script>