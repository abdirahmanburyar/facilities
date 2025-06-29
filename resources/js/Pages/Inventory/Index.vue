<script setup>
import {
    ref,
    watch,
    computed,
    onMounted,
    onBeforeUnmount,
} from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Modal from "@/Components/Modal.vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import moment from "moment";
import { TailwindPagination } from "laravel-vue-pagination";

const props = defineProps({
    inventories: Object,
    products: Array,
    dosage: Array,
    category: Array,
    warehouses: Array,
    filters: Object,
    inventoryStatusCounts: Object,
});

const toast = useToast();

// Search and filter states
const search = ref(props.filters.search);
const location = ref(props.filters.location);
const dosage = ref(props.filters.dosage);
const category = ref(props.filters.category);
const warehouse = ref(props.filters.warehouse);
const per_page = ref(props.filters.per_page);
const loadedLocation = ref([]);
const isSubmitting = ref(false);

// Modal states
const showAddModal = ref(false);

async function loadLocations() {
    if (!warehouse.value) {
        loadedLocation.value = [];
        location.value = '';
        return;
    };
    await axios
        .post(route("invetnories.getLocations"), {
            warehouse: warehouse.value,
        })
        .then((response) => {
            loadedLocation.value = response.data;
        })
        .catch((error) => {
            toast.error(error.response.data);
        });
}

// Form states
const form = ref({
    product_id: null,
    product: null,
    warehouse: "",
    quantity: 0,
    manufacturing_date: "",
    expiry_date: "",
    batch_number: "",
    location: "",
    notes: "",
    is_active: true,
});

// File upload states
const uploadingFile = ref(false);
const uploadProgress = ref(0);
const showUploadModal = ref(false);
const uploadResults = ref(null);

// Set up real-time inventory updates
let echoChannel = null;

onMounted(() => {
    // Listen for inventory updates
    echoChannel = window.Echo.channel("inventory").listen(
        ".refresh",
        (data) => {
            console.log("[PUSHER-DEBUG] Received inventory update:", data);
            applyFilters();
        }
    );
});

onBeforeUnmount(() => {
    // Clean up Echo listeners when component is unmounted
    if (echoChannel) {
        echoChannel.stopListening(".refresh");
    }
});

// Apply filters
const applyFilters = () => {
    const query = {};
    // Add all filter values to query object
    if (search.value) query.search = search.value;
    if (location.value) query.location = location.value;
    if (warehouse.value) query.warehouse = warehouse.value;
    if (dosage.value) query.dosage = dosage.value;
    if (category.value) query.category = category.value;

    // Always include per_page in query if it exists
    if (per_page.value) query.per_page = per_page.value;
    if (props.filters.page) query.page = props.filters.page;

    router.get(route("inventories.index"), query, {
        preserveState: true,
        preserveScroll: true,
        only: [
            "inventories",
            "products",
            "warehouses",
            "inventoryStatusCounts",
            "locations",
            "dosage",
            "category",
        ],
    });
};

// Watch for changes in search input
watch(
    [
        () => search.value,
        () => location.value,
        () => per_page.value,
        () => warehouse.value,
        () => dosage.value,
        () => category.value,
        () => props.filters.page,
    ],
    () => {
        applyFilters();
    }
);

// Handle Excel file upload
const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Reset the file input
    event.target.value = "";

    // Validate file type
    const validTypes = [
        "application/vnd.ms-excel",
        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        "text/csv",
    ];
    if (!validTypes.includes(file.type)) {
        toast.error("Please upload a valid Excel file (.xlsx, .xls, .csv)");
        return;
    }

    // Validate file size (max 10MB)
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
        toast.error("File size exceeds the maximum limit of 10MB");
        return;
    }

    // Prepare form data
    const formData = new FormData();
    formData.append("file", file);

    // Show upload modal
    uploadingFile.value = true;
    uploadProgress.value = 0;
    showUploadModal.value = true;

    // Upload file
    await axios
        .post(route("inventories.import"), formData, {
            headers: {
                "Content-Type": "multipart/form-data",
            },
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round(
                    (progressEvent.loaded * 100) / progressEvent.total
                );
            },
        })
        .then((response) => {
            uploadingFile.value = false;
            uploadResults.value = response.data;
            toast.success(response.data.message);

            // Refresh inventory data
            applyFilters();
        })
        .catch((error) => {
            uploadingFile.value = false;
            toast.error(
                error.response?.data?.message || "Failed to upload file"
            );
            console.error("Upload error:", error);
        });
};

