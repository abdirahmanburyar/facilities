<template>
    <AuthenticatedLayout title="Tracks Your Orders" description="Keeping Essenticals Ready, Every Time"
        img="/assets/images/orders.png">
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
            <div class="col-span-2 mb-6">
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
            <h2 class="text-lg font-medium text-gray-900 mb-4">Order Items</h2>

            <table class="min-w-full border border-black border-collapse">
                <thead class="bg-blue-500">
                    <tr class="bg-gray-50">
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black">
                            Item
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black">
                            Category
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black">
                            AMC
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black">
                            No. of Days
                        </th>
                        <th class="px-2 py-2 text-left text-xs text-black capitalize border border-black">
                            Ordered Quantity
                        </th>
                        <th class="w-[150px] px-2 py-2 text-left text-xs text-black capitalize border border-black">
                            Quantity to release
                        </th>
                        <th class="px-2 py-2 text-center text-xs text-black capitalize border border-black">
                            Item Detail
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(item, index) in form" :key="item.id"
                        class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-3 py-3 text-xs text-gray-900 border border-black">
                            {{ item.product?.name }}
                        </td>
                        <td class="px-3 py-3 text-xs text-gray-900 border border-black">
                            {{ item.product?.category?.name }}
                        </td>
                        <td class="px-3 py-3 text-xs text-gray-900 border border-black">
                            <div class="flex flex-col space-y-1 text-xs">
                                <div class="flex">
                                    <span class="font-medium w-12">SOH:</span>
                                    <span>{{ item.soh }}</span>
                                </div>
                                <div class="flex">
                                    <span class="font-medium w-12">AMC:</span>
                                    <span>{{ item.amc || 0 }}</span>
                                </div>
                                <div class="flex">
                                    <span class="font-medium w-12">QOO:</span>
                                    <span>{{ item.quantity_on_order }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-sm text-gray-900 border border-black">
                            {{ item.no_of_days }}/30
                        </td>
                        <td class="px-3 py-3 text-lg text-center text-black border border-black">
                            {{ item.quantity }}
                        </td>
                        <td class="px-3 py-3 text-xs text-gray-900 border border-black">
                            <input type="number" placeholder="0" v-model="item.quantity_to_release" readonly
                                class="w-full rounded-md border border-gray-300 focus:border-orange-500 focus:ring-orange-500 sm:text-sm mb-1" />
                            <div>
                                <label>Received Quantity</label>
                                <input type="text" placeholder="0" v-model="item.received_quantity" :disabled="props.order.status !== 'delivered'
                                    " @input="validateReceivedQuantity(item)"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
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
                        <td class="text-xs text-gray-900 border border-black">
                            <table class="min-w-full border border-black">
                                <thead>
                                    <tr>
                                        <th class="text-xs border border-black px-2 py-1">
                                            QTY
                                        </th>
                                        <th class="text-xs border border-black px-2 py-1">
                                            Uom
                                        </th>
                                        <th class="text-xs border border-black px-2 py-1">
                                            Batch Number
                                        </th>
                                        <th class="text-xs border border-black px-2 py-1">
                                            Expiry Date
                                        </th>
                                        <th class="text-xs border border-black px-2 py-1">
                                            S. Location
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <tr v-for="inv in item.inventory_allocations" :key="inv.id"
                                        class="hover:bg-gray-100">
                                        <td class="px-2 py-1 text-xs border border-black">
                                            {{ inv.allocated_quantity }}
                                        </td>
                                        <td class="px-2 py-1 text-xs border border-black">
                                            {{ inv.uom }}
                                        </td>
                                        <td class="px-2 py-1 text-xs border border-black">
                                            {{ inv.batch_number }}
                                        </td>
                                        <td class="px-2 py-1 text-xs border border-black">
                                            {{
                                                moment(inv.expiry_date).format(
                                                    "DD/MM/YYYY"
                                                )
                                            }}
                                        </td>
                                        <td class="px-2 py-1 text-xs border border-black">
                                            <div class="flex flex-col">
                                                <span>WH:
                                                    {{ inv.warehouse?.name }}</span>
                                                <span>LC:
                                                    {{
                                                        inv.location?.location
                                                    }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- dispatch information -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-2">
                <div v-for="dispatch in props.order.dispatch" :key="dispatch.id" class="bg-white rounded-lg shadow-lg">
                    <div class="p-5">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Order #{{ dispatch.order_id }}
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
            </div>
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
                                    props.order.status === 'pending'
                                        ? 'bg-green-500 hover:bg-green-600'
                                        : statusOrder.indexOf(props.order.status) >
                                            statusOrder.indexOf('pending')
                                            ? 'bg-green-500'
                                            : 'bg-gray-300 cursor-not-allowed',
                                ]" class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]"
                                    disabled>
                                    <img src="/assets/images/pending.png" class="w-5 h-5 mr-2" alt="Pending" />
                                    <span class="text-sm font-bold text-white">Pending since {{
                                        moment(props.order.created_at).format('DD/MM/YYYY HH:mm') }}</span>
                                </button>
                            </div>
                            <span v-show="props.order?.user" class="text-sm text-gray-600">
                                By {{ props.order.user?.name || 'System' }}
                            </span>
                        </div>

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
                                            ? "Reviewed on" + moment(props.order.reviewed_at).format('DD/MM/YYYY HH:mm')
                                            : "Waiting to be Reviewed"
                                    }}</span>
                                </button>
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
                                            ? "Approved on" + moment(props.order.approved_at).format('DD/MM/YYYY HH:mm')
                                            : "Waiting to be Approved"
                                    }}</span>
                                </button>
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
                                            ? "Processed on" + moment(props.order.processed_at).format('DD/MM/YYYY HH:mm')
                                            : "Waiting to be Processed"
                                    }}</span>
                                </button>
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
                                            ? "Dispatched on" + moment(props.order.dispatched_at).format('DD/MM/YYYY HH:mm')
                                            : "Waiting to be Dispatched"
                                    }}</span>
                                </button>
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
                                    <button @click="changeStatus(props.order?.id, 'delivered', 'is_delivering')"
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
                                                    ? "Delivered on " + moment(props.order?.received_at).format('DD/MM/YYYY HH:mm')
                                                    :  isType['is_delivering'] ? 'Please Wait....' : "Mark as Delivered"
                                                        }}
                                        </span>
                                    </button>
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
                                                    ? "Received on " + moment(props.order?.received_at).format('DD/MM/YYYY HH:mm')
                                                    :  isType['is_receiving'] ? 'Please Wait....' : "Mark as Received"
                                                        }}
                                        </span>
                                    </button>
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
                                                <select v-model="row.type"
                                                    class="text-sm w-full border-gray-300 rounded-md shadow-sm"
                                                    :disabled="props.order.status == 'received'">
                                                    <option v-for="type in [
                                                        'Missing',
                                                        'Damaged',
                                                        'Expired',
                                                        'Lost',
                                                    ]" :key="type" :value="type">
                                                        {{ type }}
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
                                        : "Save Back Orders and Exit"
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
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, onMounted, ref, toRefs, h, watch } from "vue";
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
    if (!selectedItem.value || !selectedItem.value.backorders) return 0;
    return selectedItem.value.backorders.reduce(
        (total, bo) => total + Number(bo.quantity || 0),
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
        return rows.every((row) => row.quantity > 0 && row.type);
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

    // If there's no difference between quantity_to_release and received_quantity, no need for back orders
    if (
        item.quantity_to_release <= (item.received_quantity || 0) &&
        (!item.backorders || item.backorders.length === 0)
    ) {
        Swal.fire({
            title: "No Back Order Needed",
            text: "All quantities have been received. No back order is necessary.",
            icon: "info",
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
        });
        return;
    }

    // Check for back orders in different places and load them
    let hasExistingBackOrders = false;

    // 1. Check item.backorders (top-level back orders)
    if (item.backorders && item.backorders.length > 0) {
        hasExistingBackOrders = true;
        // Group back orders by inventory allocation
        item.backorders.forEach((backorder) => {
            const allocationId = backorder.inventory_allocation_id;
            if (!batchBackOrders.value[allocationId]) {
                batchBackOrders.value[allocationId] = [];
            }

            // Find the corresponding allocation for this back order
            const allocation = item.inventory_allocations.find(
                (alloc) => alloc.id === parseInt(allocationId)
            );

            // Add the back order with its ID for tracking
            batchBackOrders.value[allocationId].push({
                id: backorder.id, // Store the ID for editing/deleting
                inventory_allocation_id: allocationId,
                quantity: backorder.quantity,
                type: backorder.type || "Missing", // Use type instead of status
                notes: backorder.notes,
                batch_number: allocation?.batch_number || "",
                barcode: allocation?.barcode || "",
                isExisting: true, // Flag to indicate this is an existing back order
            });
        });
    }

    // 2. Check inventory_allocations.backorders (nested back orders)
    if (item.inventory_allocations && item.inventory_allocations.length > 0) {
        item.inventory_allocations.forEach((allocation) => {
            if (allocation.backorders && allocation.backorders.length > 0) {
                hasExistingBackOrders = true;

                // Make sure we have an array for this allocation
                if (!batchBackOrders.value[allocation.id]) {
                    batchBackOrders.value[allocation.id] = [];
                }

                // Add each back order from this allocation
                allocation.backorders.forEach((backorder) => {
                    // Check if we already added this back order (to avoid duplicates)
                    const alreadyAdded = batchBackOrders.value[
                        allocation.id
                    ].some((bo) => bo.id === backorder.id);

                    if (!alreadyAdded) {
                        batchBackOrders.value[allocation.id].push({
                            id: backorder.id,
                            inventory_allocation_id: allocation.id,
                            quantity: backorder.quantity,
                            type: backorder.type || "Missing", // Use type instead of status
                            notes: backorder.notes,
                            batch_number: allocation.batch_number || "",
                            barcode: allocation.barcode || "",
                            isExisting: true,
                        });
                    }
                });
            }
        });
    }
    // If no existing back orders or we still need more, pre-populate based on inventory allocations
    else if (
        item.inventory_allocations &&
        item.inventory_allocations.length > 0
    ) {
        // Calculate total missing quantity
        const missingQty =
            item.quantity_to_release - (item.received_quantity || 0);
        let remainingToAllocate = missingQty;

        // Distribute missing quantity across allocations proportionally
        item.inventory_allocations.forEach((allocation) => {
            if (remainingToAllocate > 0) {
                // Calculate how much to allocate to this batch (proportional to its size)
                const allocationRatio =
                    allocation.allocated_quantity / item.quantity_to_release;
                let batchMissingQty = Math.min(
                    Math.round(missingQty * allocationRatio), // Proportional amount
                    allocation.allocated_quantity, // Cannot exceed allocation
                    remainingToAllocate // Cannot exceed what's left to allocate
                );

                if (batchMissingQty > 0) {
                    // Add a back order for this allocation
                    const backOrders = getBatchBackOrders(allocation.id);
                    backOrders.push({
                        inventory_allocation_id: allocation.id,
                        quantity: batchMissingQty,
                        type: "Missing",
                        notes: "",
                        batch_number: allocation.batch_number,
                        barcode: allocation.barcode,
                        isExisting: false, // Flag to indicate this is a new back order
                    });

                    remainingToAllocate -= batchMissingQty;
                }
            }
        });
    }

    showBackOrderModal.value = true;
};

