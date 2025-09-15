<template>
    <AuthenticatedLayout title="MOH Dispense Details" description="View MOH dispense record details"
        img="/assets/images/dispence.png">
        
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ props.mohDispense.moh_dispense_number }}</h1>
                        <p class="mt-1 text-sm text-gray-600">MOH Dispense Record Details</p>
                    </div>
                    <div class="flex space-x-3">
                        <button v-if="props.mohDispense.status === 'draft' && props.mohDispense.excel_file_path" 
                            @click="processDispense"
                            :disabled="processing"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ processing ? 'Processing...' : 'Process Excel File' }}
                        </button>
                        <button v-if="props.mohDispense.status === 'draft'" 
                            @click="submitDispense"
                            :disabled="submitting"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ submitting ? 'Submitting...' : 'Submit for Processing' }}
                        </button>
                        <Link :href="route('moh-dispense.index')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 rounded-lg transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back to MOH Dispenses
                        </Link>
                    </div>
                </div>
            </div>

            <!-- MOH Dispense Info -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Basic Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">MOH Dispense Number</dt>
                            <dd class="text-sm text-gray-900">{{ props.mohDispense.moh_dispense_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd>
                                <span :class="getStatusClass(props.mohDispense.status)" 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ props.mohDispense.status }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created By</dt>
                            <dd class="text-sm text-gray-900">{{ props.mohDispense.created_by?.name || 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Summary Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Summary Information</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Items</dt>
                            <dd class="text-sm text-gray-900">{{ props.mohDispense.items?.length || 0 }} items</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created At</dt>
                            <dd class="text-sm text-gray-900">{{ moment(props.mohDispense.created_at).format('DD/MM/YYYY HH:mm') }}</dd>
                        </div>
                    </dl>
                </div>

            </div>

            <!-- Items Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Dispensed Items</h3>
                    <p class="mt-1 text-sm text-gray-600">Items from the uploaded Excel file</p>
                </div>
                
                <template v-if="props.mohDispense.items && props.mohDispense.items.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Item
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Source
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Batch No
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Expiry Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quantity
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dispense Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dispensed By
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in props.mohDispense.items" :key="item.id" 
                                    class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ item.product?.name || 'Unknown Product' }}
                                                </div>
                                                <div v-if="item.product?.productID" class="text-sm text-gray-500">
                                                    ID: {{ item.product.productID }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ item.source || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ item.batch_no || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ moment(item.expiry_date).format('DD/MM/YYYY') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ item.quantity }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ moment(item.dispense_date).format('DD/MM/YYYY') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ item.dispensed_by || 'N/A' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
                <template v-else>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No Items Found</h3>
                        <p class="mt-1 text-sm text-gray-500">No items were found in this MOH dispense record.</p>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import moment from 'moment';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    mohDispense: Object,
});

const submitting = ref(false);
const processing = ref(false);

const processDispense = () => {
    if (processing.value) return;
    
    Swal.fire({
        title: 'Process Excel File',
        text: 'Are you sure you want to process the uploaded Excel file? This will import all items from the file.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Process',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            processing.value = true;
            
            try {
                const response = await axios.post(route('moh-dispense.process', props.mohDispense.id), {}, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    timeout: 600000 // 10 minutes timeout
                });
                
                processing.value = false;
                
                // Update the local status
                props.mohDispense.status = 'processed';
                
                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: response.data.message || 'Excel file processed successfully!',
                    icon: 'success',
                    confirmButtonColor: '#10b981'
                });
                
            } catch (error) {
                processing.value = false;
                
                // Show error message
                let errorMessage = 'Error processing Excel file';
                if (error.response) {
                    const errorData = error.response.data;
                    errorMessage = errorData.message || errorMessage;
                } else if (error.code === 'ECONNABORTED') {
                    errorMessage = 'Processing timeout. Please try again.';
                } else {
                    errorMessage = 'Network error. Please check your connection and try again.';
                }
                
                Swal.fire({
                    title: 'Error!',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        }
    });
};

const submitDispense = () => {
    Swal.fire({
        title: 'Submit MOH Dispense',
        text: 'Are you sure you want to submit this MOH dispense for processing? This action cannot be undone.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Submit',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            submitting.value = true;
            
            router.post(route('moh-dispense.submit', props.mohDispense.id), {}, {
                onSuccess: (page) => {
                    // Update the local status
                    props.mohDispense.status = 'processed';
                    submitting.value = false;
                    
                    // Show success message from flash data
                    if (page.props.flash?.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: page.props.flash.success,
                            icon: 'success',
                            confirmButtonColor: '#10b981'
                        });
                    }
                },
                onError: (errors) => {
                    submitting.value = false;
                    
                    // Show error message from flash data
                    if (errors.error) {
                        Swal.fire({
                            title: 'Error!',
                            text: errors.error,
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Unknown error occurred',
                            icon: 'error',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                }
            });
        }
    });
};

const getStatusClass = (status) => {
    switch (status) {
        case 'processed':
            return 'bg-green-100 text-green-800';
        case 'draft':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

</script>
