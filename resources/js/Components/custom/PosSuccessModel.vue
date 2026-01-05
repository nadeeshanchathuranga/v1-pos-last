<template>
   <TransitionRoot as="template" :show="open" static>
      <Dialog class="relative z-10" static>
         <!-- Modal Overlay -->
         <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
            leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click.stop />
         </TransitionChild>
         <!-- Modal Content -->
         <div class="fixed inset-0 z-10 flex items-center justify-center">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 scale-95"
               enter-to="opacity-100 scale-100" leave="ease-in duration-200" leave-from="opacity-100 scale-100"
               leave-to="opacity-0 scale-95">
               <DialogPanel
                  class="bg-white border-4 border-blue-600 rounded-[20px] shadow-xl max-w-xl w-full p-6 text-center">
                  <!-- Modal Title -->
                  <DialogTitle class="text-5xl font-bold">Payment Successful!</DialogTitle>
                  <div class="w-full h-full flex flex-col justify-center items-center space-y-8 mt-4">
                     <p class="text-justify text-3xl text-black">
                        Order Payment is Successful!
                     </p>
                     <div>
                        <img src="/images/checked.png" class="h-24 object-cover w-full" />
                     </div>
                  </div>
                  <div class="flex justify-center items-center space-x-4 pt-4 mt-4">
                     <button
                        class="cursor-pointer bg-blue-600 text-white font-bold uppercase tracking-wider px-4 shadow-xl py-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Send Reciept To Email
                     </button>
                     <button @click="(e) => { e.stopPropagation(); handlePrintReceipt(); }"
                        class="cursor-pointer bg-blue-600 text-white font-bold uppercase tracking-wider px-4 shadow-xl py-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Print Receipt
                     </button>
                     <button @click="$emit('update:open', false)"
                        class="cursor-pointer bg-red-600 text-white font-bold uppercase tracking-wider px-4 shadow-xl py-4 rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
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
   import {
       Dialog,
       DialogPanel,
       DialogTitle,
       TransitionChild,
       TransitionRoot,
   } from "@headlessui/vue";
   import { computed } from "vue";
   import { ref } from "vue";
   import { useForm, usePage } from "@inertiajs/vue3";

   const page = usePage();

   // Access the companyInfo from the page props
   const companyInfo = computed(() => page.props.companyInfo);

   const handleClose = () => {
       console.log("Modal close prevented");
   };

   const emit = defineEmits(["update:open"]);

   // The `open` prop controls the visibility of the modal
   const props = defineProps({
       open: {
           type: Boolean,
           required: true,
       },
       products: {
           type: Array,
           required: true,
       },
       returnItems: {
           type: Array,
           required: false,
           default: () => [],
       },
       cashier: Object,
       customer: Object,
       employee: Object,
       orderid: String,
       balance: Number,
       cash: Number,
       subTotal: Number,
       totalDiscount: Number,
       total: Number,
       custom_discount: Number,
       custom_discount_type: String,
       paymentMethod: String,
       kokoSurcharge: Number,
       returnAmount: {
           type: Number,
           default: 0
       },
       newProductAmount: {
           type: Number,
           default: 0
       }
   });

   const handlePrintReceipt = () => {
       console.log('handlePrintReceipt called');
       console.log('Products:', props.products);
       console.log('Order ID:', props.orderid);
       console.log('Payment Method:', props.paymentMethod);
       console.log('Customer prop:', props.customer);
       console.log('Employee prop:', props.employee);
       console.log('Cashier prop:', props.cashier);

       // Calculate totals from props.products
       const subTotal = props.products.reduce(
           (sum, product) => {
               const price = parseFloat(product.selling_price || product.unit_price || 0);
               const qty = parseFloat(product.quantity || 0);
               return sum + (price * qty);
           },
           0
       );
       const customDiscount = Number(props.custom_discount || 0);
       const totalDiscount = props.products
           .reduce((total, item) => {
               // For return items (negative price), don't apply additional discounts
               const itemPrice = parseFloat(item.selling_price || item.unit_price || 0);
               if (itemPrice < 0) {
                   return total; // Don't discount returns
               }

               // Check if item has a discount
               if (item.discount && item.discount > 0 && item.apply_discount == true) {
                   const sellingPrice = parseFloat(item.selling_price || item.unit_price || 0);
                   const discountedPrice = parseFloat(item.discounted_price || 0);
                   const discountAmount = (sellingPrice - discountedPrice) * item.quantity;
                   return total + discountAmount;
               }
               return total; // If no discount, return total as-is
           }, 0)
           .toFixed(2); // Ensures two decimal places

       const discount = 0; // Example discount (can be dynamic)
       const total = subTotal - totalDiscount - customDiscount;

       // Generate table rows dynamically using props.products
       const productRows = props.products
           .map((product) => {
               // Determine the price based on discount
               const basePrice = product.selling_price || product.unit_price || 0;
               const price = product.discount > 0 && product.apply_discount
                   ? product.discounted_price  // Use discounted price if discount is applied
                   : basePrice;    // Use selling price or unit price if no discount

               // Check if this is a return item (negative price or quantity)
               const isReturn = parseFloat(basePrice) < 0 || parseFloat(product.quantity) < 0;
               const absPrice = Math.abs(basePrice);
               const absQty = Math.abs(product.quantity);

               return `
           <tr>
             <td>${product.name || 'Unknown'}</td>
           <td style="text-align: center;">
  ${absQty} ${product.unit_id ? (product.unit?.name || '') : ''}
</td>

             <td>
               ${product.discount > 0 && product.apply_discount
                       ? `<div style="font-weight: bold; font-size: 7px; background-color:black; color:white;text-align:center;">${product.discount}% off</div>`
                       : ""
                   }
               <div>${isReturn ? '-' : ''}${absPrice}</div>
             </td>
           </tr>
         `;
           })
           .join("");


       // Debug: Log what we're about to use in the receipt
       console.log('About to generate receipt with:');
       console.log('Customer name:', props.customer?.name);
       console.log('Employee name:', props.employee?.name);
       console.log('Customer object:', props.customer);
       console.log('Employee object:', props.employee);

       // Generate the receipt HTML
       const receiptHTML = `
     <!DOCTYPE html>
     <html lang="en">
     <head>
         <meta charset="UTF-8">
         <meta name="viewport" content="width=device-width, initial-scale=1.0">
         <title>Receipt</title>
         <style>
             @media print {
                 body {
                     margin: 0;
                     padding: 0;
                     -webkit-print-color-adjust: exact;
                 }
             }



@page {
  size: 80mm auto;
  margin: 0;
}

body {
  width: 70mm;
  padding: 0 5mm;
}

             body {
                 background-color: #ffffff;
                 font-size: 12px;
                 font-family: 'Arial', sans-serif;
                 margin: 0;
                 padding: 10px;
                 color: #000;
             }
             .header {
                 text-align: center;
                 margin-bottom: 16px;
             }
             .header h1 {
                 font-size: 20px;
                 font-weight: bold;
                 margin: 0;
             }
             .header p {
                 font-size: 10px;
                 margin: 4px 0;
             }
             .section {
                 margin-bottom: 16px;
                 padding-top: 8px;
                 border-top: 1px solid #000;
             }
             .info-row {
                 display: flex;
                 justify-content: space-between;
                 font-size: 12px;
                 margin-top: 8px;
             }
             .info-row > div:last-child {
                 text-align: right;
             }
             .info-row p {
                 margin: 0;
                 font-weight: bold;
             }
             .info-row small {
                 font-weight: normal;
             }
             table {
                 width: 100%;
                 font-size: 10px;
                 border-collapse: collapse;
                 margin-top: 8px;
             }
             table th, table td {
                 padding: 6px 8px;

             }
             table th {
                 text-align: left;
             }
             table td {
                 text-align: right;
             }
             table td:first-child {
                 text-align: left;
             }
             .totals {
                 border-top: 1px solid #000;
                 padding-top: 8px;
                 font-size: 12px;
             }
             .totals div {
                 display: flex;
                 justify-content: space-between;
                 margin-bottom: 8px;
             }
             .totals .total-line {
                 font-size: 14px;
                 font-weight: bold;
             }
             .footer {
                 text-align: center;
                 font-size: 10px;
                 margin-top: 16px;
             }
             .footer p {
                 margin: 6px 0;
             }
             .footer .italic {
                 font-style: italic;
             }


         </style>
     </head>
    <body>
     <div class="receipt-container">
       <!-- Header -->
       <div class="header" style="text-align:center;">
         <img src="/images/billlogo.png" style="width: 200px; height: 80px;" />
         ${companyInfo?.value?.name ? `<h1>${companyInfo.value.name}</h1>` : ''}
         ${companyInfo?.value?.address ? `<p>${companyInfo.value.address}</p>` : ''}
         ${(companyInfo?.value?.phone || companyInfo?.value?.phone2 || companyInfo?.value?.email)
           ? `<p>${companyInfo.value.phone || ''} | ${companyInfo.value.phone2 || ''} ${companyInfo.value.email || ''}</p>`
           : ''}
       </div>

       <!-- Order Info -->
       <div class="section">
         <div class="info-row">
           <div>
             <p>Date & Time:</p>
             <small>${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</small>
           </div>
           <div>
             <p>Order No:</p>
             <small>${props.orderid}</small>
           </div>
         </div>
         <div class="info-row">
           <div>
             <p>Customer:</p>
             <small>${props.customer?.name || 'Walk-in Customer'}</small>
           </div>
           <div style="text-align: right;">
             <p>Cashier:</p>
             <small>${props.cashier?.name || 'admin'}</small>
           </div>
         </div>
         <div class="info-row">
           <div>
             <p>Employee:</p>
             <small>${props.employee?.name || 'No Employee Selected'}</small>
           </div>
           <div style="text-align: right;">
             <p>Payment Method:</p>
             <small>${props.paymentMethod || 'Cash'}</small>
           </div>
         </div>
       </div>

       <!-- Items Table -->
       <div class="section">
        <table style="width:100%; border-collapse: collapse;">


   <thead>
     <tr>
       <th style="text-align:left;">Item</th>
       <th style="text-align:center;">Qty × Price</th>
       <th style="text-align:right;">Total</th>
     </tr>
   </thead>
   <tbody>
     ${props.products.map(item => {
       const originalPrice = Number(item.selling_price) || 0;
       const discountedPrice = item.apply_discount
         ? Number(item.discounted_price)
         : originalPrice;
       const unitName = item.unit_id && item.unit ? item.unit.name : '';
       const hasDiscount = item.discount > 0;

       return `
    <tr style="border-bottom: 1px dashed #000;">
      <td style="text-align: left; padding: 8px 4px;">
        <b>${item.name}</b>
        ${hasDiscount ? `<br><small style="background-color: #000; color: #fff; font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 4px;">
          ${item.discount_type === 'percent' ? item.discount + '% off' : item.discount.toFixed(2) + ' LKR off'}
        </small>` : ''}
      </td>
      <td style="text-align: center; padding: 8px 4px;">${item.quantity}${unitName ? ' ' + unitName : ''} × ${discountedPrice.toFixed(2)}</td>
      <td style="text-align: right; padding: 8px 4px;">${(discountedPrice * item.quantity).toFixed(2)}</td>
    </tr>
       `;
     }).join('')}
   </tbody>
   </table>

       </div>

       <!-- Totals -->
       <div class="totals">
         <div>
           <span>Sub Total</span>
           <span>${(Number(props.subTotal) || 0).toFixed(2)} LKR</span>
         </div>
         <div>
           <span>Discount</span>
           <span>${(Number(props.totalDiscount) || 0).toFixed(2)} LKR</span>
         </div>
         ${props.returnAmount > 0 ? `
         <div style="color: red;">
           <span>Return Amount</span>
           <span>( ${(Number(props.returnAmount) || 0).toFixed(2)} LKR )</span>
         </div>` : ''}
         <div>
           <span>Custom Discount</span>
           <span>
             ${(Number(props.custom_discount) || 0).toFixed(2)}
             ${props.custom_discount_type === 'percent' ? '%' :
               props.custom_discount_type === 'fixed' ? 'LKR' : ''}
           </span>
         </div>
         ${props.paymentMethod === 'Koko' ? `
         <div>
           <span>Koko Surcharge (11.5%)</span>
           <span>${(Number(props.kokoSurcharge) || 0).toFixed(2)} LKR</span>
         </div>` : ''}
         <div class="total-line">
           <span>Total</span>
           <span>${(Number(props.total) || 0).toFixed(2)} LKR</span>
         </div>
         <div>
           <span>Cash</span>
           <span>${(Number(props.cash) || 0).toFixed(2)} LKR</span>
         </div>
         <div style="font-weight: bold;">
           <span>Balance</span>
           <span>${(Number(props.balance) || 0).toFixed(2)} LKR</span>
         </div>
       </div>

       <!-- Footer -->
       <div class="footer" style="text-align:center; margin-top:10px;">
         <p>THANK YOU COME AGAIN</p>
         <p class="italic">Let the quality define its own standards</p>
         <p style="font-weight: bold;">Powered by JAAN Network Ltd.</p>

       </div>
     </div>
   </body>

     </html>
     `;

       // Create or get the print iframe
       let printFrame = document.getElementById('printFrame');
       if (!printFrame) {
           printFrame = document.createElement('iframe');
           printFrame.id = 'printFrame';
           printFrame.style.position = 'absolute';
           printFrame.style.top = '-10000px';
           printFrame.style.left = '-10000px';
           printFrame.style.width = '0';
           printFrame.style.height = '0';
           printFrame.style.border = 'none';
           document.body.appendChild(printFrame);
       }

       // Write content to iframe
       let frameDoc = printFrame.contentWindow || printFrame.contentDocument;
       if (frameDoc && frameDoc.document) {
           frameDoc = frameDoc.document;
       }

       frameDoc.open();
       frameDoc.write(receiptHTML);
       frameDoc.close();

       // Print after a short delay to ensure content is loaded
       setTimeout(() => {
           printFrame.contentWindow.focus();
           printFrame.contentWindow.print();
       }, 500);
   };
</script>
