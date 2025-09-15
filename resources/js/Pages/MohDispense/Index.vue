<template>
    <AuthenticatedLayout title="MOH Dispense" description="Manage MOH medication dispensing records from Excel files"
        img="/assets/images/dispence.png">
        
        <!-- Header Section -->
        <div class="flex flex-col space-y-6 mb-[80px]">
            <!-- Action Buttons -->
            <div class="flex items-center justify-end space-x-3">
                <button @click="showUploadModal = true"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg transition-all duration-200 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Upload Excel
                </button>
                <button @click="router.visit(route('moh-dispense.create'))"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-lg transition-all duration-200 shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    New MOH Dispense
                </button>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" v-model="search"
                            class="pl-10 pr-4 py-2 border border-gray-300 w-full rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Search MOH dispense number" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- Date Range -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">From:</label>
                            <input type="date" v-model="date_from"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">To:</label>
                            <input type="date" v-model="date_to"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200" />
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Per Page:</label>
                            <select v-model="per_page"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <button @click="clearFilters"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all duration-200">
                            Clear Filters
                        </button>
                    </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total MOH Dispenses</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ props.mohDispenses.total }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Processed</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ processedCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Draft</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ draftCount }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MOH Dispenses Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <template v-if="props.mohDispenses.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        MOH Dispense Number
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created By
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Items
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="mohDispense in props.mohDispenses.data" :key="mohDispense.id" 
                                    class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('moh-dispense.show', mohDispense.id)" 
                                            class="text-blue-600 hover:text-blue-900 font-medium">
                                            {{ mohDispense.moh_dispense_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ mohDispense.created_by?.name || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ mohDispense.items_count }} items
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusClass(mohDispense.status)" 
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            <svg v-if="mohDispense.status === 'processing'" class="animate-spin -ml-1 mr-1 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {{ mohDispense.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <Link :href="route('moh-dispense.show', mohDispense.id)"
                                                class="text-blue-600 hover:text-blue-900">
                                                View
                                            </Link>
                                            <button v-if="mohDispense.status === 'draft' && mohDispense.excel_file_path"
                                                @click="processDispense(mohDispense.id)"
                                                :disabled="processing"
                                                class="text-green-600 hover:text-green-900 disabled:opacity-50 disabled:cursor-not-allowed">
                                                {{ processing ? 'Processing...' : 'Process' }}
                                            </button>
                                        </div>
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
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No MOH Dispenses</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new MOH dispense.</p>
                        <div class="mt-6">
                            <button @click="router.visit(route('moh-dispense.create'))"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                New MOH Dispense
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pagination -->
            <div class="flex justify-end mt-2 mb-[20px]">
                <TailwindPagination :data="props.mohDispenses" @pagination-change-page="getResults" />
            </div>
        </div>

        <!-- Upload Excel Modal -->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showUploadModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitUpload">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Upload Excel File
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Upload an Excel file to create MOH dispense records.
                                        </p>
                                    </div>

                                    <!-- File Upload Area -->
                                    <div class="mt-4">
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
                                                    <label for="excel_file_modal" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                        <span>Upload a file</span>
                                                        <input id="excel_file_modal" name="excel_file" type="file" 
                                                            class="sr-only" 
                                                            accept=".xlsx,.xls,.csv"
                                                            @change="handleFileSelect"
                                                            ref="fileInputModal">
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
                                        <div v-if="uploadError" class="mt-2 text-sm text-red-600">
                                            {{ uploadError }}
                                        </div>
                                    </div>

                                    <!-- Template Download Link -->
                                <div class="mt-4">
                                    <button type="button" @click="downloadTemplate"
                                        class="text-sm text-blue-600 hover:text-blue-800 underline">
                                        Download Blank Template
                                    </button>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Download a blank template with column headers for manual data entry
                                    </p>
                                </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" :disabled="!selectedFile || uploading"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="uploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ uploading ? 'Uploading...' : 'Upload' }}
                        </button>
                            <button type="button" @click="closeUploadModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { TailwindPagination } from "laravel-vue-pagination";
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import moment from 'moment';
import axios from 'axios';

const props = defineProps({
    mohDispenses: Object,
    filters: Object,
})

// Reactive data
const per_page = ref(props.filters.per_page || 10);
const search = ref(props.filters.search || '');
const date_from = ref(props.filters.date_from || '');
const date_to = ref(props.filters.date_to || '');

// Upload modal data
const showUploadModal = ref(false);
const selectedFile = ref(null);
const isDragOver = ref(false);
const uploading = ref(false);
const uploadError = ref('');
const fileInputModal = ref(null);
const processing = ref(false);

// Computed properties
const processedCount = computed(() => {
    return props.mohDispenses.data.filter(dispense => dispense.status === 'processed').length;
});


