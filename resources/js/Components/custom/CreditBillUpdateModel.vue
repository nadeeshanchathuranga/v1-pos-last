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
            class="bg-black border-4 border-blue-600 rounded-[20px] shadow-xl w-5/6 lg:w-3/6 p-6 text-center"
          >
            <!-- Modal Title -->
            <DialogTitle class="text-xl font-bold text-white">
              Update Credit Bill Payment
            </DialogTitle>
            <form @submit.prevent="submit">
              <!-- Modal Form -->
              <div class="mt-6 space-y-4 text-left">
                <!-- Credit Bill Details Display -->
                <div class="bg-gray-800 p-4 rounded-lg">
                  <h3 class="text-lg font-semibold text-white mb-3">Credit Bill Details</h3>
                  <div class="grid grid-cols-2 gap-4 text-sm text-gray-300">
                    <div>
                      <span class="font-medium">Order ID:</span>
                      <span class="ml-2">{{ selectedCreditBill?.order_id }}</span>
                    </div>
                    <div>
                      <span class="font-medium">Customer:</span>
                      <span class="ml-2">{{ selectedCreditBill?.customer?.name || 'Walk-in Customer' }}</span>
                    </div>
                    <div>
                      <span class="font-medium">Total Amount:</span>
                      <span class="ml-2">{{ formatCurrency(selectedCreditBill?.total_amount) }}</span>
                    </div>
                    <div>
                      <span class="font-medium">Paid Amount:</span>
                      <span class="ml-2">{{ formatCurrency(selectedCreditBill?.paid_amount) }}</span>
                    </div>
                    <div>
                      <span class="font-medium">Remaining:</span>
                      <span class="ml-2 font-bold text-yellow-400">{{ formatCurrency(selectedCreditBill?.remaining_amount) }}</span>
                    </div>
                    <div>
                      <span class="font-medium">Status:</span>
                      <span class="ml-2 uppercase" :class="getStatusClass(selectedCreditBill?.payment_status)">
                        {{ selectedCreditBill?.payment_status }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Payment Update Form -->
                <div class="flex items-center gap-8 mt-6">
                  <div class="w-full">
                    <div>
                      <label class="block text-sm font-medium text-gray-300">
                        Payment Amount:
                      </label>
                      <input
                        v-model="form.payment_amount"
                        type="number"
                        step="0.01"
                        min="0"
                        :max="selectedCreditBill?.remaining_amount || 0"
                        id="payment_amount"
                        required
                        class="w-full px-4 py-3 mt-1 text-white bg-gray-700 border border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter payment amount"
                      />
                      <div v-if="form.errors.payment_amount" class="mt-2 text-red-400 text-sm">
                        {{ form.errors.payment_amount }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-8 mt-4">
                  <div class="w-full">
                    <div>
                      <label class="block text-sm font-medium text-gray-300">
                        Payment Method:
                      </label>
                      <select
                        v-model="form.payment_method"
                        id="payment_method"
                        class="w-full px-4 py-3 mt-1 text-white bg-gray-700 border border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      >
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                      </select>
                      <div v-if="form.errors.payment_method" class="mt-2 text-red-400 text-sm">
                        {{ form.errors.payment_method }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-8 mt-4">
                  <div class="w-full">
                    <div>
                      <label class="block text-sm font-medium text-gray-300">
                        Notes (Optional):
                      </label>
                      <textarea
                        v-model="form.notes"
                        id="notes"
                        rows="3"
                        class="w-full px-4 py-3 mt-1 text-white bg-gray-700 border border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Add payment notes..."
                      ></textarea>
                      <div v-if="form.errors.notes" class="mt-2 text-red-400 text-sm">
                        {{ form.errors.notes }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Modal Actions -->
              <div class="flex justify-center gap-4 mt-8">
                <!-- <button
                  @click="markAsPaid"
                  type="button"
                  :disabled="form.processing || selectedCreditBill?.payment_status === 'paid'"
                  class="px-6 py-2 font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Mark as Paid
                </button> -->
                <button
                  type="submit"
                  :disabled="form.processing || !form.payment_amount"
                  class="px-6 py-2 font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ form.processing ? "Updating..." : "Update Payment" }}
                </button>
                <button
                  @click="$emit('update:open', false)"
                  type="button"
                  class="px-6 py-2 font-medium text-gray-300 bg-gray-600 border border-transparent rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                >
                  Cancel
                </button>
              </div>
            </form>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch } from "vue";
import { useForm, router } from "@inertiajs/vue3";
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

const form = useForm({
  payment_amount: "",
  payment_method: "cash",
  notes: "",
});

// Watch for modal opening to reset form
watch(() => props.open, (newValue) => {
  if (newValue && props.selectedCreditBill) {
    form.reset();
    form.payment_amount = "";
    form.payment_method = "cash";
    form.notes = "";
  }
});

const submit = () => {
  if (!props.selectedCreditBill) return;

  form.patch(route("creditbill.updatePayment", props.selectedCreditBill.id), {
    preserveScroll: true,
    onSuccess: () => {
      emit("update:open", false);
      form.reset();
    },
  });
};

const markAsPaid = () => {
  if (!props.selectedCreditBill) return;

  if (confirm('Are you sure you want to mark this credit bill as fully paid?')) {
    router.patch(route("creditbill.markPaid", props.selectedCreditBill.id), {}, {
      preserveScroll: true,
      onSuccess: () => {
        emit("update:open", false);
      },
    });
  }
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

