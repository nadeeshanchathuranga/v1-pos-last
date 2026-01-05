<style>
/* General DataTables Pagination Container Style */
.dataTables_wrapper .dataTables_paginate {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 20px;
}

/* Style the filter container */
#CreditBillTable_filter {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  margin-bottom: 16px; /* Add spacing below the filter */
}

/* Style the label and input field inside the filter */
#CreditBillTable_filter label {
  font-size: 17px;
  color: #000000; /* Match text color of the table header */
  display: flex;
  align-items: center;
}

/* Style the input field */
#CreditBillTable_filter input[type="search"] {
  font-weight: 400;
  padding: 9px 15px;
  font-size: 14px;
  color: #000000cc;
  border: 1px solid rgb(209 213 219);
  border-radius: 5px;
  background: #fff;
  outline: none;
  transition: all 0.5s ease;
}
#CreditBillTable_filter input[type="search"]:focus {
  outline: none; /* Removes the default outline */
  border: 1px solid #4b5563;
  box-shadow: none; /* Removes any focus box-shadow */
}

#CreditBillTable_filter {
  float: left;
}

.dataTables_wrapper {
  margin-bottom: 10px;
}
</style>

<template>
  <Head title="Credit Bills" />
  <Banner />
  <div
    class="flex flex-col items-center justify-start min-h-screen py-8 space-y-8 bg-gray-100 md:px-36 px-16"
  >
    <!-- Include the Header -->
    <Header />

    <!-- Main Content -->
    <div class="w-full md:w-5/6 md:py-12 space-y-24">
      <div class="flex md:flex-row flex-col md:items-center justify-between md:space-y-0 space-y-8">
        <!-- Back Button and Title -->
        <div class="flex items-center space-x-4">
          <Link
            href="/"
          >
            <img src="/images/back-arrow.png" class="w-14 h-14" alt="Back" />
          </Link>
          <p class="text-4xl font-bold tracking-wide text-black uppercase">
            Credit Bills
          </p>
        </div>

        <!-- Total Credit Bills -->
        <div class="flex items-center">
          <p class="text-3xl italic font-bold text-black">
            <span class="px-4 py-1 mr-3 text-white bg-black rounded-xl">
              {{ (totalCreditBills && totalCreditBills.length) || (allCreditBills && allCreditBills.length) || 0 }}
            </span>
            <span class="text-xl">/ Total Credit Bills</span>
          </p>
        </div>
      </div>

      <template v-if="allCreditBills && allCreditBills.length > 0">
        <div class="overflow-x-auto">
          <table
            id="CreditBillTable"
            class="w-full text-gray-700 bg-white border border-gray-300 rounded-lg shadow-md table-auto"
          >
            <thead>
              <tr
                class="bg-gradient-to-r from-blue-600 via-blue-500 to-blue-600 text-[16px] text-white border-b border-blue-700"
              >
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Name
                </th>
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Order ID
                </th>
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Total Amount
                </th>
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Remaining
                </th>
                <th class="p-4 font-semibold tracking-wide text-left uppercase">
                  Status
                </th>
                <th
                  class="p-4 font-semibold tracking-wide text-center uppercase"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="text-[13px] font-normal">
              <tr
                v-for="bill in allCreditBills"
                :key="bill.id"
                class="transition duration-200 ease-in-out hover:bg-gray-200 hover:shadow-lg"
              >
                <td class="p-4 font-bold border-t border-gray-200">
                  {{ bill.customer?.name || "Walk-in Customer" }}
                </td>
                <td class="p-4 border-t border-gray-200">
                  {{ bill.order_id || "N/A" }}
                </td>
                <td class="p-4 border-t border-gray-200">
                  {{ formatCurrency(bill.total_amount) }}
                </td>
                <td class="p-4 border-t border-gray-200">
                  {{ formatCurrency(bill.remaining_amount) }}
                </td>
                <td class="p-4 border-t border-gray-200">
                  {{ bill.payment_status?.toUpperCase() || "N/A" }}
                </td>
                <td class="p-4 text-center border-t border-gray-200">
                  <div class="inline-flex items-center w-full space-x-2">
                    <!-- View Button -->
                    <button
                      @click="openViewModal(bill)"
                      class="px-3 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                      title="View Payment History"
                    >
                      View
                    </button>
                    <!-- Edit Button -->
                    <button
                      @click="viewDetails(bill.id)"
                      class="px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors"
                    >
                      Edit
                    </button>

                    <!-- Delete Button -->
                    <button
                      @click="deleteBill(bill.id)"
                      class="px-4 py-2 bg-red-500 text-white rounded-lg ml-2"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
      <template v-else>
        <div class="col-span-4 text-center text-blue-500">
          <p class="text-center text-red-500 text-[17px]">
            No Credit Bills available
          </p>
        </div>
      </template>
    </div>
  </div>
  <Footer />

  <CreditBillViewModal
    :selected-credit-bill="selectedCreditBillForView"
    v-model:open="isViewModalOpen"
  />

  <CreditBillViewModal
    :selected-credit-bill="selectedCreditBillForView"
    v-model:open="isViewModalOpen"
  />

  <CreditBillUpdateModel
    :creditBills="allCreditBills"
    :selected-credit-bill="selectedCreditBill"
    v-model:open="isEditModalOpen"
  />

  <CreditBillDeleteModel
    :creditBills="allCreditBills"
    :selected-credit-bill="selectedCreditBill"
    v-model:open="isDeleteModalOpen"
  />
