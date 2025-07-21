<template>
    <AuthenticatedLayout title="Back Order Management" description="Manage and process back orders efficiently"
        img="/assets/images/orders.png">
        
        <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Back Order Management</h1>
                    <p class="text-gray-600">Select a source to view and manage back order items</p>
                </div>

                <!-- Source Selection -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <div class="max-w-md">
                        <label for="source" class="block text-sm font-medium text-gray-700 mb-2">
                            Select Source
                        </label>
                        <Multiselect v-model="selectedSource" :options="sourceOptions" :searchable="true" :create-option="false"
                            placeholder="Select Order or Transfer" label="display_name" track-by="id"
                            :show-labels="false"
                            @select="handleSourceChange" />
                    </div>
                </div>

                <!-- Back Order Content -->
                <div v-if="selectedSource" class="space-y-8">
                    <!-- Back Order Information Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden" v-if="backOrderInfo">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                            <h3 class="text-lg font-semibold text-white">Back Order Information</h3>
                        </div>
                        
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <p class="text-sm font-medium text-blue-600">Back Order Number</p>
                                    <p class="text-lg font-semibold text-blue-900">{{ backOrderInfo.back_order_number }}</p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4">
                                    <p class="text-sm font-medium text-green-600">Back Order Date</p>
                                    <p class="text-lg font-semibold text-green-900">{{ moment(backOrderInfo.back_order_date).format('DD/MM/YYYY') }}</p>
                                </div>
                                <div class="bg-purple-50 rounded-lg p-4">
                                    <p class="text-sm font-medium text-purple-600">Status</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getBackOrderStatusClass(backOrderInfo.status)">
                                        {{ backOrderInfo.status }}
                                    </span>
                                </div>
                            </div>

                            <!-- Parent-level Attachments -->
                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Back Order Attachments</h4>
                                
                                <!-- File Upload -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Attachments (PDF files)</label>
                                    <input type="file" multiple accept=".pdf" @change="handleParentAttachments" 
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all duration-200" />
                                    
                                    <!-- Selected Files Preview -->
                                    <div v-if="parentAttachments.length > 0" class="mt-4">
                                        <h5 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h5>
                                        <div class="space-y-2">
                                            <div v-for="(file, index) in parentAttachments" :key="index" 
                                                class="flex items-center justify-between text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    {{ file.name }}
                                                </span>
                                                <button type="button" @click="removeParentAttachment(index)" 
                                                    class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg flex items-center justify-center hover:bg-blue-700 transition-all duration-200" 
                                            @click="uploadParentAttachments" :disabled="isUploading">
                                            <svg v-if="isUploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                            {{ isUploading ? 'Uploading...' : 'Upload Files' }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Uploaded Files -->
                                <div v-if="backOrderInfo.attach_documents && backOrderInfo.attach_documents.length" class="mt-6">
                                    <h5 class="text-sm font-medium text-gray-700 mb-3">Uploaded Files:</h5>
                                    <div class="space-y-2">
                                        <div v-for="(doc, i) in backOrderInfo.attach_documents" :key="i" 
                                            class="flex items-center justify-between text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                                            <a :href="doc.path" target="_blank" class="flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                {{ doc.name }}
                                            </a>
                                            <button type="button" @click="deleteParentAttachment(doc.path)" 
                                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Back Order Items Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-600 to-gray-700 px-6 py-4">
                            <h3 class="text-lg font-semibold text-white">Back Order Items</h3>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Item ID
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Item Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Source
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Quantity
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
                                    <template v-if="isLoading">
                                        <tr v-for="i in 3" :key="i">
                                            <td v-for="j in 7" :key="j" class="px-6 py-4">
                                                <div class="animate-pulse h-4 bg-gray-200 rounded"></div>
                                            </td>
                                        </tr>
                                    </template>
                                    <template v-else>
                                        <template v-for="item in groupedItems" :key="item.id">
                                            <tr v-for="(row, index) in item.rows" :key="index"
                                                class="hover:bg-gray-50 transition-colors duration-200">
                                                <td class="px-6 py-4 text-sm text-gray-900" v-if="index === 0" :rowspan="item.rows.length">
                                                    <span class="font-medium">{{ item.product.productID }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900" v-if="index === 0" :rowspan="item.rows.length">
                                                    <span class="font-medium">{{ item.product.name }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900" v-if="index === 0" :rowspan="item.rows.length">
                                                    <span class="text-blue-600">{{ getSourceDisplayName(item) }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-900" v-if="index === 0" :rowspan="item.rows.length">
                                                    {{ moment(item.created_at).format('DD/MM/YYYY') }}
                                                </td>
                                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                                    {{ row.quantity }}
                                                </td>
                                                <td class="px-6 py-4 text-sm">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                                        :class="getStatusClass(row.status)">
                                                        {{ row.status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-sm">
                                                    <div class="flex gap-2">
                                                        <button v-if="row.status === 'Missing'"
                                                            @click="handleAction('Liquidate', { ...item, status: row.status, quantity: row.quantity })"
                                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-yellow-500 rounded-lg hover:bg-yellow-600 transition-all duration-200"
                                                            :disabled="isLoading">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                            </svg>
                                                            Liquidate
                                                        </button>
                                                        <button v-if="row.status === 'Missing'"
                                                            @click="handleAction('Receive', { ...item, status: row.status, quantity: row.quantity })"
                                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-all duration-200"
                                                            :disabled="isLoading">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                            </svg>
                                                            Receive
                                                        </button>
                                                        <button v-if="row.status === 'Damaged' || row.status === 'Expired' || row.status === 'Low quality'"
                                                            @click="handleAction('Dispose', { ...item, status: row.status, quantity: row.quantity })"
                                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-all duration-200"
                                                            :disabled="isLoading">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                            Dispose
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr v-if="items.length === 0">
                                            <td colspan="7" class="px-6 py-8 text-center">
                                                <div class="text-gray-500">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <p class="text-lg font-medium">No back order items found</p>
                                                    <p class="text-sm">Select a different source or check back later.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!selectedSource" class="text-center py-12">
                    <div class="mx-auto h-24 w-24 text-gray-300 mb-4">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Source</h3>
                    <p class="text-gray-500">Choose an order or transfer from the dropdown above to view back order items.</p>
                </div>
            </div>
        </div>

        <!-- Liquidation Modal -->
        <Modal :show="showLiquidateModal" max-width="xl" @close="showLiquidateModal = false">
            <form id="liquidationForm" class="p-6 space-y-6" @submit.prevent="submitLiquidation">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Liquidate Item</h2>
                    <button type="button" @click="showLiquidateModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Product Info -->
                <div v-if="selectedItem" class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-6 border border-yellow-200">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-yellow-700">Product ID</p>
                            <p class="text-sm font-semibold text-yellow-900">{{ selectedItem.product.productID }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-yellow-700">Product Name</p>
                            <p class="text-sm font-semibold text-yellow-900">{{ selectedItem.product.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-yellow-700">Source</p>
                            <p class="text-sm font-semibold text-yellow-900">{{ getSourceDisplayName(selectedItem) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-yellow-700">Status</p>
                            <p class="text-sm font-semibold text-yellow-900">{{ selectedItem.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <input type="number" id="quantity" v-model="liquidateForm.quantity" readonly
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 transition-all duration-200"
                        :min="1" :max="selectedItem?.quantity" required>
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                    <textarea id="note" v-model="liquidateForm.note"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-yellow-500 focus:ring-yellow-500 transition-all duration-200"
                        rows="3" placeholder="Enter liquidation details..." required></textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Attachments (PDF files)</label>
                    <input type="file" ref="attachments" @change="(e) => handleFileChange('liquidate', e)"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100 transition-all duration-200"
                        multiple accept=".pdf">
                </div>

                <!-- Selected Files Preview -->
                <div v-if="liquidateForm.attachments.length > 0" class="mt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                    <div class="space-y-2">
                        <div v-for="(file, index) in liquidateForm.attachments" :key="index"
                            class="flex items-center justify-between text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ file.name }}
                            </span>
                            <button type="button" @click="removeLiquidateFile(index)"
                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200"
                        @click="showLiquidateModal = false">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-yellow-500 hover:bg-yellow-600 transition-all duration-200"
                        :disabled="isSubmitting">
                        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isSubmitting ? 'Liquidating...' : 'Liquidate' }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- Dispose Modal -->
        <Modal :show="showDisposeModal" max-width="xl" @close="showDisposeModal = false">
            <form id="disposeForm" class="p-6 space-y-6" @submit.prevent="submitDisposal">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Dispose Item</h2>
                    <button type="button" @click="showDisposeModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Product Info -->
                <div v-if="selectedItem" class="bg-gradient-to-r from-red-50 to-pink-50 rounded-xl p-6 border border-red-200">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-red-700">Product ID</p>
                            <p class="text-sm font-semibold text-red-900">{{ selectedItem.product.productID }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-red-700">Product Name</p>
                            <p class="text-sm font-semibold text-red-900">{{ selectedItem.product.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-red-700">Source</p>
                            <p class="text-sm font-semibold text-red-900">{{ getSourceDisplayName(selectedItem) }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-red-700">Status</p>
                            <p class="text-sm font-semibold text-red-900">{{ selectedItem.status }}</p>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <input type="number" id="quantity" v-model="disposeForm.quantity" readonly
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 transition-all duration-200"
                        :min="1" :max="selectedItem?.quantity" required>
                </div>

                <!-- Note -->
                <div>
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                    <textarea id="note" v-model="disposeForm.note"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 transition-all duration-200"
                        rows="3" placeholder="Enter disposal details..." required></textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Attachments (PDF files)</label>
                    <input type="file" ref="attachments" @change="(e) => handleFileChange('dispose', e)"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 transition-all duration-200"
                        multiple accept=".pdf">
                </div>

                <!-- Selected Files Preview -->
                <div v-if="disposeForm.attachments.length > 0" class="mt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                    <div class="space-y-2">
                        <div v-for="(file, index) in disposeForm.attachments" :key="index"
                            class="flex items-center justify-between text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ file.name }}
                            </span>
                            <button type="button" @click="removeDisposeFile(index)"
                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200"
                        @click="showDisposeModal = false">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-all duration-200"
                        :disabled="isSubmitting">
                        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isSubmitting ? 'Disposing...' : 'Dispose' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import moment from 'moment';
import Modal from '@/Components/Modal.vue';
import { useToast } from 'vue-toastification';

// Component state
const selectedSource = ref(null);
const items = ref([]);
const backOrderInfo = ref(null);
const parentAttachments = ref([]);
const sourceOptions = ref([]);

const toast = useToast();

const groupedItems = computed(() => {
    const result = [];
    // Group items by product, source, and date
    items.value.forEach(item => {
        const existingGroup = result.find(g =>
            g.product.productID === item.product.productID &&
            g.source_id === item.source_id &&
            g.source_type === item.source_type &&
            moment(g.created_at).isSame(item.created_at, 'day')
        );

        if (!existingGroup) {
            result.push({
                id: item.id,
                product: item.product,
                source_id: item.source_id,
                source_type: item.source_type,
                source: item.source,
                created_at: item.created_at,
                back_order_id: item.back_order_id,
                rows: [{
                    quantity: item.quantity,
                    status: item.status,
                    actions: getAvailableActions(item.status),
                    finalized: item.finalized
                }],
            });
        } else {
            existingGroup.rows.push({
                quantity: item.quantity,
                status: item.status,
                actions: getAvailableActions(item.status)
            });
        }
    });

    return result;
});

const getAvailableActions = (status) => {
    if (status === 'Missing') return ['Receive', 'Liquidate'];
    if (status === 'Damaged') return ['Receive', 'Dispose'];
    if (status === 'Lost') return ['Receive'];
    if (status === 'Expired') return ['Receive', 'Dispose'];
    if (status === 'Low quality') return ['Receive', 'Dispose'];
    return [];
};

const getBackOrderStatusClass = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'processing':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'Missing':
            return 'bg-yellow-100 text-yellow-800';
        case 'Damaged':
            return 'bg-red-100 text-red-800';
        case 'Lost':
            return 'bg-gray-100 text-gray-800';
        case 'Expired':
            return 'bg-orange-100 text-orange-800';
        case 'Low quality':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getSourceDisplayName = (item) => {
    if (item.source_type === 'order') {
        return `Order: ${item.source?.order_number || 'N/A'}`;
    } else if (item.source_type === 'transfer') {
        return `Transfer: ${item.source?.transfer_id || 'N/A'}`;
    }
    return 'Unknown Source';
};

const isLoading = ref(false);
const isSubmitting = ref(false);
const showLiquidateModal = ref(false);
const showDisposeModal = ref(false);
const selectedItem = ref(null);

const liquidateForm = ref({
    quantity: 0,
    note: '',
    attachments: []
});

const disposeForm = ref({
    quantity: 0,
    note: '',
    attachments: []
});

const handleFileChange = (formType, e) => {
    const files = Array.from(e.target.files || []);
    if (formType === 'liquidate') {
        liquidateForm.value.attachments = files;
    } else {
        disposeForm.value.attachments = files;
    }
};

const removeLiquidateFile = (index) => {
    liquidateForm.value.attachments.splice(index, 1);
};

const removeDisposeFile = (index) => {
    disposeForm.value.attachments.splice(index, 1);
};

// Component props
const props = defineProps({
    orders: {
        required: true,
        type: Array
    },
    transfers: {
        required: true,
        type: Array
    }
});

// Load source options on mount
onMounted(() => {
    // Combine orders and transfers for source options
    const orderOptions = props.orders.map(order => ({
        id: order.id,
        display_name: `Order: ${order.order_number}`,
        type: 'order',
        ...order
    }));
    
    const transferOptions = props.transfers.map(transfer => ({
        id: transfer.id,
        display_name: `Transfer: ${transfer.transferID || transfer.transfer_id || transfer.id}`,
        type: 'transfer',
        ...transfer
    }));
    
    sourceOptions.value = [...orderOptions, ...transferOptions];
});

// Action handlers
        const receiveItems = async (item) => {
    const { value: quantity } = await Swal.fire({
        title: 'Enter Quantity to Receive',
        input: 'number',
        inputLabel: `Maximum quantity: ${item.quantity}`,
        inputValue: item.quantity,
        inputAttributes: {
            min: '1',
            max: item.quantity.toString(),
            step: '1'
        },
        showCancelButton: true,
        confirmButtonText: 'Receive',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6B7280',
        showLoaderOnConfirm: true,
        preConfirm: async (value) => {
            const num = parseInt(value);
            if (!value || num < 1) {
                Swal.showValidationMessage('Please enter a quantity greater than 0');
                return false;
            }
            if (num > item.quantity) {
                Swal.showValidationMessage(`Cannot receive more than ${item.quantity} items`);
                return false;
            }

            try {
                isLoading.value = true;
                const requestData = {
                    id: item.id,
                    back_order_id: backOrderInfo.value.id,
                    product_id: item.product.id,
                    source_id: item.source_id,
                    source_type: item.source_type,
                    quantity: num,
                    original_quantity: item.quantity,
                    status: item.status
                };
                await axios.post(route('backorders.receive'), requestData)
                    .then(response => {
                        Swal.fire({
                            title: 'Success!',
                            text: response.data.message,
                            icon: 'success',
                            confirmButtonColor: '#10B981',
                        });
                    })
                    .catch(error => {
                        console.error('Failed to receive items:', error);
                        Swal.showValidationMessage(error.response?.data?.message || 'Failed to receive items');
                    });
                await handleSourceChange(selectedSource.value);
                return true;
            } catch (error) {
                console.error('Failed to receive items:', error);
                Swal.showValidationMessage(error.response?.data?.message || 'Failed to receive items');
                return false;
            } finally {
                isLoading.value = false;
            }
        },
        allowOutsideClick: () => !Swal.isLoading()
    });
};

// Event handlers
const handleSourceChange = async (source) => {
    if (!source) {
        items.value = [];
        backOrderInfo.value = null;
        return;
    }
    
    isLoading.value = true;
    let url = '';
    if (source.type === 'order') {
        url = route('backorders.get-back-order.order', source.id);
    } else if (source.type === 'transfer') {
        url = route('backorders.get-back-order.transfer', source.id);
    }
    
    await axios.get(url)
        .then((response) => {
            isLoading.value = false;
            // Sort items by created_at to ensure consistent grouping
            items.value = response.data.sort((a, b) =>
                new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
            );
            
            // Extract back order information from the first item (all items should have the same back order)
            if (items.value.length > 0 && items.value[0].back_order) {
                backOrderInfo.value = items.value[0].back_order;
            } else {
                backOrderInfo.value = null;
            }
        })
        .catch((error) => {
            isLoading.value = false;
            console.error('Failed to fetch back order items:', error);
            items.value = [];
            backOrderInfo.value = null;
        });
};

const submitLiquidation = async () => {
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('id', selectedItem.value.id);
    formData.append('product_id', selectedItem.value.product.id);
    formData.append('source_id', selectedItem.value.source_id);
    formData.append('source_type', selectedItem.value.source_type);
    formData.append('quantity', liquidateForm.value.quantity);
    formData.append('original_quantity', selectedItem.value.quantity);
    formData.append('status', selectedItem.value.status);
    formData.append('type', selectedItem.value.status);
    formData.append('note', liquidateForm.value.note);
    
    // Get back_order_id from backOrderInfo or from the item itself
    const backOrderId = backOrderInfo.value?.id || selectedItem.value.back_order_id;
    if (backOrderId) {
        formData.append('back_order_id', backOrderId);
    }

    // Append each attachment
    for (let i = 0; i < liquidateForm.value.attachments.length; i++) {
        formData.append('attachments[]', liquidateForm.value.attachments[i]);
    }

    await axios.post(route('backorders.liquidate'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            isSubmitting.value = false
            showLiquidateModal.value = false;
            Swal.fire({
                icon: 'success',
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                handleSourceChange(selectedSource.value);
                liquidateForm.value = {
                    quantity: 0,
                    note: '',
                    attachments: []
                };
            });
        })
        .catch((error) => {
            isSubmitting.value = false
            console.error('Failed to liquidate items:', error);
            Swal.fire({
                icon: 'error',
                title: error.response.data,
                showConfirmButton: false,
                timer: 1500
            });
        });
};

const handleAction = async (action, item) => {
    selectedItem.value = item;

    switch (action) {
        case 'Receive':
            await receiveItems(item);
            break;

        case 'Liquidate':
            liquidateForm.value = {
                quantity: item.quantity,
                note: '',
                attachments: [],
                ...item
            };
            showLiquidateModal.value = true;
            break;

        case 'Dispose':
            disposeForm.value = {
                quantity: item.quantity,
                note: '',
                attachments: [],
                ...item
            };
            showDisposeModal.value = true;
            break;
    }
};

const submitDisposal = async () => {
    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('id', selectedItem.value.id);
    formData.append('product_id', selectedItem.value.product.id);
    formData.append('source_id', selectedItem.value.source_id);
    formData.append('source_type', selectedItem.value.source_type);
    formData.append('quantity', disposeForm.value.quantity);
    formData.append('original_quantity', selectedItem.value.quantity);
    formData.append('status', selectedItem.value.status);
    formData.append('type', selectedItem.value.status);
    formData.append('note', disposeForm.value.note);
    
    // Get back_order_id from backOrderInfo or from the item itself
    const backOrderId = backOrderInfo.value?.id || selectedItem.value.back_order_id;
    if (backOrderId) {
        formData.append('back_order_id', backOrderId);
    }

    // Append each attachment
    for (let i = 0; i < disposeForm.value.attachments.length; i++) {
        formData.append('attachments[]', disposeForm.value.attachments[i]);
    }

    await axios.post(route('backorders.dispose'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            isSubmitting.value = false
            showDisposeModal.value = false;
            Swal.fire({
                icon: 'success',
                title: response.data.message,
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                disposeForm.value = {
                    quantity: 0,
                    note: '',
                    attachments: []
                };
                handleSourceChange(selectedSource.value);
            });
        })
        .catch((error) => {
            isSubmitting.value = false
            console.error('Failed to dispose items:', error);
            Swal.fire({
                icon: 'error',
                title: error.response.data,
                showConfirmButton: false,
                timer: 1500
            });
        });
};

function handleParentAttachments(e) {
    parentAttachments.value = Array.from(e.target.files || []);
}

function removeParentAttachment(index) {
    parentAttachments.value.splice(index, 1);
}

const isUploading = ref(false);

async function uploadParentAttachments() {
    console.log('Upload attachments called with:', {
        backOrderInfo: backOrderInfo.value,
        parentAttachments: parentAttachments.value,
        backOrderId: backOrderInfo.value?.id
    });
    
    if (!backOrderInfo.value || parentAttachments.value.length === 0) return;
    const result = await Swal.fire({
        title: 'Upload Attachments?',
        text: 'Are you sure you want to upload these attachments to the back order?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, upload!'
    });
    if (!result.isConfirmed) return;
    isUploading.value = true;
    const formData = new FormData();
    parentAttachments.value.forEach(file => formData.append('attachments[]', file));
    
    console.log('Making upload request to:', route('backorders.uploadAttachment', backOrderInfo.value.id));
    
    try {
        const { data } = await axios.post(route('backorders.uploadAttachment', backOrderInfo.value.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        console.log('Upload response:', data);
        parentAttachments.value = [];
        if (backOrderInfo.value.attach_documents) {
            backOrderInfo.value.attach_documents = data.files;
        }
        toast.success(data.message);
    } catch (error) {
        console.error('Upload error:', error);
        toast.error(error.response?.data?.message || 'Failed to upload attachments');
    } finally {
        isUploading.value = false;
    }
}

async function deleteParentAttachment(filePath) {
    console.log('Delete attachment called with:', {
        filePath: filePath,
        backOrderId: backOrderInfo.value?.id
    });
    
    const result = await Swal.fire({
        title: 'Delete Attachment?',
        text: 'Are you sure you want to delete this attachment? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    });
    if (!result.isConfirmed) return;
    try {
        console.log('Making delete request to:', route('backorders.deleteAttachment', backOrderInfo.value.id));
        const { data } = await axios.delete(route('backorders.deleteAttachment', backOrderInfo.value.id), {
            data: { file_path: filePath }
        });
        console.log('Delete response:', data);
        if (backOrderInfo.value.attach_documents) {
            backOrderInfo.value.attach_documents = data.files;
        }
        toast.success(data.message);
    } catch (error) {
        console.error('Delete error:', error);
        toast.error(error.response?.data?.message || 'Failed to delete attachment');
    }
}

</script>

<style>
.loader {
  border: 2px solid #f3f3f3;
  border-top: 2px solid #3498db;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
  display: inline-block;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style> 