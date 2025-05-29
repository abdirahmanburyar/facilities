<template>
    <AuthenticatedLayout>
      <!-- Order Header -->
    <div v-if="error">
      {{ error }}
    </div>
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <Link :href="route('orders.index')">Back to orders</Link>
            <h1 class="text-2xl font-semibold text-gray-900">Order ID. {{ order?.value?.order_number }}</h1>
          </div>
          <div class="flex items-center space-x-4">
            <span :class="[statusClasses[order?.value?.status] || statusClasses.default]" class="flex items-center text-lg font-bold px-4 py-2">
              <!-- Status Icon -->
              <span class="mr-3">
                <!-- Pending Icon -->
                <img v-if="order?.value?.status === 'pending'" src="/assets/images/pending.svg" class="w-6 h-6"
                  alt="Pending" />

                <!-- Approved Icon -->
                <img v-else-if="order?.value?.status === 'approved'" src="/assets/images/approved.png" class="w-6 h-6"
                  alt="Approved" />

                <!-- In Process Icon -->
                <img v-else-if="order?.value?.status === 'in_process'" src="/assets/images/inprocess.png" class="w-6 h-6"
                  alt="In Process" />

                <!-- Dispatched Icon -->
                <img v-else-if="order?.value?.status === 'dispatched'" src="/assets/images/dispatch.png" class="w-6 h-6"
                  alt="Dispatched" />

                <!-- Delivered Icon -->
                <img v-else-if="order.status === 'delivered'" src="/assets/images/delivery.png" class="w-6 h-6"
                  alt="Delivered" />

                <!-- Received Icon -->
                <img v-else-if="order.status === 'received'" src="/assets/images/received.png" class="w-6 h-6"
                  alt="Received" />

                <!-- Rejected Icon -->
                <svg v-else-if="order.status === 'rejected'" class="w-6 h-6 text-red-700" fill="none"
                  stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </span>
              {{ order.status.toUpperCase() }}
            </span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
        <!-- Facility Information -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
          <h2 class="text-lg font-medium text-gray-900">Facility Details</h2>
          <div class="flex items-center">
            <BuildingOfficeIcon class="h-5 w-5 text-gray-400 mr-2" />
            <span class="text-sm text-gray-600">{{ order.facility?.name }}</span>
          </div>
          <div class="flex items-center">
            <EnvelopeIcon class="h-5 w-5 text-gray-400 mr-2" />
            <span class="text-sm text-gray-600">{{ order.facility?.email }}</span>
          </div>
          <div class="flex items-center">
            <PhoneIcon class="h-5 w-5 text-gray-400 mr-2" />
            <span class="text-sm text-gray-600">{{ order.facility?.phone }}</span>
          </div>
          <div class="flex items-center">
            <MapPinIcon class="h-5 w-5 text-gray-400 mr-2" />
            <span class="text-sm text-gray-600">{{ order.facility?.address }}, {{ order.facility?.city }}</span>
          </div>
        </div>
        <div>
          <div class="bg-gray-50 rounded-lg p-4 space-y-2">
            <h2 class="text-lg font-medium text-gray-900">Order Details</h2>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm font-medium text-gray-500">Order Type</p>
                <p class="text-sm text-gray-900">{{ order.order_type }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Order Date</p>
                <p class="text-sm text-gray-900">{{ formatDate(order.order_date) }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Expected Date</p>
                <p class="text-sm text-gray-900">{{ formatDate(order.expected_date) }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Created By</p>
                <p class="text-sm text-gray-900">{{ order.user.name }}</p>
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
          <div class="absolute top-7 left-0 h-2 bg-green-500 z-0 transition-all duration-500 ease-in-out" :style="{
            width: `${(statusOrder.indexOf(order.status) / (statusOrder.length - 1)) * 100}%`
          }"></div>

          <!-- Timeline Steps -->
          <div class="relative flex justify-between">
            <!-- Pending -->
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full border-4 flex items-center justify-center z-10"
                :class="[statusOrder.indexOf(order.status) >= statusOrder.indexOf('pending') ? 'bg-white border-orange-500' : 'bg-white border-gray-200']">
                <img src="/assets/images/pending.svg" class="w-10 h-10" alt="Pending"
                  :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('pending') ? '' : 'opacity-40'" />
              </div>
              <span class="mt-3 text-lg font-bold"
                :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('pending') ? 'text-green-600' : 'text-gray-500'">Pending</span>
            </div>

            <!-- Approved -->
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full border-4 flex items-center justify-center z-10"
                :class="[statusOrder.indexOf(order.status) >= statusOrder.indexOf('approved') ? 'bg-white border-orange-500' : 'bg-white border-gray-200']">
                <img src="/assets/images/approved.png" class="w-10 h-10" alt="Approved"
                  :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('approved') ? '' : 'opacity-40'" />
              </div>
              <span class="mt-3 text-lg font-bold"
                :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('approved') ? 'text-green-600' : 'text-gray-500'">Approved</span>
            </div>

            <!-- In Process -->
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full border-4 flex items-center justify-center z-10"
                :class="[statusOrder.indexOf(order.status) >= statusOrder.indexOf('in_process') ? 'bg-white border-orange-500' : 'bg-white border-gray-200']">
                <img src="/assets/images/inprocess.png" class="w-10 h-10" alt="In Process"
                  :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('in_process') ? '' : 'opacity-40'" />
              </div>
              <span class="mt-3 text-lg font-bold"
                :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('in_process') ? 'text-green-600' : 'text-gray-500'">In
                Process</span>
            </div>

            <!-- Dispatch -->
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full border-4 flex items-center justify-center z-10"
                :class="[statusOrder.indexOf(order.status) >= statusOrder.indexOf('dispatched') ? 'bg-white border-orange-500' : 'bg-white border-gray-200']">
                <img src="/assets/images/dispatch.png" class="w-10 h-10" alt="Dispatch"
                  :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('dispatched') ? '' : 'opacity-40'" />
              </div>
              <span class="mt-3 text-lg font-bold"
                :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('dispatched') ? 'text-green-600' : 'text-gray-500'">Dispatch</span>
            </div>

            <!-- Delivered -->
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full border-4 flex items-center justify-center z-10"
                :class="[statusOrder.indexOf(order.status) >= statusOrder.indexOf('delivered') ? 'bg-white border-orange-500' : 'bg-white border-gray-200']">
                <img src="/assets/images/delivery.png" class="w-10 h-10" alt="Delivered"
                  :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('delivered') ? '' : 'opacity-40'" />
              </div>
              <span class="mt-3 text-lg font-bold"
                :class="statusOrder.indexOf(order.status) >= statusOrder.indexOf('delivered') ? 'text-green-600' : 'text-gray-500'">Delivered</span>
            </div>

            <!-- Received -->
            <div class="flex flex-col items-center">
              <div class="w-16 h-16 rounded-full border-4 flex items-center justify-center z-10" :class="[statusOrder.indexOf(order.status) >= statusOrder.indexOf('received') ?
                (order.has_back_order ? 'bg-white border-orange-500' : 'bg-green-500 border-green-600') :
                'bg-white border-gray-200']">
                <img
                  v-if="order.has_back_order && statusOrder.indexOf(order.status) >= statusOrder.indexOf('received')"
                  src="/assets/images/received.png" class="w-10 h-10" alt="Partially Received" />
                <img v-else-if="statusOrder.indexOf(order.status) >= statusOrder.indexOf('received')"
                  src="/assets/images/check.svg" class="w-10 h-10" alt="Completed" />
                <img v-else src="/assets/images/received.png" class="w-10 h-10 opacity-40" alt="Received" />
              </div>
              <span class="mt-3 text-lg font-bold" :class="[statusOrder.indexOf(order.status) >= statusOrder.indexOf('received') ?
                (order.has_back_order ? 'text-orange-600' : 'text-green-600') :
                'text-gray-500']">
                {{ statusOrder.indexOf(order.status) >= statusOrder.indexOf('received') ?
                  (order.has_back_order ? 'Partially Received' : 'Completed') : 'Received' }}
              </span>
              <button
                v-if="order.has_back_order && statusOrder.indexOf(order.status) >= statusOrder.indexOf('received')"
                @click="showBackOrderModal()"
                class="mt-1 text-xs text-orange-600 underline hover:text-orange-800 cursor-pointer">
                View Back Order
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- Order Items Table -->
      <h2 class="text-lg font-medium text-gray-900 mb-4">Order Items</h2>
      
      <table class="min-w-full border border-black mb-4">
        <thead>
          <tr class="bg-gray-50">
            <th class="px-3 py-3 text-left text-sm font-medium text-gray-500 uppercase border border-black">Item
            </th>
            <th class="px-3 py-3 text-left text-sm font-medium text-gray-500 uppercase border border-black">
              Facility
              Indicators</th>
            <th class="px-3 py-3 text-left text-sm font-medium text-gray-500 uppercase border border-black">Ordered
              Quantity</th>
            <th class="px-3 py-3 text-left text-sm font-medium text-gray-500 uppercase border border-black">
              Quantity to
              release</th>
            <th class="px-3 py-3 text-left text-sm font-medium text-gray-500 uppercase border border-black">Days
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(item, index) in form" :key="item.id" class="hover:bg-gray-50 transition-colors duration-150">
            <td class="px-3 py-3 text-sm text-gray-900 border border-black">
              <div class="flex flex-col">
                <span class="font-medium text-sm">{{ item.product?.name }}</span>
              </div>
            </td>
            <td class="px-3 py-3 text-sm text-gray-900 border border-black">
              <div class="flex flex-col space-y-1 text-sm">
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
              {{ item.quantity }}
            </td>
            <td class="px-3 py-3 text-sm text-gray-900 border border-black">
              <div class="flex justify-between items-start gap-2">
                <div class="w-[30%]">
                  <input type="text" placeholder="0" v-model="item.quantity_to_release"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                 <div>
                  <label>Received Quantity</label>
                  <input type="text" placeholder="0" v-model="item.received_quantity"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
                 </div>
                  <button
                    v-if="order.status === 'delivered'"
                    @click="openBackOrderModal(item)"
                    class="mt-2 px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-xs w-full"
                  >
                    Back Order
                  </button>
                </div>
                <div class="border rounded-md overflow-hidden text-sm flex-1">
                  <table class="min-w-full divide-y divide-gray-200">
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="inv in item.inventory_allocations" :key="inv.id" class="hover:bg-gray-100">
                        <td class="px-2 py-1">
                          <div class="font-medium">QTY: {{ inv.allocated_quantity }}</div>
                          <div class="font-medium">Batch: {{ inv.batch_number }}</div>
                          <div class="font-medium">Barcode: {{ inv.barcode || 'N/A' }}</div>
                          <div class="font-medium">WH:{{ inv.warehouse?.name }}</div>
                          <div class="font-medium">Loc: {{ inv.location?.location }}</div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </td>
            <td class="px-3 py-3 text-sm text-gray-900 border border-black">
              <div class="w-full flex justify-center">
                <input type="number" v-model="item.no_of_days"
                  class="w-[70%] rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm" />
              </div>
            </td>

            <td class="px-2 py-3 text-sm text-gray-900 w-10 border border-black">
              <div class="flex flex-col space-y-2">
                <button type="button"
                  class="inline-flex items-center justify-center w-8 h-8 bg-red-100 border border-transparent rounded text-sm text-red-700 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                  title="Delete">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Status Actions Section - Single row with actions and status icons -->
      <div class="mt-8 mb-6 px-6 py-6 bg-white rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 text-center">Order Status Actions</h3>
        <div class="flex justify-center items-center mb-6">

          <!-- Status Action Buttons -->
          <div class="flex flex-wrap items-center justify-center gap-4">
            <!-- Pending status indicator -->
            <div class="relative">
              <button 
                :class="[
                  props.order.status === 'pending' ? 'bg-[#f59e0b] hover:bg-[#d97706]' : 
                  statusOrder.indexOf(props.order.status) > statusOrder.indexOf('pending') ? 'bg-[#55c5ff]' : 'bg-gray-300 cursor-not-allowed'
                ]"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]" disabled>
                <img src="/assets/images/pending.svg" class="w-5 h-5 mr-2" alt="Pending" />
                <span class="text-sm font-bold text-white">Pending</span>
              </button>
              <div v-if="props.order.status === 'pending'" class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
            </div>
            <!-- Approve button -->
            <div class="relative">
              <button @click="changeStatus(props.order.id, 'approved')"
                :disabled="isLoading || props.order.status !== 'pending'"
                :class="[
                  props.order.status === 'pending' ? 'bg-[#f59e0b] hover:bg-[#d97706]' : 
                  statusOrder.indexOf(props.order.status) > statusOrder.indexOf('pending') ? 'bg-[#55c5ff]' : 'bg-gray-300 cursor-not-allowed'
                ]"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                <svg v-if="isLoading && props.order.status === 'pending'" class="animate-spin h-5 w-5 mr-2"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                <template v-else>
                  <img src="/assets/images/approved.png" class="w-5 h-5 mr-2" alt="Approve" />
                  <span class="text-sm font-bold text-white">{{ statusOrder.indexOf(props.order.status) > statusOrder.indexOf('pending') ? 'Approved' : 'Approve' }}</span>
                </template>
              </button>
              <div v-if="props.order.status === 'pending'" class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
            </div>

            <!-- Process button -->
            <div class="relative">
              <button @click="changeStatus(props.order.id, 'in_process')"
                :disabled="isLoading || props.order.status !== 'approved'"
                :class="[
                  props.order.status === 'approved' ? 'bg-[#f59e0b] hover:bg-[#d97706]' : 
                  statusOrder.indexOf(props.order.status) > statusOrder.indexOf('approved') ? 'bg-[#55c5ff]' : 'bg-gray-300 cursor-not-allowed'
                ]"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                <svg v-if="isLoading && props.order.status === 'approved'" class="animate-spin h-5 w-5 mr-2"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                <template v-else>
                  <img src="/assets/images/inprocess.png" class="w-5 h-5 mr-2" alt="Process" />
                  <span class="text-sm font-bold text-white">{{ statusOrder.indexOf(props.order.status) > statusOrder.indexOf('approved') ? 'Processed' : 'Process' }}</span>
                </template>
              </button>
              <div v-if="props.order.status === 'approved'" class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
            </div>

            <!-- Dispatch button -->
            <div class="relative">
              <button @click="changeStatus(order.id, 'dispatched')"
                :disabled="isLoading || order.status !== 'in_process'"
                :class="[
                  order.status === 'in_process' ? 'bg-[#f59e0b] hover:bg-[#d97706]' : 
                  statusOrder.indexOf(order.status) > statusOrder.indexOf('in_process') ? 'bg-[#55c5ff]' : 'bg-gray-300 cursor-not-allowed'
                ]"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                <svg v-if="isLoading && order.status === 'in_process'" class="animate-spin h-5 w-5 mr-2"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                <template v-else>
                  <img src="/assets/images/dispatch.png" class="w-5 h-5 mr-2" alt="Dispatch" />
                  <span class="text-sm font-bold text-white">{{ statusOrder.indexOf(order.status) > statusOrder.indexOf('in_process') ? 'Dispatched' : 'Dispatch' }}</span>
                </template>
              </button>
              <div v-if="order.status === 'in_process'" class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
            </div>

            <!-- Deliver button -->
            <div class="relative">
              <button @click="changeStatus(props.order.id, 'delivered')"
                :disabled="isLoading || props.order.status !== 'dispatched'"
                :class="[
                  props.order.status === 'dispatched' ? 'bg-[#f59e0b] hover:bg-[#d97706]' : 
                  statusOrder.indexOf(props.order.status) > statusOrder.indexOf('dispatched') ? 'bg-[#55c5ff]' : 'bg-gray-300 cursor-not-allowed'
                ]"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                <svg v-if="isLoading && props.order.status === 'dispatched'" class="animate-spin h-5 w-5 mr-2"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                <template v-else>
                  <img src="/assets/images/delivery.png" class="w-5 h-5 mr-2" alt="Deliver" />
                  <span class="text-sm font-bold text-white">{{ statusOrder.indexOf(props.order.status) > statusOrder.indexOf('dispatched') ? 'Delivered' : 'Deliver' }}</span>
                </template>
              </button>
              <div v-if="props.order.status === 'dispatched'" class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
            </div>

            <!-- Receive button -->
            <div class="relative">
              <button @click="changeStatus(props.order.id, 'received')"
                :disabled="isLoading || props.order.status !== 'delivered'"
                :class="[
                  props.order.status === 'delivered' ? 'bg-[#f59e0b] hover:bg-[#d97706]' : 
                  statusOrder.indexOf(props.order.status) > statusOrder.indexOf('delivered') ? 'bg-[#55c5ff]' : 'bg-gray-300 cursor-not-allowed'
                ]"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white min-w-[160px]">
                <svg v-if="isLoading && props.order.status === 'delivered'" class="animate-spin h-5 w-5 mr-2"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                <template v-else>
                  <img src="/assets/images/received.png" class="w-5 h-5 mr-2" alt="Receive" />
                  <span class="text-sm font-bold text-white">{{ statusOrder.indexOf(props.order.status) > statusOrder.indexOf('delivered') ? 'Received' : 'Receive' }}</span>
                </template>
              </button>
              <div v-if="props.order.status === 'delivered'" class="absolute -top-2 -right-2 w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
            </div>

            <!-- Reject button (only available for pending status) -->
            <div class="relative" v-if="props.order.status === 'pending'">
              <button @click="changeStatus(props.order.id, 'rejected')"
                :disabled="isLoading"
                class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm transition-colors duration-150 text-white bg-red-600 hover:bg-red-700 min-w-[160px]">
                <svg v-if="isLoading" class="animate-spin h-5 w-5 mr-2"
                  xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
                <template v-else>
                  <svg class="w-5 h-5 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  <span class="text-sm font-bold text-white">Reject</span>
                </template>
              </button>
            </div>

            <!-- Status indicator for rejected status -->
            <div v-if="props.order.status === 'rejected'" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-red-100 text-red-800 min-w-[160px]">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="text-sm font-bold">Rejected</span>
            </div>

            <!-- Status indicator for received status -->
            <div v-if="props.order.status === 'received'" 
              :class="[props.order.has_back_order ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800']" 
              class="inline-flex items-center justify-center px-4 py-2 rounded-lg min-w-[160px]">
              <svg v-if="!props.order.has_back_order" class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M9,16.17 L4.83,12 L3.41,13.41 L9,19 L21,7 L19.59,5.59 L9,16.17 Z" fill="currentColor" />
              </svg>
              <span class="text-sm font-bold">{{ props.order.has_back_order ? 'Partially Received' : 'Completed' }}</span>
              <button v-if="props.order.has_back_order" @click="showBackOrderModal()" class="ml-2 underline hover:text-orange-900 focus:outline-none text-xs">
                View Back Order
              </button>
            </div>
          </div>
        </div>

      </div>
    <!-- Back Order Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
      <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[80vh] overflow-hidden">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900">Back Order Details</h3>
          <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-4 overflow-y-auto max-h-[60vh]">
          <div class="mb-4">
            <span class="text-sm font-medium text-gray-700">Order #{{ order.id }}</span>
            <h2 class="text-xl font-bold text-gray-900">Back Ordered Items</h2>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                    Quantity</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                    Status</th>
                  <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">
                    Quantity to Release</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(row, index) in backOrderRows" :key="index">
                  <td class="px-3 py-2">
                    <input type="number" v-model="row.quantity" :disabled="row.finalized != null"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                      min="0" @input="validateBackOrderQuantities">
                  </td>
                  <td class="px-3 py-2">
                    <select v-model="row.status"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                      <option v-for="status in ['Missing', 'Damaged', 'Expired', 'Lost']" 
                              :key="status" 
                              :value="status">
                        {{ status }}
                      </option>
                    </select>
                  </td>
                  <td class="px-3 py-2">
                    <button @click="removeBackOrderRow(index, row)" v-if="!row.finalized"
                      class="text-red-600 hover:text-red-800">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
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
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h2 class="text-lg font-medium text-gray-900">Incomplete Back Orders</h2>
        </div>

        <div class="mb-6 bg-gray-50 p-4 rounded-lg">
          <p class="text-sm font-medium text-gray-600 mb-2">Item: {{ selectedItem?.product?.name }}</p>
          <p class="text-sm font-medium text-gray-600 mb-2">Missing Quantity: {{ missingQuantity }}</p>
          <p class="text-sm font-medium text-gray-600">Remaining to Allocate: {{ remainingToAllocate }}</p>
        </div>
      </div>

      <h2 v-else class="text-lg font-medium text-gray-900 mb-4">Back Order Details</h2>

      <div class="mb-4 bg-gray-50 p-3 rounded">
        <p class="text-sm font-medium text-gray-600">Product: {{ selectedItem?.product?.name }}</p>
        <p class="text-sm font-medium text-gray-600">Expected Quantity: {{ selectedItem?.quantity }}</p>
        <p class="text-sm font-medium text-gray-600">Received Quantity: {{ selectedItem?.received_quantity || 0 }}</p>
        <p class="text-sm font-medium text-gray-600">Back Orders: {{ totalExistingDifferences }}</p>
        <p class="text-sm font-medium text-yellow-800">Actual Mismatches: {{ actualMismatches }}</p>
      </div>

      <!-- Batch-specific back orders -->
      <div class="mb-4">
        <h3 class="text-md font-medium text-gray-700 mb-2">Batch Information</h3>
        <div class="bg-gray-50 p-3 rounded mb-4">
          <p class="text-sm text-gray-500 mb-2">Please select which batch(es) have issues and specify the problem type and quantity.</p>
        </div>
        
        <div v-for="(allocation, allocIndex) in selectedItem?.inventory_allocations" :key="allocation.id" class="border rounded-md p-3 mb-3">
          <div class="flex items-center justify-between mb-2">
            <div class="font-medium text-gray-700">
              Batch: {{ allocation.batch_number }} 
              <span class="ml-2 text-sm text-gray-500">({{ allocation.allocated_quantity }} units)</span>
            </div>
            <div>
              <button 
                @click="addBatchBackOrder(allocation)" 
                class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600"
                :disabled="!canAddMoreToAllocation(allocation)"
              >
                Add Issue
              </button>
            </div>
          </div>
          
          <div class="text-xs text-gray-600 mb-2">
            <div>Barcode: {{ allocation.barcode || 'N/A' }}</div>
            <div>Location: {{ allocation.location?.location || 'N/A' }}</div>
            <div>Warehouse: {{ allocation.warehouse?.name || 'N/A' }}</div>
          </div>
          
          <!-- Back order rows for this batch -->
          <div v-if="getBatchBackOrders(allocation.id).length > 0" class="mt-3">
            <table class="min-w-full divide-y divide-gray-200 border">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Issue Type</th>
                  <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Quantity</th>
                  <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Notes</th>
                  <th class="px-2 py-1 text-left text-xs font-medium text-gray-500">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(row, rowIndex) in getBatchBackOrders(allocation.id)" :key="rowIndex" class="hover:bg-gray-50">
                  <td class="px-2 py-1">
                    <select v-model="row.status" class="text-sm w-full border-gray-300 rounded-md shadow-sm">
                      <option v-for="status in ['Missing', 'Damaged', 'Expired', 'Lost']" :key="status" :value="status">
                        {{ status }}
                      </option>
                    </select>
                  </td>
                  <td class="px-2 py-1">
                    <input 
                      type="number" 
                      v-model="row.quantity" 
                      @input="validateBatchBackOrderQuantity(row, allocation)"
                      min="1" 
                      :max="allocation.allocated_quantity"
                      class="text-sm w-full border-gray-300 rounded-md shadow-sm"
                    />
                  </td>
                  <td class="px-2 py-1">
                    <input 
                      type="text" 
                      v-model="row.notes" 
                      placeholder="Optional notes"
                      class="text-sm w-full border-gray-300 rounded-md shadow-sm"
                    />
                  </td>
                  <td class="px-2 py-1 text-center">
                    <button @click="removeBatchBackOrder(allocation.id, rowIndex)" class="text-red-600 hover:text-red-800">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
            <span :class="{'text-green-600': isValidForSave, 'text-red-600': !isValidForSave}">
              {{ totalBatchBackOrderQuantity }}
            </span>
            <span class="text-gray-600"> / {{ selectedItem?.quantity - (selectedItem?.received_quantity || 0) }} items recorded</span>
          </div>
        </div>

        <div class="flex gap-2">
          <button 
            @click="saveBackOrders"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="!isValidForSave || isSaving">
            {{ isSaving ? 'Saving...' : 'Save Back Orders' }}
          </button>
          <button 
            @click="attemptCloseModal"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </Modal>
