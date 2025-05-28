<template>
    <AuthenticatedLayout>
        <div id="printThis">
             <!-- Back button -->
             <div class="mb-6 flex justify-between items-center">
                <Link :href="route('dispence.index')" class="flex items-center text-gray-600 hover:text-gray-900">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dispences
                </Link>
                <button @click="printPrescription" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Prescription
                </button>
            </div>

            <!-- Prescription Card -->
            <div id="prescription" class="bg-white shadow-lg rounded-lg overflow-hidden border-t-8 border-blue-600 mb-[100px]">
                <!-- Facility Header -->
                <div class="bg-gray-50 p-4 border-b">
                    <div class="text-center">
                        <h1 class="text-xl font-bold text-gray-900 mb-1">{{ props.dispence.facility.name }}</h1>
                        <p class="text-gray-600">{{ props.dispence.facility.facility_type }}</p>
                        <div class="text-sm text-gray-600 mt-1">
                            <p>{{ props.dispence.facility.address }} - {{ props.dispence.facility.district }}</p>
                            <p>Tel: {{ props.dispence.facility.phone }} | Email: {{ props.dispence.facility.email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Header -->
                <div class="p-8 border-b">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-2">PRESCRIPTION</h2>
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

                <!-- Diagnosis -->
                <div class="px-8 pt-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-3">Diagnosis</h2>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <p class="text-gray-700 whitespace-pre-line">{{ props.dispence.diagnosis }}</p>
                        </div>
                    </div>
                </div>

                <!-- Medications -->
                <div class="px-8 pb-8">
                    <div class="flex items-center mb-6">
                        <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <h2 class="text-xl font-semibold text-gray-800">Medications</h2>
                    </div>

                    <!-- Medications Table -->
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Medication</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Dose</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Frequency</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Start Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(item, index) in props.dispence.items" :key="item.id" 
                                    :class="{'bg-gray-50': index % 2 === 0}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm border-r border-gray-200">{{ index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap border-r border-gray-200">
                                        <div class="text-sm font-medium text-gray-900">{{ item.product.name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm border-r border-gray-200">{{ item.dose }} units</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm border-r border-gray-200">{{ item.frequency }} times/day</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm border-r border-gray-200">{{ item.duration }} days</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm border-r border-gray-200">{{ moment(item.start_date).format('DD/MM/YYYY') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.quantity }} units</td>
                                </tr>
                            </tbody>
                        </table>
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

const printPrescription = () => {
    window.print();
};

const props = defineProps({
    dispence: Object,
});
</script>