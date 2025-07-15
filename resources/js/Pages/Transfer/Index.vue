<template>
    <AuthenticatedLayout title="Optimize Your Transfers" description="Moving Supplies, Bridging needs"
        img="/assets/images/transfer.png">
        
        <!-- Transfer Direction Tabs (Very Top Level) -->
        <div class="mb-8">
            <nav class="flex space-x-1 bg-gray-100 p-1 rounded-xl">
                <button v-for="tab in transferDirectionTabs" :key="tab.value" @click="currentDirectionTab = tab.value"
                    class="relative whitespace-nowrap py-3 px-6 font-semibold text-lg flex items-center gap-3 rounded-lg transition-all duration-300 ease-in-out" :class="[
                        currentDirectionTab === tab.value
                            ? 'bg-white text-blue-600 shadow-lg shadow-blue-100 ring-1 ring-blue-200'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-white/50',
                    ]">
                    <!-- Tab Icons -->
                    <div class="flex items-center justify-center w-6 h-6 rounded-full transition-all duration-300" :class="[
                        currentDirectionTab === tab.value 
                            ? 'bg-blue-100' 
                            : 'bg-gray-200 group-hover:bg-gray-300'
                    ]">
                        <svg v-if="tab.value === 'in'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                        <svg v-else-if="tab.value === 'out'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                        </svg>
                    </div>
                    
                    <span class="tracking-wide">{{ tab.label }}</span>
                    
                    <!-- Active indicator -->
                    <div v-if="currentDirectionTab === tab.value" 
                        class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-blue-600 rounded-full">
                    </div>
                </button>
            </nav>
        </div>

        <!-- Header Section -->
        <div class="flex flex-col space-y-6">
            <!-- Buttons First -->
            <div class="flex items-center justify-end">
                <!-- New Transfer -->
                <button @click="router.visit(route('transfers.create'))"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    New Transfer
                </button>
            </div>

            <!-- Filters Section (Direction-specific filters can be added here) -->
            <div class="mb-4">
                <div class="grid grid-cols-4 gap-3">
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" v-model="search"
                            class="pl-10 pr-4 py-2 border border-gray-300 w-full focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Search a Transfer" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>

                    <!-- warehouse or facility selection -->
                    <div>
                        <Multiselect v-model="transfer_type" :options="facilityType" :searchable="true"
                            :allow-empty="true" :show-labels="false" placeholder="All Transfer Type" class="">
                        </Multiselect>
                    </div>

                    <!-- Facility Selector -->
                    <div>
                        <Multiselect v-model="facility" :options="props.facilities" :searchable="true" :allow-empty="true"
                            :show-labels="false" placeholder="All Facilities" class="">
                        </Multiselect>
                    </div>

                    <!-- Warehouse Selector -->
                    <div>
                        <Multiselect v-model="warehouse" :options="props.warehouses" :close-on-select="true"
                            :clear-on-select="false" :preserve-search="true" :show-labels="false" placeholder="All Warehouses" class=""
                            :preselect-first="false">
                        </Multiselect>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full mt-2">
                    <input type="date" v-model="date_from"
                        class="border border-gray-300 w-full focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        @change="date_to = null"
                        placeholder="From Date" />
                        <span>To</span>
                    <input type="date" v-model="date_to"
                        class="border border-gray-300 w-full focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        :min="date_from"
                        placeholder="To Date" />
                </div>

                <div class="flex justify-end items-center gap-2 mt-2">
                    <select class="rounded-3xl" name="per_page" id="per_page" @change="props.filters.page = 1"
                        v-model="per_page">
                        <option value="2">2 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                    <!-- Icon Legend Button -->
                    <button
                        @click="showIconLegend = true"
                        class="flex items-center justify-center w-10 h-10 bg-blue-50 text-blue-700 rounded-full hover:bg-blue-100 transition-colors duration-200 shadow"
                        aria-label="Show Icon Legend"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Status Tabs (Second Level) -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button v-for="tab in statusTabs" :key="tab.value" @click="currentTab = tab.value"
                        class="whitespace-nowrap py-4 px-3 border-b-4 font-bold text-xs" :class="[
                            currentTab === tab.value
                                ? 'border-green-500 text-green-600'
                                : 'border-transparent text-black hover:text-gray-700 hover:border-gray-300',
                        ]">
                        {{ tab.label }}
                    </button>
                </nav>
            </div>
        </div>

        <!-- Icon Legend Slideover -->
        <div
            v-if="showIconLegend"
            class="fixed inset-0 overflow-hidden z-50"
            aria-labelledby="slide-over-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 overflow-hidden">
                <!-- Background overlay -->
                <div
                    class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="showIconLegend = false"
                ></div>

                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex sm:pl-16">
                    <div class="w-screen max-w-md">
                        <div class="h-full flex flex-col bg-white shadow-xl">
                            <!-- Header -->
                            <div class="px-4 py-6 bg-blue-50 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-medium text-blue-900" id="slide-over-title">
                                        Transfer Status Icons Legend
                                    </h2>
                                    <button
                                        @click="showIconLegend = false"
                                        class="rounded-md text-blue-400 hover:text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <span class="sr-only">Close panel</span>
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="relative flex-1 px-4 sm:px-6 overflow-y-auto">
                                <div class="space-y-6 py-6">
                                    <div class="text-sm text-gray-600 mb-4">
                                        <p>These icons represent the current status of each transfer in the workflow:</p>
                                    </div>
                                    
                                    <!-- Icon Legend Items -->
                                    <div class="space-y-4">
                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                            <img src="/assets/images/pending.png" class="w-8 h-8" alt="Pending" />
                                            <div>
                                                <h3 class="font-medium text-gray-900">Pending</h3>
                                                <p class="text-sm text-gray-600">Transfer has been submitted and is awaiting approval</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                            <img src="/assets/images/approved.png" class="w-8 h-8" alt="Approved" />
                                            <div>
                                                <h3 class="font-medium text-gray-900">Approved</h3>
                                                <p class="text-sm text-gray-600">Transfer has been approved for processing</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                            <img src="/assets/images/inprocess.png" class="w-8 h-8" alt="In Process" />
                                            <div>
                                                <h3 class="font-medium text-gray-900">In Process</h3>
                                                <p class="text-sm text-gray-600">Transfer is being prepared and processed</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                            <img src="/assets/images/dispatch.png" class="w-8 h-8" alt="Dispatched" />
                                            <div>
                                                <h3 class="font-medium text-gray-900">Dispatched</h3>
                                                <p class="text-sm text-gray-600">Transfer has been dispatched for delivery</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                            <img src="/assets/images/delivery.png" class="w-8 h-8" alt="Delivered" />
                                            <div>
                                                <h3 class="font-medium text-gray-900">Delivered</h3>
                                                <p class="text-sm text-gray-600">Transfer has been delivered to destination</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                            <img src="/assets/images/received.png" class="w-8 h-8" alt="Received" />
                                            <div>
                                                <h3 class="font-medium text-gray-900">Received</h3>
                                                <p class="text-sm text-gray-600">Transfer has been received and confirmed by destination</p>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                            <img src="/assets/images/rejected.png" class="w-8 h-8" alt="Rejected" />
                                            <div>
                                                <h3 class="font-medium text-gray-900">Rejected</h3>
                                                <p class="text-sm text-gray-600">Transfer has been rejected and will not proceed</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Transfer Workflow Information -->
                                    <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                                        <h3 class="font-medium text-blue-900 mb-2">Transfer Workflow</h3>
                                        <p class="text-sm text-blue-800">
                                            Transfers progress through these stages sequentially. Each icon represents a completed stage in the process.
                                        </p>
                                    </div>

                                    <!-- Transfer Types Information -->
                                    <div class="mt-6 p-4 bg-green-50 rounded-lg">
                                        <h3 class="font-medium text-green-900 mb-2">Transfer Types</h3>
                                        <div class="text-sm text-green-800 space-y-2">
                                            <p><strong>Warehouse to Warehouse:</strong> Transfers between different warehouse locations</p>
                                            <p><strong>Facility to Facility:</strong> Transfers between different healthcare facilities</p>
                                            <p><strong>Facility to Warehouse:</strong> Returns from facilities to warehouse</p>
                                            <p><strong>Warehouse to Facility:</strong> Supplies sent from warehouse to facilities</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-12 gap-1 mb-[40px]">
            <!-- Table Section (9 cols) -->
            <div class="md:col-span-9 sm:col-span-12 mt-3">
                <div class="overflow-auto">
                    <table class="w-full table-sm">
                        <thead style="background-color: #F4F7FB;">
                            <tr>
                                <th
                                    class="px-2 py-2 text-left text-xs font-bold uppercase border-b rounded-tl-lg"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;"
                                >
                                    Transfer ID
                                </th>
                                <th
                                    class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;"
                                >
                                    From
                                </th>
                                <th
                                    class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;"
                                >
                                    To
                                </th>
                                <th
                                    class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;"
                                >
                                    Transfer Type
                                </th>
                                <th
                                    class="px-2 py-2 text-left text-xs font-bold uppercase border-b"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;"
                                >
                                    Items
                                </th>
                                <th
                                    class="px-2 py-2 text-left text-xs font-bold uppercase border-b rounded-tr-lg"
                                    style="color: #4F6FCB; border-bottom: 2px solid #B7C6E6;"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr v-if="props.transfers.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-2 py-2 text-center text-sm text-gray-600 border-b"
                                    style="border-bottom: 1px solid #B7C6E6;"
                                >
                                    No transfers found
                                </td>
                            </tr>
                            <tr
                                v-for="transfer in props.transfers.data"
                                :key="transfer.id"
                                class="border-b"
                                :class="{
                                    'hover:bg-gray-50': true,
                                    'text-red-500':
                                        transfer.status === 'rejected',
                                }"
                                style="border-bottom: 1px solid #B7C6E6;"
                            >
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    <Link :href="route('transfers.show', transfer.id)">{{ transfer.transferID }}</Link>
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-600 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{
                                        moment(transfer.transfer_date).format('DD/MM/YYYY')
                                    }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{
                                        transfer.from_warehouse?.name ||
                                        transfer.from_facility?.name
                                    }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{
                                        transfer.to_warehouse?.name ||
                                        transfer.to_facility?.name
                                    }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-600 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{ transfer.transfer_type }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-600 border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    {{ transfer.items_count }}
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap border-b" style="border-bottom: 1px solid #B7C6E6;">
                                    <div class="flex items-center gap-2">
                                        <!-- Status Progress Icons - Only show actions taken -->
                                        <div class="flex items-center gap-2">
                                            <!-- Show status progression up to current status - icons with labels -->
                                            <!-- Always show pending as it's the initial state -->
                                            <div class="flex items-center gap-1">
                                                <img src="/assets/images/pending.png" class="w-8 h-8" alt="Pending"
                                                    title="Pending" />
                                            </div>

                                            <!-- Show reviewed if status is reviewed or further -->
                                            <template v-if="
                                                [
                                                    'reviewed',
                                                    'approved',
                                                    'in_process',
                                                    'dispatched',
                                                    'transferred',
                                                    'delivered',
                                                    'received',
                                                ].includes(
                                                    transfer.status?.toLowerCase()
                                                )
                                            ">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/review.png" class="w-8 h-8"
                                                        alt="Reviewed" title="Reviewed" />
                                                </div>
                                            </template>

                                            <!-- Show approved if status is approved or further -->
                                            <template v-if="
                                                [
                                                    'approved',
                                                    'in_process',
                                                    'dispatched',
                                                    'transferred',
                                                    'delivered',
                                                    'received',
                                                ].includes(
                                                    transfer.status?.toLowerCase()
                                                )
                                            ">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/approved.png" class="w-8 h-8"
                                                        alt="Approved" title="Approved" />
                                                </div>
                                            </template>

                                            <!-- Show processed if status is in_process or further -->
                                            <template v-if="
                                                [
                                                    'in_process',
                                                    'dispatched',
                                                    'transferred',
                                                    'delivered',
                                                    'received',
                                                ].includes(
                                                    transfer.status?.toLowerCase()
                                                )
                                            ">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/inprocess.png" class="w-8 h-8"
                                                        alt="Processed" title="Processed" />
                                                </div>
                                            </template>

                                            <!-- Show dispatched if status is dispatched or further -->
                                            <template v-if="
                                                [
                                                    'dispatched',
                                                    'transferred',
                                                    'delivered',
                                                    'received',
                                                ].includes(
                                                    transfer.status?.toLowerCase()
                                                )
                                            ">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/dispatch.png" class="w-8 h-8"
                                                        alt="Dispatched" title="Dispatched" />
                                                </div>
                                            </template>

                                            <!-- Show delivered if status is delivered or further -->
                                            <template v-if="
                                                [
                                                    'delivered',
                                                    'received',
                                                ].includes(
                                                    transfer.status?.toLowerCase()
                                                )
                                            ">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/delivery.png" class="w-8 h-8"
                                                        alt="Delivered" title="Delivered" />
                                                </div>
                                            </template>

                                            <!-- Show received if status is received -->
                                            <template v-if="
                                                ['received'].includes(
                                                    transfer.status?.toLowerCase()
                                                )
                                            ">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/received.png" class="w-8 h-8"
                                                        alt="Received" title="Received" />
                                                </div>
                                            </template>

                                            <!-- Show rejected if status is rejected (special case) -->
                                            <template v-if="
                                                transfer.status?.toLowerCase() ===
                                                'rejected'
                                            ">
                                                <div class="flex items-center gap-1">
                                                    <img src="/assets/images/rejected.png" class="w-8 h-8"
                                                        alt="Rejected" title="Rejected" />
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 flex justify-end items-center">
                    <TailwindPagination :data="props.transfers" :limit="2" @pagination-change-page="getResults" />
                </div>
            </div>

            <!-- Statistics Section (3 cols) -->
            <div class="md:col-span-3 sm:col-span-12">
                <div class="bg-white mb-4">
                    <h3 class="text-xs text-black mb-4">Transfer Statistics</h3>
                    <div class="flex justify-between gap-1">
                        <!-- Pending -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-amber-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/pending_small.png" class="h-8 w-8 object-contain"
                                        alt="Pending" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-amber-500 to-amber-400 transition-all duration-500"
                                    :style="{
                                        height: `${(pendingCount / totalCount) * 100}%`,
                                    }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ Math.round((pendingCount / totalCount) * 100) }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reviewed -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-blue-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/review.png" class="h-8 w-6 object-contain"
                                        alt="Reviewed" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-blue-600 to-blue-400 transition-all duration-500"
                                    :style="{
                                        height: `${(reviewedCount / totalCount) * 100}%`,
                                    }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ Math.round((reviewedCount / totalCount) * 100) }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Approved -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-blue-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/approved_small.png" class="h-8 w-8 object-contain"
                                        alt="Approved" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-blue-600 to-blue-400 transition-all duration-500"
                                    :style="{
                                        height: `${(approvedCount / totalCount) * 100}%`,
                                    }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ Math.round((approvedCount / totalCount) * 100) }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- In Process -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-slate-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/inprocess.png" class="h-8 w-8 object-contain"
                                        alt="In Process" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-slate-600 to-slate-400 transition-all duration-500"
                                    :style="{
                                        height: `${(inProcessCount / totalCount) * 100}%`,
                                    }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ Math.round((inProcessCount / totalCount) * 100) }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dispatched -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-purple-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/dispatch.png" class="h-8 w-8 object-contain"
                                        alt="Dispatched" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-purple-600 to-purple-400 transition-all duration-500"
                                    :style="{
                                        height: `${(dispatchedCount / totalCount) * 100}%`,
                                    }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ Math.round((dispatchedCount / totalCount) * 100) }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delivered -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-orange-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/delivery.png" class="h-8 w-8 object-contain"
                                        alt="Delivered" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-orange-600 to-orange-400 transition-all duration-500"
                                    :style="{
                                        height: `${(deliveredCount / totalCount) * 100}%`,
                                    }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ Math.round((deliveredCount / totalCount) * 100) }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Received -->
                        <div class="flex flex-col items-center">
                            <div class="h-[250px] w-8 bg-emerald-50 relative overflow-hidden shadow-md rounded-2xl">
                                <div class="absolute top-0 inset-x-0 flex justify-center pt-2">
                                    <img src="/assets/images/received.png" class="h-8 w-8 object-contain"
                                        alt="Received" />
                                </div>
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-emerald-600 to-emerald-400 transition-all duration-500"
                                    :style="{
                                        height: `${(receivedCount / totalCount) * 100}%`,
                                    }">
                                    <div
                                        class="absolute bottom-0 left-0 right-0 text-center py-1 text-black font-bold text-xs tracking-wide">
                                        {{ Math.round((receivedCount / totalCount) * 100) }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import {TailwindPagination} from "laravel-vue-pagination";
import moment from "moment";

const props = defineProps({
    transfers: Object,
    filters: Object,
    facilities: Array,
    warehouses: Array,
});

// Transfer direction tabs (facilities version - simpler)
const transferDirectionTabs = ref([
    { value: 'in', label: 'Incoming' },
    { value: 'out', label: 'Outgoing' },
]);

const currentDirectionTab = ref('in');

// Status tabs
const statusTabs = ref([
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'reviewed', label: 'Reviewed' },
    { value: 'approved', label: 'Approved' },
    { value: 'in_process', label: 'In Process' },
    { value: 'dispatched', label: 'Dispatched' },
    { value: 'received', label: 'Received' },
    { value: 'rejected', label: 'Rejected' },
]);

