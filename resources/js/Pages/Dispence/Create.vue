<template>
    <AuthenticatedLayout :title="'Create New Dispence'">
        <div class="flex items-center justify-between mb-4">
            <div class="flex flex-col items-start">
                <Link :href="route('dispence.index')" class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Back
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Create New Dispence</h1>
            </div>
        </div>

        <form @submit.prevent="submit" class="w-full mb-6" novalidate>
            <div class="bg-white mb-6">
                <h2 class="text-lg font-semibold mb-4">Patient Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="patient_name" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                            <span>Patient Name</span>
                        </InputLabel>
                        <TextInput
                            id="patient_name"
                            type="text"
                            class="mt-1 block w-full pl-10"
                            v-model="form.patient_name"
                            placeholder="Enter full name"
                            required
                        />
                        <InputError :message="errors.patient_name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="phone_number" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                            <span>Phone Number</span>
                        </InputLabel>
                        <TextInput
                            id="phone_number"
                            type="tel"
                            class="mt-1 block w-full pl-10"
                            v-model="form.phone_number"
                            placeholder="Enter phone number"
                            required
                        />
                        <InputError :message="errors.phone_number" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="bg-white">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-lg font-semibold flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        </svg>
                        Dispence Items
                    </h2>
                </div>
                <table class="min-w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-[30%] border border-black">Product</th>
                            <th class="border border-black">Dose</th>
                            <th class="border border-black">Frequency</th>
                            <th class="border border-black">Start Date</th>
                            <th class="border border-black">Duration</th>
                            <th class="border border-black">Quantity</th>
                            <th class="border border-black">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <tr v-for="(item, index) in form.items" :key="index">
                            <td class="border border-black">
                                <Multiselect
                                    v-model="item.product"
                                    :options="props.inventories"
                                    :searchable="true"
                                    :close-on-select="true"
                                    :allow-empty="true"
                                    placeholder="Select product"
                                    track-by="id"
                                    label="name"
                                    @select="checkInventory(index, $event)"
                                >
                                    <template v-slot:option="{option}">
                                        <span>{{ option.name }}</span>
                                        <small class="text-gray-600 ml-2">(Qty: {{ option.quantity }})</small>
                                    </template>
                                </Multiselect>
                            </td>
                            <td class="border border-black">
                                <TextInput
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model="item.dose"
                                    placeholder="Enter dose amount"
                                    min="1"
                                    @input="calculateItemQuantity(index)"
                                    required
                                />
                            </td>
                            <td class="border border-black">
                                <TextInput
                                    type="number"
                                        class="mt-1 block w-full"
                                    v-model="item.frequency"
                                    placeholder="How many times per day"
                                    min="1"
                                    @input="calculateItemQuantity(index)"
                                    required
                                />
                            </td>
                            <td class="border border-black">
                                <TextInput
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="item.start_date"
                                    required
                                />
                            </td>
                            <td class="border border-black">
                                <TextInput
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model="item.duration"
                                    placeholder="Treatment duration in days"
                                    min="1"
                                    @input="calculateItemQuantity(index)"
                                    required
                                />
                            </td>
                            <td class="border border-black">
                                <TextInput
                                    type="number"
                                    class="mt-1 block w-full bg-gray-100"
                                    v-model="item.quantity"
                                    readonly
                                    required
                                />
                                <small v-if="item.max_quantity" class="text-sm block">
                                    Available: {{ item.max_quantity }}
                                </small>
                                <small v-if="item.max_quantity && item.quantity > item.max_quantity" class="text-red-600 block">
                                    Exceeds stock by {{ item.quantity - item.max_quantity }}
                                </small>
                            </td>
                            <td class="border border-black">
                                <button 
                                    type="button"
                                    @click="removeItem(index)"
                                    class="text-red-600 hover:text-red-900"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <PrimaryButton 
                    type="button" 
                    @click="addItem" 
                    class="mt-4 flex items-center gap-2" 
                    :disabled="isProcessing"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Add Item
                </PrimaryButton>
            </div>

            <div class="flex justify-end mt-6">
                <PrimaryButton 
                    :disabled="isProcessing"
                    class="flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    {{ isProcessing ? 'Processing...' : 'Submit Dispence'}}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import Swal from 'sweetalert2';
import { useToast } from 'vue-toastification'

const toast = useToast();

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
    phone_number: '',
    items: [{
        product_id: '',
        product: null,
        dose: '',
        frequency: '',
        start_date: '',
        duration: '',
        quantity: 0,
        max_quantity: null
    }]
});

const addItem = () => {
    form.value.items.push({
        product_id: '',
        product: null,
        dose: '',
        frequency: '',
        start_date: '',
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
    if(selected) {
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

        const item = form.value.items[index];
        item.product = selected;
        item.product_id = selected.id; // Changed from selected.product_id to selected.id
        item.max_quantity = parseInt(selected.quantity);
        calculateItemQuantity(index);
        return;
    }
    form.value.items[index].product_id = "";
    form.value.items[index].product = null;
    form.value.items[index].max_quantity = null;
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
    console.log(form.value);
    isProcessing.value = true;
    await axios.post(route('dispence.store'), form.value)
        .then((response) => {
            toast.success(response.data);
            reloadDispences();
        })
        .catch((error) => {
            isProcessing.value = false;
            console.error('Error creating dispence:', error);
            toast.error(error.response.data);
        });
};

function reloadDispences(){
    form.value = {
        patient_name: '',
        phone_number: '',
        items: [{
            product_id: '',
            product: null,
            dose: '',
            frequency: '',
            start_date: '',
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