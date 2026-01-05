<template>
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-10" @close="$emit('update:open', false)">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity"></div>
      </TransitionChild>
      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="relative bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full p-6">
              <div>
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100">
                  <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c0 .866.79 1.57 1.763 1.57h15.08c.973 0 1.763-.704 1.763-1.57V2.05c0-.866-.79-1.57-1.763-1.57H3.46c-.973 0-1.763.704-1.763 1.57V16.616z" />
                  </svg>
                </div>
                <div class="mt-3 text-center sm:mt-5">
                  <h3 class="text-lg font-medium leading-6 text-gray-900">Delete Expense</h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">Are you sure you want to delete this expense? This action cannot be undone.</p>
                  </div>
                </div>
              </div>
              <div class="mt-6 sm:mt-8 sm:flex sm:flex-row-reverse gap-3">
                <button @click="deleteIt" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                  Delete
                </button>
                <button @click="$emit('update:open', false)" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:mt-0 sm:w-auto sm:text-sm">
                  Cancel
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from "@headlessui/vue";
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ open: Boolean, expenseId: Number });
const emit = defineEmits(["update:open", "deleted"]);

const form = useForm({});

function deleteIt() {
  form.delete(`/expenses/${props.expenseId}`, {
    onSuccess: () => {
      emit('deleted');
      emit('update:open', false);
    },
    onError: (errors) => {
      console.error('Error deleting expense:', errors);
    }
  });
}
</script>
