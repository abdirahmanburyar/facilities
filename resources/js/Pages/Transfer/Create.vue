<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Multiselect from "vue-multiselect";
import "vue-multiselect/dist/vue-multiselect.css";
import "@/Components/multiselect.css";
import axios from "axios";
import { useToast } from "vue-toastification";
import Swal from "sweetalert2";
import moment from "moment";

const toast = useToast();

const props = defineProps({
    inventories: Array,
    warehouses: {
        type: Array,
        default: () => [],
    },
    facilities: {
        type: Array,
        default: () => [],
    },
    transferID: {
        type: String,
        required: true,
    },
    facilityID: {
        type: Number,
        required: true,
    },
});

const sourceType = ref("warehouse");
const destinationType = ref("warehouse");
const loading = ref(false);
const selectedSource = ref(null);
const selectedDestination = ref(null);

const form = ref({
    destination_type: "warehouse",
    destination_id: null,
    source_type: "facility",
    source_id: props.facilityID,
    transfer_date: moment().format("YYYY-MM-DD"),
    transferID: props.transferID,
    transfer_type: "",
    items: [
        {
            id: null,
            product_id: "",
            product: null,
            quantity: 0,
            available_quantity: 0,
            details: [],
        },
    ],
});

const errors = ref({});

const destinationOptions = computed(() => {
    return destinationType.value === "warehouse"
        ? props.warehouses
        : props.facilities;
});

// Filter out the selected source from destination options if they are of the same type
const filteredDestinationOptions = computed(() => {
    if (sourceType.value === destinationType.value && selectedSource.value) {
        return destinationOptions.value.filter(
            (item) => item.id !== selectedSource.value.id
        );
    }
    return destinationOptions.value;
});

const handleDestinationSelect = (selected) => {
    form.value.destination_id = selected ? selected.id : null;
};

// Watch for changes in destination type and update form
watch(destinationType, (newValue) => {
    form.value.destination_type = newValue;
    form.value.destination_id = null;
    selectedDestination.value = null;
});

const submit = async () => {
    loading.value = true;
    console.log(form.value);

    await axios
        .post(route("transfers.store"), form.value)
        .then((response) => {
            loading.value = false;
            Swal.fire({
                title: "Success!",
                text: response.data,
                icon: "success",
                confirmButtonColor: "#4F46E5",
            }).then(() => {
                router.get(route("transfers.index"));
            });
        })
        .catch((error) => {
            console.error(error.response);
            loading.value = false;
            Swal.fire({
                title: "Error!",
                text: error.response?.data || "Failed to create transfer",
                icon: "error",
                confirmButtonColor: "#4F46E5",
            });
        });
};

const isLoading = ref([]);
async function handleProductSelect(index, selected) {
    isLoading.value[index] = true;
    const item = form.value.items[index];
    item.details = [];
    if (selected) {
        await axios
            .post(route("transfers.inventory"), {
                product_id: selected.id,
            })
            .then((response) => {
                console.log(response.data);
                isLoading.value[index] = false;

                item.details = response.data;
                item.available_quantity = response.data?.reduce(
                    (sum, item) => sum + item.quantity,
                    0
                );
                item.product = selected;
                item.product_id = selected.id;
                addNewItem();
            })
            .catch((error) => {
                isLoading.value[index] = false;
                console.log(error);

                // Clear product fields on error
                item.product_id = null;
                item.product = null;
                item.details = [];
                item.available_quantity = 0;

                Swal.fire({
                    title: "Error!",
                    text: error.response.data,
                    icon: "error",
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 5000,
                });
            });
    }
}

function addNewItem() {
    form.value.items.push({
        id: null,
        product_id: null,
        product: null,
        available_quantity: 0,
        quantity: 0,
        details: [],
    });
}

function removeItem(index) {
    // Check if we have more than one item before removing
    if (form.value.items.length > 1) {
        form.value.items.splice(index, 1);
    }
}

function checkQuantity(index) {
    const item = form.value.items[index];

    // Ensure quantity is at least 1
    if (item.quantity < 1) {
        item.quantity = 1;
        toast.info("Minimum quantity is 1");
    }

    // Ensure quantity doesn't exceed available quantity
    if (item.quantity > item.available_quantity) {
        // Reset to available quantity if exceeded
        item.quantity = item.available_quantity;
        toast.warning(
            `Quantity reset to maximum available (${item.available_quantity})`
        );
    }
}

function formatDate(date) {
    return moment(date).format("DD/MM/YYYY");
}
</script>

