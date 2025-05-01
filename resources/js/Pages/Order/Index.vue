<template>

    <Head title="Orders" />
    <AuthenticatedLayout title="Orders" description="Manage orders" img="/assets/image/order.png">
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-full mx-auto">
                <!-- Main Content -->
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                        Select Order
                    </h3>

                    <select v-model="id"
                        class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Select an Order</option>
                        <option v-for="order in orders" :key="order.id" :value="order.id">
                            Order #{{ order.order_number }} - {{ order.order_type }}
                        </option>
                    </select>
                    <button @click="showOrderModal = true"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        Create New Order
                    </button>
                    <button
                        v-if="props.currentOrder?.status == 'pending' && props.currentOrder?.order_type != 'quarterly'"
                        @click="showAddItemModal = true"
                        class="px-4 py-2 bg-indigo-500 text-white rounded-md hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                        Add Item
                    </button>
                </div>

                <div class="flex justify-between items-center bg-white">
                    <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                        <h2 class="text-lg font-semibold mb-2">Order #{{ props.currentOrder?.id }}</h2>
                        <!-- Current order details -->
                        <div class="px-4 py-3 border-b border-gray-200">
                            <div class="flex justify-between items-center">
                                <h2 class="text-lg font-medium text-gray-900">
                                    Order #{{ props.currentOrder?.order_number }}
                                </h2>

                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-500">
                                        Expected: {{ new
                                            Date(props.currentOrder?.expected_date).toLocaleDateString() }}
                                    </span>
                                    <span :class="{
                                        'px-2 py-1 text-sm font-medium rounded-full': true,
                                        'bg-yellow-100 text-yellow-800': props.currentOrder?.status === 'pending',
                                        'bg-green-100 text-green-800': props.currentOrder?.status === 'completed',
                                        'bg-blue-100 text-blue-800': props.currentOrder?.status === 'processing'
                                    }">
                                        {{ props.currentOrder?.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class=" p-4">
                        <div class="flex justfiy-between gap-2 fade-in">
                            <!-- Pending Orders -->
                            <div class="relative h-[100px] flex items-center">
                                <div class="relative w-[100px]">
                                    <Doughnut :data="{
                                        labels: ['Pending', 'Other'],
                                        datasets: [{
                                            data: [props.stats?.pending || 0, (getTotalOrders || 1) - (props.stats?.pending || 0)],
                                            backgroundColor: ['#eab308', '#fef3c7'],
                                            borderWidth: 0
                                        }]
                                    }" :options="{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '80%',
                                        plugins: {
                                            legend: { display: false }
                                        }
                                    }" />
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-sm font-bold text-gray-900">{{
                                            getPercentage(props.stats?.pending) }}%</span>
                                    </div>
                                </div>
                                <div class="flex items-start flex-col">
                                    <span class="ml-3 text-xl text-gray-600">{{ props.stats?.pending || 0
                                    }}</span>
                                    <span class="ml-3 text-xs text-gray-600">Pending</span>
                                </div>
                            </div>
                            <!-- Approved Orders -->
                            <div class="relative h-[100px] flex items-center">
                                <div class="relative w-[100px]">
                                    <Doughnut :data="{
                                        labels: ['Approved', 'Other'],
                                        datasets: [{
                                            data: [props.stats?.approved || 0, (getTotalOrders || 1) - (props.stats?.approved || 0)],
                                            backgroundColor: ['#16a34a', '#dcfce7'],
                                            borderWidth: 0
                                        }]
                                    }" :options="{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '80%',
                                        plugins: {
                                            legend: { display: false }
                                        }
                                    }" />
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-sm font-bold text-gray-900">{{
                                            getPercentage(props.stats?.approved) }}%</span>
                                    </div>
                                </div>
                                <div class="flex items-start flex-col">
                                    <span class="ml-3 text-xl text-gray-600">{{ props.stats?.approved || 0
                                    }}</span>
                                    <span class="ml-3 text-xs text-gray-600">Approved</span>
                                </div>
                            </div>

                            <!-- Rejected Orders -->
                            <div class="relative h-[100px] flex items-center">
                                <div class="relative w-[100px]">
                                    <Doughnut :data="{
                                        labels: ['Rejected', 'Other'],
                                        datasets: [{
                                            data: [props.stats?.rejected || 0, (getTotalOrders || 1) - (props.stats?.rejected || 0)],
                                            backgroundColor: ['#dc2626', '#fee2e2'],
                                            borderWidth: 0
                                        }]
                                    }" :options="{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '80%',
                                        plugins: {
                                            legend: { display: false }
                                        }
                                    }" />
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-sm font-bold text-gray-900">{{
                                            getPercentage(props.stats?.rejected) }}%</span>
                                    </div>
                                </div>
                                <div class="flex items-start flex-col">
                                    <span class="ml-3 text-xl text-gray-600">{{ props.stats?.rejected || 0
                                    }}</span>
                                    <span class="ml-3 text-xs text-gray-600">Rejected</span>
                                </div>
                            </div>

                            <!-- In Processing Orders -->
                            <div class="relative h-[100px] flex items-center">
                                <div class="relative w-[100px]">
                                    <Doughnut :data="{
                                        labels: ['In Processing', 'Other'],
                                        datasets: [{
                                            data: [props.stats?.['in processing'] || 0, (getTotalOrders || 1) - (props.stats?.['in processing'] || 0)],
                                            backgroundColor: ['#2563eb', '#dbeafe'],
                                            borderWidth: 0
                                        }]
                                    }" :options="{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '80%',
                                        plugins: {
                                            legend: { display: false }
                                        }
                                    }" />
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-sm font-bold text-gray-900">{{
                                            getPercentage(props.stats?.['in processing']) }}%</span>
                                    </div>
                                </div>
                                <div class="flex items-start flex-col">
                                    <span class="ml-3 text-xl text-gray-600">{{ props.stats?.['in processing']
                                        || 0
                                    }}</span>
                                    <span class="ml-3 text-xs text-gray-600">In Process</span>
                                </div>
                            </div>

                            <!-- Dispatched Orders -->
                            <div class="relative h-[100px] flex items-center">
                                <div class="relative w-[100px]">
                                    <Doughnut :data="{
                                        labels: ['Dispatched', 'Other'],
                                        datasets: [{
                                            data: [props.stats?.dispatched || 0, (getTotalOrders || 1) - (props.stats?.dispatched || 0)],
                                            backgroundColor: ['#9333ea', '#f3e8ff'],
                                            borderWidth: 0
                                        }]
                                    }" :options="{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '80%',
                                        plugins: {
                                            legend: { display: false }
                                        }
                                    }" />
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-sm font-bold text-gray-900">{{
                                            getPercentage(props.stats?.dispatched) }}%</span>
                                    </div>
                                </div>
                                <div class="flex items-start flex-col">
                                    <span class="ml-3 text-xl text-gray-600">{{ props.stats?.dispatched || 0
                                    }}</span>
                                    <span class="ml-3 text-xs text-gray-600">Dispatched</span>
                                </div>
                            </div>

                            <!-- Delivered Orders -->
                            <div class="relative h-[100px] flex items-center">
                                <div class="relative w-[100px]">
                                    <Doughnut :data="{
                                        labels: ['Delivered', 'Other'],
                                        datasets: [{
                                            data: [props.stats?.delivered || 0, (getTotalOrders || 1) - (props.stats?.delivered || 0)],
                                            backgroundColor: ['#4b5563', '#f3f4f6'],
                                            borderWidth: 0
                                        }]
                                    }" :options="{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        cutout: '80%',
                                        plugins: {
                                            legend: { display: false }
                                        }
                                    }" />
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-sm font-bold text-gray-900">{{
                                            getPercentage(props.stats?.delivered) }}%</span>
                                    </div>
                                </div>
                                <div class="flex items-start flex-col">
                                    <span class="ml-3 text-xl text-gray-600">{{ props.stats?.delivered || 0
                                    }}</span>
                                    <span class="ml-3 text-xs text-gray-600">Delivered</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main content area -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <input type="search" v-model="search" placeholder="Search products [name, barcode]"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4 overflow-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    SN</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    QOO</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Lost Quantity</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Damaged Quantity</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="isLoading">
                                <td colspan="4" class="px-6 py-4 text-center">
                                    <span>Loading...</span>
                                </td>
                            </tr>
                            <tr v-else v-for="(item, i) in filteredItems" :key="item.id"
                                class="hover:bg-gray-50" :class="{
                                    'bg-green-100': item.status === 'delivered',
                                    'bg-yellow-100': item.status === 'pending',
                                    'bg-red-100': item.status === 'processing',
                                    'bg-grey-100': item.status === 'approved',
                                }">
                                <td class="px-6 py-4 whitespace-nowrap  w-[20px]">{{ i + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap  w-[100px]">
                                    <div class="flex flex-col">
                                        <span class="text-dark-500">{{ item.product.name }}</span>
                                        <span class="text-sm text-grey-500">Barcode: {{ item.product.barcode
                                        }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ item.quantity }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ item.quantity_on_order }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ item.lost_quantity }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ item.damaged_quantity }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="{
                                        'px-2 py-1 text-sm font-medium rounded-full': true,
                                        'bg-yellow-100 text-yellow-800': item.status === 'pending',
                                        'bg-green-100 text-green-800': item.status === 'received',
                                        'bg-blue-100 text-blue-800': item.status === 'processing',
                                        'bg-blue-100 text-green': item.status === 'delivered',
                                    }">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <button
                                            v-if="item.status == 'pending' || item.status == 'delivery_pending'"
                                            @click="editItem(item)"
                                            class="text-blue-600 hover:text-blue-900 mr-2" title="Edit Item">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button v-if="item.status === 'pending'" @click="removeItem(item)"
                                            class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button
                                            v-if="item.status === 'dispatched' || item.status === 'delivery_pending'"
                                            @click="openReceiveModal(item)"
                                            class="text-green-600 hover:text-green-900" title="Receive Item">
                                            Received
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order Creation Modal -->
            <div v-show="showOrderModal" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div
                            class="w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all z-50">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">
                                Create New Order
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Order Type</label>
                                    <select v-model="order_type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select Order Type</option>
                                        <option value="Replenishment">Replenishment</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Expected Date</label>
                                    <input type="date" v-model="expected_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button type="button"
                                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    @click="showOrderModal = false">
                                    Cancel
                                </button>
                                <button type="button"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    :disabled="order_type == '' || isCreated" @click="confirmCreateOrder">
                                    {{ isCreated ? "Processing..." : "Create Order" }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Item Modal -->
            <div v-show="showAddItemModal" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div
                            class="w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all z-50">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">
                                Add Item to Order
                            </h3>
                            <div class="mt-4">
                                <form @submit.prevent="addItem">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Product</label>
                                            <Multiselect v-model="selectedProduct" :options="searchResults"
                                                :custom-label="option => option ? `${option.name} - Stock: ${option.stock_on_hand}` : ''"
                                                track-by="id" :searchable="true" :loading="isSearching"
                                                :internal-search="false" :clear-on-select="false"
                                                :close-on-select="true" :preserve-search="true" :preselect-first="true"
                                                :options-limit="300" :limit="3" :max-height="600"
                                                :show-no-results="true" :hide-selected="true"
                                                @search-change="onProductSearch" @select="selectProduct"
                                                @remove="clearProduct" placeholder="Search by name or scan barcode"
                                                class="product-select">
                                                <template v-slot:option="{ option }">
                                                    <div v-if="option" class="flex justify-between items-center">
                                                        <div>
                                                            <div class="font-medium">{{ option.name }}</div>
                                                            <div class="text-sm text-gray-500">Barcode: {{
                                                                option.barcode }}</div>
                                                        </div>
                                                        <div class="text-sm">
                                                            Stock: {{ option.stock_on_hand }}
                                                        </div>
                                                    </div>
                                                </template>
                                                <template v-slot:noResult>
                                                    <div class="text-sm text-gray-500 p-2">No products found</div>
                                                </template>
                                                <template v-slot:selection="{ option }">
                                                    <div v-if="option" class="multiselect__single">
                                                        <span class="font-medium">{{ option.name }}</span>
                                                        <span class="text-sm text-gray-500 ml-2">Stock: {{
                                                            option.stock_on_hand }}</span>
                                                    </div>
                                                </template>
                                            </Multiselect>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="w-full">
                                                <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                                <input type="number" v-model="form.quantity" readonly
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    min="1" />
                                            </div>
                                            <div class="w-full">
                                                <label class="block text-sm font-medium text-gray-700">Quantity on
                                                    order</label>
                                                <input type="number" v-model="form.quantity_on_order"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    min="0" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex justify-end space-x-3">
                                        <button type="button"
                                            class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                            @click="showAddItemModal = false">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                            :disabled="!form.product_id || !form.quantity || isAdded">
                                            {{ isAdded ? "Adding..." : "Add Item" }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receive Item Modal -->
            <div v-if="showReceiveModal" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl">
                        <h2 class="text-lg font-semibold mb-4">Receive Item</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Product</label>
                                <p class="mt-1 text-sm text-gray-900">{{ selectedItem?.product.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ordered Quantity</label>
                                <p class="mt-1 text-sm text-gray-900">{{ selectedItem?.quantity }}</p>
                            </div>
                            <div>
                                <label for="lost_quantity" class="block text-sm font-medium text-gray-700">Lost
                                    Quantity</label>
                                <input type="number" id="lost_quantity" v-model="receiveForm.lost_quantity" min="0"
                                    :max="maxLostQuantity"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    @input="validateQuantities" />
                                <p v-if="receiveForm.lost_quantity > maxLostQuantity" class="mt-1 text-sm text-red-600">
                                    Cannot exceed remaining quantity ({{ maxLostQuantity }})
                                </p>
                            </div>
                            <div>
                                <label for="damaged_quantity" class="block text-sm font-medium text-gray-700">Damaged
                                    Quantity</label>
                                <input type="number" id="damaged_quantity" v-model="receiveForm.damaged_quantity"
                                    min="0" :max="maxDamagedQuantity"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    @input="validateQuantities" />
                                <p v-if="receiveForm.damaged_quantity > maxDamagedQuantity"
                                    class="mt-1 text-sm text-red-600">
                                    Cannot exceed remaining quantity ({{ maxDamagedQuantity }})
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Received Quantity</label>
                                <p class="mt-1 text-sm text-gray-900">{{ receivedQuantity }}</p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button @click="showReceiveModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </button>
                            <button @click="confirmReceiveItem" :disabled="!isValidReceiveForm || isConfirmed"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ isConfirmed ? 'Processing...' : 'Confirm' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Item Modal -->
            <div v-if="showEditModal" class="relative z-50">
                <div class="fixed inset-0 bg-black/30" aria-hidden="true" />
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl">
                        <h2 class="text-lg font-semibold mb-4">Edit Item</h2>
                        <form @submit.prevent="updateItem" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Product</label>
                                <p class="mt-1 text-sm text-gray-900">{{ form.product_name }}</p>
                            </div>
                            <div>
                                <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                                <input type="number" v-model="form.quantity" min="1"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>
                            <div>
                                <label for="quantity_on_order" class="block text-sm font-medium text-gray-700">Quantity
                                    on
                                    order</label>
                                <input type="number" v-model="form.quantity_on_order" min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>
                            <div class="mt-6 flex justify-end space-x-3">
                                <button type="button" @click="showEditModal = false" :disabled="isSubmitting"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="isSubmitting"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ isSubmitting ? "Please wait...." : "Update Item" }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@import 'vue-multiselect/dist/vue-multiselect.css';

.multiselect {
    min-height: 45px;
}

.multiselect__tags {
    min-height: 45px;
    padding: 8px 40px 0 8px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.multiselect__input {
    font-size: 14px;
}

.multiselect__single {
    font-size: 14px;
    padding-left: 5px;
}

.multiselect__tag {
    background: #4f46e5;
    color: white;
    font-size: 14px;
}

.multiselect__option--highlight {
    background: #4f46e5;
    color: white;
}

.multiselect__option--selected.multiselect__option--highlight {
    background: #ef4444;
    color: white;
}

.multiselect__placeholder {
    padding-left: 5px;
    font-size: 14px;
}

.multiselect__content-wrapper {
    border-radius: 0 0 6px 6px;
    border: 1px solid #e5e7eb;
    border-top: none;
}

.multiselect__option {
    padding: 12px;
    min-height: 40px;
    line-height: 16px;
    font-size: 14px;
}
</style>

<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import axios from "axios";
import { Head } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { debounce } from 'lodash';
import { Multiselect } from 'vue-multiselect';
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Doughnut } from 'vue-chartjs';
import { useToast } from "vue-toastification";
import { usePage } from '@inertiajs/vue3'

const page = usePage();

const toast = useToast();
ChartJS.register(ArcElement, Tooltip, Legend);

onMounted(() => {
    if (props.currentOrder && page.props.facility?.id != props.currentOrder?.facility_id) {
        router.get(route('orders.index'), {}, {
            preserveScroll: false,
            preserveState: false,
        })
    }
})

const STORAGE_KEY = "current_order";
const isAdded = ref(false);
const searchResults = ref([]);
const selectedProduct = ref(null);
const search = ref("");
const debouncedSearch = ref("");
const isSearching = ref(false);

// Create debounced search function
const debouncedProductSearch = debounce(async (query) => {
    if (!query) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    try {
        const response = await axios.post(route("orders.search"), {
            barcode: query,
            name: query
        });

        if (response.data.product) {
            // Single product found (likely from barcode)
            searchResults.value = [response.data.product];
            // Auto-select if it's a barcode match
            if (query.length > 8) {
                selectProduct(response.data.product);
            }
        } else if (response.data.products?.length) {
            searchResults.value = response.data.products;
        } else {
            searchResults.value = [];
        }
    } catch (error) {
        console.error('Search error:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
}, 300);

// Watch for search changes
const onProductSearch = (query) => {
    debouncedProductSearch(query);
};

const clearProduct = () => {
    selectedProduct.value = null;
    form.value.product_id = null;
    form.value.product_name = '';
    form.value.quantity = 0;
    form.value.stock_on_hand = 0;
};

const selectProduct = (product) => {
    if (!product) return;
    selectedProduct.value = product;
    form.value.product_id = product.id;
    form.value.product_name = product.name;
    form.value.quantity = product.suggested_quantity || 1;
    form.value.stock_on_hand = product.stock_on_hand;
};

const props = defineProps({
    stats: {
        type: Object
    },
    orders: {
        type: Array,
        required: true,
    },
    currentOrder: {
        type: Object,
        default: null,
    },
    products: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});


const filteredItems = computed(() => {
    if (!search.value) return props.currentOrder?.items || [];
    return props.currentOrder?.items.filter(i => i.product?.name.toLowerCase().includes(search.value.toLowerCase()) || i.product?.barcode.toLowerCase().includes(search.value.toLowerCase())) || [];
});

const form = ref({
    order_id: props.currentOrder?.id || null,
    product_id: null,
    product_name: '',
    quantity: 0,
    quantity_on_order: 0,
    stock_on_hand: 0,
});

const id = ref(props.filters.id);

// Update the form when currentOrder changes
watch(
    [() => id.value],
    () => {
        reloadOrder();
    }
);

onMounted(() => {
    // Initialize Echo listener for OrderEvent
    window.Echo.channel("orders").listen(".order-received", (e) => {
        // reload();
        console.log(e);
        reloadOrder();
    });
});

const getTotalOrders = computed(() => {
    if (!props.stats) return 0;
    return Object.values(props.stats).reduce((a, b) => a + b, 0);
});

const getPercentage = (value) => {
    if (!value || !getTotalOrders.value) return 0;
    return Math.round((value / getTotalOrders.value) * 100);
};

const orderSubmitted = ref(false);

const order_type = ref(props.currentOrder?.order_type || "Replenishment");
const expected_date = ref(props.currentOrder?.expected_date);

const isCreated = ref(false);
const confirmCreateOrder = () => {
    isCreated.value = true;
    if (!order_type.value) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Please select an order type",
        });
        isCreated.value = false;
        return;
    }

    Swal.fire({
        title: "Are you sure?",
        text: "This will create a new order",
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Yes, create order",
    }).then(async (result) => {
        if (result.isConfirmed) {
            await axios
                .post(route("orders.create"), {
                    order_type: order_type.value,
                    expected_date: expected_date.value,
                })
                .then((response) => {
                    isCreated.value = false;
                    const newOrder = response.data.order;

                    // Update all order-related states
                    form.value.order_id = newOrder.id;
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(newOrder));

                    // Update form with new reactive object
                    form.value = Object.assign({}, form.value, {
                        id: null,
                        product_id: null,
                        product_name: null,
                        quantity: 0,
                        quantity_on_order: 0,
                        stock_on_hand: 0,
                        order_id: newOrder.id,
                    });

                    // Reload the page with new order
                    reloadOrder();

                    Swal.fire({
                        icon: "success",
                        title: response.data.message,
                        timer: 1500,
                        showConfirmButton: false,
                    });
                })
                .catch((error) => {
                    isCreated.value = false;
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: error.response?.data || "Failed to create order",
                    });
                });
        } else {
            isCreated.value = false;
        }
    });
};

