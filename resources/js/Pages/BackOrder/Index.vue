<template>
    <AuthenticatedLayout title="Back Orders" description="Manage Back Orders">
        <div class="p-6">
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
            >
                <!-- Left: Search bar -->
                <div class="w-full md:w-auto">
                    <input
                        type="text"
                        v-model="search"
                        class="w-full sm:w-[400px] border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Search by [Item Name,barcode, batch number]"
                    />
                </div>

                <!-- Right: Per Page + Status -->
                <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
                    <!-- Per Page -->
                    <select
                        v-model="per_page"
                        @change="props.filters.page = 1"
                        class="w-full sm:w-[200px] border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="10">10 Per page</option>
                        <option value="25">25 Per page</option>
                        <option value="50">50 Per page</option>
                        <option value="100">100 Per page</option>
                    </select>

                    <!-- Status -->
                    <select
                        v-model="status"
                        @change="props.filters.page = 1"
                        class="w-full sm:w-[200px] border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="p-6 bg-white shadow rounded-lg mb-[80px]">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Backorders</h2>

            <div class="overflow-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-gray-600 text-sm font-medium">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Item</th>
                            <th class="px-4 py-3 text-left">Item Info</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="w-[200px] px-4 py-3 text-left">
                                Quantity
                            </th>
                            <th class="px-4 py-3 text-left">Notes</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Created At</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 text-sm text-gray-700"
                    >
                        <tr
                            v-for="(item, index) in props.backorders.data"
                            :key="item.id"
                            class="hover:bg-gray-50"
                        >
                            <td class="px-4 py-2">{{ index + 1 }}</td>
                            <td class="px-4 py-2">{{ item.product?.name }}</td>
                            <td class="px-4 py-2">
                                <div class="flex flex-col">
                                    <span class="text-gray-600"
                                        >Batch Number:
                                        {{
                                            item.inventory_allocation
                                                ?.batch_number || item.transfer_item?.batch_number || "N/A"
                                        }}</span
                                    >
                                    <span class="text-gray-600"
                                        >Barcode:
                                        {{
                                            item.inventory_allocation
                                                ?.barcode || item.transfer_item?.barcode || "N/A"
                                        }}</span
                                    >
                                    <span class="text-gray-600"
                                        >UoM:
                                        {{
                                            item.inventory_allocation?.uom || item.transfer_item?.uom || "N/A"
                                        }}</span
                                    >
                                </div>
                            </td>
                            <td class="px-4 py-2">
                                <span
                                    class="inline-block px-2 py-1 rounded-full text-xs font-semibold"
                                    :class="{
                                        'bg-red-100 text-red-800':
                                            item.type === 'Missing',
                                        'bg-yellow-100 text-yellow-800':
                                            item.type === 'Lost',
                                        'bg-orange-100 text-orange-800':
                                            item.type === 'Damaged',
                                        'bg-purple-100 text-purple-800':
                                            item.type === 'Expired',
                                    }"
                                >
                                    {{ item.type }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex flex-col">
                                    <span
                                        >Allocated QTY:
                                        {{
                                            item.inventory_allocation
                                                ?.allocated_quantity || item.transfer_item?.quantity || "N/A"
                                        }}</span
                                    >
                                    <span
                                        >Backordered QTY:
                                        {{ item.quantity }}</span
                                    >
                                </div>
                            </td>
                            <td class="px-4 py-2">{{ item.notes }}</td>
                            <td class="px-4 py-2">
                                <span
                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800"
                                >
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="px-4 py-2 space-x-2">
                                <!-- Conditional Actions -->
                                <template
                                    v-if="
                                        ['Missing', 'Lost'].includes(item.type)
                                    "
                                >
                                    <!-- @click="liquidate(item)" -->
                                    <button
                                        @click="
                                            handleAction('Liquidate', {
                                                ...item,
                                                status: item.status,
                                                quantity: item.quantity,
                                            })
                                        "
                                        class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-red-500 text-white hover:bg-red-600"
                                    >
                                        Liquidate
                                    </button>
                                    <button
                                        @click="receive(item)"
                                        class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-green-500 text-white hover:bg-green-600"
                                    >
                                        Receive
                                    </button>
                                </template>
                                <template
                                    v-else-if="
                                        ['Damaged', 'Expired'].includes(
                                            item.type
                                        )
                                    "
                                >
                                    <button
                                        @click="
                                            handleAction('Dispose', {
                                                ...item,
                                                status: item.status,
                                                quantity: item.quantity,
                                            })
                                        "
                                        class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-yellow-500 text-white hover:bg-yellow-600"
                                    >
                                        Dispose
                                    </button>
                                    <button
                                        @click="receive(item)"
                                        class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-green-500 text-white hover:bg-green-600"
                                    >
                                        Receive
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <TailwindPagination
                :data="props.backorders"
                @pagination-change-page="getResult"
            />
        </div>

        <!-- Liquidation Modal -->
        <Modal
            :show="showLiquidateModal"
            max-width="xl"
            @close="showLiquidateModal = false"
        >
            <form
                id="liquidationForm"
                class="p-6 space-y-4"
                @submit.prevent="submitLiquidation"
            >
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Liquidate Item
                </h2>

                <!-- Product Info -->
                <div v-if="selectedItem" class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Item
                            </p>
                            <p class="text-sm text-gray-900">
                                {{ selectedItem.product.name }}
                            </p>
                            <p class="text-sm font-medium text-gray-500">
                                Item Condition
                            </p>
                            <p class="text-sm text-gray-900">
                                {{ selectedItem.type }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Order Info
                            </p>
                            <p class="text-sm text-gray-900">
                                {{
                                    selectedItem.order_item?.order?.order_number
                                }}
                                -
                                {{ selectedItem.order_item?.order?.order_type }}
                            </p>
                            <p class="text-sm font-medium text-gray-500">
                                Status
                            </p>
                            <p class="text-sm text-gray-900">
                                {{ selectedItem.status }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label
                        for="quantity"
                        class="block text-sm font-medium text-gray-700"
                        >Quantity</label
                    >
                    <input
                        type="number"
                        id="quantity"
                        v-model="liquidateForm.quantity"
                        readonly
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :min="1"
                        :max="selectedItem?.quantity"
                        required
                    />
                </div>

                <!-- Note -->
                <div>
                    <label
                        for="note"
                        class="block text-sm font-medium text-gray-700"
                        >Note</label
                    >
                    <textarea
                        id="note"
                        v-model="liquidateForm.note"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        rows="3"
                        required
                    ></textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Attachments (PDF files)</label
                    >
                    <input
                        type="file"
                        ref="attachments"
                        @change="(e) => handleFileChange('liquidate', e)"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        multiple
                        accept=".pdf"
                        required
                    />
                </div>

                <!-- Selected Files Preview -->
                <div v-if="liquidateForm.attachments.length > 0" class="mt-2">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">
                        Selected Files:
                    </h4>
                    <ul class="space-y-2">
                        <li
                            v-for="(file, index) in liquidateForm.attachments"
                            :key="index"
                            class="flex items-center justify-between text-sm text-gray-500 bg-gray-50 p-2 rounded"
                        >
                            <span>{{ file.name }}</span>
                            <button
                                type="button"
                                @click="removeFile('liquidate', index)"
                                class="text-red-500 hover:text-red-700"
                            >
                                Remove
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="showLiquidateModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-yellow-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? "Liquidating..." : "Liquidate" }}
                    </button>
                </div>
            </form>
        </Modal>

        <!-- disposals -->
        <!-- Dispose Modal -->
        <Modal
            :show="showDisposeModal"
            max-width="xl"
            @close="showDisposeModal = false"
        >
            <form
                id="disposeForm"
                class="p-6 space-y-4"
                @submit.prevent="submitDisposal"
            >
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Dispose Item
                </h2>

                <!-- Product Info -->
                <div v-if="selectedItem" class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Item
                            </p>
                            <p class="text-sm text-gray-900">
                                {{ selectedItem.product.name }}
                            </p>
                            <p class="text-sm font-medium text-gray-500">
                                Item Condition
                            </p>
                            <p class="text-sm text-gray-900">
                                {{ selectedItem.type }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Info
                            </p>
                            <p class="text-sm text-gray-900">
                                {{
                                    selectedItem.order_item?.order?.order_number || "TransferID: " + selectedItem.transfer_item?.transfer?.transferID
                                }}
                                -
                                {{ selectedItem.order_item?.order?.order_type || '' }}
                            </p>
                            <p class="text-sm font-medium text-gray-500">
                                Status
                            </p>
                            <p class="text-sm text-gray-900">
                                {{ selectedItem.status }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quantity -->
                <div>
                    <label
                        for="quantity"
                        class="block text-sm font-medium text-gray-700"
                        >Quantity</label
                    >
                    <input
                        type="number"
                        id="quantity"
                        v-model="disposeForm.quantity"
                        readonly
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :min="1"
                        :max="selectedItem?.quantity"
                        required
                    />
                </div>

                <!-- Note -->
                <div>
                    <label
                        for="note"
                        class="block text-sm font-medium text-gray-700"
                        >Note</label
                    >
                    <textarea
                        id="note"
                        v-model="disposeForm.note"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        rows="3"
                        required
                    ></textarea>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-medium text-gray-700"
                        >Attachments (PDF files)</label
                    >
                    <input
                        type="file"
                        ref="attachments"
                        @change="(e) => handleFileChange('dispose', e)"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        multiple
                        accept=".pdf"
                        required
                    />
                </div>

                <!-- Selected Files Preview -->
                <div v-if="disposeForm.attachments.length > 0" class="mt-2">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">
                        Selected Files:
                    </h4>
                    <ul class="space-y-2">
                        <li
                            v-for="(file, index) in disposeForm.attachments"
                            :key="index"
                            class="flex items-center justify-between text-sm text-gray-500 bg-gray-50 p-2 rounded"
                        >
                            <span>{{ file.name }}</span>
                            <button
                                type="button"
                                @click="removeFile('dispose', index)"
                                class="text-red-500 hover:text-red-700"
                            >
                                Remove
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        type="button"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        @click="showDisposeModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-yellow-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? "Disposing..." : "Dispose" }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { TailwindPagination } from "laravel-vue-pagination";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import axios from "axios";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    backorders: Object,
    filters: Object,
});

const search = ref(props.filters.search);
const per_page = ref(props.filters.per_page);
const status = ref(props.filters.status);

watch(
    [
        () => per_page.value,
        () => props.filters.page,
        () => search.value,
        () => status.value,
    ],
    () => {
        reloadBackOrder();
    }
);

function reloadBackOrder() {
    const query = {};

    // Add all non-empty filter values
    if (search.value) query.search = search.value;
    if (per_page.value) query.per_page = per_page.value;
    if (props.filters.page) query.page = props.filters.page;
    if (status.value) query.status = status.value;

    // Update the URL immediately
    router.get(route("backorders.index"), query, {
        preserveState: true,
        preserveScroll: true,
        only: ["backorders"],
    });
}

function getResult(page = 1) {
    props.filters.page = page;
}

// liquidations
const showLiquidateModal = ref(false);
const isSubmitting = ref(false);
const showDisposeModal = ref(false);
const selectedItem = ref(null);

const liquidateForm = ref({
    quantity: 0,
    note: "",
    attachments: [],
});

const disposeForm = ref({
    quantity: 0,
    note: "",
    attachments: [],
});

const handleFileChange = (formType, e) => {
    const files = Array.from(e.target.files || []);
    if (formType === "liquidate") {
        liquidateForm.value.attachments = files;
    } else {
        disposeForm.value.attachments = files;
    }
};


function removeFile(formType, index) {
    if (formType === "liquidate") {
        liquidateForm.value.attachments.splice(index, 1);
    } else {
        disposeForm.value.attachments.splice(index, 1);
    }
}

// backorder actions

const handleAction = async (action, item) => {
    selectedItem.value = item;

    switch (action) {
        // case 'Receive':
        //     await receiveItems(item);
        //     break;

        case "Liquidate":
            liquidateForm.value = {
                quantity: item.quantity,
                note: "",
                attachments: [],
            };
            showLiquidateModal.value = true;
            break;

        case "Dispose":
            disposeForm.value = {
                quantity: item.quantity,
                note: "",
                attachments: [],
            };
            showDisposeModal.value = true;
            break;
    }
};

// liquidation
const submitLiquidation = async () => {
    isSubmitting.value = true;
    console.log(selectedItem.value);
    const formData = new FormData();
    console.log(selectedItem.value);
    formData.append("id", selectedItem.value.id);
    formData.append("product_id", selectedItem.value.product.id);
    formData.append("quantity", liquidateForm.value.quantity);
    formData.append("status", selectedItem.value.status);
    formData.append("note", liquidateForm.value.note);

    // Append each attachment
    for (let i = 0; i < liquidateForm.value.attachments.length; i++) {
        formData.append("attachments[]", liquidateForm.value.attachments[i]);
    }

    await axios
        .post(route("backorders.liquidate"), formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        })
        .then((response) => {
            isSubmitting.value = false;
            console.log(response);
            showLiquidateModal.value = false;
            Swal.fire({
                icon: "success",
                title: response.data,
                showConfirmButton: true,
                timer: 1500,
            }).then(() => {
                liquidateForm.value = {
                    quantity: 0,
                    note: "",
                    attachments: [],
                };
                reloadBackOrder();
            });
        })
        .catch((error) => {
            isSubmitting.value = false;
            console.error("Failed to liquidate items:", error);
            Swal.fire({
                icon: "error",
                title: error.response.data,
                showConfirmButton: false,
                timer: 1500,
            });
        });
};

const submitDisposal = async () => {
    isSubmitting.value = true;
    console.log(selectedItem.value);
    const formData = new FormData();
    console.log(selectedItem.value);
    formData.append("id", selectedItem.value.id);
    formData.append("product_id", selectedItem.value.product.id);
    formData.append("quantity", disposeForm.value.quantity);
    formData.append("status", selectedItem.value.status);
    formData.append("note", disposeForm.value.note);

    // Append each attachment
    for (let i = 0; i < disposeForm.value.attachments.length; i++) {
        formData.append("attachments[]", disposeForm.value.attachments[i]);
    }

    await axios
        .post(route("backorders.dispose"), formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
        })
        .then((response) => {
            isSubmitting.value = false;
            showDisposeModal.value = false;
            Swal.fire({
                icon: "success",
                title: response.data,
                showConfirmButton: false,
                timer: 1500,
            }).then(() => {
                disposeForm.value = {
                    quantity: 0,
                    note: "",
                    attachments: [],
                };
                reloadBackOrder();
            });
        })
        .catch((error) => {
            isSubmitting.value = false;
            console.error("Failed to dispose items:", error);
            Swal.fire({
                icon: "error",
                title: error.response.data,
                showConfirmButton: false,
                timer: 1500,
            });
        });
};

function receive(item) {
    Swal.fire({
        title: 'Enter Quantity',
        input: 'number',
        inputAttributes: {
            min: 1,
            max: item.quantity,
            step: 1
        },
        inputLabel: `Max: ${item.quantity}`,
        inputPlaceholder: `Enter a quantity (1 - ${item.quantity})`,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        preConfirm: (value) => {
            const qty = Number(value);
            if (!value || qty < 1 || qty > item.quantity) {
                Swal.showValidationMessage(`Please enter a value between 1 and ${item.quantity}`);
            }
            return qty;
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            const enteredQuantity = result.value;
            console.log(`Received quantity: ${enteredQuantity}`);
            await axios.post(route('backorders.received'), {
                id: item.id,
                quantity: enteredQuantity
            })
            .then((response) => {
                Swal.fire({
                    icon: 'success',
                    title: response.data,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    reloadBackOrder();
                });
            })
            .catch((error) => {
                Swal.fire({
                    icon: 'error',
                    title: error.response.data,
                    showConfirmButton: false,
                    timer: 1500
                });
            });
        }
    });
}


function formatDate(date) {
    return new Date(date).toLocaleDateString();
}
</script>
