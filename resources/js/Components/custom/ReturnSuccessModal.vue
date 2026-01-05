<template>
    <TransitionRoot as="template" :show="open" static>
        <Dialog class="relative z-10" static>
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
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click.stop />
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
                    <DialogPanel class="bg-white border-4 border-blue-600 rounded-[20px] shadow-xl max-w-xl w-full p-6 text-center">
                        <!-- Modal Title -->
                        <DialogTitle class="text-5xl font-bold">Return Successful!</DialogTitle>
                        <div class="w-full h-full flex flex-col justify-center items-center space-y-8 mt-4">
                            <p class="text-justify text-3xl text-black">
                                Return has been processed successfully!
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
                            <button @click="handleClose"
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

const emit = defineEmits(["update:open", "printed", "close"]);

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    returnData: {
        type: Object,
        default: null,
    },
    companyInfo: {
        type: Object,
        default: null,
    },
});

const handlePrintAndClose = () => {
    if (!props.returnData) {
        emit('printed');
        emit('update:open', false);
        return;
    }

    const receiptHTML = generateReceiptHTML();

    // Open print window
    const printWindow = window.open('', 'ReturnReceipt', 'width=450,height=650,scrollbars=yes');

    if (printWindow) {
        printWindow.document.write(receiptHTML);
        printWindow.document.close();

        // Focus and print
        printWindow.focus();

        // Wait for content to load then print
        printWindow.onload = function() {
            printWindow.print();
        };

        // Also try immediate print after small delay (for browsers that don't trigger onload)
        setTimeout(() => {
            try {
                printWindow.print();
            } catch (e) {
                console.log('Print triggered');
            }
        }, 500);

        // Listen for after print or window close to redirect
        printWindow.onafterprint = function() {
            printWindow.close();
            emit('printed');
            emit('update:open', false);
        };

        // Fallback: if user closes window without printing, still redirect after delay
        const checkClosed = setInterval(() => {
            if (printWindow.closed) {
                clearInterval(checkClosed);
                emit('printed');
                emit('update:open', false);
            }
        }, 500);

        // Safety timeout - redirect after 30 seconds max
        setTimeout(() => {
            clearInterval(checkClosed);
            if (!printWindow.closed) {
                printWindow.close();
            }
            emit('printed');
            emit('update:open', false);
        }, 30000);

    } else {
        // Popup blocked - use iframe fallback
        let printFrame = document.getElementById('returnPrintFrame');
        if (printFrame) {
            printFrame.remove();
        }

        printFrame = document.createElement('iframe');
        printFrame.id = 'returnPrintFrame';
        printFrame.style.position = 'fixed';
        printFrame.style.right = '0';
        printFrame.style.bottom = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = 'none';
        document.body.appendChild(printFrame);

        const frameDoc = printFrame.contentWindow.document;
        frameDoc.open();
        frameDoc.write(receiptHTML);
        frameDoc.close();

        setTimeout(() => {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();

            // Redirect after print dialog
            setTimeout(() => {
                printFrame.remove();
                emit('printed');
                emit('update:open', false);
            }, 1000);
        }, 500);
    }
};