const currentTab = ref('all');

// Icon Legend state
const showIconLegend = ref(false);

// Filter states
const search = ref(props.filters.search || '');
const transfer_type = ref(props.filters.transfer_type || '');
const facility = ref(props.filters.facility || '');
const warehouse = ref(props.filters.warehouse || '');
const date_from = ref(props.filters.date_from || '');
const date_to = ref(props.filters.date_to || '');
const per_page = ref(props.filters.per_page || '25');

// Transfer type options
const facilityType = ref([
    'Facility to Warehouse',
    'Warehouse to Facility',
    'Facility to Facility',
    'Warehouse to Warehouse'
]);

// Computed properties for statistics (facilities version - client-side calculation)
const totalCount = computed(() => props.transfers.total || 0);
const pendingCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'pending').length || 0;
});
const reviewedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'reviewed').length || 0;
});
const approvedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'approved').length || 0;
});
const inProcessCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'in_process').length || 0;
});
const dispatchedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'dispatched').length || 0;
});
const deliveredCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'delivered').length || 0;
});
const receivedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'received').length || 0;
});
const rejectedCount = computed(() => {
    return props.transfers.data?.filter(t => t.status === 'rejected').length || 0;
});

// Watch for filter changes
watch([search, transfer_type, facility, warehouse, date_from, date_to, per_page, currentTab, currentDirectionTab], () => {
    getResults();
}, { deep: true });

const getResults = () => {
    router.get(route('transfers.index'), {
        search: search.value,
        transfer_type: transfer_type.value,
        facility: facility.value,
        warehouse: warehouse.value,
        date_from: date_from.value,
        date_to: date_to.value,
        per_page: per_page.value,
        status: currentTab.value,
        direction: currentDirectionTab.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
}
.slide-enter-to, .slide-leave-from {
  transform: translateX(0);
}
</style>
