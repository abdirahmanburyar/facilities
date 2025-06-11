<template>
    <AuthenticatedLayout title="Monthly Inventory Report">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Monthly Inventory Report
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8">
                        
                        <!-- Current Facility Info -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">Current Facility</h3>
                            <p class="text-blue-800">{{ facility.name }}</p>
                            <p class="text-sm text-blue-600">{{ facility.facility_type }}</p>
                        </div>

                        <!-- Report Generation Form -->
                        <form @submit.prevent="generateReport" class="mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <!-- Year Selection -->
                                <div>
                                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                        Year
                                    </label>
                                    <select 
                                        id="year"
                                        v-model="form.year"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                        <option v-for="year in availableYears" :key="year" :value="year">
                                            {{ year }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Month Selection -->
                                <div>
                                    <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                                        Month
                                    </label>
                                    <select 
                                        id="month"
                                        v-model="form.month"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    >
                                        <option v-for="(month, index) in months" :key="index + 1" :value="index + 1">
                                            {{ month }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Generate Button -->
                            <div class="mt-6 flex justify-between items-center">
                                <div class="flex items-center space-x-4">
                                    <button
                                        type="submit"
                                        :disabled="isGenerating"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    >
                                        <svg v-if="isGenerating" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ isGenerating ? 'Generating...' : 'Generate Report' }}
                                    </button>
                                    
                                    <label class="flex items-center">
                                        <input 
                                            type="checkbox" 
                                            v-model="form.force"
                                            class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        >
                                        <span class="text-sm text-gray-700">Force regenerate if exists</span>
                                    </label>
                                </div>
                                
                                <button
                                    type="button"
                                    @click="viewReport"
                                    :disabled="!form.year || !form.month"
                                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                >
                                    View Report
                                </button>
                            </div>
                        </form>

                        <!-- Status Messages -->
                        <div v-if="statusMessage" class="mb-6 p-4 rounded-md" :class="statusMessage.type === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'">
                            {{ statusMessage.message }}
                        </div>

                        <!-- Recent Reports -->
                        <div class="mt-12">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Reports</h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <p class="text-gray-500 text-center">No recent reports available</p>
                                <p class="text-sm text-gray-400 text-center mt-2">Generated reports will appear here</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Props
const props = defineProps({
    facility: Object,
    currentYear: Number,
    currentMonth: Number,
})

// Reactive data
const form = ref({
    year: props.currentYear,
    month: props.currentMonth,
    force: false
})
const isGenerating = ref(false)
const statusMessage = ref(null)

// Computed properties
const availableYears = computed(() => {
    const currentYear = new Date().getFullYear()
    return Array.from({ length: 6 }, (_, i) => currentYear - i)
})

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]

// Methods
const generateReport = async () => {
    isGenerating.value = true
    statusMessage.value = null

    try {
        const response = await fetch('/reports/monthly-inventory/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                year: form.value.year,
                month: form.value.month,
                force: form.value.force,
            }),
        })

        const data = await response.json()

        if (data.success) {
            statusMessage.value = {
                type: 'success',
                message: data.message
            }
        } else {
            statusMessage.value = {
                type: 'error',
                message: data.message || 'Failed to generate report'
            }
        }
    } catch (error) {
        statusMessage.value = {
            type: 'error',
            message: 'An error occurred while generating the report'
        }
    } finally {
        isGenerating.value = false
    }
}

const viewReport = () => {
    const reportPeriod = `${form.value.year}-${String(form.value.month).padStart(2, '0')}`
    router.get('/reports/monthly-inventory/view', {
        report_period: reportPeriod,
    })
}
</script>