<template>
    <AuthenticatedLayout
        title="Transfer Item"
        description="Transfer Item"
        img="/assets/images/transfer.png"
    >
        <div class="mb-5">
            <div class="p-6 text-gray-900">
                <div class="flex justify-between mb-6">
                    <h2 class="text-xs">Transfer Item</h2>
                    <div class="flex flex-col">
                        Transfer ID: {{ props.transferID }}
                    </div>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="flex gap-2">
                        <div>
                            <div class="flex flex-col">
                                <label for="transfer_date">Transfer Date</label>
                                <input
                                    type="date"
                                    v-model="form.transfer_date"
                                    class="form-input"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <label for="transfer_type">Transfer Type</label>
                            <textarea
                                name="transfer_type"
                                id="transfer_type"
                                v-model="form.transfer_type"
                                class="form-input w-[300px]"
                                placeholder="Enter transfer type [Soon to expire, Replenishment, ...]"
                            ></textarea>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Source Type Selection -->
                        <div>
                            <InputLabel value="Transfer From" />
                            <div class="mt-2 space-x-4">
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        v-model="destinationType"
                                        value="warehouse"
                                        class="form-radio"
                                    />
                                    <span class="ml-2">Warehouse</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input
                                        type="radio"
                                        v-model="destinationType"
                                        value="facility"
                                        class="form-radio"
                                    />
                                    <span class="ml-2">Facility</span>
                                </label>
                            </div>
                        </div>

                        <!-- Destination Selection -->
                        <div>
                            <InputLabel
                                :value="`Select Destination ${
                                    destinationType === 'warehouse'
                                        ? 'Warehouse'
                                        : 'Facility'
                                }`"
                            />
                            <Multiselect
                                v-model="selectedDestination"
                                :options="filteredDestinationOptions"
                                :searchable="true"
                                :close-on-select="true"
                                :show-labels="false"
                                :allow-empty="true"
                                :placeholder="`Select destination ${
                                    destinationType === 'warehouse'
                                        ? 'warehouse'
                                        : 'facility'
                                }`"
                                track-by="id"
                                label="name"
                                @select="handleDestinationSelect"
                                required
                                :class="{
                                    'border-red-500': errors.destination_id,
                                }"
                                @open="errors.destination_id = null"
                            >
                                <template v-slot:option="{ option }">
                                    <span>{{ option.name }}</span>
                                </template>
                            </Multiselect>
                            <InputError
                                :message="errors.destination_id"
                                class="mt-2"
                            />
                        </div>
                        <!-- here for items -->
                    </div>

                    <div class="mb-4">
                        <table class="min-w-full">
                            <thead class="w-full bg-gray-50">
                                <tr>
                                    <th
                                        class="w-[400px] text-left text-xs font-medium text-black border border-black capitalize"
                                    >
                                        Item
                                    </th>
                                    <th
                                        class="w-[300px] text-left text-xs font-medium text-black capitalize border border-black tracking-wider"
                                    >
                                        Item Information
                                    </th>
                                    <th
                                        class="w-[200px] text-left text-xs font-medium text-black capitalize border border-black tracking-wider"
                                    >
                                        Available quantity
                                    </th>
                                    <th
                                        class="w-[200px] text-left text-xs font-medium text-black capitalize border border-black tracking-wider"
                                    >
                                        Quantity to release
                                    </th>
                                    <th
                                        class="w-[70px] text-left text-xs font-medium text-black capitalize border border-black tracking-wider"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Inventory Items rows -->
                                <tr
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="border border-black">
                                        <Multiselect
                                            v-model="item.product"
                                            :value="item.product_id"
                                            :options="props.inventories"
                                            placeholder="Search for an item..."
                                            required
                                            track-by="id"
                                            label="name"
                                            :searchable="true"
                                            :allow-empty="true"
                                            :loading="isLoading[index]"
                                            @select="
                                                handleProductSelect(
                                                    index,
                                                    $event
                                                )
                                            "
                                        />
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-1 py-1 border border-black text-sm text-gray-500"
                                    >
                                        <table
                                            class="w-full text-xs border border-black"
                                        >
                                            <thead class="bg-gray-50">
                                                <tr class="text-gray-600">
                                                    <th
                                                        class="text-xs border border-black"
                                                    >
                                                        UoM
                                                    </th>
                                                    <th
                                                        class="text-xs border border-black"
                                                    >
                                                        QTY
                                                    </th>
                                                    <th
                                                        class="text-xs border border-black"
                                                    >
                                                        Batch
                                                    </th>
                                                    <th
                                                        class="text-xs border border-black"
                                                    >
                                                        Expiry
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr
                                                    v-for="detail in item?.details"
                                                    :key="detail.id"
                                                    class="bg-white even:bg-gray-50"
                                                >
                                                    <td
                                                        class="text-xs border border-black"
                                                    >
                                                        {{ detail.uom }}
                                                    </td>
                                                    <td
                                                        class="text-xs border border-black"
                                                    >
                                                        {{ detail.quantity }}
                                                    </td>
                                                    <td
                                                        class="text-xs border border-black"
                                                    >
                                                        {{
                                                            detail.batch_number
                                                        }}
                                                    </td>
                                                    <td
                                                        class="text-xs border border-black"
                                                    >
                                                        {{
                                                            formatDate(
                                                                detail.expiry_date
                                                            )
                                                        }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>

                                    <td
                                        class="whitespace-nowrap px-1 py-1 border border-black text-center text-sm text-black"
                                    >
                                        {{ item.available_quantity }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-1 py-1 border border-black text-sm text-black"
                                    >
                                        <input
                                            type="text"
                                            v-model.number="item.quantity"
                                            required
                                            :class="[
                                                'w-full text-sm',
                                                errors[`item_${index}_quantity`]
                                                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                                                    : '',
                                            ]"
                                            min="1"
                                            :max="item.available_quantity"
                                            :disabled="!item.product?.id"
                                            placeholder="0"
                                            @input="
                                                checkQuantity(index);
                                                errors[
                                                    `item_${index}_quantity`
                                                ] = null;
                                            "
                                        />
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-1 py-1 border border-black text-sm text-gray-500"
                                    >
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
                                            class="text-red-600 hover:text-red-900"
                                            :disabled="form.items.length <= 1"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-5 w-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="flex items-center px-1 py-1 justify-between space-x-4 mb-4"
                    >
                        <button
                            type="button"
                            @click="addNewItem"
                            class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                        >
                            Add Another Item
                        </button>
                        <div>
                            <SecondaryButton
                                :href="route('transfers.index')"
                                as="a"
                                :disabled="loading"
                                class="opacity-75"
                                :class="{
                                    'opacity-50 cursor-not-allowed': loading,
                                }"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton :disabled="loading" class="relative">
                                <span v-if="loading" class="absolute left-2">
                                    <svg
                                        class="animate-spin h-5 w-5 text-white"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                </span>
                                <span :class="{ 'pl-7': loading }">{{
                                    loading ? "Processing..." : "Transfer Item"
                                }}</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
