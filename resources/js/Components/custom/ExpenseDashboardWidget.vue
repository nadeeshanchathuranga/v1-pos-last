<template>
  <div class="space-y-6">
    <!-- Today's Expenses Card -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow p-6 text-white">
      <p class="text-sm opacity-90">Today's Expenses</p>
      <p class="text-3xl font-bold">{{ formatAmount(todayTotal) }}</p>
    </div>

    <!-- This Month's Expenses Card -->
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow p-6 text-white">
      <p class="text-sm opacity-90">This Month</p>
      <p class="text-3xl font-bold">{{ formatAmount(monthTotal) }}</p>
    </div>

    <!-- Add Expense Button -->
    <button @click="openModal = true" class="w-full px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
      + Add Expense
    </button>

    <!-- View Report Link -->
    <Link href="/expenses" class="block w-full text-center px-4 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-medium">
      View All Expenses
    </Link>

    <!-- Expense Modal -->
    <ExpenseModal v-model:open="openModal" @saved="fetchTotals" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import ExpenseModal from '@/Components/custom/ExpenseModal.vue';

const openModal = ref(false);
const todayTotal = ref(0);
const monthTotal = ref(0);

function formatAmount(amount) {
  return parseFloat(amount).toFixed(2);
}

function fetchTotals() {
  fetch('/expenses/dashboard-summary')
    .then(res => res.json())
    .then(data => {
      todayTotal.value = data.todayTotal;
      monthTotal.value = data.monthTotal;
    })
    .catch(err => console.error('Error fetching expense totals:', err));
}

onMounted(fetchTotals);

// Refresh totals every minute
setInterval(fetchTotals, 60000);
</script>