</template>

<script setup>
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import { Link, useForm, router } from "@inertiajs/vue3";
import Header from "@/Components/custom/Header.vue";
import Footer from "@/Components/custom/Footer.vue";
import Banner from "@/Components/Banner.vue";
import CreditBillViewModal from "@/Components/custom/CreditBillViewModal.vue";
import CreditBillUpdateModel from "@/Components/custom/CreditBillUpdateModel.vue";
import CreditBillDeleteModel from "@/Components/custom/CreditBillDeleteModel.vue";

const props = defineProps({
  allCreditBills: Array,
  totalCreditBills: Array,
});

const form = useForm({});

const openDeleteModal = (bill) => {
  selectedCreditBill.value = bill;
  isDeleteModalOpen.value = true;
};

const isViewModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedCreditBill = ref(null);
const selectedCreditBillForView = ref(null);

// Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-LK', {
    style: 'currency',
    currency: 'LKR',
    minimumFractionDigits: 2
  }).format(amount || 0).replace('LKR', '').trim() + ' LKR'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const openViewModal = (bill) => {
  console.log("Opening view modal for credit bill:", bill);
  selectedCreditBillForView.value = bill;
  isViewModalOpen.value = true;
};

const openEditModal = (bill) => {
  console.log("Opening edit modal for credit bill:", bill);
  selectedCreditBill.value = bill;
  isEditModalOpen.value = true;
};

const viewDetails = (id) => {
  const bill = props.allCreditBills.find(b => b.id === id);
  if (bill) {
    openEditModal(bill);
  }
}

const deleteBill = (id) => {
  const bill = props.allCreditBills.find(b => b.id === id);
  if (bill) {
    openDeleteModal(bill);
  }
}

$(document).ready(function () {
  let table = $("#CreditBillTable").DataTable({
    dom: "Bfrtip",
    pageLength: 10,
    buttons: [],
    columnDefs: [
      {
        targets: [5],
        searchable: false,
        orderable: false,
      },
    ],
    initComplete: function () {
      let searchInput = $("div.dataTables_filter input");
      searchInput.attr("placeholder", "Search ...");
      searchInput.off("keyup");
      searchInput.on("keypress", function (e) {});
    },
    language: {
      search: "",
    },
  });
});
</script>