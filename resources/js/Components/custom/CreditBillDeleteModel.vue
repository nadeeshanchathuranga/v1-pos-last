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
            class="bg-black border-4 border-red-600 rounded-[20px] shadow-xl w-5/6 lg:w-2/6 p-6 text-center"
          >
            <!-- Modal Title -->
            <DialogTitle class="text-xl font-bold text-white mb-4">
              Delete Credit Bill
            </DialogTitle>
            
            <!-- Warning Message -->
            <div class="mb-6">
              <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                  </path>
                </svg>
              </div>
              
              <p class="text-gray-300 text-sm">
                Are you sure you want to delete this credit bill? This action cannot be undone.
              </p>
            </div>

            <!-- Credit Bill Details -->
            <div v-if="selectedCreditBill" class="bg-gray-800 p-4 rounded-lg mb-6 text-left">
              <h3 class="text-sm font-semibold text-white mb-2">Credit Bill Details:</h3>
              <div class="space-y-1 text-xs text-gray-300">
                <div>
                  <span class="font-medium">Order ID:</span>
                  <span class="ml-2">{{ selectedCreditBill.order_id }}</span>
                </div>
                <div>
                  <span class="font-medium">Customer:</span>
                  <span class="ml-2">{{ selectedCreditBill.customer?.name || 'Walk-in Customer' }}</span>
                </div>
                <div>
                  <span class="font-medium">Total Amount:</span>
                  <span class="ml-2">{{ formatCurrency(selectedCreditBill.total_amount) }}</span>
                </div>
                <div>
                  <span class="font-medium">Remaining:</span>
                  <span class="ml-2 font-bold text-yellow-400">{{ formatCurrency(selectedCreditBill.remaining_amount) }}</span>
                </div>
                <div>
                  <span class="font-medium">Status:</span>
                  <span class="ml-2 uppercase" :class="getStatusClass(selectedCreditBill.payment_status)">
                    {{ selectedCreditBill.payment_status }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Modal Actions -->
            <div class="flex justify-center gap-4">
              <button
                @click="deleteConfirmed"
                :disabled="form.processing"
                class="px-6 py-2 font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ form.processing ? "Deleting..." : "Yes, Delete" }}
              </button>
              <button
                @click="$emit('update:open', false)"
                type="button"
                :disabled="form.processing"
                class="px-6 py-2 font-medium text-gray-300 bg-gray-600 border border-transparent rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancel
              </button>
            </div>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
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
  creditBills: Array,
});

const emit = defineEmits(["update:open"]);

const form = useForm({});

const deleteConfirmed = () => {
  if (!props.selectedCreditBill) return;

  form.delete(route("creditbill.destroy", props.selectedCreditBill.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit("update:open", false);
    },
  });
};

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
</script>