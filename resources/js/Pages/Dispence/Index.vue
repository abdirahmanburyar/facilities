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
    <div class="mt-6 flex justify-between items-center gap-2">
        <input type="text" v-model="search" class="w-[400px] border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search...">
        <select v-model="per_page" class="w-[200px] border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring focus:ring-indigo-500 focus:border-indigo-500">
            <option value="10">10 per page</option>
            <option value="25">25 per page</option>
            <option value="50">50 per page</option>
            <option value="100">100 per page</option>
        </select>
    </div>
    
    <div class="mt-6 mb-6">
      <div class="bg-white">
        <template v-if="props.dispences.data.length > 0">
          <table class="min-w-full">
            <thead>
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-black">Dispence Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-black">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-black">Patient Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-black">Phone</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-black">Items Count</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border border-black">Dispenced By</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="dispence in props.dispences.data" :key="dispence.id">
                <td class="px-6 py-4 whitespace-nowrap border border-black">
                  <Link :href="route('dispence.show', dispence.id)" class="text-indigo-600 hover:text-indigo-900">
                    {{ dispence.dispence_number }}
                  </Link>
                </td>
                <td class="px-6 py-4 whitespace-nowrap border border-black">{{ moment( dispence.dispence_date).format('DD/MM/YYYY') }}</td>
                <td class="px-6 py-4 whitespace-nowrap border border-black">{{ dispence.patient_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap border border-black">{{ dispence.patient_phone }}</td>
                <td class="px-6 py-4 whitespace-nowrap border border-black">{{ dispence.items_count }}</td>
                <td class="px-6 py-4 whitespace-nowrap border border-black">{{ dispence.dispenced_by?.name }}</td>
              </tr>
            </tbody>
          </table>
        </template>
        <template v-else>
          <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No dispences found</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ search ? 'No results match your search criteria.' : 'Get started by creating a new dispence.' }}
            </p>
            <div class="mt-6" v-if="!search">
              <Link :href="route('dispence.create')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Create New Dispence
              </Link>
            </div>
          </div>
        </template>
      </div>
      <div class="flex justify-end mt-4">
        <TailwindPagination :data="props.dispences" @pagination-change-page="getResults" />
      </div>
    </div>
   </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { TailwindPagination } from "laravel-vue-pagination";
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.css';
import '@/Components/multiselect.css';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import moment from 'moment';

const props = defineProps({
    dispences: Object,
    filters: Object,
})

const per_page = ref(props.filters.per_page);
const search = ref(props.filters.search);

watch([ () => search.value, () => per_page.value, () => props.filters.page ], () => {
    reloadDispences();
})

function reloadDispences() {
    const query = {}
    if(search.value) query.search = search.value
    if(per_page.value) {
        props.filters.page = 1
        query.per_page = per_page.value
    }
    if(props.filters.page) query.page = props.filters.page
    router.get(route('dispence.index'), query, { preserveState: true, preserveScroll: true, only: ['dispences'] })
}

function getResults(page = 1){
    props.filters.page = page
}
</script>