const isLoading = ref(false);
const isConfirmed = ref(false);

function reloadOrder() {
    const query = {};
    if (id.value) {
        query.id = id.value;
    }
    router.get(
        route("orders.index"),
        query,
        {
            preserveState: true,
            preserveScroll: true,
            only: ["currentOrder", "orders", "products", 'stats']
        }
    );
}

async function removeItem(order) {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, remove",
    }).then((result) => {
        if (result.isConfirmed) {
            axios
                .get(route("orders.remove", { id: order.id }))
                .then((response) => {
                    reloadOrder();
                    Swal.fire({
                        icon: "success",
                        title: response.data,
                        showConfirmButton: false,
                        timer: 1500,
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        icon: "error",
                        title: "Error removing item",
                        text: error.response.data,
                    });
                });
        }
    });
}

const showOrderModal = ref(false);
const showSelectModal = ref(false);
const showAddItemModal = ref(false);

const showReceiveModal = ref(false);
const selectedItem = ref(null);
const receiveForm = ref({
    lost_quantity: 0,
    damaged_quantity: 0
});

const maxLostQuantity = computed(() => {
    if (!selectedItem.value) return 0;
    const total = selectedItem.value.quantity;
    const damaged = Number(receiveForm.value.damaged_quantity) || 0;
    return total - damaged;
});

