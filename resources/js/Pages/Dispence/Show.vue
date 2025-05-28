<template>
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto py-6">
            <!-- Back button -->
            <div class="mb-6">
                <Link :href="route('dispence.index')" class="flex items-center text-gray-600 hover:text-gray-900 w-fit">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Dispences
                </Link>
            </div>

            <!-- Prescription Card -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden border-t-8 border-blue-600">
                <!-- Header -->
                <div class="p-8 border-b">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">PRESCRIPTION</h1>
                            <p class="text-gray-600">{{ props.dispence.dispence_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600">Date:</p>
                            <p class="font-semibold">{{ moment(props.dispence.dispence_date).format('DD/MM/YYYY') }}</p>
                        </div>
                    </div>

                    <!-- Patient Info -->
                    <div class="mt-6 pt-6 border-t">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-600">Patient Name:</p>
                                <p class="font-semibold text-lg">{{ props.dispence.patient_name }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Phone:</p>
                                <p class="font-semibold">{{ props.dispence.patient_phone }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Medications -->
                <div class="p-8">
                    <div class="flex items-center mb-6">
                        <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-800">Medications</h2>
                    </div>

                    <!-- Medication List -->
                    <div class="space-y-6">
                        <div v-for="item in props.dispence.items" :key="item.id" 
                             class="p-4 rounded-lg border-2 border-gray-200 hover:border-blue-200 transition-colors">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-800">{{ item.product.name }}</h3>
                                    <div class="mt-2 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-gray-600 text-sm">Dose:</p>
                                            <p class="font-medium">{{ item.dose }} units</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600 text-sm">Frequency:</p>
                                            <p class="font-medium">{{ item.frequency }} times per day</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600 text-sm">Duration:</p>
                                            <p class="font-medium">{{ item.duration }} days</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-600 text-sm">Total Quantity:</p>
                                            <p class="font-medium">{{ item.quantity }} units</p>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <p class="text-gray-600 text-sm">Start Date:</p>
                                        <p class="font-medium">{{ moment(item.start_date).format('DD/MM/YYYY') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-8 bg-gray-50 border-t">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-600 text-sm">Dispenced By:</p>
                            <p class="font-medium">{{ props.dispence.dispenced_by?.name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-600 text-sm">Dispence Date:</p>
                            <p class="font-medium">{{ moment(props.dispence.created_at).format('DD/MM/YYYY HH:mm') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import moment from 'moment';

const props = defineProps({
    dispence: Object,
});
</script>