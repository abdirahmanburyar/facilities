<template>
    <AuthenticatedLayout>
        <div class="flex h-screen bg-gray-100 mb-6">
            <!-- Left side - Product listing -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Search and filters -->
                <div class="p-4 bg-white shadow">
                    <div class="flex gap-4 mb-4">
                        <div class="flex-1">
                            <input 
                                type="text" 
                                v-model="search" 
                                placeholder="Search products..." 
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <select 
                            v-model="selectedType" 
                            class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">All Types</option>
                            <option value="Lab">Lab</option>
                            <option value="Consumable">Consumable</option>
                        </select>
                    </div>
                </div>

                <!-- Products grid -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div 
                            v-for="item in filteredInventory" 
                            :key="item.id"
                            @click="openPrescriptionModal(item)"
                            class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow cursor-pointer"
                        >
                            <h3 class="font-semibold text-gray-800">{{ item.product.name }}</h3>
                            <p class="text-gray-600 text-sm">{{ item.product.description }}</p>
                            <div class="mt-2 flex flex-col space-y-1">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Pack Size: {{ item.product.pack_size }}</span>
                                    <span :class="getStockLevelClass(item.quantity, item.product.reorder_level)"
                                        class="px-2 py-1 rounded-full text-xs">
                                        {{ item.quantity }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Barcode: {{ item.product.barcode || 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    Location: {{ item.location || 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    Expires: {{ item.expiry_date ? new Date(item.expiry_date).toLocaleDateString() : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Recent Transactions -->
            <div class="w-1/4 bg-white p-4 rounded-lg shadow h-screen flex flex-col">
                <div class="flex-none">
                    <h2 class="text-lg font-semibold mb-4">Recent Transactions</h2>
                    <div class="mb-4">
                        <input type="text" v-model="search_recent" placeholder="Search transactions..." class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            
                <div class="flex-1 overflow-y-auto">
                    <div v-if="pos.length === 0" class="text-center text-gray-500 mt-8">
                        No recent transactions
                    </div>
                    <div v-else class="space-y-3 pr-2">
                        <div 
                            v-for="transaction in pos" 
                            :key="transaction.id"
                            class="bg-gray-50 border rounded-lg p-3"
                        >
                            <div class="text-sm">
                                <p class="font-semibold">{{ transaction.product.name }}</p>
                                <p class="text-gray-600">Patient: {{ transaction.patient_name }}</p>
                                <p class="text-gray-600">Phone: {{ transaction.patient_phone }}</p>
                                <p class="text-gray-600">Qty: {{ transaction.total_quantity }}</p>
                                <p class="text-xs text-gray-500">{{ moment(transaction.pos_date).format('LT') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prescription Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-[9999]">
            <div class="bg-white rounded-lg w-full max-w-7xl relative z-[10000]">
                <div class="p-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-semibold">New Prescription</h3>
                    <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                        <span class="text-2xl">&times;</span>
                    </button>
                </div>

                <div class="p-4">
                    <div v-if="err.message" class="text-start">
                        <p class="text-red-500 mt-2">{{ err.message }}</p>
                    </div>
                    <form @submit.prevent="submitPrescription" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Patient Name</label>
                                <input v-model="formData.patient_name" type="text" required
                                    placeholder="Enter patient's full name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Patient Phone</label>
                                <input v-model="formData.patient_phone" type="text" required
                                    placeholder="Enter patient's phone number"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dose</label>
                                <input v-model="formData.dose" type="number" step="0.01" required
                                    placeholder="Enter dose amount"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Frequency</label>
                                <select v-model="formData.frequency" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Select dosage frequency</option>
                                    <option value="1">Once a day</option>
                                    <option value="2">Twice a day</option>
                                    <option value="3">Three times a day</option>
                                    <option value="4">Four times a day</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Start Date</label>
                                <input v-model="formData.start_date" type="date" required
                                    :min="new Date().toISOString().split('T')[0]"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duration (days)</label>
                                <input v-model="formData.duration" type="number" min="1" required
                                    placeholder="Number of days"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Total Quantity</label>
                                <input type="number" min="1" required
                                    placeholder="Auto-calculated based on dose, frequency and duration"
                                    :value="calculateTotalQuantity"
                                    disabled
                                    class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" @click="showModal = false" :disabled="isSubmit"
                                class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ isSubmit ? 'Submitting...' : ' Submit Prescription' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Print Template (hidden) -->
        <div class="hidden" id="prescription-print">
            <div class="p-4">
                <div class="text-center mb-4">
                    <h2 class="text-xl font-bold">Prescription</h2>
                    <p class="text-sm">{{ new Date().toLocaleDateString() }}</p>
                </div>
                <div class="space-y-2">
                    <p><strong>Patient Name:</strong> {{ formData.patient_name }}</p>
                    <p><strong>Patient Phone:</strong> {{ formData.patient_phone }}</p>
                    <p><strong>Medicine:</strong> {{ selectedProduct?.product?.name }} {{ selectedProduct?.product?.description }}</p>
                    <p><strong>Dose:</strong> {{ formData.dose }}</p>
                    <p><strong>Frequency:</strong> {{ formData.frequency === 'once_a_day' ? 'Once a day' : 'Twice a day' }}</p>
                    <p><strong>Start Date:</strong> {{ formData.start_date }}</p>
                    <p><strong>Duration:</strong> {{ formData.duration }} days</p>
                    <p><strong>Total Quantity:</strong> {{ calculateTotalQuantity }}</p>
                </div>
                <div class="mt-8 text-center">
                    <p>_______________________</p>
                    <p class="mt-2">Doctor's Signature</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';
import moment from 'moment';
import { nextTick } from 'vue';

const props = defineProps({
    inventories: Array,
    pos: {
        type: Array,
        default: () => []
    }
});

// State
const search = ref('');
const selectedType = ref('');
const showModal = ref(false);
const selectedProduct = ref(null);
const err = ref({
    message: '',
});
const formData = ref({
    id: null,
    product_id: null,
    dose: '',
    frequency: '',
    start_date: new Date().toISOString().split('T')[0],
    duration: '',
    total_quantity: '',
    patient_name: '',
    patient_phone: '',
});

function reloadPos(){
    const query = {};
    if(search_recent.value) query.search_recent = search_recent.value;
    router.get(route('pos.index'), query, {
        preserveState: true,
        preserveScroll: true,
        only: ['pos', 'inventories']
    });
}

// Computed
const filteredInventory = computed(() => {
    return props.inventories.filter(item => {
        const matchesSearch = item.product.name.toLowerCase().includes(search.value.toLowerCase()) ||
                            item.product.barcode.toLowerCase().includes(search.value.toLowerCase());
        const matchesType = !selectedType.value || item.product.type === selectedType.value;
        return matchesSearch && matchesType;
    });
});

const calculateTotalQuantity = computed(() => {
    if (!formData.value.dose || !formData.value.frequency || !formData.value.duration) {
        return 0;
    }
    return formData.value.dose * formData.value.frequency * formData.value.duration;
});

const search_recent = ref('');
watch([
    () => search_recent.value
], () => {
    reloadPos();
})

// Watch for changes in dose, frequency, and duration to update total quantity
watch([
    () => formData.value.dose,
    () => formData.value.frequency,
    () => formData.value.duration
], () => {
    formData.value.total_quantity = calculateTotalQuantity.value;
});

// Methods
const getStockLevelClass = (quantity, reorderLevel) => {
    if (quantity <= reorderLevel) {
        return 'bg-red-100 text-red-800';
    } else if (quantity <= reorderLevel * 2) {
        return 'bg-yellow-100 text-yellow-800';
    }
    return 'bg-green-100 text-green-800';
};

const openPrescriptionModal = (item) => {
    selectedProduct.value = item;
    formData.value = {
        id: null,
        product_id: item.product.id,
        dose: '',
        frequency: '',
        start_date: new Date().toISOString().split('T')[0],
        duration: '',
        total_quantity: '',
        patient_name: '',
        patient_phone: '',
    };
    showModal.value = true;
};

const isSubmit = ref(false);

const submitPrescription = async () => {
    err.value.message = '';
    isSubmit.value = true;
    try {
        const response = await axios.post(route('pos.store'), formData.value);
        isSubmit.value = false;
        showModal.value = false; // Close modal first
        await nextTick(); // Wait for Vue to update the DOM
        
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.data,
            backdrop: `rgba(0,0,0,0.4)`,
            allowOutsideClick: false,
            zIndex: 99999,
        });
        reloadPos();
        formData.value = {
            product_id: null,
            dose: '',
            frequency: '',
            start_date: '',
            duration: '',
            total_quantity: '',
            patient_name: '',
            patient_phone: '',
        };
    } catch (error) {
        console.error('Error submitting prescription:', error);
        isSubmit.value = false;        
        err.value.message = error.response.data;
    }
};
</script>