</AuthenticatedLayout>
</template>

<script setup>
import { computed, onMounted, ref, toRefs, h, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { router, Link } from '@inertiajs/vue3';
import {
  BuildingOfficeIcon,
  EnvelopeIcon,
  PhoneIcon,
  MapPinIcon
} from '@heroicons/vue/24/outline';
import Swal from 'sweetalert2';
import axios from 'axios';
import moment from 'moment';

const props = defineProps({
  order: {
    type: Object,
    required: true
  },
  error: {
    type: String,
    default: null
  },
  products: {
    type: Array,
    default: () => []
  }
});

const { order, error, products } = toRefs(props);

// Back order modal state
const showBackOrderModal = ref(false);
const selectedItem = ref(null);
const batchBackOrders = ref({});
const showIncompleteBackOrderModal = ref(false);
const isSaving = ref(false);

// Computed property to get back order items
const backOrderItems = computed(() => {
  if (!props.order.items) return [];
  return props.order.items.filter(item => {
    return item.received_quantity !== undefined &&
      item.received_quantity < item.quantity;
  });
});

// Computed properties for back order modal
const missingQuantity = computed(() => {
  if (!selectedItem.value) return 0;
  return selectedItem.value.quantity - (selectedItem.value.received_quantity || 0);
});

const totalBatchBackOrderQuantity = computed(() => {
  let total = 0;
  Object.values(batchBackOrders.value).forEach(rows => {
    rows.forEach(row => {
      total += Number(row.quantity || 0);
    });
  });
  return total;
});

const totalExistingDifferences = computed(() => {
  if (!selectedItem.value || !selectedItem.value.backorders) return 0;
  return selectedItem.value.backorders.reduce((total, bo) => total + Number(bo.quantity || 0), 0);
});

const actualMismatches = computed(() => {
  return missingQuantity.value - totalExistingDifferences.value;
});

const remainingToAllocate = computed(() => {
  return missingQuantity.value - totalBatchBackOrderQuantity.value;
});

const isValidForSave = computed(() => {
  // Check if we have any back orders
  const hasBackOrders = Object.values(batchBackOrders.value).some(rows => rows.length > 0);
  
  // Check if all back orders have valid data
  const allValid = Object.values(batchBackOrders.value).every(rows => {
    return rows.every(row => row.quantity > 0 && row.status);
  });
  
  // Check if total matches the missing quantity
  const totalMatches = totalBatchBackOrderQuantity.value === missingQuantity.value;
  
  return hasBackOrders && allValid && totalMatches;
});

// Functions for back order modal
const openBackOrderModal = (item) => {
  selectedItem.value = item;
  batchBackOrders.value = {};
  showIncompleteBackOrderModal.value = false;
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
  // Get current back orders for this allocation
  const currentBackOrders = getBatchBackOrders(allocation.id);
  
  // Calculate total quantity already back-ordered for this allocation
  const totalBackOrdered = currentBackOrders.reduce((sum, bo) => sum + Number(bo.quantity || 0), 0);
  
  // Can add more if there's still quantity available in this allocation
  return totalBackOrdered < allocation.allocated_quantity;
};

// Add a back order for a specific batch
const addBatchBackOrder = (allocation) => {
  const currentBackOrders = getBatchBackOrders(allocation.id);
  
  // Calculate remaining quantity for this allocation
  const totalBackOrdered = currentBackOrders.reduce((sum, bo) => sum + Number(bo.quantity || 0), 0);
  const remainingForAllocation = allocation.allocated_quantity - totalBackOrdered;
  
  // Add a new back order row for this batch
  currentBackOrders.push({
    inventory_allocation_id: allocation.id,
    quantity: remainingForAllocation,
    status: 'Missing',
    notes: '',
    batch_number: allocation.batch_number,
    barcode: allocation.barcode
  });
};

// Remove a back order for a specific batch
const removeBatchBackOrder = (allocationId, index) => {
  if (batchBackOrders.value[allocationId]) {
    batchBackOrders.value[allocationId].splice(index, 1);
  }
};

// Validate back order quantity for a specific batch
const validateBatchBackOrderQuantity = (row, allocation) => {
  // Ensure quantity is a valid number
  if (isNaN(row.quantity) || row.quantity < 0) {
    row.quantity = 0;
  }
  
  // Get all back orders for this allocation
  const allocationBackOrders = getBatchBackOrders(allocation.id);
  
  // Calculate total back ordered for this allocation
  const totalForAllocation = allocationBackOrders.reduce((sum, bo) => sum + Number(bo.quantity || 0), 0);
  
  // If total exceeds allocation quantity, adjust this row
  if (totalForAllocation > allocation.allocated_quantity) {
    // Calculate how much to reduce by
    const excess = totalForAllocation - allocation.allocated_quantity;
    row.quantity = Math.max(0, row.quantity - excess);
  }
};

const saveBackOrders = async () => {
  if (!isValidForSave.value) return;
  
  isSaving.value = true;
  
  try {
    // Flatten the batch back orders into a single array
    const backOrdersArray = [];
    Object.entries(batchBackOrders.value).forEach(([allocationId, rows]) => {
      rows.forEach(row => {
        backOrdersArray.push({
          inventory_allocation_id: allocationId,
          quantity: row.quantity,
          status: row.status,
          notes: row.notes || null
        });
      });
    });
    
    const response = await axios.post(route('orders.backorder'), {
      order_id: props.order.id,
      item_id: selectedItem.value.id,
      backorders: backOrdersArray
    });
    
    Swal.fire({
      title: 'Success!',
      text: 'Back orders have been recorded successfully',
      icon: 'success',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    }).then(() => {
      // Close modal and reload page to show updated data
      showBackOrderModal.value = false;
      router.reload();
    });
  } catch (error) {
    Swal.fire({
      title: 'Error!',
      text: error.response?.data?.message || 'Failed to save back orders',
      icon: 'error',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });
  } finally {
    isSaving.value = false;
  }
};

const attemptCloseModal = () => {
  if (remainingToAllocate.value > 0 && totalBatchBackOrderQuantity.value > 0) {
    // Show warning if there are unallocated quantities
    showIncompleteBackOrderModal.value = true;
  } else {
    // Close modal directly if everything is allocated or nothing has been entered
    showBackOrderModal.value = false;
    showIncompleteBackOrderModal.value = false;
  }
};

const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-800 rounded-full font-bold',
  approved: 'bg-green-100 text-green-800 rounded-full font-bold',
  'in process': 'bg-blue-100 text-blue-800 rounded-full font-bold',
  dispatched: 'bg-purple-100 text-purple-800 rounded-full font-bold',
  delivered: 'bg-gray-100 text-gray-800 rounded-full font-bold',
  received: 'bg-green-100 text-green-800 rounded-full font-bold flex items-center',
  'partially_received': 'bg-orange-100 text-orange-800 rounded-full font-bold',
  default: 'bg-gray-100 text-gray-800 rounded-full font-bold'
};

