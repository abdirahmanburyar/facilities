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

                <!-- Transfer Timeline -->

                <div class="col-span-2 mb-6 mt-5">
                    <div class="relative">
                        <!-- Timeline Track Background -->
                        <div class="absolute top-7 left-0 right-0 h-2 bg-gray-200 z-0"></div>

                        <!-- Timeline Progress -->
                        <div class="absolute top-7 left-0 h-2 bg-green-500 z-0 transition-all duration-500 ease-in-out"
                            :style="{
                                width: `${(statusOrder.indexOf(
                                    props.transfer.status
                                ) /
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
                <div class="mb-6 bg-white rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        Transfer Items
                    </h3>

                    <div class="overflow-auto">
                        <table class="min-w-full border border-collapse border-black">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th rowspan="2"
                                        class="min-w-[300px] px-2 py-1 text-xs border border-black text-left text-black font-semibold">
                                        Item Name
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-black text-left text-black font-semibold">
                                        Category
                                    </th>
                                    <th colspan="5"
                                        class="px-2 py-1 text-xs border border-black text-center text-black font-semibold">
                                        Item details
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-black text-left text-black font-semibold">
                                        Total Quantity on Hand Per Unit
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-black text-left text-black font-semibold">
                                        Reasons for Transfers
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-black text-left text-black font-semibold">
                                        Quantity to be transferred
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-black text-left text-black font-semibold">
                                        Received Quantity
                                    </th>
                                    <th rowspan="2"
                                        class="px-2 py-1 text-xs border border-black text-center text-black font-semibold">
                                        Action
                                    </th>
                                </tr>
                                <tr class="bg-gray-50">
                                    <th
                                        class="px-2 py-1 text-xs border border-black text-center text-black font-semibold">
                                        UoM
                                    </th>
                                    <th
                                        class="px-2 py-1 text-xs border border-black text-center text-black font-semibold">
                                        QTY
                                    </th>
                                    <th
                                        class="px-2 py-1 text-xs border border-black text-center text-black font-semibold">
                                        Batch Number
                                    </th>
                                    <th
                                        class="px-2 py-1 text-xs border border-black text-center text-black font-semibold">
                                        Expiry Date
                                    </th>
                                    <th
                                        class="px-2 py-1 text-xs border border-black text-center text-black font-semibold">
                                        Location
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <template v-for="(item, index) in form" :key="item.id">
                        <!-- Show allocations if they exist, otherwise show one row with main item data -->
                                <tr v-for="(allocation, allocIndex) in (item.inventory_allocations?.length > 0 ? item.inventory_allocations : [{}])" :key="`${item.id}-${allocIndex}`" class="hover:bg-gray-50 transition-colors duration-150 border-b border-black">
                                        <!-- Item Name -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-black text-left text-black align-top">
                                            <div class="font-medium">
                                                {{ item.product?.name }}
                                            </div>
                                            {{ item.quantity_to_release }}
                                        </td>

                                        <!-- Category -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-black text-left text-black align-top">
                                            {{ item.product?.category?.name }}
                                        </td>

                                        <!-- UoM -->
                                        <td class="px-2 py-1 text-xs border border-black text-center text-black">
                                            {{ item.uom || "N/A" }}
                                        </td>

                                        <!-- QTY -->
                                        <td class="px-2 py-1 text-xs border border-black text-center text-black">
                                            {{
                                                allocation.allocated_quantity ||
                                                0
                                            }}
                                        </td>

                                        <!-- Batch Number -->
                                        <td class="px-2 py-1 text-xs border border-black text-center text-black">
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
                                        <td class="px-2 py-1 text-xs border border-black text-center text-black">
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
                                        <td class="px-2 py-1 text-xs border border-black text-center text-black">
                                            {{
                                                allocation.warehouse?.name ||
                                                "N/A"
                                            }}
                                        </td>

                                        <!-- Total Quantity on Hand -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-black text-center text-black align-top">
                                            {{ item.quantity_per_unit || 0 }}
                                        </td>

                                        <!-- Reasons for Transfers -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-black text-center text-black align-top">
                                            <span :class="{
                                                'text-red-600':
                                                    allocation.batch_number ===
                                                    'HK5273',
                                            }">
                                                {{
                                                    props.transfer.transfer_type
                                                }}
                                            </span>
                                        </td>

                                        <!-- Quantity to be transferred -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-black text-center text-black align-top">
                                            <input type="number" v-model="item.quantity_to_release" @input="updateQuantity(item, index)"
                                                class="w-20 text-center border border-black rounded px-2 py-1 text-sm" />
                                            <span v-if="isUpading[index]" class="text-green-600">
                                                {{
                                                    isUpading[index]
                                                        ? "Updating..."
                                                        : ""
                                                }}
                                            </span>
                                        </td>

                                        <!-- Received Quantity -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            "
                                            class="px-2 py-1 text-xs border border-black text-center text-black align-top">
                                            <input type="number" v-model="item.received_quantity" @keyup.enter="
                                                receivedQty(item, index)
                                                " :max="item.quantity_to_release ||
                                                    0
                                                    " :readonly="props.transfer.to_facility_id !=
                                                    $page.props.auth?.user?.facility_id
                                                    " min="0" @input="
                                                    validateReceivedQuantity(
                                                        item
                                                    )
                                                    " :id="`received-quantity-${index}`"
                                                class="w-20 text-center border border-black rounded px-2 py-1 text-sm" />
                                            <span class="text-green-600">
                                                {{
                                                    isSavingQty[index]
                                                        ? "Updating..."
                                                        : ""
                                                }}
                                            </span>

                                            <button @click="
                                                openBackOrderModal(item)
                                                " v-if="
                                                    (item.quantity_to_release ||
                                                        0) >
                                                    (item.received_quantity ||
                                                        0)
                                                "
                                                class="text-xs text-orange-600 underline hover:text-orange-800 cursor-pointer mt-1 block">
                                                Back Order
                                            </button>
                                        </td>

                                        <!-- Action -->
                                        <td v-if="allocIndex === 0" :rowspan="item.inventory_allocations
                                                ?.length || 1
                                            " class="px-2 py-1 text-xs border border-black text-center align-top">
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

            <!-- Delivery Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-2">
                <div v-for="dispatch in props.transfer.dispatch_info" :key="dispatch.id"
                    class="bg-white rounded-lg shadow-lg">
                    <div class="p-5">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Transfer ID #{{ props.transfer.transferID }}
                            </h3>
                            <span class="text-sm text-gray-500">
                                {{
                                    new Date(
                                        dispatch.created_at
                                    ).toLocaleDateString()
                                }}
                            </span>
                        </div>

                        <!-- Driver Info -->
                        <div class="text-sm text-gray-600 space-y-1 mb-4">
                            <p>
                                <span class="font-medium text-gray-700">Driver:</span>
                                {{ dispatch.driver_name }}
                            </p>
                            <p>
                                <span class="font-medium text-gray-700">Phone:</span>
                                {{ dispatch.driver_number }}
                            </p>
                            <p>
                                <span class="font-medium text-gray-700">Plate #:</span>
                                {{ dispatch.plate_number }}
                            </p>
                        </div>

                        <!-- Dispatch Details -->
                        <div class="flex items-center justify-between">
                            <div class="text-sm">
                                <span class="text-gray-500">Cartons</span>
                                <div class="font-semibold text-gray-800">
                                    {{ dispatch.no_of_cartoons }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- actions -->
                <div class="mt-8 mb-6 px-6 py-6 bg-white rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">
                        Order Status Actions
                    </h3>
                    <div class="flex justify-center items-center mb-6">
                        <!-- Status Action Buttons -->
                        <div class="flex flex-wrap items-center justify-center gap-4">
                            <!-- Pending status indicator -->
                            <div class="relative">
                                <div class="flex flex-col">
                                    <button :class="[
                                        props.transfer.status === 'pending'
                                            ? 'bg-green-500 hover:bg-green-600'
                                            : statusOrder.indexOf(
                                                props.transfer.status
                                            ) > statusOrder.indexOf('pending')
                                                ? 'bg-green-500'
                                                : 'bg-gray-300 cursor-not-allowed',
                                    ]" class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]"
                                        disabled>
                                        <img src="/assets/images/pending.png" class="w-5 h-5 mr-2" alt="Pending" />
                                        <span class="text-sm font-bold text-white">Pending since
                                            {{
                                                moment(
                                                    props.transfer.created_at
                                                ).format("DD/MM/YYYY HH:mm")
                                            }}</span>
                                    </button>
                                </div>
                                <span v-show="props.transfer?.user" class="text-sm text-gray-600">
                                    By {{ props.transfer.user?.name || "System" }}
                                </span>
                            </div>
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
                                                ? "Reviewed on" +
                                                moment(
                                                    props.transfer.reviewed_at
                                                ).format("DD/MM/YYYY HH:mm")
                                                : isType["is_reviewing"]
                                                    ? "Please Wait..."
                                                    : props.transfer.status ===
                                                        "pending"
                                                        ? "Waiting to be reviewed"
                                                        : "Review"
                                        }}</span>
                                    </button>
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
                                                            ? "Received on " +
                                                            moment(
                                                                props.transfer
                                                                    .received_at
                                                            ).format(
                                                                "DD/MM/YYYY HH:mm"
                                                            )
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

                            <!-- Reject button (only available for pending status) -->
                            <div class="relative" v-if="props.transfer.status === 'pending'">
                                <button @click="
                                    changeStatus(
                                        props.transfer.id,
                                        'rejected',
                                        'is_reject'
                                    )
                                    " :disabled="isType['is_reject'] || isLoading"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white bg-red-600 hover:bg-red-700 min-w-[160px]">
                                    <svg v-if="isType['is_reject']" class="animate-spin h-5 w-5 mr-2"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4">
                                        </circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <template v-else>
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <span class="text-sm font-bold text-white">Reject</span>
                                    </template>
                                </button>
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

                    <!-- Instructions -->
                    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-sm font-medium text-blue-800">
                                Instructions
                            </h3>
                        </div>
                        <p class="text-sm text-blue-700">
                            Record the missing quantity by categorizing items as
                            Missing, Damaged, Lost, Expired, or Low Quality. You
                            can add multiple entries per batch to account for different
                            issue types. The total of all entries should equal
                            the missing quantity ({{ missingQuantity }}).
                        </p>
                    </div>

                    <!-- Batch-wise Backorder Recording -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Record Missing Items by Batch
                        </h3>

                        <!-- Error Message -->
                        <div v-if="message" class="mb-4 bg-red-50 border border-red-200 text-red-600 p-4 rounded">
                            {{ message }}
                        </div>

                        <!-- Batch Tables -->
                        <div v-for="(allocations, batchKey) in selectedItem.inventory_allocations" :key="batchKey"
                            class="mb-6">
                            <div class="bg-gray-100 p-3 rounded-lg mb-3">
                                <h4 class="font-medium text-gray-800">
                                    Batch: {{ allocations.batch_number }}
                                    (Available: {{ allocations.available_quantity }})
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
                                        <tr v-for="(row, index) in batchBackOrders[allocations.id]" :key="index">
                                            <td class="px-3 py-2">
                                                <input type="number" v-model="row.quantity"
                                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                    min="1" :max="allocations.available_quantity"
                                                    @input="validateBatchBackOrderQuantities" />
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
                                                    placeholder="Add note..." />
                                            </td>
                                            <td class="px-3 py-2">
                                                <button @click="removeBatchBackOrderRow(allocations.id, index)"
                                                    v-if="batchBackOrders[allocations.id].length > 1"
                                                    class="text-red-600 hover:text-red-800 transition-colors duration-150">
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

                            <!-- Add Row for this batch -->
                            <div class="mt-3">
                                <button @click="addBatchBackOrderRow(allocations.id)"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    Add Row for Batch {{ allocations.batch_number }}
                                </button>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div class="mt-6 flex justify-between items-center bg-gray-50 p-4 rounded-lg">
                            <div class="text-sm">
                                <span class="font-medium text-gray-900">{{ totalBatchBackOrderQuantity }}</span>
                                <span class="text-gray-600"> / {{ missingQuantity }} items recorded</span>
                            </div>

                            <!-- Status indicator -->
                            <div class="text-sm">
                                <span v-if="remainingToAllocate <= 0" class="text-green-600 font-medium">
                                    ✓ All missing items recorded
                                </span>
                                <span v-else class="text-yellow-600 font-medium">
                                    {{ remainingToAllocate }} items remaining
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button @click="attemptCloseModal"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors duration-150">
                        Exit
                    </button>
                    <button @click="saveBackOrders"
                        class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isSaving || totalBatchBackOrderQuantity !== missingQuantity">
                        <span v-if="isSaving">Saving...</span>
                        <span v-else>Save Back Orders and Exit</span>
                    </button>
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
                        You have {{ remainingToAllocate }} items that haven't been allocated to any back order status.
                        Are you sure you want to close without completing the back order?
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
import { ref, computed, onMounted } from "vue";
import { router, Head, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import moment from "moment";
import axios from "axios";
import Swal from "sweetalert2";
import { useToast } from "vue-toastification";
import Modal from "@/Components/Modal.vue";
import debounce from 'lodash.debounce';

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


// Back order modal state
const showBackOrderModal = ref(false);
const selectedItem = ref(null);
const batchBackOrders = ref({});
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
});

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

// Computed properties for table functionality
const showReceivedColumn = computed(() => {
    return ["delivered", "received"].includes(props.transfer.status);
});

const showActionsColumn = computed(() => {
    return ["delivered", "received"].includes(props.transfer.status);
});

const canEditReceivedQuantity = computed(() => {
    return props.transfer.status === "delivered";
});

const totalOrderedQuantity = computed(() => {
    return (
        props.transfer.items?.reduce(
            (total, item) => total + (item.quantity || 0),
            0
        ) || 0
    );
});

const totalReceivedQuantity = computed(() => {
    return (
        props.transfer.items?.reduce(
            (total, item) => total + (item.received_quantity || 0),
            0
        ) || 0
    );
});

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

// Debounced version of the axios call
const debouncedUpdateQuantityApi = debounce(async (item, index, resolve, reject) => {
    await axios
        .post(route("transfers.update-quantity"), {
            item_id: item.id,
            quantity: item.quantity_to_release,
        })
        .then((response) => {
            resolve(response);
        })
        .catch((error) => {
            reject(error);
        });
}, 500);

async function updateQuantity(item, index) {
    isUpading.value[index] = true;
    return new Promise((resolve, reject) => {
        debouncedUpdateQuantityApi(item, index, (response) => {
            isUpading.value[index] = false;
            Swal.fire({
                title: "Success!",
                text: response.data,
                icon: "success",
                confirmButtonText: "OK",
            }).then(() => {
                router.get(route("transfers.show", props.transfer.id), {}, {
                    preserveScroll: true,
                    only: ['transfer'],
                });
            });
            resolve(response);
        }, (error) => {
            isUpading.value[index] = false;
            console.log(error);
            toast.error(error.response?.data || "Failed to update quantity");
            reject(error);
        });
    });
}

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

const validateReceivedQuantity = (item) => {
    if (item.received_quantity > item.quantity_to_release) {
        item.received_quantity = item.quantity_to_release;
    }
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

// Remove a difference for a specific batch
const removeBatchBackOrder = async (row, index) => {
    message.value = "";
    if (batchBackOrders.value[row.inventory_allocation_id]) {
        batchBackOrders.value[row.inventory_allocation_id].splice(index, 1);
    }
};

// Validate batch back order quantity
const validateBatchBackOrderQuantity = (row, allocation) => {
    const currentQuantity = Number(row.quantity || 0);
    const allocationDifferences = getBatchBackOrders(allocation.id);

    // Calculate total quantity for this allocation (excluding current row)
    const totalOtherRows = Object.values(batchBackOrders.value).reduce(
        (total, rows) => {
            return total + rows.reduce((rowTotal, r) => {
                if (r.inventory_allocation_id === allocation.id && r !== row) {
                    return rowTotal + Number(r.quantity || 0);
                }
                return rowTotal;
            }, 0);
        },
        0
    );

    // Check if total exceeds allocation quantity
    if (totalOtherRows + currentQuantity > allocation.allocated_quantity) {
        row.quantity = Math.max(0, allocation.allocated_quantity - totalOtherRows);
        toast.warning(
            `Quantity adjusted to fit within allocation limit (${allocation.allocated_quantity})`
        );
    }

    // Check if total exceeds missing quantity
    const totalAllDifferences = totalBatchBackOrderQuantity.value;
    if (totalAllDifferences > missingQuantity.value) {
        const excess = totalAllDifferences - missingQuantity.value;
        row.quantity = Math.max(0, currentQuantity - excess);
        toast.warning(
            `Quantity adjusted to match missing quantity (${missingQuantity.value})`
        );
    }
};



// Save back orders
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
        transfer_item_id: selectedItem.value.id,
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
        .post(route("transfers.backorder"), differenceData)
        .then((response) => {
            isSaving.value = false;
            showBackOrderModal.value = false;
            toast.success(response.data || "Differences saved successfully");
            router.visit(
                route("transfers.show", props.transfer.id),
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

const forceCloseModal = () => {
    showBackOrderModal.value = false;
    showIncompleteBackOrderModal.value = false;
};

// Add batch back order row
const addBatchBackOrderRow = (allocationId) => {
    if (!batchBackOrders.value[allocationId]) {
        batchBackOrders.value[allocationId] = [];
    }
    batchBackOrders.value[allocationId].push({
        quantity: 0,
        status: "",
        notes: "",
        isExisting: false,
    });
};

// Remove batch back order row
const removeBatchBackOrderRow = (allocationId, index) => {
    if (batchBackOrders.value[allocationId]) {
        batchBackOrders.value[allocationId].splice(index, 1);
    }
};

// Validate batch back order quantities
const validateBatchBackOrderQuantities = () => {
    // Check if total exceeds missing quantity
    const totalAllDifferences = totalBatchBackOrderQuantity.value;
    if (totalAllDifferences > missingQuantity.value) {
        const excess = totalAllDifferences - missingQuantity.value;
        // Find the last modified row and adjust it
        Object.values(batchBackOrders.value).forEach((rows) => {
            rows.forEach((row) => {
                if (row.quantity > excess) {
                    row.quantity = Math.max(0, row.quantity - excess);
                    return;
                }
            });
        });
        toast.warning(
            `Quantity adjusted to match missing quantity (${missingQuantity.value})`
        );
    }
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
