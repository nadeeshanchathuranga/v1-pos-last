<template>
  <Head title="Employer Commission" />
  <Banner />
  <div class="flex flex-col items-center justify-start min-h-screen py-8 space-y-8 bg-gray-100 md:px-36 px-16">
    <Header />
    
    <!-- Main Container -->
    <div class="w-full md:w-5/6 py-8 space-y-8">
      
      <!-- Page Header -->
      <div class="flex md:flex-row flex-col w-full">
        <div class="flex items-center w-full h-16 space-x-4 rounded-2xl">
          <Link href="/">
            <img src="/images/back-arrow.png" class="w-14 h-14" />
          </Link>
          <p class="text-4xl font-bold tracking-wide text-black uppercase">
            Employer Commission
          </p>
        </div>
      </div>

      <!-- Quick Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Commission Card -->
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
          <p class="text-sm text-gray-600 mb-1">Total Commission</p>
          <p class="text-2xl font-bold text-gray-900">Rs. {{ formatCurrency(summary?.total_commission || 0) }}</p>
        </div>

        <!-- Total Sales Card -->
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
              </svg>
            </div>
          </div>
          <p class="text-sm text-gray-600 mb-1">Total Sales</p>
          <p class="text-2xl font-bold text-gray-900">Rs. {{ formatCurrency(summary?.total_sales || 0) }}</p>
        </div>

        <!-- Transactions Card -->
        <div class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow p-6 border border-gray-100">
          <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
          </div>
          <p class="text-sm text-gray-600 mb-1">Transactions</p>
          <p class="text-2xl font-bold text-gray-900">{{ summary?.total_transactions || 0 }}</p>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 border border-gray-100">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-semibold text-gray-900">Filter Results</h2>
          <button 
            v-if="hasActiveFilters"
            @click="clearFilters"
            class="text-sm text-blue-600 hover:text-blue-700 font-medium"
          >
            Clear All
          </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Quick Date Filters -->
          <div class="md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Quick Filter</label>
            <div class="flex flex-col space-y-2">
              <button 
                @click="setQuickFilter('today')"
                :class="quickFilter === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition"
              >
                Today
              </button>
              <button 
                @click="setQuickFilter('this_month')"
                :class="quickFilter === 'this_month' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                class="px-4 py-2 rounded-lg text-sm font-medium transition"
              >
                This Month
              </button>
              <button 
                v-if="hasActiveFilters"
                @click="clearFilters"
                class="px-4 py-2 rounded-lg text-sm font-medium bg-red-100 text-red-700 hover:bg-red-200 transition"
              >
                Clear All
              </button>
            </div>
          </div>

          <!-- Employee Filter -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
            <select 
              v-model="filters.employee_id" 
              @change="applyFilters"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            >
              <option value="">All Employees</option>
              <option v-for="employee in allEmployees" :key="employee.id" :value="employee.id">
                {{ employee.name }}
              </option>
            </select>
          </div>

          <!-- Date Range -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
            <input 
              type="date" 
              v-model="filters.start_date" 
              @change="applyFilters"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
            <input 
              type="date" 
              v-model="filters.end_date" 
              @change="applyFilters"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
            />
          </div>
        </div>
      </div>

      <!-- Employee Commission Cards -->
      <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Employee Summary</h2>
        
        <div v-if="employeeSummary.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="summary in employeeSummary" 
            :key="summary.employee_id"
            class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 overflow-hidden group"
          >
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-100">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-blue-600 font-semibold text-lg">
                      {{ summary.employee_name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <h3 class="font-semibold text-gray-900">{{ summary.employee_name }}</h3>
                </div>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  {{ summary.transaction_count }} sales
                </span>
              </div>
            </div>

            <!-- Card Body -->
            <div class="p-6 space-y-4">
              <!-- Total Commission -->
              <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Commission</p>
                <p class="text-2xl font-bold text-green-600">Rs. {{ formatCurrency(summary.total_commission) }}</p>
              </div>

              <!-- Stats Grid -->
              <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                <div>
                  <p class="text-xs text-gray-500 mb-1">Total Sales</p>
                  <p class="text-sm font-semibold text-gray-900">Rs. {{ formatCurrency(summary.total_sales) }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-500 mb-1">Avg Commission</p>
                  <p class="text-sm font-semibold text-gray-900">Rs. {{ formatCurrency(summary.average_commission) }}</p>
                </div>
              </div>

              <!-- View Details Button -->
              <button
                @click="openDetailsModal(summary.employee_id, summary.employee_name)"
                class="block w-full mt-4 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-center rounded-lg font-medium transition-colors"
              >
                View Details
              </button>
            </div>
          </div>
        </div>

        <div v-else class="bg-white rounded-2xl shadow-sm p-12 text-center border border-gray-100">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <p class="text-gray-500 text-lg">No commission data available</p>
          <p class="text-gray-400 text-sm mt-2">Try adjusting your filters</p>
        </div>
      </div>

      <!-- Details Modal -->
      <div 
        v-if="showDetailsModal" 
        class="fixed inset-0 z-50 overflow-y-auto" 
        @click.self="closeDetailsModal"
      >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
          <!-- Background overlay -->
          <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeDetailsModal"></div>

          <!-- Modal panel -->
          <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                    <span class="text-white font-semibold text-lg">
                      {{ selectedEmployeeName.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <h3 class="text-xl font-semibold text-white">{{ selectedEmployeeName }}</h3>
                    <p class="text-blue-100 text-sm">Commission Details</p>
                  </div>
                </div>
                <button 
                  @click="closeDetailsModal"
                  class="text-white hover:text-gray-200 transition"
                >
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
              <div v-if="loadingDetails" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="text-gray-500 mt-2">Loading details...</p>
              </div>

              <div v-else-if="selectedEmployeeDetails.length > 0" class="overflow-x-auto">
                <table class="w-full">
                  <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sales</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                      v-for="detail in selectedEmployeeDetails"
                      :key="detail.id"
                      class="hover:bg-gray-50 transition"
                    >
                      <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                        {{ formatDate(detail.commission_date) }}
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-900">{{ detail.product?.name || 'N/A' }}</td>
                      <td class="px-4 py-3 text-sm text-gray-600">{{ detail.category?.name || 'N/A' }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900">{{ detail.quantity }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900">Rs. {{ formatCurrency(detail.total_product_amount) }}</td>
                      <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                          {{ Number(detail.commission_percentage).toFixed(1) }}%
                        </span>
                      </td>
                      <td class="px-4 py-3 text-sm font-semibold text-green-600">
                        Rs. {{ formatCurrency(detail.commission_amount) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-else class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <p class="text-gray-500">No commission records found</p>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-4 flex justify-end">
              <button
                @click="closeDetailsModal"
                class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium transition-colors"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <Footer />
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Header from '@/Components/custom/Header.vue';
import Footer from '@/Components/custom/Footer.vue';
import Banner from '@/Components/Banner.vue';

const props = defineProps({
  commissions: Array,
  employeeSummary: Array,
  allEmployees: Array,
  filters: Object,
  summary: Object,
});

const filters = reactive({
  employee_id: props.filters.employee_id || '',
  start_date: props.filters.start_date || '',
  end_date: props.filters.end_date || '',
});

const quickFilter = ref('');
const showDetailsModal = ref(false);
const selectedEmployeeId = ref(null);
const selectedEmployeeName = ref('');
const selectedEmployeeDetails = ref([]);
const loadingDetails = ref(false);

const hasActiveFilters = computed(() => {
  return filters.employee_id || filters.start_date || filters.end_date;
});

const openDetailsModal = async (employeeId, employeeName) => {
  selectedEmployeeId.value = employeeId;
  selectedEmployeeName.value = employeeName;
  showDetailsModal.value = true;
  loadingDetails.value = true;
  
  try {
    // Build URL with query parameters for date filters
    let url = route('cashier-commission.show', employeeId);
    const params = new URLSearchParams();
    
    if (filters.start_date) {
      params.append('start_date', filters.start_date);
    }
    if (filters.end_date) {
      params.append('end_date', filters.end_date);
    }
    
    if (params.toString()) {
      url += '?' + params.toString();
    }
    
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    const data = await response.json();
    selectedEmployeeDetails.value = data.commissions || [];
  } catch (error) {
    console.error('Error loading details:', error);
    selectedEmployeeDetails.value = [];
  } finally {
    loadingDetails.value = false;
  }
};

const closeDetailsModal = () => {
  showDetailsModal.value = false;
  selectedEmployeeId.value = null;
  selectedEmployeeName.value = '';
  selectedEmployeeDetails.value = [];
};

const applyFilters = () => {
  quickFilter.value = '';
  router.get(route('cashier-commission.index'), filters, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  filters.employee_id = '';
  filters.start_date = '';
  filters.end_date = '';
  quickFilter.value = '';
  applyFilters();
};

const setQuickFilter = (type) => {
  quickFilter.value = type;
  const today = new Date();
  
  if (type === 'today') {
    filters.start_date = today.toISOString().split('T')[0];
    filters.end_date = today.toISOString().split('T')[0];
  } else if (type === 'this_month') {
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    filters.start_date = firstDay.toISOString().split('T')[0];
    filters.end_date = lastDay.toISOString().split('T')[0];
  }
  
  applyFilters();
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const formatCurrency = (value) => {
  if (!value) return '0.00';
  return Number(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};
</script>