const draftCount = computed(() => {
    return props.mohDispenses.data.filter(dispense => dispense.status === 'draft').length;
});

// Methods
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

const clearFilters = () => {
    search.value = '';
    date_from.value = '';
    date_to.value = '';
    per_page.value = 10;
    reloadMohDispenses();
};

const reloadMohDispenses = () => {
    const query = {};
    if (search.value) query.search = search.value;
    if (date_from.value) query.date_from = date_from.value;
    if (date_to.value) query.date_to = date_to.value;
    if (per_page.value) {
        query.per_page = per_page.value;
    }
    if (props.filters.page) query.page = props.filters.page;
    
    router.get(route('moh-dispense.index'), query, { 
        preserveState: true, 
        preserveScroll: true, 
        only: ['mohDispenses'] 
    });
};


const getResults = (page) => {
    const query = {};
    if (search.value) query.search = search.value;
    if (date_from.value) query.date_from = date_from.value;
    if (date_to.value) query.date_to = date_to.value;
    if (per_page.value) query.per_page = per_page.value;
    
    router.get(route('moh-dispense.index'), { ...query, page }, { 
        preserveState: true, 
        preserveScroll: true, 
        only: ['mohDispenses'] 
    });
};

// Upload methods
const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (!isValidFileType(file)) {
            uploadError.value = 'Please select a valid Excel file (.xlsx, .xls, .csv)';
            selectedFile.value = null;
            return;
        }
        
        // File size validation removed - no limits
        
        selectedFile.value = file;
        uploadError.value = '';
    }
};

const handleFileDrop = (event) => {
    isDragOver.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        if (!isValidFileType(file)) {
            uploadError.value = 'Please select a valid Excel file (.xlsx, .xls, .csv)';
            selectedFile.value = null;
            return;
        }
        
        // File size validation removed - no limits
        
        selectedFile.value = file;
        uploadError.value = '';
    }
};

const removeFile = () => {
    selectedFile.value = null;
    if (fileInputModal.value) {
        fileInputModal.value.value = '';
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

// File size validation removed - no limits

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

const closeUploadModal = () => {
    showUploadModal.value = false;
    selectedFile.value = null;
    uploadError.value = '';
    if (fileInputModal.value) {
        fileInputModal.value.value = '';
    }
};

const processDispense = async (id) => {
    if (processing.value) return;
    
    processing.value = true;
    
    try {
        const response = await axios.post(route('moh-dispense.process', id), {}, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            timeout: 600000 // 10 minutes timeout
        });
        
        processing.value = false;
        alert('MOH Dispense processed successfully!');
        router.reload({ only: ['mohDispenses'] });
        
    } catch (error) {
        processing.value = false;
        
        if (error.response) {
            const errorData = error.response.data;
            alert('Error processing MOH Dispense: ' + (errorData.message || 'Unknown error'));
        } else if (error.code === 'ECONNABORTED') {
            alert('Processing timeout. Please try again.');
        } else {
            alert('Network error. Please check your connection and try again.');
        }
    }
};

const submitUpload = async () => {
    if (!selectedFile.value) return;
    
    uploading.value = true;
    uploadError.value = '';
    
    const formData = new FormData();
    formData.append('excel_file', selectedFile.value);
    
    try {
        const response = await axios.post(route('moh-dispense.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            timeout: 600000, // 10 minutes timeout
            onUploadProgress: (progressEvent) => {
                if (progressEvent.lengthComputable) {
                    const percentComplete = (progressEvent.loaded / progressEvent.total) * 100;
                    console.log('Upload progress:', percentComplete + '%');
                }
            }
        });
        
        // Success
        closeUploadModal();
        const mohDispenseNumber = response.data.moh_dispense_number || 'Unknown';
        alert(`Excel file uploaded successfully!\nMOH Dispense Number: ${mohDispenseNumber}\nYou can now process it from the list.`);
        router.reload({ only: ['mohDispenses'] });
        
    } catch (error) {
        uploading.value = false;
        
        if (error.response) {
            // Server responded with error status
            const errorData = error.response.data;
            if (errorData.message) {
                uploadError.value = errorData.message;
            } else if (errorData.errors && errorData.errors.excel_file) {
                uploadError.value = errorData.errors.excel_file[0];
            } else {
                uploadError.value = 'Upload failed. Please try again.';
            }
        } else if (error.code === 'ECONNABORTED') {
            // Timeout error
            uploadError.value = 'Upload timeout. The file might be too large. Please try a smaller file.';
        } else {
            // Network error
            uploadError.value = 'Network error. Please check your connection and try again.';
        }
    }
};

// Watchers
watch([search, date_from, date_to, per_page], () => {
    reloadMohDispenses();
}, { deep: true });
</script>