const maxDamagedQuantity = computed(() => {
    if (!selectedItem.value) return 0;
    const total = selectedItem.value.quantity;
    const lost = Number(receiveForm.value.lost_quantity) || 0;
    return total - lost;
});

const receivedQuantity = computed(() => {
    if (!selectedItem.value) return 0;
    const total = selectedItem.value.quantity;
    const lost = Number(receiveForm.value.lost_quantity) || 0;
    const damaged = Number(receiveForm.value.damaged_quantity) || 0;
    return Math.max(0, total - lost - damaged);
});

const isValidReceiveForm = computed(() => {
    if (!selectedItem.value) return false;
    const total = selectedItem.value.quantity;
    const lost = Number(receiveForm.value.lost_quantity) || 0;
    const damaged = Number(receiveForm.value.damaged_quantity) || 0;
    return lost >= 0 && damaged >= 0 && (lost + damaged) <= total;
});

const validateQuantities = () => {
    const total = selectedItem.value?.quantity || 0;
    let lost = Number(receiveForm.value.lost_quantity);
    let damaged = Number(receiveForm.value.damaged_quantity);

    // Ensure values are not negative
    lost = Math.max(0, lost);
    damaged = Math.max(0, damaged);

    // If sum exceeds total, adjust the last changed value
    if (lost + damaged > total) {
        if (lost > maxLostQuantity) {
            receiveForm.value.lost_quantity = maxLostQuantity;
        }
        if (damaged > maxDamagedQuantity) {
            receiveForm.value.damaged_quantity = maxDamagedQuantity;
        }
    }

    // Update the form with validated values
    receiveForm.value.lost_quantity = lost;
    receiveForm.value.damaged_quantity = damaged;
};

