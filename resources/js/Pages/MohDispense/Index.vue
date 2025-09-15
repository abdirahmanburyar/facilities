<template>
    <AuthenticatedLayout title="MOH Dispense" description="Manage MOH medication dispensing records">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">MOH Dispense</h1>
            <div class="flex space-x-3">
                <button @click="showUploadModal = true"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Upload Excel
                </button>
                <Link :href="route('moh-dispense.create')"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    Create New
                </Link>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white p-4 rounded-lg shadow mb-6">
            <div class="flex space-x-4">
                <input v-model="search" type="text" placeholder="Search by MOH number..."
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2">
                <select v-model="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="processed">Processed</option>
                </select>
            </div>
        </div>

        <!-- MOH Dispenses Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">MOH Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="mohDispense in mohDispenses.data" :key="mohDispense.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <Link :href="route('moh-dispense.show', mohDispense.id)"
                                class="text-blue-600 hover:text-blue-900 font-medium">
                                {{ mohDispense.moh_dispense_number }}
                            </Link>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ mohDispense.created_by?.name || 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ mohDispense.items?.length || 0 }} items
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="getStatusClass(mohDispense.status)"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                {{ mohDispense.status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(mohDispense.created_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <Link :href="route('moh-dispense.show', mohDispense.id)"
                                    class="text-blue-600 hover:text-blue-900">View</Link>
                                <button v-if="mohDispense.status === 'draft' && mohDispense.excel_file_path"
                                    @click="processDispense(mohDispense.id)"
                                    :disabled="processing"
                                    class="text-green-600 hover:text-green-900 disabled:opacity-50">
                                    {{ processing ? 'Processing...' : 'Process' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            <TailwindPagination :data="mohDispenses" @pagination-change-page="loadPage" />
        </div>

        <!-- Upload Modal -->
        <div v-if="showUploadModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showUploadModal = false"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitUpload">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Upload Excel File</h3>
                            
                            <div class="mb-4">
                                <input type="file" ref="fileInput" @change="handleFileSelect" accept=".xlsx,.xls,.csv"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            </div>

                            <div v-if="selectedFile" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-green-800">{{ selectedFile.name }}</span>
                                    <button type="button" @click="removeFile" class="ml-auto text-red-600 hover:text-red-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div v-if="uploadError" class="mb-4 text-sm text-red-600">
                                {{ uploadError }}
                            </div>

                            <div class="mb-4">
                                <button type="button" @click="downloadTemplate"
                                    class="text-sm text-blue-600 hover:text-blue-800 underline">
                                    Download Template
                                </button>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="!selectedFile || uploading"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
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
});

// Reactive data
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const showUploadModal = ref(false);
const selectedFile = ref(null);
const uploading = ref(false);
const uploadError = ref('');
const processing = ref(false);
const fileInput = ref(null);

// Methods
const getStatusClass = (status) => {
    return {
        'bg-yellow-100 text-yellow-800': status === 'draft',
        'bg-green-100 text-green-800': status === 'processed',
    };
};

const formatDate = (date) => {
    return moment(date).format('MMM DD, YYYY');
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        uploadError.value = '';
    }
};

const removeFile = () => {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const downloadTemplate = () => {
    window.open(route('moh-dispense.download-template'), '_blank');
};

const closeUploadModal = () => {
    showUploadModal.value = false;
    selectedFile.value = null;
    uploadError.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
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
            timeout: 300000, // 5 minutes timeout
        });
        
        closeUploadModal();
        alert(`Excel file processed successfully!\nMOH Number: ${response.data.moh_dispense_number}`);
        router.reload({ only: ['mohDispenses'] });
        
    } catch (error) {
        uploading.value = false;
        
        if (error.response) {
            const errorData = error.response.data;
            uploadError.value = errorData.message || 'Upload failed. Please try again.';
        } else if (error.code === 'ECONNABORTED') {
            uploadError.value = 'Upload timeout. Please try again.';
        } else {
            uploadError.value = 'Network error. Please check your connection.';
        }
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
            timeout: 300000 // 5 minutes timeout
        });
        
        processing.value = false;
        alert('MOH Dispense processed successfully!');
        router.reload({ only: ['mohDispenses'] });
        
    } catch (error) {
        processing.value = false;
        
        if (error.response) {
            const errorData = error.response.data;
            alert('Error: ' + (errorData.message || 'Unknown error'));
        } else {
            alert('Network error. Please try again.');
        }
    }
};

const loadPage = (page) => {
    router.get(route('moh-dispense.index'), {
        search: search.value,
        status: statusFilter.value,
        page: page
    }, {
        preserveState: true,
        replace: true
    });
};

// Watchers
watch([search, statusFilter], () => {
    router.get(route('moh-dispense.index'), {
        search: search.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        replace: true
    });
}, { deep: true });
</script>