// Submit form
const submitForm = async () => {
    isSubmitting.value = true;

    await axios
        .post(route("inventories.store"), form.value)
        .then(() => {
            showAddModal.value = false;
            toast("Inventory item added successfully", "success");
            isSubmitting.value = false;
            applyFilters();
        })
        .catch((errors) => {
            console.log(errors);
            formErrors.value = errors;
            isSubmitting.value = false;
            toast.error(errors.response.data);
        });
};

// Format date
const formatDate = (date) => {
    if (!date) return "N/A";
    return moment(date).format("DD/MM/YYYY");
};

// Check if inventory is low
const isLowStock = (inventory) => {
    const total = inventory.items.reduce((sum, i) => sum + i.quantity, 0);
    return (
        total > 0 && total <= inventory.reorder_level
    );
};

// Check if inventory is out of stock
const isOutOfStock = (inventory) => {
    return inventory.items?.reduce((sum, i) => sum + i.quantity, 0) <= 0;
};

// Check if product is expiring soon (within 30 days)
const isExpiringSoon = (inventory) => {
    if (!inventory.expiry_date) return false;
    const expiryDate = moment(inventory.expiry_date);
    const today = moment();
    const diffDays = expiryDate.diff(today, "days");
    return diffDays <= 160 && diffDays > 0;
};

// Check if product is expired
const isExpired = (inventory) => {
    if (!inventory.expiry_date) return false;
    const expiryDate = moment(inventory.expiry_date);
    const today = moment();
    return expiryDate.isBefore(today);
};

// Computed properties for inventory status counts
const inStockCount = computed(() => {
    const stat = Object.values(props.inventoryStatusCounts).find(
        (s) => s.status === "in_stock"
    );
    return stat.count;
});

const lowStockCount = computed(() => {
    const stat = Object.values(props.inventoryStatusCounts).find(
        (s) => s.status === "low_stock"
    );
    return stat.count;
});

const outOfStockCount = computed(() => {
    const stat = Object.values(props.inventoryStatusCounts).find(
        (s) => s.status === "out_of_stock"
    );
    return stat.count;
});

const expiredCount = computed(() => {
    const stat = Object.values(props.inventoryStatusCounts).find(
        (s) => s.status === "expired"
    );
    return stat.count;
});

function getResults(page = 1) {
    props.filters.page = page;
}
</script>

