<template>
  <Head title="Expenses" />
  <div class="min-h-screen bg-gray-100 py-8 px-4 md:px-36 flex flex-col">
    <Header />
    
    <!-- Header with Back Button -->
    <div class="mb-8 flex items-center gap-4">
      <Link href="/dashboard" class="p-2 hover:bg-gray-200 rounded-full transition">
        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
      </Link>
      <h1 class="text-4xl font-bold tracking-wider">EXPENSES</h1>
    </div>

    <div class="flex justify-end mb-6">
      <button @click="openAddModal" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold">+ Add Expense</button>
    </div>

    <!-- Expenses Card -->
    <div class="bg-white rounded-lg shadow p-8 flex-grow overflow-x-auto mb-8">
      <table class="w-full text-base">
        <thead>
          <tr class="border-b-2 bg-gray-50">
            <th class="text-left py-4 px-6 font-bold text-gray-700">Date</th>
            <th class="text-left py-4 px-6 font-bold text-gray-700">Category</th>
            <th class="text-left py-4 px-6 font-bold text-gray-700">Description</th>
            <th class="text-left py-4 px-6 font-bold text-gray-700">Amount</th>
            <th class="text-left py-4 px-6 font-bold text-gray-700">Payment Method</th>
            <th class="text-left py-4 px-6 font-bold text-gray-700">Reference</th>
            <th class="text-center py-4 px-6 font-bold text-gray-700">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="expense in paginatedExpenses" :key="expense.id" class="border-b hover:bg-blue-50 transition">
            <td class="py-4 px-6">{{ formatDate(expense.date) }}</td>
            <td class="py-4 px-6"><span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">{{ expense.category }}</span></td>
            <td class="py-4 px-6">{{ expense.description || '-' }}</td>
            <td class="py-4 px-6 font-semibold text-green-600">{{ formatAmount(expense.amount) }}</td>
            <td class="py-4 px-6">{{ expense.payment_method }}</td>
            <td class="py-4 px-6">{{ expense.reference || '-' }}</td>
            <td class="py-4 px-6 text-center space-x-2">
              <button @click="editExpense(expense)" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm font-medium transition">Edit</button>
              <button @click="confirmDelete(expense.id)" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 text-sm font-medium transition">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="expenses.length === 0" class="text-center py-12 text-gray-500 text-lg">
        No expenses found. Click "Add Expense" to create one.
      </div>
      
      <!-- Pagination -->
      <div v-if="expenses.length > 0" class="mt-8 flex justify-between items-center">
        <div class="text-gray-600 font-medium">
          Showing <span class="font-bold">{{ startIndex + 1 }}</span> to <span class="font-bold">{{ Math.min(endIndex, expenses.length) }}</span> of <span class="font-bold">{{ expenses.length }}</span> expenses
        </div>
        <div class="space-x-2">
          <button @click="previousPage" :disabled="currentPage === 1" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 disabled:opacity-50 disabled:cursor-not-allowed font-medium">← Previous</button>
          <span class="px-4 py-2 text-gray-700 font-semibold">Page {{ currentPage }} of {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 disabled:opacity-50 disabled:cursor-not-allowed font-medium">Next →</button>
        </div>
      </div>

      <!-- Total -->
      <div v-if="expenses.length > 0" class="mt-8 text-right border-t pt-6">
        <p class="text-xl font-bold">Total Expenses: <span class="text-green-600">{{ formatAmount(total) }}</span></p>
      </div>
    </div>

    <!-- Add/Edit Expense Modal -->
    <ExpenseModal v-model:open="openModal" :selectedExpense="selectedExpense" @saved="fetchExpenses" />
    
    <!-- Delete Confirmation Modal -->
    <DeleteExpenseModal v-model:open="deleteModal" :expenseId="deleteId" @deleted="fetchExpenses" />

    <Footer />
  </div>
</template>

<script setup>
import Header from '@/Components/custom/Header.vue';
import Footer from '@/Components/custom/Footer.vue';
import { Head, usePage, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import ExpenseModal from '@/Components/custom/ExpenseModal.vue';
import DeleteExpenseModal from '@/Components/custom/DeleteExpenseModal.vue';

const page = usePage();
const openModal = ref(false);
const deleteModal = ref(false);
const deleteId = ref(null);
const selectedExpense = ref(null);
const expenses = ref(page.props.expenses || []);
const total = ref(page.props.total || 0);

const itemsPerPage = ref(10);
const currentPage = ref(1);

const totalPages = computed(() => Math.ceil(expenses.value.length / itemsPerPage.value));
const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage.value);
const endIndex = computed(() => startIndex.value + itemsPerPage.value);
const paginatedExpenses = computed(() => expenses.value.slice(startIndex.value, endIndex.value));

function formatDate(date) {
  return new Date(date).toLocaleDateString();
}

function formatAmount(amount) {
  return 'Rs. ' + parseFloat(amount).toFixed(2);
}

function openAddModal() {
  selectedExpense.value = null;
  openModal.value = true;
}

function editExpense(expense) {
  selectedExpense.value = expense;
  openModal.value = true;
}

function confirmDelete(id) {
  deleteId.value = id;
  deleteModal.value = true;
}

function nextPage() {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
}

function previousPage() {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
}

function fetchExpenses() {
  location.reload();
}

onMounted(() => {
  expenses.value = page.props.expenses || [];
  total.value = page.props.total || 0;
});
</script>
