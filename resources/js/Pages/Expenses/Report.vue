<template>
  <Head title="Expense Report" />
  <div class="min-h-screen bg-gray-100 py-8 px-4 md:px-36">
    <Header />
    <div class="mb-8">
      <h1 class="text-2xl font-bold">Expense Report</h1>
      <!-- Filters -->
      <div class="flex flex-wrap gap-4 mt-4 bg-white p-4 rounded shadow">
        <input type="date" v-model="filters.start_date" class="border rounded px-2 py-1" placeholder="Start Date" />
        <input type="date" v-model="filters.end_date" class="border rounded px-2 py-1" placeholder="End Date" />
        <select v-model="filters.category" class="border rounded px-2 py-1">
          <option value="">All Categories</option>
          <option value="Rent">Rent</option>
          <option value="Salary">Salary</option>
          <option value="Utilities">Utilities</option>
          <option value="Transport">Transport</option>
          <option value="Maintenance">Maintenance</option>
          <option value="Other">Other</option>
        </select>
        <select v-model="filters.payment_method" class="border rounded px-2 py-1">
          <option value="">All Payment Methods</option>
          <option value="Cash">Cash</option>
          <option value="Bank">Bank</option>
          <option value="Card">Card</option>
          <option value="Other">Other</option>
        </select>
        <button @click="applyFilters" class="px-4 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">Filter</button>
      </div>
    </div>
    <div class="bg-white rounded shadow p-6 overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b">
            <th class="text-left py-2">Date</th>
            <th class="text-left py-2">Category</th>
            <th class="text-left py-2">Description</th>
            <th class="text-left py-2">Amount</th>
            <th class="text-left py-2">Payment Method</th>
            <th class="text-left py-2">Reference</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="expense in expenses" :key="expense.id" class="border-b hover:bg-gray-50">
            <td class="py-2">{{ formatDate(expense.date) }}</td>
            <td class="py-2">{{ expense.category }}</td>
            <td class="py-2">{{ expense.description || '-' }}</td>
            <td class="py-2">{{ formatAmount(expense.amount) }}</td>
            <td class="py-2">{{ expense.payment_method }}</td>
            <td class="py-2">{{ expense.reference || '-' }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="expenses.length === 0" class="text-center py-6 text-gray-500">
        No expenses found
      </div>
      <div class="mt-6 text-right font-bold text-lg border-t pt-4">
        Total Expenses: {{ formatAmount(total) }}
      </div>
    </div>
    <Footer />
  </div>
</template>

<script setup>
import Header from '@/Components/custom/Header.vue';
import Footer from '@/Components/custom/Footer.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const expenses = ref(page.props.expenses || []);
const total = ref(page.props.total || 0);
const filters = ref({
  start_date: '',
  end_date: '',
  category: '',
  payment_method: ''
});

function formatDate(date) {
  return new Date(date).toLocaleDateString();
}

function formatAmount(amount) {
  return parseFloat(amount).toFixed(2);
}

function applyFilters() {
  router.get('/expenses', filters.value, {
    preserveState: true,
    replace: true
  });
}
</script>
