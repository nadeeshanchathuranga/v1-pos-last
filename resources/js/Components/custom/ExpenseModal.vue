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
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" />
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
          <DialogPanel class="bg-black border-4 border-blue-600 rounded-[20px] shadow-xl w-5/6 lg:w-2/5 p-10 text-center">
            <!-- Modal Title -->
            <DialogTitle class="text-xl font-bold text-white">
              {{ isEditing ? '✏️ Edit Expense' : '➕ Add Expense' }}
            </DialogTitle>

            <form @submit.prevent="submit">
              <!-- Modal Form -->
              <div class="mt-6 space-y-4 text-left">
                <!-- Date and Category Row -->
                <div class="flex items-center gap-8">
                  <div class="w-full">
                    <label class="block text-sm font-medium text-gray-300">Date:</label>
                    <input type="date" v-model="form.date" required class="w-full px-4 py-2 mt-2 text-black bg-white rounded-md focus:outline-none focus:ring focus:ring-blue-600" />
                  </div>
                  <div class="w-full">
                    <label class="block text-sm font-medium text-gray-300">Category:</label>
                    <select v-model="form.category" required class="w-full px-4 py-2 mt-2 text-black bg-white rounded-md focus:outline-none focus:ring focus:ring-blue-600">
                      <option value="">Select Category</option>
                      <option value="Rent">🏠 Rent</option>
                      <option value="Salary">💰 Salary</option>
                      <option value="Utilities">💡 Utilities</option>
                      <option value="Transport">🚗 Transport</option>
                      <option value="Maintenance">🔧 Maintenance</option>
                      <option value="Other">📌 Other</option>
                    </select>
                  </div>
                </div>

                <!-- Amount and Payment Method Row -->
                <div class="flex items-center gap-8">
                  <div class="w-full">
                    <label class="block text-sm font-medium text-gray-300">Amount:</label>
                    <input type="number" v-model="form.amount" required class="w-full px-4 py-2 mt-2 text-black bg-white rounded-md focus:outline-none focus:ring focus:ring-blue-600" min="0" step="0.01" placeholder="0.00" />
                  </div>
                  <div class="w-full">
                    <label class="block text-sm font-medium text-gray-300">Payment Method:</label>
                    <select v-model="form.payment_method" required class="w-full px-4 py-2 mt-2 text-black bg-white rounded-md focus:outline-none focus:ring focus:ring-blue-600">
                      <option value="">Select Method</option>
                      <option value="Cash">💵 Cash</option>
                      <option value="Bank">🏦 Bank</option>
                      <option value="Card">💳 Card</option>
                      <option value="Other">📝 Other</option>
                    </select>
                  </div>
                </div>

                <!-- Reference and Description Row -->
                <div class="flex items-center gap-8">
                  <div class="w-full">
                    <label class="block text-sm font-medium text-gray-300">Reference/Invoice #:</label>
                    <input type="text" v-model="form.reference" class="w-full px-4 py-2 mt-2 text-black bg-white rounded-md focus:outline-none focus:ring focus:ring-blue-600" placeholder="Optional" />
                  </div>
                  <div class="w-full">
                    <label class="block text-sm font-medium text-gray-300">Description:</label>
                    <input type="text" v-model="form.description" class="w-full px-4 py-2 mt-2 text-black bg-white rounded-md focus:outline-none focus:ring focus:ring-blue-600" placeholder="Optional" />
                  </div>
                </div>

                <!-- Note -->
                <div>
                  <label class="block text-sm font-medium text-gray-300">Notes (Optional):</label>
                  <textarea v-model="form.note" class="w-full px-4 py-2 mt-2 text-black bg-white rounded-md focus:outline-none focus:ring focus:ring-blue-600" rows="3" placeholder="Additional notes..."></textarea>
                </div>
              </div>

              <!-- Modal Buttons -->
              <div class="mt-6 space-x-4 text-center">
                <button class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700" type="submit" :disabled="form.processing">
                  {{ form.processing ? 'Saving...' : isEditing ? 'Update Expense' : 'Save Expense' }}
                </button>
                <button class="px-4 py-2 text-gray-700 bg-gray-300 rounded hover:bg-gray-400" type="button" @click="$emit('update:open', false)">
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
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from "@headlessui/vue";
import { watch, computed } from "vue";
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ open: Boolean, selectedExpense: Object });
const emit = defineEmits(["update:open", "saved"]);

const getTodayDate = () => new Date().toISOString().split('T')[0];

const form = useForm({ 
  date: getTodayDate(),
  category: '', 
  amount: null, 
  payment_method: '',
  reference: '',
  note: '',
  description: ''
});

const isEditing = computed(() => props.selectedExpense && props.selectedExpense.id);

function submit() {
  if (isEditing.value) {
    form.post(`/expenses/${props.selectedExpense.id}`, {
      onSuccess: () => {
        emit('saved');
        emit('update:open', false);
        form.reset();
        form.date = getTodayDate();
      },
      onError: (errors) => {
        console.error('Error saving expense:', errors);
      }
    });
  } else {
    form.post('/expenses', {
      onSuccess: () => {
        emit('saved');
        emit('update:open', false);
        form.reset();
        form.date = getTodayDate();
      },
      onError: (errors) => {
        console.error('Error saving expense:', errors);
      }
    });
  }
}

watch(() => props.open, (val) => {
  if (val) {
    if (isEditing.value) {
      form.date = props.selectedExpense.date;
      form.category = props.selectedExpense.category;
      form.amount = props.selectedExpense.amount;
      form.payment_method = props.selectedExpense.payment_method;
      form.reference = props.selectedExpense.reference || '';
      form.note = props.selectedExpense.note || '';
      form.description = props.selectedExpense.description || '';
    } else {
      form.reset();
      form.date = getTodayDate();
    }
  }
});
</script>
