<template>
    <AuthenticatedLayout title="New MOH Dispense" description="Upload Excel file for MOH medication dispensing"
        img="/assets/images/dispence.png">
        
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">New MOH Dispense</h1>
                        <p class="mt-1 text-sm text-gray-600">Upload an Excel file to create MOH dispense records</p>
                    </div>
                    <Link :href="route('moh-dispense.index')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to MOH Dispenses
                    </Link>
                </div>
            </div>

            <!-- Upload Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                <form @submit.prevent="submitForm" enctype="multipart/form-data">
                    <!-- Excel File Upload -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Excel File <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors duration-200"
                            :class="{ 'border-blue-400 bg-blue-50': isDragOver }"
                            @dragover.prevent="isDragOver = true"
                            @dragleave.prevent="isDragOver = false"
                            @drop.prevent="handleFileDrop">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="excel_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload a file</span>
                                        <input id="excel_file" name="excel_file" type="file" 
                                            class="sr-only" 
                                            accept=".xlsx,.xls,.csv"
                                            @change="handleFileSelect"
                                            ref="fileInput">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                            <p class="text-xs text-gray-500">Excel files (.xlsx, .xls, .csv) - no size limit</p>
                            </div>
                        </div>
                        
                        <!-- Selected File Display -->
                        <div v-if="selectedFile" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm font-medium text-green-800">{{ selectedFile.name }}</span>
                                <span class="ml-2 text-sm text-green-600">({{ formatFileSize(selectedFile.size) }})</span>
                                <button type="button" @click="removeFile" 
                                    class="ml-auto text-red-600 hover:text-red-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Error Display -->
                        <div v-if="errors.excel_file" class="mt-2 text-sm text-red-600">
                            {{ errors.excel_file }}
                        </div>
                    </div>


                    <!-- Excel Template Info -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-blue-800">Excel File Format</h3>
                                <p class="mt-1 text-sm text-blue-700">
                                    Your Excel file should contain the following columns. 
                                    <span v-if="props.eligibleItemsCount > 0" class="font-medium">
                                        Your facility has {{ props.eligibleItemsCount }} eligible items.
                                    </span>
                                </p>
                                <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                                    <li><strong>item</strong> - Product name, ID, or productID</li>
                                    <li><strong>batch_no</strong> - Batch number from the medication package</li>
                                    <li><strong>expiry_date</strong> - Expiry date (YYYY-MM-DD format)</li>
                                    <li><strong>quantity</strong> - Quantity dispensed (number only)</li>
                                    <li><strong>dispense_date</strong> - Date of dispense (YYYY-MM-DD format)</li>
                                    <li><strong>dispensed_by</strong> - Name of person who dispensed</li>
                                </ul>
                                <!-- Column Examples -->
                                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <h4 class="text-sm font-medium text-yellow-800 mb-2">Column Examples:</h4>
                                    <div class="text-xs text-yellow-700 space-y-1">
                                        <div><strong>batch_no:</strong> "BATCH001", "LOT12345", "2024-001"</div>
                                        <div><strong>quantity:</strong> "10", "25", "100" (numbers only)</div>
                                        <div><strong>dispensed_by:</strong> "Dr. Smith", "Nurse Johnson", "Pharmacist"</div>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="button" @click="downloadTemplate"
                                        class="inline-flex items-center px-3 py-2 border border-blue-300 text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 rounded-lg transition-all duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Download Blank Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <Link :href="route('moh-dispense.index')"
                            class="px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 rounded-lg transition-all duration-200">
                            Cancel
                        </Link>
                        <button type="submit" :disabled="!selectedFile || processing"
                            class="px-4 py-2 border border-transparent text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ processing ? 'Processing...' : 'Create MOH Dispense' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    errors: Object,
    eligibleItemsCount: Number,
});

const form = reactive({});

const selectedFile = ref(null);
const isDragOver = ref(false);
const processing = ref(false);
const fileInput = ref(null);

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
    }
};

const handleFileDrop = (event) => {
    isDragOver.value = false;
    const file = event.dataTransfer.files[0];
    if (file && isValidFileType(file)) {
        selectedFile.value = file;
    }
};

const removeFile = () => {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const isValidFileType = (file) => {
    const validTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
        'application/csv', // .csv alternative
        'text/plain', // .csv sometimes reported as this
        'application/octet-stream' // fallback for some systems
    ];
    return validTypes.includes(file.type) || file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.name.endsWith('.csv');
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const downloadTemplate = () => {
    window.open(route('moh-dispense.download-template'), '_blank');
};

const submitForm = () => {
    if (!selectedFile.value) return;
    
    processing.value = true;
    
    const formData = new FormData();
    formData.append('excel_file', selectedFile.value);
    
    router.post(route('moh-dispense.store'), formData, {
        onSuccess: (page) => {
            // Redirect to the created MOH dispense or index
            if (page.props.flash?.moh_dispense_id) {
                router.visit(route('moh-dispense.show', page.props.flash.moh_dispense_id));
            } else {
                router.visit(route('moh-dispense.index'));
            }
        },
        onError: () => {
            processing.value = false;
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};
</script>