// Function to get the display status
const getDisplayStatus = (status, hasBackOrder) => {
  if (status === 'received') {
    if (hasBackOrder) {
      return 'Partially Received';
    }
    return 'Completed';
  }
  return status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
};



const form = ref([]);
const isLoading = ref(false);

onMounted(() => {
  form.value = props.order.items || [];
});

const formatDate = (date) => {
  return moment(date).format('DD/MM/YYYY');
};

const statusOrder = ['pending', 'approved', 'in_process', 'dispatched', 'delivered', 'received'];

// Function to change order status
const changeStatus = (orderId, newStatus) => {
  Swal.fire({
    title: 'Are you sure?',
    text: `Do you want to change the order status to ${newStatus}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, change it!'
  }).then(async (result) => {
    if (result.isConfirmed) {
      // Set loading state
      isLoading.value = true;

      await axios.post(route('orders.change-status'), {
        order_id: orderId,
        status: newStatus
      })
        .then(response => {
          // Reset loading state
          isLoading.value = false;

          Swal.fire({
            title: 'Updated!',
            text: 'Order status has been updated.',
            icon: 'success',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
          }).then(() => {
            // Reload the page to show the updated status
            router.reload();
          });
        })
        .catch(error => {
          // Reset loading state
          isLoading.value = false;

          Swal.fire({
            title: 'Error!',
            text: error.response?.data || 'Failed to update order status',
            icon: 'error',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
          });
        });
    }
  });
};

const getStatusProgress = (currentStatus) => {
  const currentIndex = statusOrder.indexOf(currentStatus);
  return statusOrder.map((status, index) => ({
    status,
    isActive: index <= currentIndex,
    isPast: index < currentIndex
  }));
};

async function submit(item, index) {
  console.log(item)
}

const statusProgress = computed(() => getStatusProgress(order.status));

</script>