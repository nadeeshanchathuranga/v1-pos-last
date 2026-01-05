<template>
  <Head :title="`Supplier: ${supplier.name}`" />
  <div class="min-h-screen bg-gray-100 py-8 px-4 md:px-36 flex flex-col">
    <Header />
    
    <!-- Header with Back Button -->
    <div class="mb-8 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <Link href="/suppliers" class="p-2 hover:bg-gray-200 rounded-full transition">
          <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </Link>
        <h1 class="text-4xl font-bold tracking-wider">{{ supplier.name }}</h1>
      </div>
      <div class="flex gap-4">
        <!-- Date Filter -->
        <input 
          v-model="filterStartDate" 
          type="date" 
          class="px-4 py-2 border rounded-lg"
          placeholder="Start Date"
        />
        <input 
          v-model="filterEndDate" 
          type="date" 
          class="px-4 py-2 border rounded-lg"
          placeholder="End Date"
        />
        <button 
          @click="applyDateFilter"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition"
        >
          Filter
        </button>
        <button 
          @click="generateReport"
          class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
          </svg>
          Generate Report
        </button>
      </div>
    </div>

    <!-- Supplier Info -->
    <div class="bg-white rounded-lg shadow p-8 mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <p class="text-gray-600 font-semibold mb-2">Contact:</p>
          <p class="text-lg">{{ supplier.contact || 'N/A' }}</p>
        </div>
        <div>
          <p class="text-gray-600 font-semibold mb-2">Email:</p>
          <p class="text-lg">{{ supplier.email || 'N/A' }}</p>
        </div>
        <div>
          <p class="text-gray-600 font-semibold mb-2">Address:</p>
          <p class="text-lg">{{ supplier.address || 'N/A' }}</p>
        </div>
        <div>
          <p class="text-gray-600 font-semibold mb-2">Total Products:</p>
          <p class="text-lg text-blue-600 font-bold">{{ products.length }}</p>
        </div>
      </div>
    </div>

    <!-- Products Section -->
    <div class="bg-white rounded-lg shadow p-8 mb-8">
      <h2 class="text-2xl font-bold mb-6">Products Supplied</h2>
      <div v-if="products.length > 0" class="overflow-x-auto">
        <table class="w-full text-base">
          <thead>
            <tr class="border-b-2 bg-gray-50">
              <th class="text-left py-4 px-6 font-bold text-gray-700">Product Code</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Product Name</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Category</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Batch</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Cost Price</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Selling Price</th>
              <th class="text-center py-4 px-6 font-bold text-gray-700">Stock Qty</th>
              <th class="text-center py-4 px-6 font-bold text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="product in products" :key="product.id" class="border-b hover:bg-blue-50 transition">
              <td class="py-4 px-6 font-semibold">{{ product.code }}</td>
              <td class="py-4 px-6">{{ product.name }}</td>
              <td class="py-4 px-6">
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                  {{ product.category?.name || 'N/A' }}
                </span>
              </td>
              <td class="py-4 px-6">{{ product.batch_no }}</td>
              <td class="py-4 px-6 text-green-600 font-semibold">{{ product.cost_price }} LKR</td>
              <td class="py-4 px-6 text-blue-600 font-semibold">{{ product.selling_price }} LKR</td>
              <td class="py-4 px-6 text-center font-bold">{{ product.stock_quantity }}</td>
              <td class="py-4 px-6 text-center">
                <button 
                  @click="viewTransfers(product.id)"
                  class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 font-medium transition"
                >
                  View Transfers
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-center py-12 text-gray-500 text-lg">
        No products supplied by this supplier.
      </div>
    </div>

    <!-- Stock Transfers Section -->
    <div v-if="selectedProductId" class="bg-white rounded-lg shadow p-8 mb-8 no-print">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Stock Transfers - {{ selectedProduct?.name }}</h2>
        <div class="flex gap-3">
          <button 
            @click="printProductTransfers"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-medium flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print Report
          </button>
          <button 
            @click="selectedProductId = null"
            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 font-medium"
          >
            Close
          </button>
        </div>
      </div>
      <div v-if="transfers.length > 0" class="overflow-x-auto">
        <table class="w-full text-base">
          <thead>
            <tr class="border-b-2 bg-gray-50">
              <th class="text-left py-4 px-6 font-bold text-gray-700">Date</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Type</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Quantity</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Reason</th>
              <th class="text-left py-4 px-6 font-bold text-gray-700">Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transfer in transfers" :key="transfer.id" class="border-b hover:bg-green-50 transition">
              <td class="py-4 px-6">{{ formatDate(transfer.created_at) }}</td>
              <td class="py-4 px-6">
                <span :class="getTransferTypeClass(transfer.transaction_type)" class="px-3 py-1 rounded-full text-sm font-medium">
                  {{ transfer.transaction_type }}
                </span>
              </td>
              <td class="py-4 px-6 font-bold">{{ transfer.quantity }}</td>
              <td class="py-4 px-6">{{ transfer.reason || '-' }}</td>
              <td class="py-4 px-6">{{ formatDate(transfer.transaction_date) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="text-center py-12 text-gray-500 text-lg">
        No stock transfers for this product.
      </div>
    </div>

    <!-- Product Transfers Report (Hidden, shown on print) -->
    <div id="productTransfersReport" style="display: none;">
      <div class="bg-white p-8">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold mb-2">Supplier Stock Transfers Report</h1>
          <h2 class="text-2xl mb-4">{{ selectedProduct?.name }}</h2>
          <div class="text-gray-600">
            <p><strong>Supplier:</strong> {{ supplier.name }}</p>
            <p><strong>Product Code:</strong> {{ selectedProduct?.code }}</p>
            <p><strong>Category:</strong> {{ selectedProduct?.category?.name || 'N/A' }}</p>
            <p><strong>Current Stock:</strong> {{ selectedProduct?.stock_quantity }}</p>
            <p class="mt-2">Generated on: {{ new Date().toLocaleDateString() }}</p>
          </div>
        </div>

        <table class="w-full text-sm border-collapse border">
          <thead>
            <tr class="bg-gray-100">
              <th class="border p-3 text-left">Date</th>
              <th class="border p-3 text-left">Type</th>
              <th class="border p-3 text-center">Quantity</th>
              <th class="border p-3 text-left">Reason</th>
              <th class="border p-3 text-left">Transaction Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="transfer in transfers" :key="transfer.id">
              <td class="border p-3">{{ formatDate(transfer.created_at) }}</td>
              <td class="border p-3">{{ transfer.transaction_type }}</td>
              <td class="border p-3 text-center font-bold">{{ transfer.quantity }}</td>
              <td class="border p-3">{{ transfer.reason || '-' }}</td>
              <td class="border p-3">{{ formatDate(transfer.transaction_date) }}</td>
            </tr>
          </tbody>
        </table>

        <div class="mt-8 grid grid-cols-2 gap-4">
          <div class="border p-4 text-center">
            <p class="text-gray-600 font-semibold">Total Transactions</p>
            <p class="text-2xl font-bold text-blue-600">{{ transfers.length }}</p>
          </div>
          <div class="border p-4 text-center">
            <p class="text-gray-600 font-semibold">Current Stock</p>
            <p class="text-2xl font-bold text-green-600">{{ selectedProduct?.stock_quantity || 0 }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- All Transactions Section (Hidden by default, shown on print) -->
    <div id="reportSection" style="display: none;">
      <div class="bg-white p-8">
        <!-- Report Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold mb-2">Supplier Stock Transaction Report</h1>
          <h2 class="text-2xl mb-4">{{ supplier.name }}</h2>
          <div class="text-gray-600">
            <p>Contact: {{ supplier.contact || 'N/A' }} | Email: {{ supplier.email || 'N/A' }}</p>
            <p>Address: {{ supplier.address || 'N/A' }}</p>
            <p v-if="filterStartDate || filterEndDate" class="mt-2 font-semibold">
              Period: {{ filterStartDate || 'Start' }} to {{ filterEndDate || 'End' }}
            </p>
            <p class="mt-2">Generated on: {{ new Date().toLocaleDateString() }}</p>
          </div>
        </div>

        <!-- Products Summary -->
        <div class="mb-8">
          <h3 class="text-xl font-bold mb-4 border-b-2 pb-2">Products Summary</h3>
          <table class="w-full text-sm border-collapse border">
            <thead>
              <tr class="bg-gray-100">
                <th class="border p-2 text-left">Code</th>
                <th class="border p-2 text-left">Product Name</th>
                <th class="border p-2 text-left">Category</th>
                <th class="border p-2 text-left">Batch</th>
                <th class="border p-2 text-right">Cost Price</th>
                <th class="border p-2 text-right">Selling Price</th>
                <th class="border p-2 text-center">Stock Qty</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in filteredProducts" :key="product.id">
                <td class="border p-2">{{ product.code }}</td>
                <td class="border p-2">{{ product.name }}</td>
                <td class="border p-2">{{ product.category?.name || 'N/A' }}</td>
                <td class="border p-2">{{ product.batch_no }}</td>
                <td class="border p-2 text-right">{{ product.cost_price }} LKR</td>
                <td class="border p-2 text-right">{{ product.selling_price }} LKR</td>
                <td class="border p-2 text-center font-bold">{{ product.stock_quantity }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- All Transactions -->
        <div class="mb-8">
          <h3 class="text-xl font-bold mb-4 border-b-2 pb-2">All Stock Transactions</h3>
          <table class="w-full text-sm border-collapse border">
            <thead>
              <tr class="bg-gray-100">
                <th class="border p-2 text-left">Date</th>
                <th class="border p-2 text-left">Product</th>
                <th class="border p-2 text-left">Code</th>
                <th class="border p-2 text-left">Type</th>
                <th class="border p-2 text-center">Quantity</th>
                <th class="border p-2 text-left">Reason</th>
                <th class="border p-2 text-left">Transaction Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="transfer in filteredTransfers" :key="transfer.id">
                <td class="border p-2">{{ formatDate(transfer.created_at) }}</td>
                <td class="border p-2">{{ transfer.product_name }}</td>
                <td class="border p-2">{{ getProductCode(transfer.product_id) }}</td>
                <td class="border p-2">{{ transfer.transaction_type }}</td>
                <td class="border p-2 text-center font-bold">{{ transfer.quantity }}</td>
                <td class="border p-2">{{ transfer.reason || '-' }}</td>
                <td class="border p-2">{{ formatDate(transfer.transaction_date) }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="filteredTransfers.length === 0" class="text-center py-8 text-gray-500">
            No transactions found for the selected period.
          </div>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-3 gap-4 mt-8">
          <div class="border p-4 text-center">
            <p class="text-gray-600 font-semibold">Total Products</p>
            <p class="text-2xl font-bold text-blue-600">{{ filteredProducts.length }}</p>
          </div>
          <div class="border p-4 text-center">
            <p class="text-gray-600 font-semibold">Total Transactions</p>
            <p class="text-2xl font-bold text-green-600">{{ filteredTransfers.length }}</p>
          </div>
          <div class="border p-4 text-center">
            <p class="text-gray-600 font-semibold">Total Stock Quantity</p>
            <p class="text-2xl font-bold text-purple-600">{{ totalStockQty }}</p>
          </div>
        </div>
      </div>
    </div>

    <Footer />
  </div>
</template>

<script setup>
import Header from '@/Components/custom/Header.vue';
import Footer from '@/Components/custom/Footer.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const page = usePage();
const supplier = ref(page.props.supplier);
const products = ref(page.props.products || []);
const allTransfers = ref(page.props.transfers || []);
const selectedProductId = ref(null);
const filterStartDate = ref('');
const filterEndDate = ref('');

const selectedProduct = computed(() => {
  return products.value.find(p => p.id === selectedProductId.value);
});

const transfers = computed(() => {
  if (!selectedProductId.value) return [];
  return allTransfers.value.filter(t => t.product_id === selectedProductId.value);
});

const filteredProducts = computed(() => {
  return products.value;
});

const filteredTransfers = computed(() => {
  let filtered = allTransfers.value;
  
  if (filterStartDate.value || filterEndDate.value) {
    filtered = filtered.filter(t => {
      const transactionDate = new Date(t.transaction_date || t.created_at);
      const start = filterStartDate.value ? new Date(filterStartDate.value) : null;
      const end = filterEndDate.value ? new Date(filterEndDate.value) : null;
      
      if (start && transactionDate < start) return false;
      if (end && transactionDate > end) return false;
      return true;
    });
  }
  
  return filtered;
});

const totalStockQty = computed(() => {
  return filteredProducts.value.reduce((sum, product) => sum + (product.stock_quantity || 0), 0);
});

function formatDate(date) {
  return new Date(date).toLocaleDateString();
}

function getTransferTypeClass(type) {
  switch (type) {
    case 'Stock In':
      return 'bg-green-100 text-green-800';
    case 'Stock Out':
      return 'bg-red-100 text-red-800';
    case 'Transfer':
      return 'bg-blue-100 text-blue-800';
    case 'Return':
      return 'bg-yellow-100 text-yellow-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function viewTransfers(productId) {
  selectedProductId.value = productId;
}

function getProductCode(productId) {
  const product = products.value.find(p => p.id === productId);
  return product ? product.code : '-';
}

function applyDateFilter() {
  // Filtering is reactive via computed properties
  // You can also reload from server if needed
  router.get(`/suppliers/${supplier.value.id}/products`, {
    start_date: filterStartDate.value,
    end_date: filterEndDate.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
}

function generateReport() {
  const reportSection = document.getElementById('reportSection');
  
  if (!reportSection) {
    console.error('Report section not found');
    return;
  }
  
  // Clone the report content
  const reportContent = reportSection.innerHTML;
  
  // Create a new window for printing
  const printWindow = window.open('', '', 'width=800,height=600');
  
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Supplier Report - ${props.supplier.name}</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          margin: 20px;
          color: black;
          background: white;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 20px 0;
        }
        th, td {
          border: 1px solid #000;
          padding: 10px;
          text-align: left;
        }
        th {
          background-color: #f0f0f0;
          font-weight: bold;
        }
        h1, h2 {
          text-align: center;
          color: black;
        }
        .text-center {
          text-align: center;
        }
        .grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
          margin-top: 20px;
        }
        .border {
          border: 1px solid #000;
          padding: 15px;
        }
        .font-bold {
          font-weight: bold;
        }
        .text-2xl {
          font-size: 1.5rem;
        }
        .mb-8 {
          margin-bottom: 2rem;
        }
        @media print {
          @page {
            margin: 1cm;
          }
        }
      </style>
    </head>
    <body>
      ${reportContent}
    </body>
    </html>
  `);
  
  printWindow.document.close();
  
  // Wait for content to load then print
  setTimeout(() => {
    printWindow.focus();
    printWindow.print();
    printWindow.close();
  }, 250);
}

function printProductTransfers() {
  const reportSection = document.getElementById('productTransfersReport');
  
  if (!reportSection) {
    console.error('Report section not found');
    return;
  }
  
  // Clone the report content
  const reportContent = reportSection.innerHTML;
  
  // Create a new window for printing
  const printWindow = window.open('', '', 'width=800,height=600');
  
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Stock Transfers Report - ${selectedProduct.value?.name}</title>
      <style>
        body {
          font-family: Arial, sans-serif;
          margin: 20px;
          color: black;
          background: white;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 20px 0;
        }
        th, td {
          border: 1px solid #000;
          padding: 10px;
          text-align: left;
        }
        th {
          background-color: #f0f0f0;
          font-weight: bold;
        }
        h1, h2 {
          text-align: center;
          color: black;
        }
        .text-center {
          text-align: center;
        }
        .grid {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 20px;
          margin-top: 20px;
        }
        .border {
          border: 1px solid #000;
          padding: 15px;
        }
        .font-bold {
          font-weight: bold;
        }
        .text-2xl {
          font-size: 1.5rem;
        }
        @media print {
          @page {
            margin: 1cm;
          }
        }
      </style>
    </head>
    <body>
      ${reportContent}
    </body>
    </html>
  `);
  
  printWindow.document.close();
  
  // Wait for content to load then print
  setTimeout(() => {
    printWindow.focus();
    printWindow.print();
    printWindow.close();
  }, 250);
}
</script>

<style>
@media print {
  /* Show report sections */
  #reportSection,
  #productTransfersReport {
    display: block !important;
  }
  
  /* Hide everything else */
  .no-print, button, header, footer, nav, .min-h-screen {
    display: none !important;
  }
  
  /* Reset body and html for print */
  html, body {
    background: white !important;
    margin: 0;
    padding: 0;
    width: 100%;
    height: auto;
  }
  
  /* Ensure content is visible */
  * {
    visibility: visible !important;
  }
  
  /* Make sure tables print properly */
  table {
    page-break-inside: auto;
    width: 100%;
    border-collapse: collapse;
  }
  
  tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }
  
  thead {
    display: table-header-group;
  }
  
  /* Ensure borders show */
  table, th, td {
    border: 1px solid #000 !important;
    padding: 8px;
  }
  
  /* Show text */
  h1, h2, h3, h4, p, td, th, span, div {
    color: black !important;
  }
  
  @page {
    margin: 1cm;
    size: A4;
  }
}
</style>
