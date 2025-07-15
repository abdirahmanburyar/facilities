<template>
 <AuthenticatedLayout
        title="Tracks Your Orders"
        description="Keeping Essentials Ready, Every Time"
        img="/assets/images/orders.png"
    >
        <!-- Order Header -->
        <div v-if="error">
            {{ error }}
        </div>
        <div v-else>
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <Link :href="route('orders.index')">Back to orders</Link>
                        <h1 class="text-xs font-semibold text-gray-900">
                            Order ID. {{ props.order.order_number }}
                        </h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span :class="[
                            statusClasses[props.order.status] ||
                            statusClasses.default,
                        ]" class="flex items-center text-xs font-bold px-4 py-2">
                            <!-- Status Icon -->
                            <span class="mr-3">
                                <!-- Pending Icon -->
                                <img v-if="props.order.status === 'pending'" src="/assets/images/pending.png"
                                    class="w-4 h-4" alt="Pending" />

                                <!-- reviewed Icon -->
                                <img v-else-if="props.order.status === 'reviewed'" src="/assets/images/reviewed.png"
                                    class="w-4 h-4" alt="Reviewed" />

                                <!-- Approved Icon -->
                                <img v-else-if="props.order.status === 'approved'" src="/assets/images/approved.png"
                                    class="w-4 h-4" alt="Approved" />

                                <!-- In Process Icon -->
                                <img v-else-if="props.order.status === 'in_process'" src="/assets/images/inprocess.png"
                                    class="w-4 h-4" alt="In Process" />

                                <!-- Dispatched Icon -->
                                <img v-else-if="props.order.status === 'dispatched'" src="/assets/images/dispatch.png"
                                    class="w-4 h-4" alt="Dispatched" />

                                <!-- Received Icon -->
                                <img v-else-if="props.order.status === 'received'" src="/assets/images/received.png"
                                    class="w-4 h-4" alt="Received" />

                                <!-- Rejected Icon -->
                                <svg v-else-if="props.order.status === 'rejected'" class="w-4 h-4 text-red-700"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            {{ props.order.status.toUpperCase() }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                <!-- Facility Information -->
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <h2 class="text-lg font-medium text-gray-900">
                        Facility Details
                    </h2>
                    <div class="flex items-center">
                        <BuildingOfficeIcon class="h-4 w-4 text-gray-400 mr-2" />
                        <span class="text-sm text-gray-600">{{
                            props.order.facility.name
                        }}</span>
                    </div>
                    <div class="flex items-center">
                        <EnvelopeIcon class="h-4 w-4 text-gray-400 mr-2" />
                        <span class="text-sm text-gray-600">{{
                            props.order.facility.email
                        }}</span>
                    </div>
                    <div class="flex items-center">
                        <PhoneIcon class="h-4 w-4 text-gray-400 mr-2" />
                        <span class="text-sm text-gray-600">{{
                            props.order.facility.phone
                        }}</span>
                    </div>
                    <div class="flex items-center">
                        <MapPinIcon class="h-4 w-4 text-gray-400 mr-2" />
                        <span class="text-sm text-gray-600">{{ props.order.facility.address }},
                            {{ props.order.facility.city }}</span>
                    </div>
                </div>
                <div>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        <h2 class="text-xs font-medium text-gray-900">
                            Order Details
                        </h2>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500">
                                    Order Type
                                </p>
                                <p class="text-xs text-gray-900">
                                    {{ props.order.order_type }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">
                                    Order Date
                                </p>
                                <p class="text-xs text-gray-900">
                                    {{ formatDate(props.order.order_date) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">
                                    Expected Date
                                </p>
                                <p class="text-xs text-gray-900">
                                    {{ formatDate(props.order.expected_date) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Stage Timeline -->
             <div v-if="props.order.status == 'rejected'">
                <div class="flex flex-col items-center">
                    <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10 bg-white border-red-500">
                        <img src="/assets/images/rejected.png" class="w-7 h-7" alt="Rejected" />
                    </div>
                    <h1 class="mt-3 text-2xl text-red-600 font-bold ">Rejected</h1>
                </div>
             </div>
            <div v-else class="col-span-2 mb-6">
                <div class="relative">
                    <!-- Timeline Track Background -->
                    <div class="absolute top-7 left-0 right-0 h-2 bg-gray-200 z-0"></div>

                    <!-- Timeline Progress -->
                    <div class="absolute top-7 left-0 h-2 bg-green-500 z-0 transition-all duration-500 ease-in-out"
                        :style="{
                            width: `${(statusOrder.indexOf(props.order.status) /
                                (statusOrder.length - 1)) *
                                100
                                }%`,
                        }"></div>

                    <!-- Timeline Steps -->
                    <div class="relative flex justify-between">
                        <!-- Pending -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10" :class="[
                                statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('pending')
                                    ? 'bg-white border-orange-500'
                                    : 'bg-white border-gray-200',
                            ]">
                                <img src="/assets/images/pending.png" class="w-7 h-7" alt="Pending" :class="statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('pending')
                                    ? ''
                                    : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(props.order.status) >=
                                statusOrder.indexOf('pending')
                                ? 'text-green-600'
                                : 'text-gray-500'
                                ">Pending</span>
                        </div>

                        <!-- Reviewed -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10" :class="[
                                statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('reviewed')
                                    ? 'bg-white border-orange-500'
                                    : 'bg-white border-gray-200',
                            ]">
                                <img src="/assets/images/review.png" class="w-7 h-7" alt="Reviewed" :class="statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('reviewed')
                                    ? ''
                                    : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(props.order.status) >=
                                statusOrder.indexOf('reviewed')
                                ? 'text-green-600'
                                : 'text-gray-500'
                                ">Reviewed</span>
                        </div>

                        <!-- Approved -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10" :class="[
                                statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('approved')
                                    ? 'bg-white border-orange-500'
                                    : 'bg-white border-gray-200',
                            ]">
                                <img src="/assets/images/approved.png" class="w-7 h-7" alt="Approved" :class="statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('approved')
                                    ? ''
                                    : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(props.order.status) >=
                                statusOrder.indexOf('approved')
                                ? 'text-green-600'
                                : 'text-gray-500'
                                ">Approved</span>
                        </div>

                        <!-- In Process -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10" :class="[
                                statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('in_process')
                                    ? 'bg-white border-orange-500'
                                    : 'bg-white border-gray-200',
                            ]">
                                <img src="/assets/images/inprocess.png" class="w-7 h-7" alt="In Process" :class="statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('in_process')
                                    ? ''
                                    : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(props.order.status) >=
                                statusOrder.indexOf('in_process')
                                ? 'text-green-600'
                                : 'text-gray-500'
                                ">In Process</span>
                        </div>

                        <!-- Dispatch -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10" :class="[
                                statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('dispatched')
                                    ? 'bg-white border-orange-500'
                                    : 'bg-white border-gray-200',
                            ]">
                                <img src="/assets/images/dispatch.png" class="w-7 h-7" alt="Dispatch" :class="statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('dispatched')
                                    ? ''
                                    : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(props.order.status) >=
                                statusOrder.indexOf('dispatched')
                                ? 'text-green-600'
                                : 'text-gray-500'
                                ">Dispatched</span>
                        </div>

                        <!-- Delivered -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10" :class="[
                                statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('delivered')
                                    ? 'bg-white border-orange-500'
                                    : 'bg-white border-gray-200',
                            ]">
                                <img src="/assets/images/delivery.png" class="w-7 h-7" alt="Dispatch" :class="statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('delivered')
                                    ? ''
                                    : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(props.order.status) >=
                                statusOrder.indexOf('delivered')
                                ? 'text-green-600'
                                : 'text-gray-500'
                                ">Delivered</span>
                        </div>

                        <!-- Received -->
                        <div class="flex flex-col items-center">
                            <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10" :class="[
                                statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('received')
                                    ? 'bg-white border-green-500'
                                    : 'bg-white border-gray-200',
                            ]">
                                <img src="/assets/images/received.png" class="w-7 h-7" alt="Received" :class="statusOrder.indexOf(props.order.status) >=
                                    statusOrder.indexOf('received')
                                    ? ''
                                    : 'opacity-40'
                                    " />
                            </div>
                            <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(props.order.status) >=
                                statusOrder.indexOf('received')
                                ? 'text-green-600'
                                : 'text-gray-500'
                                ">Received</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items Table -->
            <h2 class="text-xs text-gray-900 mb-4">Order Items</h2>
            
            <!-- Receive Instructions -->
            <div v-if="props.order.status === 'delivered'" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Receive Items</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Enter the actual quantity received for each item. The received quantity cannot exceed the quantity to release.</p>
                            <p class="mt-1">Once all items are fully received, you can mark the order as received.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- All Items Received Message -->
            <div v-if="props.order.status === 'delivered' && allItemsFullyReceived" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">All Items Received</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>All items have been fully received. You can now mark the order as received.</p>
                        </div>
                    </div>
                </div>
            </div>
            <table class="min-w-full border border-black border-collapse">
                <thead class="bg-blue-500">
                    <tr class="bg-gray-50">
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black" rowspan="2">
                            Item
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black" rowspan="2">
                            Category
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black" rowspan="2">
                            UoM
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black" rowspan="2">
                            Facility Inventory Data
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black" rowspan="2">
                            No. of Days
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black" rowspan="2">
                            Ordered Quantity
                        </th>
                        <th class="w-[150px] px-2 py-2 text-left text-xs text-black capitalize border border-black"
                            rowspan="2">
                            Quantity to release
                        </th>
                        <th class="px-2 py-2 text-center text-xs text-black capitalize border border-black" colspan="4">
                            Item Detail
                        </th>
                    </tr>
                    <tr class="bg-gray-50">
                        <th class="px-2 py-1 text-xs border border-black text-left">
                            QTY
                        </th>
                        <th class="px-2 py-1 text-xs border border-black text-left">
                            Batch Number
                        </th>
                        <th class="px-2 py-1 text-xs border border-black text-left">
                            Expiry Date
                        </th>
                        <th class="px-2 py-1 text-xs border border-black text-left w-32">
                            Location
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Debug Info -->
                    <tr v-if="form.length === 0">
                        <td colspan="11" class="px-3 py-3 text-center text-gray-500">
                            No items found. Form length: {{ form.length }}, Order items: {{ props.order.items?.length || 0 }}
                        </td>
                    </tr>
                    
                    <template v-for="(item, index) in form" :key="item.id">
                        <!-- Show allocations if they exist, otherwise show one row with main item data -->
                        <tr v-for="(inv, invIndex) in (item.inventory_allocations?.length > 0 ? item.inventory_allocations : [{}])"
                            :key="`${item.id}-${inv.id || invIndex}`"
                            class="hover:bg-gray-50 transition-colors duration-150">
                            <!-- Show item details only on first row for this item -->
                            <td v-if="invIndex === 0" :rowspan="Math.max(item.inventory_allocations?.length || 1, 1)"
                                class="px-3 py-3 text-xs text-gray-900 border border-black align-top">
                                {{ item.product?.name }}
                            </td>

                            <td v-if="invIndex === 0" :rowspan="Math.max(item.inventory_allocations?.length || 1, 1)"
                                class="px-3 py-3 text-xs text-gray-900 border border-black align-top">
                                {{ item.product?.category?.name }}
                            </td>

                            <td v-if="invIndex === 0" :rowspan="Math.max(item.inventory_allocations?.length || 1, 1)"
                                class="px-3 py-3 text-xs text-gray-900 border border-black align-top">
                                {{ item.inventory_allocations?.[0]?.uom || item.uom || 'N/A' }}
                            </td>

                            <td v-if="invIndex === 0" :rowspan="Math.max(item.inventory_allocations?.length || 1, 1)"
                                class="px-3 py-3 text-xs text-gray-900 border border-black align-top">
                                <div class="flex flex-col space-y-1 text-xs">
                                    <div class="flex">
                                        <span class="font-medium text-xs w-12">SOH:</span>
                                        <span>{{ item.soh }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="font-medium text-xs w-12">AMC:</span>
                                        <span>{{ item.amc || 0 }}</span>
                                    </div>
                                    <div class="flex">
                                        <span class="font-medium text-xs w-12">QOO:</span>
                                        <span>{{ item.quantity_on_order }}</span>
                                    </div>
                                </div>
                            </td>

                            <td v-if="invIndex === 0" :rowspan="Math.max(item.inventory_allocations?.length || 1, 1)"
                                class="px-3 py-3 text-xs text-gray-900 border border-black align-top">
                                {{ item.no_of_days }}
                            </td>

                            <td v-if="invIndex === 0" :rowspan="Math.max(item.inventory_allocations?.length || 1, 1)"
                                class="px-3 py-3 text-xs text-center text-black border border-black align-top">
                                {{ item.quantity }}
                            </td>

                            <td v-if="invIndex === 0" :rowspan="Math.max(item.inventory_allocations?.length || 1, 1)"
                                class="px-3 py-3 text-xs text-gray-900 border border-black align-top">
                                <input type="number" placeholder="0" v-model="item.quantity_to_release" readonly
                                    class="w-full rounded-md border border-gray-300 focus:border-orange-500 focus:ring-orange-500 sm:text-sm mb-1" />
                                <div>
                                    <label>Received Quantity</label>
                                    <input type="number" min="0" :max="item.quantity_to_release" placeholder="0" v-model="item.received_quantity" :disabled="props.order.status !== 'delivered'"
                                        @input="handleReceivedQuantityInput(item, index)"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                                    <span class="text-xs" v-if="isSavingQty[index]">Updating</span>
                                    <span class="text-xs text-red-500" v-if="item.received_quantity > item.quantity_to_release">Cannot exceed quantity to release</span>
                                    <span class="text-xs text-gray-600">Remaining: {{ getRemainingQuantity(item) }}</span>
                                    <span class="text-xs text-green-600" v-if="getRemainingQuantity(item) === 0">✓ Fully received</span>
                                </div>
                                <button
                                    v-if="props.order.status === 'dispatched' || item.received_quantity < item.quantity_to_release"
                                    @click="openBackOrderModal(item)"
                                    class="mt-2 px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-xs w-full">
                                    Back Order
                                </button>
                                <label for="days">No. of Days</label>
                                <input type="number" placeholder="0" v-model="item.days" readonly
                                    class="w-full rounded-md border border-gray-300 focus:border-orange-500 focus:ring-orange-500 sm:text-sm mb-1" />
                                <button @click="showBackOrderModal(item)" v-if="
                                    item.inventory_allocations &&
                                    item.inventory_allocations.some(
                                        (a) => a.back_order?.length > 0
                                    )
                                " class="text-xs text-orange-600 underline hover:text-orange-800 cursor-pointer mt-1">
                                    Show Back Order
                                </button>
                            </td>

                            <!-- Item Details Columns -->
                            <!-- Quantity -->
                            <td class="px-2 py-1 text-xs border border-black text-left">
                                {{ inv.allocated_quantity || '' }}
                            </td>

                            <!-- Batch Number -->
                            <td class="px-2 py-1 text-xs border border-black text-left">
                                {{ inv.batch_number || '' }}
                            </td>

                            <!-- Expiry Date -->
                            <td class="px-2 py-1 text-xs border border-black text-left">
                                {{ inv.expiry_date ? moment(inv.expiry_date).format("DD/MM/YYYY") : '' }}
                            </td>

                            <!-- Location -->
                            <td class="px-2 py-1 text-xs border border-black text-left w-32">
                                <div class="flex flex-col text-xs">
                                    <span v-if="inv.warehouse?.name">WH: {{ inv.warehouse.name }}</span>
                                    <span v-if="inv.location?.location">LC: {{ inv.location.location }}</span>
                                    <span v-if="!inv.warehouse?.name && !inv.location?.location">{{ inv.location || '' }}</span>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <!-- dispatch information -->
            <div v-if="props.order.dispatch?.length > 0" class="mt-8 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        Dispatch Note (Driver Handover Log)
                    </h2>
                </div>

                <div class="bg-white rounded-lg shadow-lg divide-y divide-gray-200">
                    <div v-for="dispatch in props.order.dispatch" :key="dispatch.id" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Driver & Company Info -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Driver Information</h3>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <span class="text-sm text-gray-900">{{ dispatch.driver?.name }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="text-sm text-gray-600">Phone: {{ dispatch.driver_number || 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="text-sm text-gray-600">Plate: {{ dispatch.plate_number || 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dispatch Details -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Dispatch Details</h3>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Cartons:</span>
                                            <span class="text-sm font-semibold text-gray-800">{{ dispatch.received_cartons || 0 }}/{{ dispatch.no_of_cartoons || 0 }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-gray-600">Date:</span>
                                            <span class="text-sm text-gray-800">{{ dispatch.created_at ? new Date(dispatch.created_at).toLocaleDateString() : 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- approval actions -->
            <div class="mt-8 mb-6 px-6 py-6 bg-white rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
                    Order Status Actions
                </h3>
                <div class="flex justify-center items-center mb-6">
                    <!-- Status Action Buttons -->
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <!-- Review button -->
                        <div class="relative">
                            <div class="flex flex-col">
                                <button :class="[
                                    props.order.status === 'pending'
                                        ? 'bg-gray-300 cursor-not-allowed'
                                        : statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf('pending')
                                            ? 'bg-green-500'
                                            : 'bg-gray-300 cursor-not-allowed',
                                ]"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                    <img src="/assets/images/review.png" class="w-5 h-5 mr-2" alt="Review" />
                                    <span class="text-sm font-bold text-white">{{
                                        statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf("pending")
                                            ? "Reviewed"
                                            : "Waiting to be Reviewed"
                                    }}</span>
                                </button>
                                <span v-show="props.order?.reviewed_at" class="text-sm text-gray-600">
                                    On {{ moment(props.order.reviewed_at).format('DD/MM/YYYY HH:mm') }}
                                </span>
                                <span v-show="props.order?.reviewed_by" class="text-sm text-gray-600">
                                    By {{ props.order?.reviewed_by?.name }}
                                </span>
                            </div>
                            <div v-if="props.order.status === 'pending'"
                                class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
                        </div>

                        <div class="relative">
                            <div class="flex flex-col">
                                <button :class="[
                                    props.order.status === 'reviewed'
                                        ? 'bg-gray-300 cursor-not-allowed'
                                        : statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf('reviewed')
                                            ? 'bg-green-500'
                                            : 'bg-gray-300 cursor-not-allowed',
                                ]"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                    <img src="/assets/images/approved.png" class="w-5 h-5 mr-2" alt="Review" />
                                    <span class="text-sm font-bold text-white">{{
                                        statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf("reviewed")
                                            ? "Approved"
                                            : "Waiting to be Approved"
                                    }}</span>
                                </button>
                                <span v-show="props.order?.approved_at" class="text-sm text-gray-600">
                                    On {{ moment(props.order.approved_at).format('DD/MM/YYYY HH:mm') }}
                                </span>
                                <span v-show="props.order?.approved_by" class="text-sm text-gray-600">
                                    By {{ props.order?.approved_by?.name }}
                                </span>
                            </div>
                            <div v-if="props.order.status === 'reviewed'"
                                class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
                        </div>

                        <!-- Process button -->
                        <div class="relative">
                            <div class="flex flex-col">
                                <button :class="[
                                    props.order.status === 'approved'
                                        ? 'bg-gray-300 cursor-not-allowed'
                                        : statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf('approved')
                                            ? 'bg-green-500 cursor-not-allowed'
                                            : 'bg-gray-300 cursor-not-allowed',
                                ]"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                    <img src="/assets/images/inprocess.png" class="w-5 h-5 mr-2" alt="Review" />
                                    <span class="text-sm font-bold text-white">{{
                                        statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf("approved")
                                            ? "Processed"
                                            : "Waiting to be Processed"
                                    }}</span>
                                </button>
                                <span v-show="props.order?.processed_at" class="text-sm text-gray-600">
                                    On {{ moment(props.order.processed_at).format('DD/MM/YYYY HH:mm') }}
                                </span>
                                <span v-show="props.order?.processed_by" class="text-sm text-gray-600">
                                    By {{ props.order?.processed_by?.name }}
                                </span>
                            </div>
                            <div v-if="props.order.status === 'approved'"
                                class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
                        </div>

                        <!-- Dispatch button -->
                        <div class="relative">
                            <div class="flex flex-col">
                                <button :class="[
                                    props.order.status === 'in_process'
                                        ? 'bg-gray-300 cursor-not-allowed'
                                        : statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf('in_process')
                                            ? 'bg-green-500 cursor-not-allowed'
                                            : 'bg-gray-300 cursor-not-allowed',
                                ]"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                    <img src="/assets/images/dispatch.png" class="w-5 h-5 mr-2" alt="Review" />
                                    <span class="text-sm font-bold text-white">{{
                                        statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf("in_process")
                                            ? "Dispatched"
                                            : "Waiting to be Dispatched"
                                    }}</span>
                                </button>
                                <span v-show="props.order?.dispatched_at" class="text-sm text-gray-600">
                                    On {{ moment(props.order.dispatched_at).format('DD/MM/YYYY HH:mm') }}
                                </span>
                                <span v-show="props.order?.dispatched_by" class="text-sm text-gray-600">
                                    By {{ props.order?.dispatched_by?.name }}
                                </span>
                            </div>
                            <div v-if="props.order.status === 'in_process'"
                                class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
                        </div>

                        <!-- Order Delivery Indicators -->
                        <div class="flex flex-col gap-4 sm:flex-row">
                            <!-- Delivery Status -->
                            <div class="relative">
                                <div class="flex flex-col">
                                    <button @click="openDeliveryForm()"
                                        :disabled="isType['is_delivering'] || props.order?.status != 'dispatched'"
                                        :class="[
                                            props.order.status == 'dispatched'
                                                ? 'bg-yellow-300'
                                                : statusOrder.indexOf(props.order.status) >
                                                    statusOrder.indexOf('dispatched')
                                                    ? 'bg-green-500 cursor-not-allowed'
                                                    : 'bg-gray-300 cursor-not-allowed',
                                        ]"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                        <img src="/assets/images/delivery.png" class="w-5 h-5 mr-2" alt="delivered" />
                                        <span class="text-sm font-bold text-white">
                                            {{
                                                statusOrder.indexOf(
                                                    props.order.status
                                                ) > statusOrder.indexOf("delivered")
                                                    ? "Delivered"
                                            : isType['is_delivering'] ? 'Please Wait....' : "Mark as Delivered"
                                                    }}
                                        </span>
                                    </button>
                                    <span v-show="props.order?.delivered_at" class="text-sm text-gray-600">
                                        On {{ moment(props.order.delivered_at).format('DD/MM/YYYY HH:mm') }}
                                    </span>
                                    <span v-show="props.order?.delivered_by" class="text-sm text-gray-600">
                                        By {{ props.order?.delivered_by?.name }}
                                    </span>
                                </div>

                                <!-- Pulse Indicator if currently at this status -->
                                <div v-if="props.order.status === 'dispatched'"
                                    class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                </div>
                            </div>

                            <!-- Received Status -->
                            <div class="relative">
                                <div class="flex flex-col">
                                    <button @click="changeStatus(props.order?.id, 'received', 'is_receiving')"
                                        :disabled="isType['is_delivering'] || props.order?.status != 'delivered'"
                                        :class="[
                                            props.order.status == 'delivered'
                                                ? 'bg-yellow-300'
                                                : statusOrder.indexOf(props.order.status) >
                                                    statusOrder.indexOf('delivered')
                                                    ? 'bg-green-500 cursor-not-allowed'
                                                    : 'bg-gray-300 cursor-not-allowed'
                                        ]"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                        <img src="/assets/images/received.png" class="w-5 h-5 mr-2" alt="delivered" />
                                        <span class="text-sm font-bold text-white">
                                            {{
                                                statusOrder.indexOf(
                                                    props.order.status
                                                ) > statusOrder.indexOf("delivered")
                                                    ? "Received"
                                            : isType['is_receiving'] ? 'Please Wait....' : "Mark as Received"
                                                    }}
                                        </span>
                                    </button>
                                    <span v-show="props.order?.received_at" class="text-sm text-gray-600">
                                        On {{ moment(props.order.received_at).format('DD/MM/YYYY HH:mm') }}
                                    </span>
                                    <span v-show="props.order?.received_by" class="text-sm text-gray-600">
                                        By {{ props.order?.received_by?.name }}
                                    </span>
                                </div>

                                <!-- Pulse Indicator if currently at this status -->
                                <div v-if="props.order.status === 'delivered'"
                                    class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                </div>
                            </div>
                        </div>

                        <!-- Status indicator for rejected status -->
                        <div v-if="props.order.status === 'rejected'"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-100 text-red-800 min-w-[160px]">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="text-sm font-bold">Rejected</span>
                            <span v-show="props.order?.rejected_at" class="text-sm text-gray-600">
                                On {{ moment(props.order.rejected_at).format('DD/MM/YYYY HH:mm') }}
                            </span>
                            <span v-show="props.order?.rejected_by" class="text-sm text-gray-600">
                                By {{ props.order?.rejected_by?.name }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Status Actions Section - Single row with actions and status icons -->

            <!-- Back Order Modal -->
            <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[80vh] overflow-hidden">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">
                            Back Order Details
                        </h3>
                        <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto max-h-[60vh]">
                        <div class="mb-4">
                            <span class="text-sm font-medium text-gray-700">Order #{{ order.id }}</span>
                            <h2 class="text-xl font-bold text-gray-900">
                                Back Ordered Items
                            </h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                            Quantity
                                        </th>
                                        <th
                                            class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                            Status
                                        </th>
                                        <th
                                            class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                                            Quantity to Release
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(row, index) in backOrderRows" :key="index">
                                        <td class="px-3 py-2">
                                            <input type="number" v-model="row.quantity"
                                                :disabled="row.finalized != null"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                min="0" @input="validateBackOrderQuantities" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <select v-model="row.status"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <option v-for="status in [
                                                    'Missing',
                                                    'Damaged',
                                                    'Expired',
                                                    'Lost',
                                                ]" :key="status" :value="status">
                                                    {{ status }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <button @click="
                                                removeBackOrderRow(index, row)
                                                " v-if="!row.finalized" class="text-red-600 hover:text-red-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
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
                </div>
            </div>

            <!-- Back Order Modal -->
            <Modal :show="showBackOrderModal" @close="attemptCloseModal" maxWidth="2xl">
                <div class="p-6">
                    <div v-if="showIncompleteBackOrderModal" class="mb-6">
                        <div class="flex items-center mb-4">
                            <div class="rounded-full bg-yellow-100 p-3 mr-3">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h2 class="text-lg font-medium text-gray-900">
                                Incomplete Back Orders
                            </h2>
                        </div>

                        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm font-medium text-gray-600 mb-2">
                                Item: {{ selectedItem?.product?.name }}
                            </p>
                            <p class="text-sm font-medium text-gray-600 mb-2">
                                Missing Quantity: {{ missingQuantity }}
                            </p>
                            <p class="text-sm font-medium text-gray-600">
                                Remaining to Allocate: {{ remainingToAllocate }}
                            </p>
                        </div>
                    </div>

                    <h2 v-else class="text-sm font-medium text-gray-900 mb-4">
                        Back Order Details
                    </h2>
                    <span v-if="message" class="text-sm text-red-600">{{
                        message
                        }}</span>

                    <div class="mb-4 bg-gray-50 p-3 rounded">
                        <p class="text-xs font-medium text-gray-600">
                            Product: {{ selectedItem?.product?.name }}
                        </p>

                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="border-r pr-2">
                                <p class="text-sm font-medium text-gray-600">
                                    Quantity to Release:
                                </p>
                                <p class="text-lg font-bold text-gray-800">
                                    {{ selectedItem?.quantity_to_release || 0 }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-600">
                                    Received Quantity:
                                </p>
                                <p class="text-lg font-bold text-gray-800">
                                    {{ selectedItem?.received_quantity || 0 }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 p-2 bg-yellow-50 rounded border border-yellow-200">
                            <div class="flex justify-between items-center">
                                <p class="text-xs font-medium text-gray-700">
                                    Missing Quantity (Back Order):
                                </p>
                                <p class="text-sm font-bold" :class="missingQuantity > 0
                                    ? 'text-red-600'
                                    : 'text-green-600'
                                    ">
                                    {{ missingQuantity }}
                                </p>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                This is the difference between quantity to release
                                and received quantity
                            </p>
                        </div>

                        <div class="mt-3">
                            <div class="flex justify-between items-center">
                                <p class="text-xs font-medium text-gray-600">
                                    Existing Back Orders:
                                </p>
                                <p class="text-xs font-bold text-gray-800">
                                    {{ totalExistingDifferences }}
                                </p>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-xs font-medium text-yellow-800">
                                    Remaining to Allocate:
                                </p>
                                <p class="text-xs font-bold" :class="remainingToAllocate > 0
                                    ? 'text-red-600'
                                    : 'text-green-600'
                                    ">
                                    {{ remainingToAllocate }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Batch-specific back orders -->
                    <div class="mb-4">
                        <h3 class="text-xs font-medium text-gray-700 mb-2">
                            Batch Information
                        </h3>
                        <div class="bg-gray-50 p-3 rounded mb-4">
                            <p class="text-xs text-gray-500 mb-2">
                                Please allocate the missing quantity ({{
                                    missingQuantity
                                }}) across batches and specify the problem type.
                            </p>
                            <p class="text-xs text-gray-500">
                                Back orders represent the difference between
                                quantity to release and received quantity.
                            </p>
                        </div>

                        <div v-for="(allocation, allocIndex) in selectedItem?.inventory_allocations"
                            :key="allocation.id" class="border rounded-md p-3 mb-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="font-medium text-gray-700">
                                    Batch: {{ allocation.batch_number }}
                                    <span class="ml-2 text-sm text-gray-500">({{
                                        allocation.allocated_quantity
                                        }}
                                        units)</span>
                                </div>
                                <div>
                                    <button @click="addBatchBackOrder(allocation)"
                                        class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600"
                                        :disabled="!canAddMoreToAllocation(allocation) ||
                                            isSaving
                                            ">
                                        Add Issue
                                    </button>
                                </div>
                            </div>
                            <!-- Back order rows for this batch -->

                            <div v-if="getBatchBackOrders(allocation.id).length > 0" class="mt-3">
                                <table class="min-w-full divide-y divide-gray-200 border">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">
                                                Issue Type
                                            </th>
                                            <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">
                                                Quantity
                                            </th>
                                            <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">
                                                Notes
                                            </th>
                                            <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(row, rowIndex) in getBatchBackOrders(allocation.id)" :key="rowIndex"
                                            class="hover:bg-gray-50">
                                            <td class="px-2 py-1">
                                                <select v-model="row.status"
                                                    class="text-sm w-full border-gray-300 rounded-md shadow-sm"
                                                    :disabled="props.order.status == 'received'">
                                                    <option v-for="status in [
                                                        'Missing',
                                                        'Damaged',
                                                        'Expired',
                                                        'Lost',
                                                    ]" :key="status" :value="status">
                                                        {{ status }}
                                                    </option>
                                                </select>
                                                <span>{{ row.finalized }}</span>
                                            </td>
                                            <td class="px-2 py-1">
                                                <input :disabled="props.order.status == 'received'" type="number"
                                                    v-model="row.quantity" @input="
                                                        validateBatchBackOrderQuantity(
                                                            row,
                                                            allocation
                                                        )
                                                        " min="0" :max="allocation.allocated_quantity
                                                            "
                                                    class="text-sm w-full border-gray-300 rounded-md shadow-sm" />
                                            </td>
                                            <td class="px-2 py-1">
                                                <input :disabled="props.order.status == 'received'" type="text"
                                                    v-model="row.notes" placeholder="Optional notes"
                                                    class="text-sm w-full border-gray-300 rounded-md shadow-sm" />
                                            </td>
                                            <td class="px-2 py-1 text-center">
                                                <button :disabled="props.order.status == 'received'" @click="
                                                    removeBatchBackOrder(
                                                        row,
                                                        rowIndex
                                                    )
                                                    " class="text-red-600 hover:text-red-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
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
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div class="text-sm">
                                <span :class="{
                                    'text-green-600': isValidForSave,
                                    'text-red-600': !isValidForSave,
                                }">
                                    {{ totalBatchBackOrderQuantity }}
                                </span>
                                <span class="text-gray-600">
                                    / {{ missingQuantity }} items recorded</span>
                                <div v-if="
                                    missingQuantity > 0 &&
                                    totalBatchBackOrderQuantity ===
                                    missingQuantity
                                " class="text-xs text-green-600 mt-1">
                                    ✓ All missing items accounted for
                                </div>
                                <div v-else-if="
                                    missingQuantity > 0 &&
                                    totalBatchBackOrderQuantity <
                                    missingQuantity
                                " class="text-xs text-yellow-600 mt-1">
                                    {{
                                        missingQuantity -
                                        totalBatchBackOrderQuantity
                                    }}
                                    more items need to be allocated
                                </div>
                                <div v-else-if="
                                    missingQuantity > 0 &&
                                    totalBatchBackOrderQuantity >
                                    missingQuantity
                                " class="text-xs text-red-600 mt-1">
                                    Over-allocated by
                                    {{
                                        totalBatchBackOrderQuantity -
                                        missingQuantity
                                    }}
                                    items
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="saveBackOrders"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!isValidForSave || isSaving || props.order.status == 'received'">
                                {{
                                    isSaving
                                        ? "Saving..."
                                        : "Save Differences and Exit"
                                }}
                            </button>
                            <button :disabled="isSaving" @click="attemptCloseModal"
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                                Exit
                            </button>
                        </div>
                    </div>
                </div>
            </Modal>

            <!-- Delivery Form Modal -->
            <Modal :show="showDeliveryModal" @close="closeDeliveryForm" maxWidth="4xl">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Mark Order as Delivered
                        </h2>
                        <button @click="closeDeliveryForm" class="text-gray-400 hover:text-gray-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Dispatch Information Summary -->
                    <div v-if="props.order.dispatch?.length > 0" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h3 class="text-lg font-medium text-blue-900 mb-3">Dispatch Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="dispatch in props.order.dispatch" :key="dispatch.id" class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-blue-700">Driver:</span>
                                    <span class="text-sm text-blue-800">{{ dispatch.driver_name || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-blue-700">Phone:</span>
                                    <span class="text-sm text-blue-800">{{ dispatch.driver_number || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-blue-700">Plate Number:</span>
                                    <span class="text-sm text-blue-800">{{ dispatch.plate_number || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-blue-700">Dispatched Cartons:</span>
                                    <span class="text-sm text-blue-800">{{ dispatch.no_of_cartoons || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-blue-700">Dispatch Date:</span>
                                    <span class="text-sm text-blue-800">{{ dispatch.created_at ? new Date(dispatch.created_at).toLocaleDateString() : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Form -->
                    <form @submit.prevent="submitDeliveryForm" class="space-y-6">
                        <!-- Received Cartons Section -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Received Cartons</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="dispatch in props.order.dispatch" :key="dispatch.id" class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Received Cartons for {{ dispatch.driver_name || 'Driver' }}
                                    </label>
                                    <input 
                                        type="number" 
                                        v-model="deliveryForm.received_cartoons[dispatch.id]"
                                        :min="0"
                                        :max="dispatch.no_of_cartoons"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :placeholder="`Max: ${dispatch.no_of_cartoons}`"
                                    />
                                    <p class="text-xs text-gray-500">
                                        Dispatched: {{ dispatch.no_of_cartoons }} | 
                                        Received: {{ deliveryForm.received_cartoons[dispatch.id] || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-900">Upload Images</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Received Items Photos {{ hasDiscrepancy ? '(Required)' : '(Optional)' }}
                                    </label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                        <div class="space-y-1 text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="received-images" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                    <span>Upload images</span>
                                                    <input 
                                                        id="received-images" 
                                                        type="file" 
                                                        multiple 
                                                        accept="image/*"
                                                        @change="handleImageUpload"
                                                        class="sr-only"
                                                    />
                                                </label>
                                                <p class="pl-1">or drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500">PNG, JPG, GIF up to 10MB each</p>
                                        </div>
                                    </div>
                                    <div v-if="deliveryForm.images.length > 0" class="mt-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <div v-for="(image, index) in deliveryForm.images" :key="index" class="relative">
                                                <img :src="image.preview" class="h-20 w-full object-cover rounded" />
                                                <button @click="removeImage(index)" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">
                                                    ×
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Notes (Optional)
                                    </label>
                                    <textarea 
                                        v-model="deliveryForm.notes"
                                        rows="4"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Add any additional notes about the delivery..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Validation Messages -->
                        <div v-if="!isDeliveryFormValid" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Please fix the following issues:</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <ul class="list-disc list-inside space-y-1">
                                            <li v-if="!Object.values(deliveryForm.received_cartoons).some(qty => qty > 0)">
                                                At least some cartons must be received
                                            </li>
                                            <li v-if="hasDiscrepancy && deliveryForm.images.length === 0 && !deliveryForm.acknowledgeDiscrepancy">
                                                Either upload images or acknowledge the discrepancy
                                            </li>
                                            <li v-if="!Object.entries(deliveryForm.received_cartoons).every(([dispatchId, received]) => {
                                                const dispatch = props.order.dispatch?.find(d => d.id == dispatchId);
                                                return dispatch ? (received || 0) <= dispatch.no_of_cartoons : true;
                                            })">
                                                Received cartons cannot exceed dispatched cartons
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button 
                                type="button"
                                @click="closeDeliveryForm"
                                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                :disabled="isSubmittingDelivery || !isDeliveryFormValid"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ isSubmittingDelivery ? 'Submitting...' : 'Mark as Delivered' }}
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref, toRefs, h, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { router, Link } from "@inertiajs/vue3";
import {
    BuildingOfficeIcon,
    EnvelopeIcon,
    PhoneIcon,
    MapPinIcon,
} from "@heroicons/vue/24/outline";
import Swal from "sweetalert2";
import axios from "axios";
import moment from "moment";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    error: {
        type: String,
        default: null,
    },
    products: {
        type: Array,
        default: () => [],
    },
});

const { order, error, products } = toRefs(props);

// Back order modal state
const showBackOrderModal = ref(false);
const showModal = ref(false); // Added missing ref for the old modal
const selectedItem = ref(null);
const batchBackOrders = ref({});
const showIncompleteBackOrderModal = ref(false);
const isSaving = ref(false);

// Computed properties for back order modal
const missingQuantity = computed(() => {
    if (!selectedItem.value) return 0;
    return (
        selectedItem.value.quantity_to_release -
        (selectedItem.value.received_quantity || 0)
    );
});

const totalBatchBackOrderQuantity = computed(() => {
    let total = 0;
    Object.values(batchBackOrders.value).forEach((rows) => {
        rows.forEach((row) => {
            total += Number(row.quantity || 0);
        });
    });
    return total;
});

const totalExistingDifferences = computed(() => {
    if (!selectedItem.value || !selectedItem.value.differences) return 0;
    return selectedItem.value.differences.reduce(
        (total, diff) => total + Number(diff.quantity || 0),
        0
    );
});

const remainingToAllocate = computed(() => {
    return missingQuantity.value - totalBatchBackOrderQuantity.value;
});

const isValidForSave = computed(() => {
    // Check if we have any back orders
    const hasBackOrders = Object.values(batchBackOrders.value).some(
        (rows) => rows.length > 0
    );

    // Check if all back orders have valid data
    const allValid = Object.values(batchBackOrders.value).every((rows) => {
        return rows.every((row) => row.quantity > 0 && row.status);
    });

    // Check if total matches the missing quantity
    const totalMatches =
        totalBatchBackOrderQuantity.value === missingQuantity.value;

    return hasBackOrders && allValid && totalMatches;
});

// Functions for back order modal
const openBackOrderModal = (item) => {
    selectedItem.value = item;
    batchBackOrders.value = {};
    showIncompleteBackOrderModal.value = false;

    // If there's no difference between quantity_to_release and received_quantity, no need for differences
    if (
        item.quantity_to_release <= (item.received_quantity || 0) &&
        (!item.differences || item.differences.length === 0)
    ) {
        toast.info("All quantities have been received. No differences to report.");
        return;
    }

    // Only use PackingListDifference (item.differences)
    if (item.differences && item.differences.length > 0) {
        item.differences.forEach((difference) => {
            const allocationId = difference.inventory_allocation_id;
            if (!batchBackOrders.value[allocationId]) {
                batchBackOrders.value[allocationId] = [];
            }
            // Find the corresponding allocation for this difference
            const allocation = item.inventory_allocations.find(
                (alloc) => alloc.id === parseInt(allocationId)
            );
            batchBackOrders.value[allocationId].push({
                id: difference.id, // Store the ID for editing/deleting
                inventory_allocation_id: allocationId,
                quantity: difference.quantity,
                status: difference.status || "Missing",
                notes: difference.notes,
                batch_number: allocation?.batch_number || "",
                barcode: allocation?.barcode || "",
                isExisting: true, // Flag to indicate this is an existing difference
            });
        });
    }

    // If no existing differences, pre-populate based on inventory allocations
    if (
        (!item.differences || item.differences.length === 0) &&
        item.inventory_allocations &&
        item.inventory_allocations.length > 0
    ) {
        const missingQty = item.quantity_to_release - (item.received_quantity || 0);
        let remainingToAllocate = missingQty;
        item.inventory_allocations.forEach((allocation) => {
            if (remainingToAllocate > 0) {
                const allocationRatio = allocation.allocated_quantity / item.quantity_to_release;
                let batchMissingQty = Math.min(
                    Math.round(missingQty * allocationRatio),
                    allocation.allocated_quantity,
                    remainingToAllocate
                );
                if (batchMissingQty > 0) {
                    const differences = getBatchBackOrders(allocation.id);
                    differences.push({
                        inventory_allocation_id: allocation.id,
                        quantity: batchMissingQty,
                        status: "Missing",
                        notes: "",
                        batch_number: allocation.batch_number,
                        barcode: allocation.barcode,
                        isExisting: false,
                    });
                    remainingToAllocate -= batchMissingQty;
                }
            }
        });
    }
    showBackOrderModal.value = true;
};

// Get differences for a specific batch
const getBatchBackOrders = (allocationId) => {
    if (!batchBackOrders.value[allocationId]) {
        batchBackOrders.value[allocationId] = [];
    }
    return batchBackOrders.value[allocationId];
};

// Check if we can add more back orders to an allocation
const canAddMoreToAllocation = (allocation) => {
    // First check if there's a mismatch between quantity_to_release and received_quantity
    if (
        !selectedItem.value ||
        !(
            selectedItem.value.quantity_to_release >
            (selectedItem.value.received_quantity || 0)
        )
    ) {
        return false;
    }

    // Get current back orders for this allocation
    const currentBackOrders = getBatchBackOrders(allocation.id);

    // Calculate total quantity already recorded as differences for this allocation
    const totalBackOrdered = currentBackOrders.reduce(
        (sum, diff) => sum + Number(diff.quantity || 0),
        0
    );

    // Calculate remaining quantity to record as differences overall
    const remainingOverall =
        missingQuantity.value - totalBatchBackOrderQuantity.value;

    // Can add more if there's still quantity available in this allocation AND we need more differences overall
    return (
        totalBackOrdered < allocation.allocated_quantity && remainingOverall > 0
    );
};

// Add a difference for a specific batch
const addBatchBackOrder = (allocation) => {
    const currentDifferences = getBatchBackOrders(allocation.id);

    // Calculate total missing quantity (difference between quantity_to_release and received_quantity)
    const totalMissingQuantity = missingQuantity.value;

    // Calculate how much has already been allocated in all differences
    const totalAlreadyAllocated = totalBatchBackOrderQuantity.value;

    // Calculate how much is still remaining to allocate
    const remainingToAllocate = totalMissingQuantity - totalAlreadyAllocated;

    // Only add if there's quantity that still needs to be allocated
    if (remainingToAllocate <= 0) {
        Swal.fire({
            title: "Cannot Add Issue",
            text: "All missing quantity has already been allocated to differences.",
            icon: "warning",
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
        });
        return;
    }

    // Add a new difference row for this batch with a default quantity of the remaining to allocate
    // (user can adjust this as needed)
    currentDifferences.push({
        inventory_allocation_id: allocation.id,
        quantity: remainingToAllocate,
        status: "Missing",
        notes: "",
        batch_number: allocation.batch_number,
        barcode: allocation.barcode,
    });
};

onMounted(() => {
    setTimeout(() => {
        message.value = "";
    }, 2000);
});
// Remove a difference for a specific batch
const removeBatchBackOrder = async (row, index) => {
    message.value = "";
    if (batchBackOrders.value[row.inventory_allocation_id]) {
        batchBackOrders.value[row.inventory_allocation_id].splice(index, 1);
        // Note: We'll handle deletion through the main save function now
        // since we're using the new differences pattern
    }
};

// Validate difference quantity for a specific batch
const validateBatchBackOrderQuantity = (row, allocation) => {
    // Ensure quantity is a number and within valid range
    const qty = Number(row.quantity);
    if (isNaN(qty) || qty <= 0) {
        row.quantity = 0;
        return;
    }

    // Get all differences for this allocation
    const allocationDifferences = getBatchBackOrders(allocation.id);

    // Calculate total differences for this allocation except this row
    const totalOtherRowsInAllocation = allocationDifferences.reduce(
        (subtotal, difference) => {
            // Skip the current row being validated
            if (difference === row) return subtotal;
            return subtotal + Number(difference.quantity || 0);
        }, 0
    );

    // Calculate total differences for all allocations except this row
    const totalOtherRows = Object.values(batchBackOrders.value).reduce(
        (total, rows) => {
            return (
                total +
                rows.reduce((subtotal, difference) => {
                    // Skip the current row being validated
                    if (difference === row) return subtotal;
                    return subtotal + Number(difference.quantity || 0);
                }, 0)
            );
        },
        0
    );

    // Calculate maximum allowed for this row based on overall missing quantity
    const maxForThisRowByMissing = missingQuantity.value - totalOtherRows;

    // Calculate maximum allowed for this row based on allocation quantity
    const maxForThisRowByAllocation = allocation.allocated_quantity - totalOtherRowsInAllocation;

    // Take the smaller of the two maximums
    const maxForThisRow = Math.min(maxForThisRowByMissing, maxForThisRowByAllocation);

    // when propograting the value to the imput first check how much it needs
    // for example this batch needs 20 and by default he is given 30

    // If the quantity exceeds what's available, set it to 0
    if (qty > maxForThisRow) {
        row.quantity = 0;
        Swal.fire({
            title: "Invalid Quantity",
            text: qty > maxForThisRowByAllocation
                ? `Quantity cannot exceed the allocated quantity for batch ${allocation.batch_number}`
                : "The quantity exceeds the remaining missing quantity.",
            icon: "warning",
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 2000,
        });
    }
};

const message = ref('');
const saveBackOrders = async () => {
    message.value = "";
    if (totalBatchBackOrderQuantity.value !== missingQuantity.value) {
        Swal.fire({
            title: "Cannot Save",
            text: `The total difference quantity (${totalBatchBackOrderQuantity.value}) must exactly match the missing quantity (${missingQuantity.value}).`,
            icon: "error",
            confirmButtonText: "OK",
        });
        return;
    }
    const allValid = Object.values(batchBackOrders.value).every((rows) => {
        return rows.every((row) => row.quantity > 0 && row.status);
    });
    if (!allValid) {
        Swal.fire({
            title: "Invalid Data",
            text: "All differences must have a quantity greater than 0 and a valid status.",
            icon: "error",
            confirmButtonText: "OK",
        });
        return;
    }
    isSaving.value = true;
    // Prepare data for API
    const differenceData = {
        order_item_id: selectedItem.value.id,
        received_quantity: selectedItem.value.received_quantity || 0,
        differences: [],
        deleted_differences: [],
    };
    Object.entries(batchBackOrders.value).forEach(([allocationId, rows]) => {
        rows.forEach((row) => {
            // Only send the fields the backend expects
            if (row.isExisting && row.id) {
                differenceData.differences.push({
                    id: row.id,
                    inventory_allocation_id: allocationId,
                    quantity: row.quantity,
                    status: row.status,
                    notes: row.notes || null,
                });
            } else {
                differenceData.differences.push({
                    inventory_allocation_id: allocationId,
                    quantity: row.quantity,
                    status: row.status,
                    notes: row.notes || null,
                });
            }
        });
    });
    // Find deleted differences
    if (selectedItem.value.differences) {
        const currentDifferenceIds = Object.values(batchBackOrders.value)
            .flat()
            .filter((row) => row.isExisting && row.id)
            .map((row) => row.id);
        const deletedDifferences = selectedItem.value.differences
            .filter((diff) => !currentDifferenceIds.includes(diff.id))
            .map((diff) => diff.id);
        differenceData.deleted_differences = deletedDifferences;
    }
    await axios
        .post(route("orders.backorder"), differenceData)
        .then((response) => {
            isSaving.value = false;
            showBackOrderModal.value = false;
            toast.success(response.data || "Differences saved successfully");
            router.visit(
                route("orders.show", props.order.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: false,
                    replace: true,
                }
            );
        })
        .catch((error) => {
            console.log(error.response);
            isSaving.value = false;
            toast.error(error.response?.data || "Failed to save differences");
        });
};

const attemptCloseModal = () => {
    if (
        remainingToAllocate.value > 0 &&
        totalBatchBackOrderQuantity.value > 0
    ) {
        // Show warning if there are unallocated quantities
        showIncompleteBackOrderModal.value = true;
    } else {
        // Close modal directly if everything is allocated or nothing has been entered
        showBackOrderModal.value = false;
        showIncompleteBackOrderModal.value = false;
    }
};

const statusClasses = {
    pending: "bg-yellow-100 text-yellow-800 rounded-full font-bold",
    reviewed: "bg-amber-100 text-amber-800 rounded-full font-bold",
    approved: "bg-green-100 text-green-800 rounded-full font-bold",
    rejected: "bg-red-100 text-red-800 rounded-full font-bold",
    "in process": "bg-blue-100 text-blue-800 rounded-full font-bold",
    dispatched: "bg-purple-100 text-purple-800 rounded-full font-bold",
    delivered: "bg-indigo-100 text-indigo-800 rounded-full font-bold",
    received:
        "bg-green-100 text-green-800 rounded-full font-bold flex items-center",
    partially_received: "bg-orange-100 text-orange-800 rounded-full font-bold",
    default: "bg-gray-100 text-gray-800 rounded-full font-bold",
};

const form = ref([]);
const isLoading = ref(false);

onMounted(() => {
    form.value = props.order.items || [];
});

const formatDate = (date) => {
    return moment(date).format("DD/MM/YYYY");
};

const statusOrder = [
    "pending",
    "reviewed",
    "approved",
    "in_process",
    "dispatched",
    "delivered",
    "received",
];

// Function to change order status
const isType = ref([]);
const changeStatus = (orderId, newStatus, type) => {
    console.log(orderId, newStatus, type);
    
    // Special handling for approve action - check if quantity_to_release is 0
    if (newStatus === 'approved') {
        const totalQuantityToRelease = form.value.reduce((total, item) => {
            return total + (parseFloat(item.quantity_to_release) || 0);
        }, 0);
        
        if (totalQuantityToRelease === 0) {
            Swal.fire({
                title: "No Quantity to Release",
                text: "There is no quantity release for the current order. Do you want to proceed? Proceeding will lead to rejection of the order.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Proceed (Reject Order)",
                cancelButtonText: "Cancel"
            }).then(async (result) => {
                if (result.isConfirmed) {
                    await performStatusChange(orderId, 'rejected', type);
                }
            });
            return;
        }
    }
    
    Swal.fire({
        title: "Are you sure?",
        text: `Do you want to change the order status to ${newStatus}?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, change it!",
    }).then(async (result) => {
        if (result.isConfirmed) {
            await performStatusChange(orderId, newStatus, type);
        }
    });
};

const performStatusChange = async (orderId, newStatus, type) => {
    // Set loading state
    isType.value[type] = true;

    await axios
        .post(route("orders.change-status"), {
            order_id: orderId,
            status: newStatus
        })
        .then((response) => {
            isType.value[type] = false;
            // Reset loading state
            isLoading.value = false;
            
            const successMessage = newStatus === 'rejected' 
                ? "Order status has been updated to rejected."
                : response.data;
                
            Swal.fire({
                title: "Updated!",
                text: successMessage,
                icon: "success",
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
            }).then(() => {
                // Reload the page to show the updated status
                router.get(route("orders.show", props.order.id), {}, {
                    preserveScroll: true,
                    only: ['orders']
                });
            });
        })
        .catch((error) => {
            isType.value[type] = false;
            console.log(error.response);
            // Reset loading state
            isLoading.value = false;

            toast.error(error.response?.data || "Failed to update order status");
        });
};

const notSubmitted = ref(false);

const itemsWithZeroReceived = computed(() => {
    return props.order?.items?.some(item => item.received_quantity === 0);
});

const getRemainingQuantity = (item) => {
    return Math.max(0, (item.quantity_to_release || 0) - (item.received_quantity || 0));
};

const allItemsFullyReceived = computed(() => {
    return props.order?.items?.every(item => 
        (item.received_quantity || 0) >= (item.quantity_to_release || 0)
    );
});

// Delivery Form Computed Properties
const hasDiscrepancy = computed(() => {
    // Check for carton discrepancy
    const hasCartonDiscrepancy = props.order.dispatch?.some(dispatch => {
        const received = deliveryForm.value.received_cartoons[dispatch.id] || 0;
        return received < dispatch.no_of_cartoons;
    });

    return hasCartonDiscrepancy;
});

const isDeliveryFormValid = computed(() => {
    // Basic validation - at least some cartons should be received
    const hasReceivedCartons = Object.values(deliveryForm.value.received_cartoons).some(qty => qty > 0);
    
    // If there's a discrepancy, either images are required OR user must acknowledge
    if (hasDiscrepancy.value && deliveryForm.value.images.length === 0 && !deliveryForm.value.acknowledgeDiscrepancy) {
        console.log('Form invalid: Has discrepancy but no images and not acknowledged');
        return false;
    }
    
    // Validate that received cartons don't exceed dispatched cartons
    const hasValidCartons = Object.entries(deliveryForm.value.received_cartoons).every(([dispatchId, received]) => {
        const dispatch = props.order.dispatch?.find(d => d.id == dispatchId);
        return dispatch ? (received || 0) <= dispatch.no_of_cartoons : true;
    });
    
    const isValid = hasReceivedCartons && hasValidCartons;
    console.log('Form validation:', {
        hasReceivedCartons,
        hasValidCartons,
        hasDiscrepancy: hasDiscrepancy.value,
        imagesCount: deliveryForm.value.images.length,
        isValid
    });
    
    return isValid;
});

watch(itemsWithZeroReceived, (newVal, oldVal) => {
    console.log('Has item with 0 received_quantity:', newVal);
});

const isSavingQty = ref([]);
const updateQuantityTimeouts = ref({});

// Delivery Form State
const showDeliveryModal = ref(false);
const isSubmittingDelivery = ref(false);
const deliveryForm = ref({
    received_cartoons: {},
    images: [],
    notes: '',
    acknowledgeDiscrepancy: false
});

// Cleanup on unmount
onBeforeUnmount(() => {
    Object.values(updateQuantityTimeouts.value).forEach(timeout => {
        if (timeout) clearTimeout(timeout);
    });
});

// Debounced input handler for received quantity
const handleReceivedQuantityInput = (item, index) => {
    const timeoutKey = `received-${item.id}-${index}`;
    
    if (updateQuantityTimeouts.value[timeoutKey]) {
        clearTimeout(updateQuantityTimeouts.value[timeoutKey]);
    }
    
    updateQuantityTimeouts.value[timeoutKey] = setTimeout(() => {
        receivedQty(item, index);
    }, 500);
};

async function receivedQty(item, index) {
    isSavingQty.value[index] = true;
    
    // Validate received quantity
    if (item.received_quantity > item.quantity_to_release) {
        item.received_quantity = item.quantity_to_release;
        toast.error('Received quantity cannot exceed quantity to release');
        isSavingQty.value[index] = false;
        return;
    }

    // Ensure received quantity is not negative
    if (item.received_quantity < 0) {
        item.received_quantity = 0;
        toast.error('Received quantity cannot be negative');
        isSavingQty.value[index] = false;
        return;
    }

    await axios.post(route('orders.receivedQuantity'), {
        order_item_id: item.id,
        received_quantity: item.received_quantity
    })
        .then((response) => {
            isSavingQty.value[index] = false;
            toast.success('Received quantity updated successfully');
        })
        .catch((error) => {
            isSavingQty.value[index] = false;
            toast.error(error.response?.data || 'Failed to update received quantity');
        });
}

// Delivery Form Functions
const openDeliveryForm = () => {
    // Initialize form
    deliveryForm.value.received_cartoons = {};
    deliveryForm.value.images = [];
    deliveryForm.value.notes = '';
    deliveryForm.value.acknowledgeDiscrepancy = false;
    
    // Pre-fill cartons with dispatched quantities (assuming all received initially)
    props.order.dispatch?.forEach(dispatch => {
        deliveryForm.value.received_cartoons[dispatch.id] = dispatch.no_of_cartoons || 0;
    });
    
    showDeliveryModal.value = true;
};

const closeDeliveryForm = () => {
    showDeliveryModal.value = false;
    deliveryForm.value = {
        received_cartoons: {},
        images: [],
        notes: '',
        acknowledgeDiscrepancy: false
    };
};

const handleImageUpload = (event) => {
    const files = Array.from(event.target.files);
    
    files.forEach(file => {
        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            toast.error(`File ${file.name} is too large. Maximum size is 10MB.`);
            return;
        }
        
        if (!file.type.startsWith('image/')) {
            toast.error(`File ${file.name} is not an image.`);
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            deliveryForm.value.images.push({
                file: file,
                preview: e.target.result
            });
        };
        reader.readAsDataURL(file);
    });
    
    // Clear the input
    event.target.value = '';
};

const removeImage = (index) => {
    deliveryForm.value.images.splice(index, 1);
};

const submitDeliveryForm = async () => {
    if (!isDeliveryFormValid.value) {
        toast.error('Please fill in all required fields and upload images if there are discrepancies.');
        return;
    }
    
    isSubmittingDelivery.value = true;
    
    try {
        // Create FormData for file upload
        const formData = new FormData();
        formData.append('order_id', props.order.id);
        formData.append('received_cartoons', JSON.stringify(deliveryForm.value.received_cartoons));
        
        // Add acknowledgment to notes if there's a discrepancy
        let notes = deliveryForm.value.notes;
        if (hasDiscrepancy.value && deliveryForm.value.acknowledgeDiscrepancy) {
            notes = (notes ? notes + '\n\n' : '') + 'DISCREPANCY ACKNOWLEDGED: User has acknowledged the discrepancy between dispatched and received cartons.';
        }
        formData.append('notes', notes);
        
        // Append images
        deliveryForm.value.images.forEach((image, index) => {
            formData.append(`images[${index}]`, image.file);
        });
        
        const response = await axios.post(route('delivery.mark-delivered'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        isSubmittingDelivery.value = false;
        closeDeliveryForm();
        
        toast.success('Order marked as delivered successfully!');
        
        // Reload the page to show updated status
        router.get(route("orders.show", props.order.id), {}, {
            preserveScroll: true,
            preserveState: false,
            replace: true
        });
        
    } catch (error) {
        isSubmittingDelivery.value = false;
        console.error('Delivery submission error:', error);
        toast.error(error.response?.data || 'Failed to mark order as delivered');
    }
};

</script>