// Get back orders for a specific batch
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

    // Calculate total quantity already back-ordered for this allocation
    const totalBackOrdered = currentBackOrders.reduce(
        (sum, bo) => sum + Number(bo.quantity || 0),
        0
    );

    // Calculate remaining quantity to back order overall
    const remainingOverall =
        missingQuantity.value - totalBatchBackOrderQuantity.value;

    // Can add more if there's still quantity available in this allocation AND we need more back orders overall
    return (
        totalBackOrdered < allocation.allocated_quantity && remainingOverall > 0
    );
};

// Add a back order for a specific batch
const addBatchBackOrder = (allocation) => {
    const currentBackOrders = getBatchBackOrders(allocation.id);

    // Calculate total missing quantity (difference between quantity_to_release and received_quantity)
    const totalMissingQuantity = missingQuantity.value;

    // Calculate how much has already been allocated in all back orders
    const totalAlreadyAllocated = totalBatchBackOrderQuantity.value;

    // Calculate how much is still remaining to allocate
    const remainingToAllocate = totalMissingQuantity - totalAlreadyAllocated;

    // Only add if there's quantity that still needs to be allocated
    if (remainingToAllocate <= 0) {
        Swal.fire({
            title: "Cannot Add Issue",
            text: "All missing quantity has already been allocated to back orders.",
            icon: "warning",
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
        });
        return;
    }

    // Add a new back order row for this batch with a default quantity of the remaining to allocate
    // (user can adjust this as needed)
    currentBackOrders.push({
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
// Remove a back order for a specific batch
const removeBatchBackOrder = async (row, index) => {
    message.value = "";
    if (batchBackOrders.value[row.inventory_allocation_id]) {
        batchBackOrders.value[row.inventory_allocation_id].splice(index, 1);
        await axios
            .post(route("orders.remove-back-order"), {
                id: row.id,
            })
            .then((response) => {
                message.value = response.data;
            })
            .catch((error) => {
                message.value = error.response.data;
                console.log(error.response.data);
            });
    }
};

// Validate back order quantity for a specific batch
const validateBatchBackOrderQuantity = (row, allocation) => {
    // Ensure quantity is a number and within valid range
    const qty = Number(row.quantity);
    if (isNaN(qty) || qty <= 0) {
        row.quantity = 0;
        return;
    }

    // Get all back orders for this allocation
    const allocationBackOrders = getBatchBackOrders(allocation.id);

    // Calculate total back ordered for this allocation except this row
    const totalOtherRowsInAllocation = allocationBackOrders.reduce(
        (subtotal, backOrder) => {
            // Skip the current row being validated
            if (backOrder === row) return subtotal;
            return subtotal + Number(backOrder.quantity || 0);
        }, 0
    );

    // Calculate total back ordered for all allocations except this row
    const totalOtherRows = Object.values(batchBackOrders.value).reduce(
        (total, rows) => {
            return (
                total +
                rows.reduce((subtotal, backOrder) => {
                    // Skip the current row being validated
                    if (backOrder === row) return subtotal;
                    return subtotal + Number(backOrder.quantity || 0);
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
    // Check if there's a mismatch between back order quantity and missing quantity
    if (totalBatchBackOrderQuantity.value !== missingQuantity.value) {
        Swal.fire({
            title: "Cannot Save",
            text: `The total back order quantity (${totalBatchBackOrderQuantity.value}) must exactly match the missing quantity (${missingQuantity.value}).`,
            icon: "error",
            confirmButtonText: "OK",
        });
        return;
    }

    // Check if all back orders have valid data
    const allValid = Object.values(batchBackOrders.value).every((rows) => {
        return rows.every((row) => row.quantity > 0 && row.type);
    });

    if (!allValid) {
        Swal.fire({
            title: "Invalid Data",
            text: "All back orders must have a quantity greater than 0 and a valid issue type.",
            icon: "error",
            confirmButtonText: "OK",
        });
        return;
    }

    isSaving.value = true;

    // Prepare data for API
    const backOrderData = {
        order_item_id: selectedItem.value.id,
        received_quantity: selectedItem.value.received_quantity || 0,
        backorders: [],
        deleted_backorders: [],
    };

    // Process each back order row
    Object.entries(batchBackOrders.value).forEach(([allocationId, rows]) => {
        rows.forEach((row) => {
            // If this is an existing back order, include its ID
            if (row.isExisting && row.id) {
                backOrderData.backorders.push({
                    id: row.id, // Include ID for updating
                    inventory_allocation_id: allocationId,
                    quantity: row.quantity,
                    type: row.type,
                    notes: row.notes || null,
                });
            } else {
                // New back order
                backOrderData.backorders.push({
                    inventory_allocation_id: allocationId,
                    quantity: row.quantity,
                    type: row.type,
                    notes: row.notes || null,
                });
            }
        });
    });

    // Check for deleted back orders
    if (selectedItem.value.backorders) {
        // Find IDs of existing back orders that are no longer in the batchBackOrders
        const currentBackOrderIds = Object.values(batchBackOrders.value)
            .flat()
            .filter((row) => row.isExisting && row.id)
            .map((row) => row.id);

        // Find back orders that have been removed
        const deletedBackOrders = selectedItem.value.backorders
            .filter((bo) => !currentBackOrderIds.includes(bo.id))
            .map((bo) => bo.id);

        backOrderData.deleted_backorders = deletedBackOrders;
    }

    await axios
        .post(route("orders.backorder"), backOrderData)
        .then((response) => {
            isSaving.value = false;
            showBackOrderModal.value = false;

            Swal.fire({
                title: "Success!",
                text: response.data,
                icon: "success",
                toast: true,
                position: "top-end",
                showConfirmButton: false,
                timer: 3000,
            }).then(() => {
                // Close modal and reload page to show updated data
                // Use Inertia to visit the current page with fresh data
                router.visit(
                    route("orders.show", props.order.id),
                    {},
                    {
                        preserveScroll: true,
                        preserveState: false, // Don't preserve state to ensure fresh data
                        replace: true, // Replace current history entry instead of adding a new one
                    }
                );
            });
        })
        .catch((error) => {
            console.log(error.response);
            message.value = error.response?.data;
            isSaving.value = false;
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
    approved: "bg-green-100 text-green-800 rounded-full font-bold",
    "in process": "bg-blue-100 text-blue-800 rounded-full font-bold",
    dispatched: "bg-purple-100 text-purple-800 rounded-full font-bold",
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

// Validate received quantity to ensure it doesn't exceed quantity_to_release
const validateReceivedQuantity = (item) => {
    if (!item.quantity_to_release) return;

    // Convert to numbers for comparison
    const qtyToRelease = Number(item.quantity_to_release);
    let receivedQty = Number(item.received_quantity);

    // Ensure received quantity is a valid number
    if (isNaN(receivedQty)) {
        item.received_quantity = 0;
        return;
    }

    // Ensure received quantity doesn't exceed quantity_to_release
    if (receivedQty > qtyToRelease) {
        item.received_quantity = qtyToRelease;
        Swal.fire({
            title: "Quantity Adjusted",
            text: "Received quantity cannot exceed quantity to release.",
            icon: "warning",
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
        });
    }
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
            // Set loading state
            isType.value[type] = true;

            await axios
                .post(route("orders.change-status"), {
                    order_id: orderId,
                    status: newStatus,
                })
                .then((response) => {
                    isType.value[type] = false;
                    // Reset loading state
                    isLoading.value = false;
                    Swal.fire({
                        title: "Updated!",
                        text: response.data,
                        icon: "success",
                        toast: true,
                        position: "top-end",
                        showConfirmButton: true,
                        timer: 3000,
                    }).then(() => {
                        // Reload the page to show the updated status
                        router.get(route("orders.show", props.order.id), {}, {
                            preserveScroll: true,
                            preserveState: true,
                            only: [
                                'orders'
                            ]
                        });
                    });
                })
                .catch((error) => {
                    isType.value[type] = false;
                    console.log(error.response);
                    // Reset loading state
                    isLoading.value = false;

                    Swal.fire({
                        title: "Error!",
                        text:
                            error.response?.data ||
                            "Failed to update order status",
                        icon: "error",
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 3000,
                    });
                });
        }
    });
};

</script>