const openReceiveModal = (item) => {
    selectedItem.value = item;
    receiveForm.value = {
        lost_quantity: 0,
        damaged_quantity: 0
    };
    showReceiveModal.value = true;
};

const confirmReceiveItem = () => {
    if (!isValidReceiveForm.value) return;


    Swal.fire({
        title: 'Confirm Receipt',
        html: `
            <p>Are you sure you want to receive:</p>
            <ul class="mt-2 text-left">
                <li>Received: ${receivedQuantity.value}</li>
                <li>Lost: ${receiveForm.value.lost_quantity}</li>
                <li>Damaged: ${receiveForm.value.damaged_quantity}</li>
            </ul>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, receive it',
        cancelButtonText: 'No, cancel'
    }).then(async (result) => {
        if (result.isConfirmed) {
            isConfirmed.value = true;
            await axios.post(route('orders.receivedItems'), {
                id: selectedItem.value.id,
                lost_quantity: receiveForm.value.lost_quantity,
                damaged_quantity: receiveForm.value.damaged_quantity,
                received_quantity: receivedQuantity.value,
                status: 'delivered'
            })
                .then(() => {
                    isConfirmed.value = false;
                    showReceiveModal.value = false;
                    Swal.fire(
                        'Received!',
                        'The item has been received successfully.',
                        'success'
                    );
                    reloadOrder();
                })
                .catch((error) => {
                    isConfirmed.value = false;
                    Swal.fire(
                        'Error!',
                        error.response?.data || 'Failed to receive the item.',
                        'error'
                    );
                });
        }
    });
};

const receiveOrderItem = (item) => {
    Swal.fire({
        title: 'Receive Item',
        text: `Are you sure you want to receive ${item.quantity} ${item.product.name}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, receive it',
        cancelButtonText: 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post(route('orders.receive-item', { order: props.currentOrder.id, item: item.id }))
                .then(() => {
                    Swal.fire(
                        'Received!',
                        'The item has been received successfully.',
                        'success'
                    );
                    reloadOrder();
                })
                .catch((error) => {
                    Swal.fire(
                        'Error!',
                        error.response?.data?.message || 'Failed to receive the item.',
                        'error'
                    );
                });
        }
    });
};

const showEditModal = ref(false);
const editingItem = ref(null);

const editItem = (item) => {
    editingItem.value = item;
    form.value = {
        id: item.id,
        quantity: item.quantity,
        quantity_on_order: item.quantity_on_order,
    };
    showEditModal.value = true;
};

const isSubmitting = ref(false)

const updateItem = async () => {
    isSubmitting.value = true;
    await axios.post(route('orders.update-item'), form.value)
        .then((response) => {
            isSubmitting.value = false;
            showEditModal.value = false;
            editingItem.value = null;
            reloadOrder();
            Swal.fire('Updated!', 'Item has been updated successfully.', 'success');
        })
        .catch((error) => {
            Swal.fire('Error!', error.response?.data || 'Failed to update item.', 'error');
        });
};

async function addItem() {
    isSubmitting.value = true;
    await axios.post(route('orders.store'), form.value)
        .then((response) => {
            isSubmitting.value = false;
            toast.success(response.data);
            reloadOrder();
            showAddItemModal.value = false;
        })
        .catch((error) => {
            isSubmitting.value = false;
            Swal.fire('Error!', error.response.data, "error");
        });
}
</script>