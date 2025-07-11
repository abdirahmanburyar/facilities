<template>
    <AuthenticatedLayout title="Transfer Details" description="Transfer Details" img="/assets/images/transfer.png">
        <div class="container mx-auto">
            <!-- Transfer Header -->
            <div class="mb-6 px-6 py-6 bg-white rounded-lg shadow-sm">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Transfer Details
                    </h1>
                    <div class="flex items-center space-x-4">
                        <span :class="[
                            statusClasses[props.transfer.status] ||
                            statusClasses.default,
                        ]" class="flex items-center text-xs font-bold px-4 py-2">
                            <!-- Status Icon -->
                            <span class="mr-3">
                                <!-- Pending Icon -->
                                <img v-if="props.transfer.status === 'pending'" src="/assets/images/pending.png"
                                    class="w-4 h-4" alt="Pending" />

                                <!-- reviewed Icon -->
                                <img v-else-if="
                                    props.transfer.status === 'reviewed'
                                " src="/assets/images/reviewed.png" class="w-4 h-4" alt="Reviewed" />

                                <!-- Approved Icon -->
                                <img v-else-if="
                                    props.transfer.status === 'approved'
                                " src="/assets/images/approved.png" class="w-4 h-4" alt="Approved" />

                                <!-- In Process Icon -->
                                <img v-else-if="
                                    props.transfer.status === 'in_process'
                                " src="/assets/images/inprocess.png" class="w-4 h-4" alt="In Process" />

                                <!-- Dispatched Icon -->
                                <img v-else-if="
                                    props.transfer.status === 'dispatched'
                                " src="/assets/images/dispatch.png" class="w-4 h-4" alt="Dispatched" />

                                <!-- Received Icon -->
                                <img v-else-if="
                                    props.transfer.status === 'received'
                                " src="/assets/images/received.png" class="w-4 h-4" alt="Received" />

                                <!-- Rejected Icon -->
                                <svg v-else-if="
                                    props.transfer.status === 'rejected'
                                " class="w-4 h-4 text-red-700" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            {{ props.transfer.status.toUpperCase() }}
                        </span>
                    </div>
                </div>

                <!-- Transfer ID and Date -->
                <div class="mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Transfer ID:</span>
                            <span class="ml-2 font-semibold">#{{ props.transfer.transferID }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Transfer Date:</span>
                            <span class="ml-2 font-semibold">{{
                                moment(props.transfer.transfer_date).format(
                                    "DD/MM/YYYY"
                                )
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- From and To Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- From Section -->
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            From
                        </h3>
                        <div v-if="props.transfer.from_warehouse">
                            <p class="font-semibold text-gray-800">
                                {{ props.transfer.from_warehouse.name }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ props.transfer.from_warehouse.address }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ props.transfer.from_warehouse.district }},
                                {{ props.transfer.from_warehouse.region }}
                            </p>
                            <div class="mt-2 text-sm">
                                <p class="text-gray-600">
                                    Manager:
                                    <span class="font-medium">{{
                                        props.transfer.from_warehouse
                                            .manager_name
                                    }}</span>
                                </p>
                                <p class="text-gray-600">
                                    Phone:
                                    <span class="font-medium">{{
                                        props.transfer.from_warehouse
                                            .manager_phone
                                    }}</span>
                                </p>
                            </div>
                            <span class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                Warehouse
                            </span>
                        </div>
                        <div v-else-if="props.transfer.from_facility">
                            <p class="font-semibold text-gray-800">
                                {{ props.transfer.from_facility.name }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ props.transfer.from_facility.address }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ props.transfer.from_facility.district }},
                                {{ props.transfer.from_facility.region }}
                            </p>
                            <div class="mt-2 text-sm">
                                <p class="text-gray-600">
                                    Type:
                                    <span class="font-medium">{{
                                        props.transfer.from_facility
                                            .facility_type
                                    }}</span>
                                </p>
                                <p class="text-gray-600">
                                    Phone:
                                    <span class="font-medium">{{
                                        props.transfer.from_facility.phone
                                        }}</span>
                                </p>
                            </div>
                            <span class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                Facility
                            </span>
                        </div>
                    </div>

                    <!-- To Section -->
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-green-800 mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            To
                        </h3>
                        <div v-if="props.transfer.to_warehouse">
                            <p class="font-semibold text-gray-800">
                                {{ props.transfer.to_warehouse.name }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ props.transfer.to_warehouse.address }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ props.transfer.to_warehouse.district }},
                                {{ props.transfer.to_warehouse.region }}
                            </p>
                            <div class="mt-2 text-sm">
                                <p class="text-gray-600">
                                    Manager:
                                    <span class="font-medium">{{
                                        props.transfer.to_warehouse.manager_name
                                        }}</span>
                                </p>
                                <p class="text-gray-600">
                                    Phone:
                                    <span class="font-medium">{{
                                        props.transfer.to_warehouse
                                            .manager_phone
                                    }}</span>
                                </p>
                            </div>
                            <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                Warehouse
                            </span>
                        </div>
                        <div v-else-if="props.transfer.to_facility">
                            <p class="font-semibold text-gray-800">
                                {{ props.transfer.to_facility.name }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ props.transfer.to_facility.address }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ props.transfer.to_facility.district }},
                                {{ props.transfer.to_facility.region }}
                            </p>
                            <div class="mt-2 text-sm">
                                <p class="text-gray-600">
                                    Type:
                                    <span class="font-medium">{{
                                        props.transfer.to_facility.facility_type
                                        }}</span>
                                </p>
                                <p class="text-gray-600">
                                    Phone:
                                    <span class="font-medium">{{
                                        props.transfer.to_facility.phone
                                        }}</span>
                                </p>
                            </div>
                            <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                Facility
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Status Stage Timeline -->
                <div v-if="props.transfer.status == 'rejected'">
                    <div class="flex flex-col items-center">
                        <div
                            class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10 bg-white border-red-500">
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
                                width: `${(statusOrder.indexOf(props.transfer.status) /
                                    (statusOrder.length - 1)) *
                                    100
                                    }%`,
                            }"></div>

                        <!-- Timeline Steps -->
                        <div class="relative flex justify-between">
                            <!-- Pending -->
                            <div class="flex flex-col items-center">
                                <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                    :class="[
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) >= statusOrder.indexOf('pending')
                                            ? 'bg-white border-orange-500'
                                            : 'bg-white border-gray-200',
                                    ]">
                                    <img src="/assets/images/pending.png" class="w-7 h-7" alt="Pending" :class="statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('pending')
                                            ? ''
                                            : 'opacity-40'
                                        " />
                                </div>
                                <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('pending')
                                        ? 'text-green-600'
                                        : 'text-gray-500'
                                    ">Pending</span>
                            </div>

                            <!-- Reviewed -->
                            <div class="flex flex-col items-center">
                                <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                    :class="[
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) >= statusOrder.indexOf('reviewed')
                                            ? 'bg-white border-orange-500'
                                            : 'bg-white border-gray-200',
                                    ]">
                                    <img src="/assets/images/review.png" class="w-7 h-7" alt="Reviewed" :class="statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('reviewed')
                                            ? ''
                                            : 'opacity-40'
                                        " />
                                </div>
                                <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('reviewed')
                                        ? 'text-green-600'
                                        : 'text-gray-500'
                                    ">Reviewed</span>
                            </div>

                            <!-- Approved -->
                            <div class="flex flex-col items-center">
                                <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                    :class="[
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) >= statusOrder.indexOf('approved')
                                            ? 'bg-white border-orange-500'
                                            : 'bg-white border-gray-200',
                                    ]">
                                    <img src="/assets/images/approved.png" class="w-7 h-7" alt="Approved" :class="statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('approved')
                                            ? ''
                                            : 'opacity-40'
                                        " />
                                </div>
                                <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('approved')
                                        ? 'text-green-600'
                                        : 'text-gray-500'
                                    ">Approved</span>
                            </div>

                            <!-- In Process -->
                            <div class="flex flex-col items-center">
                                <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                    :class="[
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) >= statusOrder.indexOf('in_process')
                                            ? 'bg-white border-orange-500'
                                            : 'bg-white border-gray-200',
                                    ]">
                                    <img src="/assets/images/inprocess.png" class="w-7 h-7" alt="In Process" :class="statusOrder.indexOf(
                                        props.transfer.status
                                    ) >=
                                            statusOrder.indexOf('in_process')
                                            ? ''
                                            : 'opacity-40'
                                        " />
                                </div>
                                <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('in_process')
                                        ? 'text-green-600'
                                        : 'text-gray-500'
                                    ">In Process</span>
                            </div>

                            <!-- Dispatch -->
                            <div class="flex flex-col items-center">
                                <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                    :class="[
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) >= statusOrder.indexOf('dispatched')
                                            ? 'bg-white border-orange-500'
                                            : 'bg-white border-gray-200',
                                    ]">
                                    <img src="/assets/images/dispatch.png" class="w-7 h-7" alt="Dispatch" :class="statusOrder.indexOf(
                                        props.transfer.status
                                    ) >=
                                            statusOrder.indexOf('dispatched')
                                            ? ''
                                            : 'opacity-40'
                                        " />
                                </div>
                                <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('dispatched')
                                        ? 'text-green-600'
                                        : 'text-gray-500'
                                    ">Dispatched</span>
                            </div>

                            <!-- Delivered -->
                            <div class="flex flex-col items-center">
                                <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                    :class="[
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) >= statusOrder.indexOf('delivered')
                                            ? 'bg-white border-orange-500'
                                            : 'bg-white border-gray-200',
                                    ]">
                                    <img src="/assets/images/delivery.png" class="w-7 h-7" alt="Dispatch" :class="statusOrder.indexOf(
                                        props.transfer.status
                                    ) >=
                                            statusOrder.indexOf('delivered')
                                            ? ''
                                            : 'opacity-40'
                                        " />
                                </div>
                                <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('delivered')
                                        ? 'text-green-600'
                                        : 'text-gray-500'
                                    ">Delivered</span>
                            </div>

                            <!-- Received -->
                            <div class="flex flex-col items-center">
                                <div class="w-14 h-14 rounded-full border-4 flex items-center justify-center z-10"
                                    :class="[
                                        statusOrder.indexOf(
                                            props.transfer.status
                                        ) >= statusOrder.indexOf('received')
                                            ? 'bg-white border-green-500'
                                            : 'bg-white border-gray-200',
                                    ]">
                                    <img src="/assets/images/received.png" class="w-7 h-7" alt="Received" :class="statusOrder.indexOf(
                                        props.transfer.status
                                    ) >= statusOrder.indexOf('received')
                                            ? ''
                                            : 'opacity-40'
                                        " />
                                </div>
                                <span class="mt-3 text-xs font-bold" :class="statusOrder.indexOf(
                                    props.transfer.status
                                ) >= statusOrder.indexOf('received')
                                        ? 'text-green-600'
                                        : 'text-gray-500'
                                    ">Received</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transfer Items Table -->
                <div class="mb-8">
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-10 h-10 bg-gradient-to-r from-purple-100 to-purple-200 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">Transfer Items</h3>
                            <p class="text-gray-600 text-sm">Detailed breakdown of items being transferred</p>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-collapse border-gray-300">
                            <thead>
                                <tr class="bg-[#EFF6FF]">
                                    <th rowspan="2"
                                        class="min-w-[300px] px-2 py-1 text-xs border border-gray-300 text-left text-[#979ECD] font-semibold uppercase">
                                        Item Name
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-gray-300 text-left text-[#979ECD] font-semibold uppercase">
                                        Category
                                    </th>
                                    <th colspan="5"
                                        class="px-2 py-1 text-xs border border-gray-300 text-center text-[#979ECD] font-semibold uppercase">
                                        Item details
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-gray-300 text-left text-[#979ECD] font-semibold uppercase">
                                        Total Quantity on Hand Per Unit
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-gray-300 text-left text-[#979ECD] font-semibold uppercase">
                                        Reasons for Transfers
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-gray-300 text-left text-[#979ECD] font-semibold uppercase">
                                        Quantity to be transferred
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-gray-300 text-left text-[#979ECD] font-semibold uppercase">
                                        Received Quantity
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-gray-300 text-center text-[#979ECD] font-semibold uppercase">
                                        Action
                                    </th>
                                </tr>
                                <tr class="bg-[#EFF6FF]">
                                    <th
                                        class="px-2 py-1 text-xs border border-gray-300 text-center text-[#979ECD] font-semibold uppercase">
                                        UoM
                                    </th>
                                    <th
                                        class="px-2 py-1 text-xs border border-gray-300 text-center text-[#979ECD] font-semibold uppercase">
                                        QTY
                                    </th>
                                    <th
                                        class="px-2 py-1 text-xs border border-gray-300 text-center text-[#979ECD] font-semibold uppercase">
                                        Batch Number
                                    </th>
                                    <th
                                        class="px-2 py-1 text-xs border border-gray-300 text-center text-[#979ECD] font-semibold uppercase">
                                        Expiry Date
                                    </th>
                                    <th
                                        class="px-2 py-1 text-xs border border-gray-300 text-center text-[#979ECD] font-semibold uppercase">
                                        Location
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <template v-for="(item, index) in form" :key="item.id">
                        <!-- Show allocations if they exist, otherwise show one row with main item data -->
                                <tr v-for="(allocation, allocIndex) in (item.inventory_allocations?.length > 0 ? item.inventory_allocations : [{}])" :key="`${item.id}-${allocIndex}`" class="hover:bg-gray-50 transition-colors duration-150 border-b border-gray-300">
                                        <!-- Item Name -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-gray-300 text-left text-black align-top">
                                            <div class="font-medium">
                                                {{ item.product?.name }}
                                            </div>
                                            {{ item.quantity_to_release }}
                                        </td>

                                        <!-- Category -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-gray-300 text-left text-black align-top">
                                            {{ item.product?.category?.name }}
                                        </td>

                                        <!-- UoM -->
                                        <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black">
                                            {{ item.uom || "N/A" }}
                                        </td>

                                        <!-- QTY -->
                                        <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black">
                                            {{
                                                allocation.allocated_quantity ||
                                                0
                                            }}
                                        </td>

                                        <!-- Batch Number -->
                                        <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black">
                                            <span :class="{
                                                'text-red-600 font-bold':
                                                    allocation.batch_number ===
                                                    'HK5273',
                                            }">
                                                {{
                                                    allocation.batch_number ||
                                                    "N/A"
                                                }}
                                            </span>
                                        </td>

                                        <!-- Expiry Date -->
                                        <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black">
                                            <span :class="{
                                                'text-red-600':
                                                    isExpiringItem(
                                                        allocation.expiry_date
                                                    ),
                                            }">
                                                {{
                                                    allocation.expiry_date
                                                        ? moment(
                                                            allocation.expiry_date
                                                        ).format("DD/MM/YYYY")
                                                        : "N/A"
                                                }}
                                            </span>
                                        </td>

                                        <!-- Location -->
                                        <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black">
                                            {{
                                                allocation.warehouse?.name ||
                                                "N/A"
                                            }}
                                        </td>

                                        <!-- Total Quantity on Hand -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-gray-300 text-center text-black align-top">
                                            {{ item }}
                                        </td>

                                        <!-- Reasons for Transfers -->
                                        <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black">
                                            {{ allocation.transfer_reason || "N/A" }}
                                        </td>

                                        <!-- Quantity to be transferred -->
                                        <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black">
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="font-medium">{{ allocation.allocated_quantity || 0 }}</span>
                                                <input 
                                                    :readonly="props.transfer.status === 'approved'"
                                                    type="number" 
                                                    v-model="allocation.updated_quantity"
                                                    :placeholder="allocation.allocated_quantity || 0"
                                                    min="1"
                                                    class="w-full text-center border border-gray-300 px-1 py-1 text-xs" 
                                                    @input="handleQuantityInput($event, allocation)"
                                                />
                                                <span class="text-xs text-gray-500" v-if="isUpdatingQuantity[allocation.id]">
                                                    Updating...
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Received Quantity -->
                                        <td class="px-2 py-1 text-xs border border-gray-300 text-center text-black"
                                        >
                                            <!-- :readonly="props.transfer.to_facility_id == null || props.transfer.status == 'received'" -->
                                            <input 
                                                type="number" 
                                                v-model="allocation.received_quantity" 
                                                :max="allocation.allocated_quantity || 0"
                                                @input="validateReceivedQuantity(allocation, allocIndex)"
                                                min="0"
                                                class="w-20 text-center border border-gray-300 px-2 py-1 text-sm" 
                                            />
                                            <span v-if="isReceived[allocIndex]" class="text-xs text-gray-500">Updating...</span>
                                            <button 
                                                v-if="(allocation.allocated_quantity || 0) !== (allocation.received_quantity || 0)"
                                                @click="openBackOrderModal(item, allocation)"
                                                class="text-xs text-orange-600 underline hover:text-orange-800 cursor-pointer mt-1 block">
                                                Back Order
                                            </button>
                                        </td>

                                        <!-- Action -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            " class="px-2 py-1 text-xs border border-gray-300 text-center align-top">
                                            <button v-if="
                                                props.transfer.status ===
                                                'pending'
                                            " @click="removeItem(index)"
                                                class="text-red-600 hover:text-red-800 transition-colors"
                                                title="Delete item">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

            <!-- dispatch information -->
            <div v-if="props.transfer.status === 'dispatched' && props.transfer.dispatch_info?.length > 0"
                class="mt-8 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Dispatch Information
                    </h2>
                </div>

                <div class="bg-white rounded-lg shadow-lg divide-y divide-gray-200">
                    <div v-for="dispatch in props.transfer.dispatch_info" :key="dispatch.id" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Driver & Company Info -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Driver Information</h3>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span class="text-sm text-gray-900">{{ dispatch.driver_name }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            <span class="text-sm text-gray-600">{{ dispatch.driver_number }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8" />
                                            </svg>
                                            <span class="text-sm text-gray-600">ID: {{ dispatch.driver_id || 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Vehicle Information</h3>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8" />
                                            </svg>
                                            <span class="text-sm text-gray-900">Plate: {{ dispatch.plate_number }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dispatch Details -->
                            <div class="space-y-4">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-500">Dispatch Details</h3>
                                    <div class="mt-2 space-y-2">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 6v6m-4-6h8" />
                                            </svg>
                                            <span class="text-sm text-gray-900">{{
                                                moment(dispatch.dispatch_date || dispatch.created_at).format('DD/MMM/YYYY') }}</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                            <span class="text-sm text-gray-600">{{ dispatch.no_of_cartoons }} Cartons</span>
                                        </div>
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-sm text-gray-600">Dispatched on {{
                                                moment(dispatch.created_at).format('MMMM D, YYYY h:mm A') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer actions -->
            <div class="mt-8 mb-[80px] bg-white rounded-lg shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
                    Transfer Status Actions
                </h3>
                <div class="flex items-start mb-6">
                    <!-- Status Action Buttons -->
                    <div class="flex flex-wrap items-center justify-center gap-4 px-1 py-2">

                            <!-- Review button -->
                            <div class="relative">
                                <div class="flex flex-col">
                                    <button :class="[
                                        props.transfer.status === 'pending'
                                            ? 'bg-yellow-500 hover:bg-yellow-600'
                                            : statusOrder.indexOf(
                                                props.transfer.status
                                            ) > statusOrder.indexOf('pending')
                                                ? 'bg-green-500'
                                                : 'bg-gray-300 cursor-not-allowed',
                                    ]"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                        <img src="/assets/images/review.png" class="w-5 h-5 mr-2" alt="Review" />
                                        <span class="text-sm font-bold text-white">{{
                                            statusOrder.indexOf(
                                                props.transfer.status
                                            ) > statusOrder.indexOf("pending")
                                                ? "Reviewed"
                                                : isType["is_reviewing"]
                                                    ? "Please Wait..."
                                                    : props.transfer.status ===
                                                        "pending"
                                                        ? "Waiting to be reviewed"
                                                        : "Review"
                                        }}</span>
                                    </button>
                                    <span v-show="props.transfer?.reviewed_at" class="text-sm text-gray-600">
                                        On {{ moment(props.transfer?.reviewed_at).format("DD/MM/YYYY HH:mm") }}
                                    </span>
                                    <span v-show="props.transfer?.reviewed_by" class="text-sm text-gray-600">
                                        By {{ props.transfer?.reviewed_by?.name }}
                                    </span>
                                </div>
                                <div v-if="props.transfer.status === 'pending'"
                                    class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                </div>
                            </div>

                            <!-- Approved button -->
                            <div class="relative" v-if="props.transfer.status !== 'rejected'">
                                <div class="flex flex-col">
                                    <button @click="
                                        changeStatus(
                                            props.transfer.id,
                                            'approved',
                                            'is_approve'
                                        )
                                        " :disabled="isType['is_approve'] ||
                                        props.transfer.status !== 'reviewed' ||
                                        !canApprove
                                        " :class="[
                                        props.transfer.status == 'reviewed'
                                            ? 'bg-yellow-500 hover:bg-yellow-600'
                                            : statusOrder.indexOf(
                                                props.transfer.status
                                            ) >
                                                statusOrder.indexOf('reviewed')
                                                ? 'bg-green-500'
                                                : 'bg-gray-300 cursor-not-allowed',
                                    ]"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                        <svg v-if="
                                            isLoading &&
                                            props.transfer.status === 'reviewed'
                                        " class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4">
                                            </circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <template v-else>
                                            <img src="/assets/images/approved.png" class="w-5 h-5 mr-2" alt="Approve" />
                                            <span class="text-sm font-bold text-white">{{
                                                statusOrder.indexOf(
                                                    props.transfer.status
                                                ) >
                                                    statusOrder.indexOf("reviewed")
                                                    ? "Approved"
                                                    : isType["is_approve"]
                                                        ? "Please Wait..."
                                                        : props.transfer.status ===
                                                            "reviewed" &&
                                                            !canApprove
                                                            ? "Waiting to be approved"
                                                            : "Approve"
                                            }}</span>
                                        </template>
                                    </button>
                                    <span v-show="props.transfer?.approved_at" class="text-sm text-gray-600">
                                        On {{ moment(props.transfer?.approved_at).format("DD/MM/YYYY HH:mm") }}
                                    </span>
                                    <span v-show="props.transfer?.approved_by" class="text-sm text-gray-600">
                                        By {{ props.transfer?.approved_by?.name }}
                                    </span>
                                </div>
                                <div v-if="props.transfer.status === 'reviewed'"
                                    class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                </div>
                            </div>

                            <!-- Process button -->
                            <div class="relative" v-if="props.transfer.status !== 'rejected'">
                                <div class="flex flex-col">
                                    <button @click="
                                        changeStatus(
                                            props.transfer.id,
                                            'in_process',
                                            'is_process'
                                        )
                                        " :disabled="isType['is_process'] ||
                                        props.transfer.status !== 'approved' ||
                                        !canDispatch
                                        " :class="[
                                        props.transfer.status === 'approved'
                                            ? 'bg-yellow-500 hover:bg-yellow-600'
                                            : statusOrder.indexOf(
                                                props.transfer.status
                                            ) >
                                                statusOrder.indexOf('approved')
                                                ? 'bg-green-500'
                                                : 'bg-gray-300 cursor-not-allowed',
                                    ]"
                                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                        <svg v-if="
                                            isType['is_process'] &&
                                            props.transfer.status == 'approved'
                                        " class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4">
                                            </circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <template v-else>
                                            <img src="/assets/images/inprocess.png" class="w-5 h-5 mr-2"
                                                alt="Process" />
                                            <span class="text-sm font-bold text-white">{{
                                                statusOrder.indexOf(
                                                    props.transfer.status
                                                ) >
                                                    statusOrder.indexOf("approved")
                                                    ? "Processed"
                                                    : isType["is_process"]
                                                        ? "Please Wait..."
                                                        : props.transfer.status ===
                                                            "approved" &&
                                                            !canDispatch
                                                            ? "Waiting to be processed"
                                                            : "Process"
                                            }}</span>
                                        </template>
                                    </button>
                                    <span v-show="props.transfer?.processed_at" class="text-sm text-gray-600">
                                        On {{ moment(props.transfer?.processed_at).format("DD/MM/YYYY HH:mm") }}
                                    </span>
                                    <span v-show="props.transfer?.processed_by" class="text-sm text-gray-600">
                                        By {{ props.transfer?.processed_by?.name }}
                                    </span>
                                </div>
                                <div v-if="props.transfer.status === 'approved'"
                                    class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                </div>
                            </div>

                            <!-- Dispatch button -->
                            <div class="relative" v-if="props.transfer.status !== 'rejected'">
                                <div class="flex flex-col">
                                    <button @click="showDispatchForm = true" :disabled="isType['is_dispatch'] ||
                                        props.transfer.status !==
                                        'in_process' ||
                                        !canDispatch
                                        " :class="[
                                        props.transfer.status === 'in_process'
                                            ? 'bg-yellow-500 hover:bg-yellow-600'
                                            : statusOrder.indexOf(
                                                props.transfer.status
                                            ) >
                                                statusOrder.indexOf('in_process')
                                                ? 'bg-green-500'
                                                : 'bg-gray-300 cursor-not-allowed',
                                    ]" class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                        <svg v-if="
                                            isType['is_dispatch'] &&
                                            props.transfer.status ===
                                            'in_process'
                                        " class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4">
                                            </circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <template v-else>
                                            <img src="/assets/images/dispatch.png" class="w-5 h-5 mr-2"
                                                alt="Dispatch" />
                                            <span class="text-sm font-bold text-white">{{
                                                statusOrder.indexOf(
                                                    props.transfer.status
                                                ) >
                                                    statusOrder.indexOf(
                                                        "in_process"
                                                    )
                                                    ? "Dispatched"
                                                    : isType["is_dispatch"]
                                                        ? "Please Wait..."
                                                        : props.transfer.status ===
                                                            "in_process" &&
                                                            !canDispatch
                                                            ? "Waiting to be dispatched"
                                                            : "Dispatch"
                                            }}</span>
                                        </template>
                                    </button>
                                    <span v-show="props.transfer?.dispatched_at" class="text-sm text-gray-600">
                                        On {{ moment(props.transfer?.dispatched_at).format("DD/MM/YYYY HH:mm") }}
                                    </span>
                                    <span v-show="props.transfer?.dispatched_by" class="text-sm text-gray-600">
                                        By {{ props.transfer?.dispatched_by?.name }}
                                    </span>
                                </div>
                                <div v-if="props.transfer.status === 'in_process'"
                                    class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                </div>
                            </div>

                            <!-- Order Delivery Indicators -->
                            <div class="flex flex-col gap-4 sm:flex-row" v-if="props.transfer.status !== 'rejected'">
                                <!-- Delivered Status -->
                                <div class="relative">
                                    <div class="flex flex-col">
                                        <button @click="
                                            changeStatus(
                                                props.transfer.id,
                                                'delivered',
                                                'is_deliver'
                                            )
                                            " :disabled="isType['is_deliver'] ||
                                            props.transfer.status !==
                                            'dispatched' ||
                                            !canReceive
                                            " :class="[
                                            props.transfer.status ===
                                                'dispatched'
                                                ? 'bg-yellow-500 hover:bg-yellow-600'
                                                : statusOrder.indexOf(
                                                    props.transfer.status
                                                ) >
                                                    statusOrder.indexOf(
                                                        'dispatched'
                                                    )
                                                    ? 'bg-green-500'
                                                    : 'bg-gray-300 cursor-not-allowed',
                                        ]"
                                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                            <svg v-if="
                                                isType['is_deliver'] &&
                                                props.transfer.status ===
                                                'dispatched'
                                            " class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <template v-else>
                                                <img src="/assets/images/delivery.png" class="w-5 h-5 mr-2"
                                                    alt="Delivered" />
                                                <span class="text-sm font-bold text-white">
                                                    {{
                                                        statusOrder.indexOf(
                                                            props.transfer.status
                                                        ) >
                                                            statusOrder.indexOf(
                                                                "dispatched"
                                                            )
                                                            ? "Delivered"
                                                            : isType["is_deliver"]
                                                                ? "Please Wait..."
                                                                : props.transfer
                                                                    .status ===
                                                                    "dispatched" &&
                                                                    !canReceive
                                                                    ? "Waiting to be delivered"
                                                                    : "Deliver"
                                                    }}
                                                </span>
                                            </template>
                                        </button>
                                        <span v-show="props.transfer?.delivered_at" class="text-sm text-gray-600">
                                            On {{ moment(props.transfer?.delivered_at).format("DD/MM/YYYY HH:mm") }}
                                        </span>
                                        <span v-show="props.transfer?.delivered_by" class="text-sm text-gray-600">
                                            By
                                            {{ props.transfer?.delivered_by?.name }}
                                        </span>
                                    </div>

                                    <!-- Pulse Indicator if currently at this status -->
                                    <div v-if="
                                        props.transfer.status === 'dispatched'
                                    " class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                    </div>
                                </div>

                                <!-- Received Status -->
                                <div class="relative">
                                    <div class="flex flex-col">
                                        <button @click="
                                            changeStatus(
                                                props.transfer.id,
                                                'received',
                                                'is_receive'
                                            )
                                            " :disabled="isType['is_receive'] ||
                                            props.transfer.status !==
                                            'delivered' ||
                                            !canReceive
                                            " :class="[
                                            props.transfer.status ===
                                                'delivered'
                                                ? 'bg-yellow-500 hover:bg-yellow-600'
                                                : statusOrder.indexOf(
                                                    props.transfer.status
                                                ) >
                                                    statusOrder.indexOf(
                                                        'delivered'
                                                    )
                                                    ? 'bg-green-500'
                                                    : 'bg-gray-300 cursor-not-allowed',
                                        ]"
                                            class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                                            <svg v-if="
                                                isType['is_receive'] &&
                                                props.transfer.status ===
                                                'delivered'
                                            " class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                    stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <template v-else>
                                                <img src="/assets/images/received.png" class="w-5 h-5 mr-2"
                                                    alt="Received" />
                                                <span class="text-sm font-bold text-white">
                                                    {{
                                                        statusOrder.indexOf(
                                                            props.transfer.status
                                                        ) >
                                                            statusOrder.indexOf(
                                                                "delivered"
                                                            )
                                                            ? "Received"
                                                            : isType["is_receive"]
                                                                ? "Please Wait..."
                                                                : props.transfer
                                                                    .status ===
                                                                    "delivered" &&
                                                                    !canReceive
                                                                    ? "Waiting to be received"
                                                                    : "Receive"
                                                    }}
                                                </span>
                                            </template>
                                        </button>
                                        <span v-show="props.transfer?.received_at" class="text-sm text-gray-600">
                                            On {{ moment(props.transfer?.received_at).format("DD/MM/YYYY HH:mm") }}
                                        </span>
                                        <span v-show="props.transfer?.received_by" class="text-sm text-gray-600">
                                            By
                                            {{ props.transfer?.received_by?.name }}
                                        </span>
                                    </div>

                                    <!-- Pulse Indicator if currently at this status -->
                                    <div v-if="props.transfer.status === 'delivered'"
                                        class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse">
                                    </div>
                                </div>
                            </div>

                            <!-- Status indicator for rejected status -->
                            <div v-if="props.transfer.status === 'rejected'"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-100 text-red-800 min-w-[160px]">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span class="text-sm font-bold">Rejected</span>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Order Modal -->
        <div v-if="showBackOrderModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-6xl mx-4 max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Back Order Details - Transfer #{{
                            props.transfer.transferID
                        }}
                    </h2>
                    <button @click="attemptCloseModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors duration-150">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="p-6">
                    <!-- Product Information -->
                    <div v-if="selectedItem" class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Product
                                </p>
                                <p class="text-sm text-gray-900">
                                    {{ selectedItem.product?.name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Product ID
                                </p>
                                <p class="text-sm text-gray-900">
                                    {{
                                        selectedItem.product?.productID
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Quantity to Release
                                </p>
                                <p class="text-sm text-gray-900">
                                    {{
                                        selectedItem.quantity_to_release
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Received Quantity
                                </p>
                                <p class="text-sm text-gray-900">
                                    {{
                                        selectedItem.received_quantity ||
                                        0
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Missing Quantity
                                </p>
                                <p class="text-sm font-bold text-red-600">
                                    {{ missingQuantity }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    Total Allocated
                                </p>
                                <p class="text-sm text-gray-900">
                                    {{ totalBatchBackOrderQuantity }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Single Batch Backorder Recording -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Record Missing Items for Batch {{ selectedAllocation.batch_number }}
                        </h3>

                        <!-- Error Message -->
                        <div v-if="message" class="mb-4 bg-red-50 border border-red-200 text-red-600 p-4 rounded">
                            {{ message }}
                        </div>

                        <!-- Batch Info -->
                        <div class="bg-gray-100 p-3 rounded-lg mb-3">
                            <h4 class="font-medium text-gray-800">
                                Batch: {{ selectedAllocation.batch_number }}
                                (Allocated: {{ selectedAllocation.allocated_quantity }}, 
                                Received: {{ selectedAllocation.received_quantity || 0 }}, 
                                Missing: {{ selectedAllocation.allocated_quantity - (selectedAllocation.received_quantity || 0) }})
                            </h4>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Quantity
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Status
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Note
                                        </th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(row, index) in batchBackOrders" :key="index">
                                        <td class="px-3 py-2">
                                            <input type="number" v-model="row.quantity"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                min="1" @input="validateBatchBackOrderQuantity(row, selectedAllocation)" />
                                        </td>
                                        <td class="px-3 py-2">
                                            <select v-model="row.status"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <option value="">Select Status...</option>
                                                <option value="Missing">Missing</option>
                                                <option value="Damaged">Damaged</option>
                                                <option value="Lost">Lost</option>
                                                <option value="Expired">Expired</option>
                                                <option value="Low Quality">Low Quality</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" v-model="row.notes"
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                placeholder="Add note..."
                                                 />
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex space-x-2">
                                                <button @click="removeBatchBackOrderRow(index)"
                                                    v-if="batchBackOrders.length > 1"
                                                    class="text-red-600 hover:text-red-800 transition-colors duration-150">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Add Row for this batch -->
                        <div class="mt-3">
                            <button @click="addBatchBackOrderRow(selectedAllocation.id)" v-if="canAddMoreToAllocation(selectedAllocation)"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                Add Row for Batch {{ selectedAllocation.batch_number }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-gray-200 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        💡 Tip: You can save each back order individually using the ✓ button, or save all at once.
                    </div>
                    <div class="flex space-x-3">
                        <button @click="attemptCloseModal"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors duration-150">
                            Exit
                        </button>
                        <button @click="saveBackOrders"
                            class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isSaving || !isCurrentAllocationComplete()">
                            <span v-if="isSaving">Saving...</span>
                            <span v-else>Save All & Exit</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Incomplete Back Order Warning Modal -->
        <div v-if="showIncompleteBackOrderModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md mx-4">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900">Incomplete Back Order</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-6">
                        Some batches have incomplete back orders where the differences don't match the missing quantities.
                        Are you sure you want to close without completing all back orders?
                    </p>
                    <div class="flex justify-end space-x-3">
                        <button @click="showIncompleteBackOrderModal = false"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors duration-150">
                            Continue Editing
                        </button>
                        <button @click="forceCloseModal"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-150">
                            Close Anyway
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showDispatchForm" @close="showDispatchForm = false">
            <div class="p-6 bg-white rounded-md shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    Dispatch Information
                </h2>

                <!-- Driver Name -->
                <div class="mb-4">
                    <label for="driver_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Driver Name
                    </label>
                    <input id="driver_name" type="text" v-model="dispatchForm.driver_name" required
                        placeholder="Enter driver name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <!-- Driver Phone Number -->
                <div class="mb-4">
                    <label for="driver_number" class="block text-sm font-medium text-gray-700 mb-1">
                        Driver Phone Number
                    </label>
                    <input id="driver_number" type="tel" v-model="dispatchForm.driver_number"
                        placeholder="Enter driver phone number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <!-- Vehicle Plate Number -->
                <div class="mb-4">
                    <label for="plate_number" class="block text-sm font-medium text-gray-700 mb-1">
                        Vehicle Plate Number
                    </label>
                    <input id="plate_number" type="text" v-model="dispatchForm.plate_number"
                        placeholder="Enter plate number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <!-- Number of Cartons -->
                <div class="mb-6">
                    <label for="no_of_cartoons" class="block text-sm font-medium text-gray-700 mb-1">
                        No. of Cartons
                    </label>
                    <input id="no_of_cartoons" type="number" min="0" v-model="dispatchForm.no_of_cartoons"
                        placeholder="Enter number of cartons"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <button @click="showDispatchForm = false" :disabled="isSaving"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button @click="createDispatch" :disabled="isSaving"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        {{ isSaving ? "Processing..." : "Save and Dispatch" }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import moment from "moment";
import Swal from "sweetalert2";
import BackOrder from "./BackOrder.vue";

const toast = useToast();
const page = usePage();

const props = defineProps({
    transfer: {
        type: Object,
        required: true,
    },
});

const form = ref([]);
const isLoading = ref(false);

// Quantity update state
const isUpdatingQuantity = ref({});
const updateQuantityTimeouts = ref({});

// Back order modal state
const showBackOrderModal = ref(false);
const selectedItem = ref(null);
const selectedAllocation = ref(null);
const batchBackOrders = ref([]);
const showIncompleteBackOrderModal = ref(false);
const isSaving = ref(false);
const message = ref('');

// dispatch info
const showDispatchForm = ref(false);

const dispatchForm = ref({
    driver_name: "",
    driver_number: "",
    plate_number: "",
    no_of_cartoons: "",
    transfer_id: props.transfer?.id,
    status: "Dispatched",
});

onMounted(() => {
    form.value = props.transfer.items || [];
    
    // Set up real-time listeners
    setupRealtimeListeners();
});

onUnmounted(() => {
    // Clean up real-time listeners
    if (window.Echo) {
        // Clean up facility-specific listeners
        if (props.transfer.from_facility_id) {
            window.Echo.leaveChannel(`private-facility-inventory.${props.transfer.from_facility_id}`);
        }
        
        // Clean up transfer status listeners
        window.Echo.leaveChannel(`private-transfer.${props.transfer.id}`);
        
        // Clean up general inventory listeners
        window.Echo.leaveChannel('private-inventory');
        
        console.log('Real-time listeners cleaned up');
    }
});

// Set up real-time listeners for facility inventory updates
const setupRealtimeListeners = () => {
    console.log('Setting up real-time listeners...');
    console.log('Transfer data:', {
        id: props.transfer.id,
        to_facility_id: props.transfer.to_facility_id,
        from_facility_id: props.transfer.from_facility_id,
        to_warehouse_id: props.transfer.to_warehouse_id,
        from_warehouse_id: props.transfer.from_warehouse_id
    });
    console.log('User facility ID:', page.props.auth.user.facility_id);
    
    // Listen for facility inventory updates (only from_facility_id where inventory changes happen)
    if (props.transfer.from_facility_id) {
        console.log('Listening for facility inventory updates for source facility:', props.transfer.from_facility_id);
        window.Echo.private(`private-facility-inventory.${props.transfer.from_facility_id}`)
            .listen('refresh', (data) => {
                console.log('Source facility inventory updated in real-time');
                
                // Show simple notification
                toast.info('Refreshed');
                
                // Refresh the page data
                router.get(route("transfers.show", props.transfer.id), {}, {
                    preserveScroll: true,
                    only: ['transfer'],
                });
            });
    } else {
        console.log('No from_facility_id found, skipping facility inventory listener');
    }
    
    // Listen for transfer status changes
    console.log('Listening for transfer status changes for transfer:', props.transfer.id);
    window.Echo.private(`transfer.${props.transfer.id}`)
        .listen('TransferStatusChanged', (data) => {
            console.log('Transfer status changed in real-time');
            
            // Show simple notification
            toast.info('Refreshed');
            
            // Refresh the page data
            router.get(route("transfers.show", props.transfer.id), {}, {
                preserveScroll: true,
                only: ['transfer'],
            });
        });
    
    // Listen for general inventory updates
    console.log('Listening for general inventory updates');
    window.Echo.private('private-inventory')
        .listen('refresh', (data) => {
            console.log('General inventory updated in real-time');
            
            // Show simple notification
            toast.info('Refreshed');
            
            // Refresh the page data
            router.get(route("transfers.show", props.transfer.id), {}, {
                preserveScroll: true,
                only: ['transfer'],
            });
        });
};

// Status styling
const statusClasses = computed(() => ({
    pending: "bg-yellow-100 text-yellow-800",
    approved: "bg-blue-100 text-blue-800",
    rejected: "bg-red-100 text-red-800",
    in_process: "bg-purple-100 text-purple-800",
    dispatched: "bg-orange-100 text-orange-800",
    delivered: "bg-indigo-100 text-indigo-800",
    received: "bg-green-100 text-green-800",
}));

const isReceived = ref([]);
const receivedQuantityTimeouts = ref({});

async function validateReceivedQuantity(allocation, allocIndex) {
    // Clear existing timeout for this allocation
    if (receivedQuantityTimeouts.value[allocIndex]) {
        clearTimeout(receivedQuantityTimeouts.value[allocIndex]);
    }

    // Set loading state
    isReceived.value[allocIndex] = true;

    // Validate the quantity
    if (allocation.received_quantity > allocation.allocated_quantity) {
        allocation.received_quantity = allocation.allocated_quantity;
    }

    // Set new timeout with 500ms delay for debouncing
    receivedQuantityTimeouts.value[allocIndex] = setTimeout(async () => {
        await axios.post(route("transfers.received-quantity"), {
            allocation_id: allocation.id,
            received_quantity: allocation.received_quantity,
            // Backend should handle:
            // 1. Update received_quantity for the allocation
            // 2. If allocated_quantity == received_quantity, DELETE ALL PackingListDifference records for this allocation_id
            // 3. Recalculate total back order quantity for the entire transfer
            // 4. This ensures no orphaned back order records exist when quantities are fully received
        })
        .then((response) => {
            console.log(response.data);
            isReceived.value[allocIndex] = false;
            router.get(route("transfers.show", props.transfer.id), {}, {
                preserveScroll: true,
                only: ['transfer'],
            });
        })
        .catch((error) => {
            isReceived.value[allocIndex] = false;
            toast.error(error.response?.data || "Failed to update received quantity");
            console.log(error);
        });
    }, 500);
}

function addBatchBackOrderRow(allocationId) {

    const allocation = selectedItem.value.inventory_allocations.find(allocation => allocation.id == allocationId);

    batchBackOrders.value.push({
        id: null,
        inventory_allocation_id: allocationId,
        batch_number: allocation.batch_number,
        barcode: allocation.barcode,
        quantity: 0,
        status: "Missing",
        notes: "",
        transfer_item_id: selectedItem.value.id,
    });

}

function validateBatchBackOrderQuantity(row, allocation) {
    // Calculate the missing quantity for this allocation
    const missingQuantity = allocation.allocated_quantity - (allocation.received_quantity || 0);
    
    // Calculate total from all other rows (excluding current row)
    const otherRowsTotal = batchBackOrders.value
        .filter(otherRow => otherRow !== row) // Exclude current row
        .reduce((sum, otherRow) => sum + Number(otherRow.quantity || 0), 0);
    
    // Calculate current row's quantity
    const currentRowQuantity = Number(row.quantity || 0);
    
    // Calculate total including current row
    const totalQuantity = otherRowsTotal + currentRowQuantity;
    
    
    // If total exceeds missing quantity, reset current row to remaining amount
    if (totalQuantity > missingQuantity) {
        const remainingQuantity = Math.max(0, missingQuantity - otherRowsTotal);
        row.quantity = remainingQuantity;
    }
}

function isCurrentAllocationComplete() {
    if (!selectedAllocation.value) return false;
    
    const allocation = selectedAllocation.value;
    const total = batchBackOrders.value.reduce((sum, row) => sum + Number(row.quantity || 0), 0);
    return (allocation.received_quantity + total) == allocation.allocated_quantity;
}

// Computed properties for back order modal
const missingQuantity = computed(() => {
    if (!selectedItem.value) return 0;
    
    // If a specific allocation is selected, calculate missing quantity for that allocation only
    if (selectedAllocation.value) {
        return selectedAllocation.value.allocated_quantity - (selectedAllocation.value.received_quantity || 0);
    }
    
    // Otherwise, calculate total missing quantity for all allocations of the item
    return selectedItem.value.inventory_allocations?.reduce((total, allocation) => {
        return total + (allocation.allocated_quantity - (allocation.received_quantity || 0));
    }, 0) || 0;
});

function canAddMoreToAllocation(allocation) {
    const total = batchBackOrders.value.reduce((sum, row) => sum + Number(row.quantity || 0), 0);
    return (allocation.received_quantity + total) < allocation.allocated_quantity;
}

const totalBatchBackOrderQuantity = computed(() => {
    let total = 0;
    batchBackOrders.value.forEach((row) => {
        total += Number(row.quantity || 0);
    });
    return total;
});

// Methods
const isExpiringItem = (expiryDate) => {
    if (!expiryDate) return false;
    const expiry = moment(expiryDate);
    const now = moment();
    const daysUntilExpiry = expiry.diff(now, "days");
    return daysUntilExpiry <= 30; // Consider items expiring within 30 days as expiring
};

const removeItem = (index) => {
    if (
        confirm("Are you sure you want to remove this item from the transfer?")
    ) {
        form.value.splice(index, 1);
        // TODO: Implement API call to remove item from transfer
        console.log("Removed item at index:", index);
    }
};

// update quantity
const isUpading = ref([]);

// Allocation-based quantity update functions
const handleQuantityInput = (event, allocation) => {
    // Clear existing timeout for this allocation
    if (updateQuantityTimeouts.value[allocation.id]) {
        clearTimeout(updateQuantityTimeouts.value[allocation.id]);
    }

    // Set new timeout with 500ms delay
    updateQuantityTimeouts.value[allocation.id] = setTimeout(() => {
        updateAllocationQuantity(event, allocation);
    }, 500);
};



const updateAllocationQuantity = async (event, allocation) => {
    const newQuantity = parseInt(event.target.value);
    
    if (!newQuantity || newQuantity <= 0) {
        toast.error("Please enter a valid quantity");
        // Reset input to original quantity
        event.target.value = allocation.allocated_quantity;
        return;
    }

    // Check if transfer is eligible for updates
    if (!['pending', 'reviewed'].includes(props.transfer.status)) {
        toast.error("Cannot update quantity for transfers that are not in pending status");
        // Reset input to original quantity
        event.target.value = allocation.allocated_quantity;
        return;
    }

    isUpdatingQuantity.value[allocation.id] = true;

    await axios.post(route("transfers.update-quantity"), {
        allocation_id: allocation.id,
        quantity: newQuantity,
    })
    .then(() => {
        isUpdatingQuantity.value[allocation.id] = false;
        
        // Reload the page to show updated values with preserveScroll
        router.get(route("transfers.show", props.transfer.id), {}, {preserveScroll: true});
    })
    .catch((error) => {
        isUpdatingQuantity.value[allocation.id] = false;
        console.error(error);
        toast.error(error.response?.data || "Failed to update quantity");
        // Reset input to original quantity on error
        event.target.value = allocation.allocated_quantity;
    });
};

// Functions for back order modal
const openBackOrderModal = (item, allocation = null) => {
    console.log('Opening back order modal for item:', item);
    console.log('Selected allocation:', allocation);

    showBackOrderModal.value = true;
    selectedItem.value = item;
    selectedAllocation.value = allocation;
    
    // Initialize batchBackOrders with existing differences for THIS SPECIFIC ALLOCATION
    batchBackOrders.value = [];
    
    // If item has existing differences, filter by the specific allocation
    if (item.differences && item.differences.length > 0) {
        console.log('Found existing differences:', item.differences);
        
        // Filter differences by the specific inventory_allocation_id
        const allocationDifferences = item.differences.filter(difference => 
            difference.inventory_allocation_id === allocation.id
        );
        
        console.log('Filtered differences for allocation:', allocation.id, allocationDifferences);
        
        allocationDifferences.forEach((difference) => {
            batchBackOrders.value.push({
                id: difference.id,
                transfer_item_id: item.id,
                inventory_allocation_id: difference.inventory_allocation_id,
                quantity: difference.quantity,
                status: difference.status,
                notes: difference.notes || '',
                isExisting: true
            });
        });
        
        console.log('Initialized batchBackOrders with filtered differences:', batchBackOrders.value);
    } else {
        console.log('No existing differences found, starting with empty form');
    }
};


// Save back orders
const saveBackOrders = async () => {
    console.log(batchBackOrders.value);
    message.value = "";  
    
    // Check if the specific batch mismatch is recorded
    if (!selectedAllocation.value) {
        message.value = "No allocation selected";
        return;
    }
    
    const allocation = selectedAllocation.value;
    const total = batchBackOrders.value.reduce((sum, row) => sum + Number(row.quantity || 0), 0);
    const missingQuantity = allocation.allocated_quantity - (allocation.received_quantity || 0);
    
    // Validate that all missing quantity is accounted for
    if (total !== missingQuantity) {
        message.value = `Batch ${allocation.batch_number} mismatch: Expected ${missingQuantity} but recorded ${total}. Please ensure all missing quantities are recorded.`;
        return;
    }
    
    // Validate that all rows have required fields
    const invalidRows = batchBackOrders.value.filter(row => 
        !row.quantity || row.quantity <= 0 || !row.status
    );
    
    if (invalidRows.length > 0) {
        message.value = "Please ensure all rows have valid quantity and status values.";
        return;
    }
    
    isSaving.value = true;
    await axios.post(route("transfers.save-back-orders"), {
        transfer_id: props.transfer.id,
        packing_list_differences: batchBackOrders.value,
    })
        .then((response) => {
            isSaving.value = false;
            console.log(response.data);
            toast.success("Back orders saved successfully");
            message.value = "";
            
            // Close the modal after successful save
            showBackOrderModal.value = false;
            showIncompleteBackOrderModal.value = false;
            
            // Refresh the page to show updated data (backend will handle recalculation)
            router.get(route("transfers.show", props.transfer.id), {}, {
                preserveScroll: true,
                only: ['transfer'],
            });
        })
        .catch((error) => {
            isSaving.value = false;
            console.log(error);
            message.value = error.response?.data || "Failed to save back orders";
        });
};

const attemptCloseModal = () => {
    if (!selectedAllocation.value) return;
    
    const allocation = selectedAllocation.value;
    const total = batchBackOrders.value.reduce((sum, row) => sum + Number(row.quantity || 0), 0);
    const missingQuantity = allocation.allocated_quantity - (allocation.received_quantity || 0);
    
    // If the total back order quantity equals the missing quantity, we can close the modal
    if (total >= missingQuantity) {
        showBackOrderModal.value = false;
        showIncompleteBackOrderModal.value = false;
    } else {
        // Show warning modal for incomplete back orders
        showIncompleteBackOrderModal.value = true;
    }
};

const forceCloseModal = () => {
    showBackOrderModal.value = false;
    showIncompleteBackOrderModal.value = false;
};


// Remove batch back order row
const removeBatchBackOrderRow = (index) => {
    batchBackOrders.value.splice(index, 1);
};

const isType = ref([]);
// Define status order for progression
const statusOrder = ref([
    "pending",
    "reviewed",
    "approved",
    "in_process",
    "dispatched",
    "delivered",
    "received",
]);

const canApprove = computed(() => {
    return page.props.auth.can?.transfer_approve || false;
});

const canDispatch = computed(() => {
    const auth = page.props.auth;
    return (
        auth.user.facility_id === props.transfer.from_facility_id &&
        auth.can.transfer_dispatch
    );
});

const canReceive = computed(() => {
    const auth = page.props.auth;
    return (
        auth.user.facility_id == props.transfer.to_facility_id &&
        auth.can.transfer_receive
    );
});

// Function to change transfer status
const changeStatus = (transferId, newStatus, type) => {
    // Get action name for better messaging
    const actionMap = {
        reviewed: "review",
        approved: "approve",
        in_process: "process",
        dispatched: "dispatch",
        delivered: "mark as delivered",
        received: "receive",
    };

    const actionName = actionMap[newStatus] || "change status of";

    Swal.fire({
        title: "Are you sure?",
        text: `Are you sure to make this Transfer ${newStatus.charAt(0).toUpperCase() +
            newStatus.slice(1).replace("_", " ")
            }?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: `Yes, ${actionName}!`,
    }).then(async (result) => {
        if (result.isConfirmed) {
            // Set loading state
            isType.value[type] = true;

            try {
                const response = await axios.post(
                    // route("transfers.change-status"),
                    route("transfers.changeItemStatus"),
                    {
                        transfer_id: transferId,
                        status: newStatus,
                    }
                );

                // Reset loading state
                isType.value[type] = false;

                Swal.fire({
                    title: "Success!",
                    text: `Transfer has been ${actionMap[newStatus] || "updated"
                        }d successfully.`,
                    icon: "success",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                }).then(() => {
                    // Reload the page to show the updated status
                    router.get(route("transfers.show", props.transfer.id));
                });
            } catch (error) {
                // Reset loading state
                isType.value[type] = false;

                // Extract error message from response
                let errorMessage = "Failed to update transfer status";

                if (error.response) {
                    if (error.response.status === 403) {
                        errorMessage =
                            error.response.data ||
                            "You don't have permission to perform this action";
                    } else if (error.response.status === 400) {
                        errorMessage =
                            error.response.data || "Invalid operation";
                    } else if (error.response.data) {
                        errorMessage = error.response.data;
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }

                Swal.fire({
                    title: "Error!",
                    text: errorMessage,
                    icon: "error",
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 5000, // Show error longer than success
                });
            }
        }
    });
};

async function createDispatch() {
    isSaving.value = true;
    await axios
        .post(route("transfers.dispatch-info"), dispatchForm.value)
        .then((response) => {
            isSaving.value = false;
            showDispatchForm.value = false;
            Swal.fire({
                title: "Success!",
                text: response.data,
                icon: "success",
                confirmButtonText: "OK",
            }).then(() => {
                router.get(route("transfers.show", props.transfer?.id));
            });
        })
        .catch((error) => {
            isSaving.value = false;
            console.log(error);
            toast.error(error.response?.data || "Failed to create dispatch");
        });
}

const isSavingQty = ref([]);
async function receivedQty(item, index) {
    isSavingQty.value[index] = true;
    // console.log(item, index);
    if (item.quantity_to_release < item.received_quantity) {
        item.received_quantity = item.quantity_to_release;
    }

    await axios
        .post(route("transfers.receivedQuantity"), {
            transfer_item_id: item.id,
            received_quantity: item.received_quantity,
        })
        .then((response) => {
            isSavingQty.value[index] = false;
        })
        .catch((error) => {
            console.log(error.response.data);
            isSavingQty.value[index] = false;
        });
    // 'orders.receivedQuantity
}

</script>
