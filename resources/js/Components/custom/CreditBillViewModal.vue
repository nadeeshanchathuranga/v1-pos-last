<template>
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-10" @close="$emit('update:open', false)">
      <!-- Modal Overlay -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div
          class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
        />
      </TransitionChild>

      <!-- Modal Content -->
      <div class="fixed inset-0 z-10 flex items-center justify-center">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-200"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <DialogPanel
            class="bg-black border-4 border-blue-600 rounded-[20px] shadow-xl w-5/6 lg:w-4/6 xl:w-3/6 p-6 text-center max-h-[90vh] overflow-y-auto"
          >
            <!-- Modal Title -->
            <DialogTitle class="text-xl font-bold text-white mb-6">
              Payment History - {{ selectedCreditBill?.order_id }}
            </DialogTitle>

            <!-- Credit Bill Summary -->
            <div class="bg-gray-800 p-4 rounded-lg mb-6 text-left">
              <h3 class="text-lg font-semibold text-white mb-4">Credit Bill Summary</h3>
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm text-gray-300">
                <div class="bg-gray-700 p-3 rounded">
                  <span class="block font-medium text-gray-400">Customer:</span>
                  <span class="text-white">{{ selectedCreditBill?.customer?.name || 'Walk-in Customer' }}</span>
                </div>
                <div class="bg-gray-700 p-3 rounded">
                  <span class="block font-medium text-gray-400">Order ID:</span>
                  <span class="text-white">{{ selectedCreditBill?.order_id }}</span>
                </div>
                <div class="bg-gray-700 p-3 rounded">
                  <span class="block font-medium text-gray-400">Total Amount:</span>
                  <span class="text-blue-400 font-bold">{{ formatCurrency(selectedCreditBill?.total_amount) }}</span>
                </div>
                <div class="bg-gray-700 p-3 rounded">
                  <span class="block font-medium text-gray-400">Paid Amount:</span>
                  <span class="text-green-400 font-bold">{{ formatCurrency(selectedCreditBill?.paid_amount) }}</span>
                </div>
                <div class="bg-gray-700 p-3 rounded">
                  <span class="block font-medium text-gray-400">Remaining:</span>
                  <span class="text-yellow-400 font-bold">{{ formatCurrency(selectedCreditBill?.remaining_amount) }}</span>
                </div>
                <div class="bg-gray-700 p-3 rounded">
                  <span class="block font-medium text-gray-400">Status:</span>
                  <span class="uppercase font-bold" :class="getStatusClass(selectedCreditBill?.payment_status)">
                    {{ selectedCreditBill?.payment_status }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Payment History Section -->
            <div class="bg-gray-800 p-4 rounded-lg text-left">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-white">Payment History</h3>
                <div class="text-sm text-gray-300">
                  <span class="font-medium">Total Payments: </span>
                  <span class="text-blue-400 font-bold">{{ selectedCreditBill?.payments?.length || 0 }}</span>
                </div>
              </div>
              
              <!-- Payment List -->
              <div v-if="selectedCreditBill?.payments && selectedCreditBill.payments.length > 0" 
                   class="max-h-96 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-600 scrollbar-track-gray-700">
                <div class="space-y-4">
                  <div 
                    v-for="(payment, index) in selectedCreditBill.payments" 
                    :key="payment.id"
                    class="relative bg-gray-700 p-4 rounded-lg border-l-4 border-blue-500 hover:bg-gray-650 transition-colors duration-200"
                  >
                    <!-- Payment Number Badge -->
                    <div class="absolute -top-2 -left-2 bg-blue-600 text-white text-xs px-3 py-1 rounded-full font-semibold">
                      Payment {{ selectedCreditBill.payments.length - index }}
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-2">
                      <div class="space-y-3">
                        <div class="flex justify-between">
                          <span class="font-medium text-gray-400">Amount:</span>
                          <span class="text-green-400 font-bold text-lg">{{ formatCurrency(payment.payment_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="font-medium text-gray-400">Method:</span>
                          <span class="capitalize bg-gray-600 px-2 py-1 rounded text-white">{{ payment.payment_method }}</span>
                        </div>
                      </div>
                      <div class="space-y-3">
                        <div class="flex justify-between">
                          <span class="font-medium text-gray-400">Date:</span>
                          <span class="text-gray-300">{{ formatDate(payment.payment_date) }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="font-medium text-gray-400">Processed by:</span>
                          <span class="text-blue-300 font-medium">{{ payment.user?.name || 'System' }}</span>
                        </div>
                      </div>
                      <div v-if="payment.notes" class="col-span-1 md:col-span-2 mt-3 pt-3 border-t border-gray-600">
                        <span class="font-medium text-gray-400 block mb-1">Notes:</span>
                        <div class="italic text-yellow-200 bg-gray-600 p-2 rounded">{{ payment.notes }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- No payments message -->
              <div v-else class="text-center text-gray-400 py-12">
                <svg class="mx-auto h-16 w-16 text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h4 class="text-lg font-medium text-gray-400 mb-2">No Payment History</h4>
                <p class="text-gray-500">No payments have been made for this credit bill yet.</p>
              </div>
            </div>

            <!-- Close Button -->
            <div class="flex justify-center mt-6">
              <button
                @click="$emit('update:open', false)"
                type="button"
                class="px-8 py-3 font-medium text-white bg-gray-600 border border-transparent rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
              >
                Close
              </button>
            </div>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch } from "vue";
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";

const props = defineProps({
  open: Boolean,
  selectedCreditBill: Object,
});

const emit = defineEmits(["update:open"]);

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-LK', {
    style: 'currency',
    currency: 'LKR',
    minimumFractionDigits: 2
  }).format(amount || 0).replace('LKR', '').trim() + ' LKR'
};

const getStatusClass = (status) => {
  switch (status) {
    case 'pending':
      return 'text-red-400'
    case 'partial':
      return 'text-yellow-400'
    case 'paid':
      return 'text-green-400'
    default:
      return 'text-gray-400'
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<style scoped>
/* Custom scrollbar for payment history */
.scrollbar-thin {
  scrollbar-width: thin;
}

.scrollbar-thin::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background: #374151;
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background: #6B7280;
  border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
  background: #9CA3AF;
}

/* Hover effect for payment items */
.bg-gray-650:hover {
  background-color: #3f4651;
}
</style>