<template>
    <Head title="Inventory Management" />

    <AuthenticatedLayout
        img="/assets/images/inventory.png"
        title="Management Your Inventory"
        description="Keeping Essentials Ready, Every Time"
    >
        <div class="mb-[100px]">
            <!-- Navigation Buttons and Heading -->
            <div class="flex justify-between items-center mb-6">
                <!-- Page Heading -->
                <h1 class="text-sm font-bold text-gray-800">
                    Warehouse Inventory
                </h1>
                <div class="flex space-x-4">
                    <!-- Excel Upload Button -->
                    <label
                        for="excelUpload"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-500 active:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150 cursor-pointer"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                            />
                        </svg>
                        Upload Excel
                    </label>
                    <input
                        id="excelUpload"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="hidden"
                        @change="handleFileUpload"
                    />
                </div>
            </div>
            <!-- Search and Filters -->
            <div
                class="mb-1 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4"
            >
                <div class="col-span-1 md:col-span-2 min-w-0">
                    <input
                        v-model="search"
                        type="text"
                        class="w-full"
                        placeholder="Search by item name, barcode, batch number, uom"
                    />
                </div>
                <div class="col-span-1 min-w-0">
                    <Multiselect
                        v-model="category"
                        :options="props.category"
                        :searchable="true"
                        :close-on-select="true"
                        :show-labels="false"
                        placeholder="Select a category"
                        :allow-empty="true"
                        class="multiselect--with-icon w-full"
                    >
                    </Multiselect>
                </div>
                <div class="col-span-1 min-w-0">
                    <Multiselect
                        v-model="dosage"
                        :options="props.dosage"
                        :searchable="true"
                        :close-on-select="true"
                        :show-labels="false"
                        placeholder="Select a dosage form"
                        :allow-empty="true"
                        class="multiselect--with-icon w-full"
                    >
                    </Multiselect>
                </div>
            </div>
            <div class="flex justify-end mt-3">
                <select
                    v-model="per_page"
                    class="rounded-full border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-[200px] mb-3"
                    @change="props.filters.page = 1"
                >
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                    <option value="100">100 per page</option>
                    <option value="200">200 per page</option>
                </select>
            </div>

            <!-- Add Button -->
            <div class="lg:grid lg:grid-cols-8 lg:gap-2">
                <div class="lg:col-span-7 overflow-auto w-full">
                    <!-- Inventory Table -->
                    <table
                        class="w-full border border-gray-300 text-sm text-left"
                    >
                        <thead class="bg-gray-100">
                            <tr class="divide-x divide-gray-300">
                                <th class="px-3 py-2">Item</th>
                                <th class="px-3 py-2">Category</th>
                                <th class="px-3 py-2 text-center">
                                    Item Details
                                </th>
                                <th class="px-3 py-2">Total Qty on Hand</th>
                                <th class="px-3 py-2">Reorder Level</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="inventory in props.inventories.data"
                                :key="inventory.id"
                                v-if="props.inventories.data.length > 0"
                                class="divide-x divide-gray-300"
                            >
                                <td class="px-3 py-2 font-medium text-gray-800">
                                    {{ inventory.product.name }}
                                </td>

                                <td class="px-3 py-2 text-gray-700">
                                    {{ inventory.product.category.name }}
                                </td>

                                <!-- Nested Table for Item Details -->
                                <td>
                                    <table
                                        class="w-full text-xs border border-gray-300"
                                    >
                                        <thead class="bg-gray-50">
                                            <tr class="text-gray-600">
                                                <th
                                                    class="border border-gray-300"
                                                >
                                                    UoM
                                                </th>
                                                <th
                                                    class="border border-gray-300"
                                                >
                                                    QTY
                                                </th>
                                                <th
                                                    class="border border-gray-300"
                                                >
                                                    Batch
                                                </th>
                                                <th
                                                    class="border border-gray-300"
                                                >
                                                    Expiry
                                                </th>
                                                <th
                                                    class="border border-gray-300"
                                                >
                                                    Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="item in inventory.items"
                                                :key="item.id"
                                                class="bg-white even:bg-gray-50"                                                
                                            >
                                            <td
                                                    :class="[
                                                        'border border-gray-300 px-2',
                                                        isExpired(item) ? 'border border-red-500 text-red-500' : ''
                                                    ]"
                                                >
                                                    {{ item.uom }}
                                                </td>
                                                <td
                                                    :class="[
                                                        'border border-gray-300 px-2',
                                                        isExpired(item) ? 'border border-red-500 text-red-500' : ''
                                                    ]"
                                                >
                                                    {{ item.quantity }}
                                                </td>
                                                <td
                                                    :class="[
                                                        'border border-gray-300 px-2',
                                                        isExpired(item) ? 'border border-red-500 text-red-500' : ''
                                                    ]"
                                                >
                                                    {{ item.batch_number }}
                                                </td>
                                                <td
                                                    :class="[
                                                        'border border-gray-300 px-2',
                                                        isExpired(item) ? 'border border-red-500 text-red-500' : ''
                                                    ]"
                                                >
                                                    {{ formatDate(item.expiry_date) }}
                                                </td>
                                                <td
                                                    :class="[
                                                        'border border-gray-300 px-2',
                                                        isExpired(item) ? 'border border-red-500 text-red-500' : ''
                                                    ]"
                                                >
                                                    <div
                                                        v-if="isExpiringSoon(item)"
                                                        class="flex flex-col items-center"
                                                    >
                                                        <img
                                                            src="/assets/images/soon_expire.png"
                                                            title="Expire soon"
                                                            class="w-6 h-6"
                                                            alt="Expire soon"
                                                        />
                                                    </div>
                                                    <div
                                                        v-if="isExpired(item)"
                                                        class="flex flex-col items-center"
                                                    >
                                                        <img
                                                            src="/assets/images/expired_stock.png"
                                                            title="Expired"
                                                            class="w-6 h-6"
                                                            alt="Expired"
                                                        />
                                                    </div>
                                                </td>
                                            </tr>                                           
                                        </tbody>
                                    </table>
                                </td>

                                <td class="px-3 py-2 text-gray-800">
                                    {{ inventory.items.reduce((sum, item) => sum + item.quantity, 0) }}
                                </td>

                                <td class="px-3 py-2 text-gray-800">
                                    {{ inventory.reorder_level }}
                                </td>

                                <td class="px-3 py-2">
                                    <div class="flex items-center space-x-2">
                                        <div
                                            v-if="isLowStock(inventory)"
                                            class="flex items-center"
                                        >
                                            <img
                                                src="/assets/images/low_stock.png"
                                                title="Low Stock"
                                                class="w-6 h-6"
                                                alt="Low Stock"
                                            />
                                        </div>

                                        <div
                                            v-if="isOutOfStock(inventory)"
                                            class="flex items-center"
                                        >
                                            <img
                                                src="/assets/images/out_stock.png"
                                                title="Out of Stock"
                                                class="w-6 h-6"
                                                alt="Out of Stock"
                                            />
                                        </div>
                                        <div
                                            v-if="
                                                !isLowStock(inventory) &&
                                                !isOutOfStock(inventory)
                                            "
                                            class="flex items-center"
                                        >
                                            <img
                                                src="/assets/images/in_stock.png"
                                                title="In Stock"
                                                class="w-6 h-6"
                                                alt="In Stock"
                                            />
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-3 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <div class="flex items-center space-x-3">
                                        <Link
                                            :href="route('orders.create')"
                                            v-if="isLowStock(inventory)"
                                            class="p-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-full"
                                            >
                                            <img src="/assets/images/reorder-status.png" alt="Reorder Status" class="w-6 h-6" title="Reorder Status">
                                            
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else>
                                <td colspan="7" class="px-3 py-2 text-center text-gray-500">
                                    No inventory items found
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-2 flex justify-end">
                        <TailwindPagination
                            :data="props.inventories"
                            @pagination-change-page="getResults"
                            :limit="2"
                        />
                    </div>
                </div>
                <div class="lg:col-span-1">
                    <div class="sticky top-0 z-10 shadow-sm">
                        <div class="space-y-4">
                            <div
                                class="flex items-center rounded-lg bg-green-50"
                            >
                                <img
                                    src="/assets/images/in_stock.png"
                                    class="w-[40px] h-[40px]"
                                    alt="In Stock"
                                />
                                <div class="ml-4 flex flex-col">
                                    <span
                                        class="text-xl font-bold text-green-600"
                                        >{{ inStockCount }}</span
                                    >
                                    <span class="ml-2 text-xs text-green-600"
                                        >In Stock</span
                                    >
                                </div>
                            </div>
                            <div
                                class="flex items-center rounded-lg bg-orange-50"
                            >
                                <img
                                    src="/assets/images/low_stock.png"
                                    class="w-[40px] h-[40px]"
                                    alt="Low Stock"
                                />
                                <div class="ml-4 flex flex-col">
                                    <span
                                        class="text-xl font-bold text-orange-600"
                                        >{{ lowStockCount }}</span
                                    >
                                    <span class="ml-2 text-xs text-orange-600"
                                        >Low Stock</span
                                    >
                                </div>
                            </div>
                            <div
                                class="flex items-center rounded-lg bg-red-50"
                            >
                                <img
                                    src="/assets/images/out_stock.png"
                                    class="w-[40px] h-[40px]"
                                    alt="Out of Stock"
                                />
                                <div class="ml-4 flex flex-col">
                                    <span
                                        class="text-xl font-bold text-red-600"
                                        >{{ outOfStockCount }}</span
                                    >
                                    <span class="ml-2 text-xs text-red-600"
                                        >Out of Stock</span
                                    >
                                </div>
                            </div>
                            <div
                                class="flex items-center rounded-lg bg-gray-50"
                            >
                                <img
                                    src="/assets/images/expired_stock.png"
                                    class="w-[40px] h-[40px]"
                                    alt="Expired"
                                />
                                <div class="ml-4 flex flex-col">
                                    <span
                                        class="text-xl font-bold text-gray-600"
                                        >{{ expiredCount }}</span
                                    >
                                    <span class="ml-2 text-xs text-gray-600"
                                        >Expired</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Excel Upload Modal -->
        <Modal :show="showUploadModal" @close="showUploadModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Excel Import
                </h2>

                <div v-if="uploadingFile">
                    <p class="mb-4">
                        Uploading and processing your Excel file...
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div
                            class="bg-blue-600 h-2.5 rounded-full"
                            :style="{ width: uploadProgress + '%' }"
                        ></div>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        {{ uploadProgress }}% Complete
                    </p>
                </div>

                <div v-else-if="uploadResults">
                    <div
                        class="mb-4 p-4 rounded-md"
                        :class="{
                            'bg-green-50 border border-green-200':
                                uploadResults.success,
                            'bg-red-50 border border-red-200':
                                !uploadResults.success,
                        }"
                    >
                        <p
                            class="font-medium"
                            :class="{
                                'text-green-800': uploadResults.success,
                                'text-red-800': !uploadResults.success,
                            }"
                        >
                            {{ uploadResults.message }}
                        </p>
                    </div>

                    <div
                        v-if="
                            uploadResults.errors &&
                            uploadResults.errors.length > 0
                        "
                        class="mt-4"
                    >
                        <h3 class="font-medium text-gray-900 mb-2">Errors:</h3>
                        <ul class="list-disc pl-5 space-y-1">
                            <li
                                v-for="(error, index) in uploadResults.errors"
                                :key="index"
                                class="text-sm text-red-600"
                            >
                                {{ error }}
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <SecondaryButton
                            @click="
                                showUploadModal = false;
                                uploadResults = null;
                            "
                        >
                            Close
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>