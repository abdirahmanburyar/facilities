<template>
   <AuthenticatedLayout>
    <div class="flex items-center justify-between mb-4">
         <div class="flex flex-col items-start">
            <Link :href="route('dispence.index')" class="flex items-center">
                 <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                 Back
            </Link>
            <h1 class="text-3xl font-bold text-gray-900">Dispence</h1>
         </div>
         <Link :href="route('dispence.create')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
             Create New Dispence
         </Link>
     </div>
   </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
const props = defineProps({
    dispences: Array,
})
</script>
