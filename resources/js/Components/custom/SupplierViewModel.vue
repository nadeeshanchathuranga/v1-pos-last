<template :key="modalKey">
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-50" @close="$emit('update:open', false)">
      <!-- Overlay -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-60 transition-opacity" />
      </TransitionChild>

      <!-- Modal Content -->
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
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
            class="bg-[#111827] border border-blue-600 rounded-2xl shadow-2xl max-w-2xl w-full p-8 text-white space-y-8"
          >
            <!-- Title -->
            <DialogTitle class="text-2xl font-semibold text-center text-blue-500">
              Supplier Payment Summary
            </DialogTitle>

            <!-- Supplier Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div>
                <label class="text-gray-400">Supplier Name:</label>
                <p class="text-lg font-bold text-white">
                  {{ supplier?.name || 'N/A' }}
                </p>
              </div>

              <!-- Final Due (before this payment) -->
              <div>
                <label class="text-gray-400">Final Due:</label>
                <p class="text-green-400 text-lg font-bold">
                  <!-- show due before this payment -->
                  LKR {{ originalDue.toFixed(2) }}
                </p>
              </div>
            </div>

            <!-- Product List -->
            <div>
              <label class="text-gray-400">Products:</label>

              <div class="max-h-64 overflow-y-auto">
                <ul class="list-disc pl-5 mt-2 space-y-1 text-sm text-white">
                  <li
                    v-for="product in supplier?.products || []"
                    :key="product.id"
                  >
                    {{ product.name }} – LKR
                    {{ parseFloat(product.cost_price || 0).toFixed(2) }}
                    × {{ product.total_quantity }}
                  </li>
                  <li
                    v-if="!supplier?.products?.length"
                    class="italic text-gray-500"
                  >
                    No products available
                  </li>
                </ul>
              </div>

              <!-- Total Cost -->
              <div
                v-if="supplier?.products?.length"
                class="mt-3 text-right text-base font-semibold text-green-400"
              >
                Total: LKR {{ totalCost.toFixed(2) }}
              </div>
            </div>

            <!-- Payment Form -->
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Pay Input -->
              <div>
                <label for="pay" class="block text-sm text-gray-400 mb-1">
                  Pay:
                </label>
                <input
                  v-model="form.pay"
                  type="number"
                  min="1"
                  step="0.01"
                  id="pay"
                  required
                  placeholder="Enter payment amount"
                  class="w-full rounded-lg border border-gray-600 bg-white text-black px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-600"
                />
                <p v-if="form.errors.pay" class="text-sm text-red-500 mt-1">
                  {{ form.errors.pay }}
                </p>
              </div>

              <!-- Balance -->
              <div>
                <label class="block text-sm text-gray-400 mb-1">
                  Balance (Due):
                </label>
                <p
                  :class="currentDue < 0 ? 'text-red-400' : 'text-yellow-300'"
                  class="text-lg font-semibold"
                >
                  LKR {{ currentDue.toFixed(2) }}
                </p>
              </div>

              <!-- Buttons -->
              <div class="flex justify-between pt-4">
                <button
                  type="submit"
                  :disabled="Number(form.pay) <= 0 || currentDue < 0"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Submit Payment
                </button>

                <button
                  type="button"
                  @click="handleCancel"
                  class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg text-sm font-medium transition"
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
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from "@headlessui/vue";
import { ref, reactive, computed, watch } from "vue";
import axios from "axios";

// Emits
const emit = defineEmits(["update:open"]);

// Props
const props = defineProps({
  supplier: {
    type: Object,
    default: () => ({}),
  },
  open: {
    type: Boolean,
    default: false,
  },
});

// Modal reset key
const modalKey = ref(Date.now());

// Form state
const form = reactive({
  pay: "",
  errors: {},
});

// server-provided paid total (fetched when modal opens)
const serverPaid = ref(null);

// Watch modal open to reset form when closing
watch(
  () => props.open,
  (val) => {
    if (!val) {
      modalKey.value = Date.now();
      form.pay = "";
      form.errors = {};
      serverPaid.value = null;
    }
    // when opening, fetch latest payments for supplier
    if (val && props.supplier?.id) {
      fetchSupplierSummary(props.supplier.id);
    }
  }
);

// Total product cost = sum of cost_price * total_quantity
const totalCost = computed(() => {
  if (!props.supplier?.products?.length) return 0;
  return props.supplier.products.reduce((sum, product) => {
    const cost = parseFloat(product.cost_price || 0);
    const qty = parseFloat(product.total_quantity || 0);
    return sum + cost * qty;
  }, 0);
});

// Already paid (coming from backend as aggregated value on supplier)
// e.g. in controller: Supplier::withSum('payments as pay', 'pay')
const alreadyPaid = computed(() => {
  if (serverPaid.value !== null) return parseFloat(serverPaid.value || 0);
  return parseFloat(props.supplier?.pay || 0);
});

// Current due after the amount typed in this modal
const currentDue = computed(() => {
  const payNow = parseFloat(form.pay || 0);
  return totalCost.value - (alreadyPaid.value + payNow);
});

// Original due (before this payment)
const originalDue = computed(() => {
  return totalCost.value - alreadyPaid.value;
});

// Fetch supplier payment summary from server
const fetchSupplierSummary = async (supplierId) => {
  try {
    const res = await axios.get(`/suppliers/${supplierId}/summary`);
    serverPaid.value = parseFloat(res.data.paid_total || 0);
    // optionally override totalCost if you want server trust
    // but we keep computing totalCost from products on frontend
  } catch (e) {
    // ignore — keep using client data
    serverPaid.value = null;
  }
};

// Submit form
const submit = async () => {
  const payAmount = parseFloat(form.pay || 0);

  if (isNaN(payAmount) || payAmount <= 0) {
    form.errors = { pay: 'Enter a valid payment amount greater than 0.' };
    return;
  }

  if (currentDue.value < 0) {
    alert('Payment exceeds due balance.');
    return;
  }

  try {
    if (!props.supplier || !props.supplier.id) {
      alert('No supplier selected.');
      return;
    }
    await axios.post(
      "/supplier-payment",
      {
        supplier_id: props.supplier.id,
        pay: payAmount,
        total: totalCost.value,
      },
      {
        headers: { Accept: 'application/json' },
      }
    );

    alert('Payment submitted successfully!');
    form.pay = '';
    form.errors = {};
    window.location.reload();
  } catch (error) {
    if (error.response?.data?.errors) {
      form.errors = error.response.data.errors;
    } else if (error.response?.data?.message) {
      alert(error.response.data.message);
    } else {
      alert('Something went wrong!');
    }
  }
};

// Optional sound
const playClickSound = () => {
  const clickSound = new Audio("/sounds/click-sound.mp3");
  clickSound.play();
};

const handleCancel = () => {
  playClickSound();
  emit('update:open', false);
};
</script>
