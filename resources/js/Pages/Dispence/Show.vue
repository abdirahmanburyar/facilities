<template>
    <AuthenticatedLayout>
            <!-- Header with back button -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex flex-col items-start">
                    <Link :href="route('dispence.index')" class="flex items-center text-gray-600 hover:text-gray-900">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Dispences
                    </Link>
                    <h1 class="text-3xl font-bold text-gray-900 mt-2">Dispence Details</h1>
                </div>
            </div>

            <!-- Dispence Information Card -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Dispence Information</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Dispence Number</p>
                        <p class="font-medium">{{ props.dispence.dispence_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date</p>
                        <p class="font-medium">{{ props.dispence.dispence_date }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Patient Name</p>
                        <p class="font-medium">{{ props.dispence.patient_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Patient Phone</p>
                        <p class="font-medium">{{ props.dispence.patient_phone }}</p>
                    </div>
                </div>
            </div>

            <!-- Dispenced Items Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold">Dispenced Items</h2>
                </div>
                {{props.dispence.items}}
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="item in props.dispence.items" :key="item.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ item.product.name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ item.quantity }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ item.instructions }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    dispence: Object,
});
</script>