const generateReceiptHTML = () => {
    const data = props.returnData || {};
    const company = props.companyInfo?.value || props.companyInfo || {};

    console.log('Generating receipt with data:', data);

    // Parse numeric values
    const returnTotal = parseFloat(data.return_total) || 0;
    const discount = parseFloat(data.discount) || 0;
    const discountValue = parseFloat(data.discount_value) || 0;
    const cashReturned = parseFloat(data.cash_returned) || 0;
    const finalRefund = parseFloat(data.final_refund) || (returnTotal - discount);

    // Generate return items rows - matching POS receipt format
    const itemRows = (data.return_items || []).map(item => {
        const unitPrice = parseFloat(item.unit_price) || 0;
        const total = parseFloat(item.total) || (unitPrice * (item.quantity || 0));
        const quantity = item.quantity || 0;

        return `
        <tr style="border-bottom: 1px dashed #000;">
            <td style="text-align: left; padding: 8px 4px;">
                <b>${item.name || 'Unknown Item'}</b>
                <br><small style="color: #666;">Reason: ${item.reason || 'N/A'}</small>
            </td>
            <td style="text-align: center; padding: 8px 4px;">${quantity} × ${unitPrice.toFixed(2)}</td>
            <td style="text-align: right; padding: 8px 4px;">${total.toFixed(2)}</td>
        </tr>
    `}).join('') || '<tr><td colspan="3" style="text-align: center; padding: 8px;">No items</td></tr>';

    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Return Receipt - ${data.order_id || 'N/A'}</title>
        <style>
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact;
                }
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
            .return-badge {
                background-color: #dc2626;
                color: white;
                padding: 4px 12px;
                font-size: 12px;
                font-weight: bold;
                display: inline-block;
                margin: 8px 0;
                border-radius: 4px;
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
                ${company.name ? `<h1>${company.name}</h1>` : ''}
                ${company.address ? `<p>${company.address}</p>` : ''}
                ${(company.phone || company.phone2 || company.email)
                    ? `<p>${company.phone || ''} | ${company.phone2 || ''} ${company.email || ''}</p>`
                    : ''}
                <div class="return-badge">RETURN RECEIPT</div>
            </div>

            <!-- Order Info -->
            <div class="section">
                <div class="info-row">
                    <div>
                        <p>Date & Time:</p>
                        <small>${data.date || new Date().toLocaleDateString()} ${data.time || new Date().toLocaleTimeString()}</small>
                    </div>
                    <div>
                        <p>Order No:</p>
                        <small>${data.order_id || 'N/A'}</small>
                    </div>
                </div>
                <div class="info-row">
                    <div>
                        <p>Customer:</p>
                        <small>${data.customer?.name || 'Walk-in Customer'}</small>
                    </div>
                    <div style="text-align: right;">
                        <p>Cashier:</p>
                        <small>${data.cashier?.name || 'admin'}</small>
                    </div>
                </div>
                <div class="info-row">
                    <div>
                        <p>Employee:</p>
                        <small>${data.employee?.name || 'No Employee Selected'}</small>
                    </div>
                    <div style="text-align: right;">
                        <p>Return Type:</p>
                        <small>Cash Return</small>
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
                        ${itemRows}
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="totals">
                <div>
                    <span>Sub Total</span>
                    <span>${returnTotal.toFixed(2)} LKR</span>
                </div>
                ${discount > 0 ? `
                <div>
                    <span>Discount ${data.discount_type === 'percent' ? `(${discountValue}%)` : ''}</span>
                    <span>${discount.toFixed(2)} LKR</span>
                </div>
                ` : ''}
                <div class="total-line">
                    <span>Total Return</span>
                    <span>${finalRefund.toFixed(2)} LKR</span>
                </div>
                <div style="font-weight: bold; color: #dc2626;">
                    <span>Cash Refunded</span>
                    <span>${cashReturned.toFixed(2)} LKR</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer" style="text-align:center; margin-top:10px;">
                <p>Original bill has been updated</p>
                <p>THANK YOU COME AGAIN</p>
                <p class="italic">Let the quality define its own standards</p>
                <p style="font-weight: bold;">Powered by JAAN Network Ltd.</p>
            </div>
        </div>
    </body>
    </html>
    `;
};

const handlePrintReceipt = () => {
    if (!props.returnData) {
        alert('No return data available to print');
        return;
    }

    const receiptHTML = generateReceiptHTML();

    // Use hidden iframe for printing (same as POS)
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

    let frameDoc = printFrame.contentWindow || printFrame.contentDocument;
    if (frameDoc && frameDoc.document) {
        frameDoc = frameDoc.document;
    }

    frameDoc.open();
    frameDoc.write(receiptHTML);
    frameDoc.close();

    setTimeout(() => {
        printFrame.contentWindow.focus();
        printFrame.contentWindow.print();
    }, 500);
};

const handleClose = () => {
    emit('close');
    emit('update:open', false);
};

const handleSkipPrint = () => {
    emit('close');
    emit('update:open', false);
};
</script>
