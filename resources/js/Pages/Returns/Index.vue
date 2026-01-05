<template>
    <Head title="Process Return" />
    <Banner />
    <div class="flex flex-col items-center justify-start min-h-screen py-8 space-y-4 bg-gray-100 md:px-36 px-16">
        <!-- Include the Header -->
        <Header />
        <div class="w-full md:w-5/6 py-12 space-y-8">
            <!-- Page Header -->
            <div class="flex items-center justify-between space-x-4">
                <div class="flex w-full space-x-4">
                    <Link href="/pos">
                        <img src="/images/back-arrow.png" class="w-14 h-14" />
                    </Link>
                    <p class="pt-3 text-4xl font-bold tracking-wide text-black uppercase">
                        Process Return
                    </p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="flex md:flex-row flex-col w-full gap-8">
                <!-- Left Side - Order Search -->
                <div class="flex flex-col md:w-1/2 w-full space-y-6">
                    <!-- Order Search Card -->
                    <div class="p-8 space-y-6 bg-white shadow-lg rounded-3xl border-2 border-gray-200">
                        <div class="flex items-center space-x-3">
                            <i class="ri-search-line text-3xl text-blue-600"></i>
                            <h2 class="text-3xl font-bold text-gray-800">Find Order</h2>
                        </div>

                        <!-- Order ID Input -->
                        <div class="space-y-2">
                            <label class="text-lg font-semibold text-gray-700">Enter Order ID</label>
                            <div class="flex space-x-3">
                                <input
                                    v-model="orderIdSearch"
                                    type="text"
                                    placeholder="e.g., ORD-ABC123"
                                    @keyup.enter="searchOrder"
                                    class="flex-1 h-14 px-4 text-lg border-2 border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                />
                                <button
                                    @click="searchOrder"
                                    :disabled="isSearching"
                                    class="px-6 h-14 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors disabled:bg-gray-400"
                                >
                                    <i v-if="isSearching" class="ri-loader-4-line animate-spin mr-2"></i>
                                    <i v-else class="ri-search-line mr-2"></i>
                                    Search
                                </button>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div v-if="errorMessage" class="p-4 bg-red-100 border-2 border-red-400 rounded-xl">
                            <p class="text-red-700 font-semibold">
                                <i class="ri-error-warning-line mr-2"></i>{{ errorMessage }}
                            </p>
                        </div>

                        <!-- Recent Orders Quick Select -->
                        <div v-if="!selectedSale" class="space-y-3">
                            <p class="text-lg font-semibold text-gray-700">Or select from recent orders:</p>
                            <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                                <div
                                    v-for="sale in recentSales"
                                    :key="sale.id"
                                    @click="loadOrder(sale.id)"
                                    class="p-4 border-b border-gray-200 cursor-pointer hover:bg-blue-50 transition-colors"
                                >
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-lg text-gray-800">{{ sale.order_id }}</p>
                                            <p class="text-sm text-gray-600">{{ sale.customer?.name || 'Walk-in Customer' }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-lg text-green-600">{{ sale.total_amount }} LKR</p>
                                            <p class="text-sm text-gray-500">{{ sale.sale_date }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="recentSales.length === 0" class="p-4 text-center text-gray-500">
                                    No recent orders found
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Order Details -->
                    <div v-if="selectedSale" class="p-8 space-y-4 bg-blue-50 shadow-lg rounded-3xl border-2 border-blue-300">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <i class="ri-file-list-3-line text-3xl text-blue-600"></i>
                                <h2 class="text-2xl font-bold text-blue-800">Order Details</h2>
                            </div>
                            <button
                                @click="clearSelection"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                <i class="ri-refresh-line mr-1"></i>Change Order
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-lg">
                            <div>
                                <p class="text-gray-600">Order ID:</p>
                                <p class="font-bold text-gray-800">{{ selectedSale.order_id }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Date:</p>
                                <p class="font-bold text-gray-800">{{ selectedSale.sale_date }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Customer:</p>
                                <p class="font-bold text-gray-800">{{ selectedSale.customer?.name || 'Walk-in Customer' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Employee:</p>
                                <p class="font-bold text-gray-800">{{ selectedSaleEmployee?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Original Total:</p>
                                <p class="font-bold text-green-600 text-xl">{{ selectedSale.total_amount }} LKR</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Payment Method:</p>
                                <p class="font-bold text-gray-800">{{ selectedSale.payment_method }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Available Items for Return -->
                    <div v-if="loadedItems.length > 0" class="p-8 space-y-4 bg-white shadow-lg rounded-3xl border-2 border-gray-200">
                        <div class="flex items-center space-x-3">
                            <i class="ri-shopping-bag-line text-3xl text-gray-600"></i>
                            <h2 class="text-2xl font-bold text-gray-800">Available Items for Return</h2>
                        </div>

                        <div class="max-h-96 overflow-y-auto space-y-3">
                            <div
                                v-for="item in loadedItems"
                                :key="'loaded-' + item.id"
                                class="flex items-center justify-between p-4 bg-gray-50 border-2 rounded-xl transition-all"
                                :class="{ 'border-green-500 bg-green-50': item.is_selected }"
                            >
                                <div class="flex items-center space-x-4">
                                    <img
                                        :src="item.product?.image ? `/${item.product.image}` : '/images/placeholder.jpg'"
                                        alt="Product"
                                        class="w-16 h-16 object-cover rounded-lg border border-gray-300"
                                    />
                                    <div>
                                        <p class="font-bold text-lg text-gray-800">{{ item.product?.name }}</p>
                                        <p class="text-sm text-gray-600">Available: {{ item.remaining_quantity }} units</p>
                                        <p class="font-semibold text-green-600">@ {{ item.unit_price }} LKR</p>
                                    </div>
                                </div>
                                <button
                                    v-if="!item.is_selected"
                                    @click="addItemToReturn(item)"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                                >
                                    <i class="ri-add-line mr-1"></i>Add to Return
                                </button>
                                <button
                                    v-else
                                    @click="removeItemFromReturn(item.id)"
                                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                                >
                                    <i class="ri-check-line mr-1"></i>Added
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Return Cart & Billing -->
                <div class="flex flex-col md:w-1/2 w-full">
                    <div class="p-8 space-y-6 bg-white shadow-lg rounded-3xl border-4 border-red-400">
                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <i class="ri-exchange-funds-line text-3xl text-red-600"></i>
                                <h2 class="text-3xl font-bold text-red-600">Return Cart</h2>
                            </div>
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full font-bold">
                                {{ returnItems.length }} item(s)
                            </span>
                        </div>

                        <!-- Return Items List -->
                        <div v-if="returnItems.length > 0" class="space-y-4 max-h-96 overflow-y-auto">
                            <div
                                v-for="(item, index) in returnItems"
                                :key="'return-' + item.id"
                                class="p-4 bg-red-50 border-2 border-red-200 rounded-xl"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center space-x-3">
                                        <img
                                            :src="item.product?.image ? `/${item.product.image}` : '/images/placeholder.jpg'"
                                            alt="Product"
                                            class="w-12 h-12 object-cover rounded"
                                        />
                                        <div>
                                            <p class="font-bold text-gray-800">{{ item.product?.name }}</p>
                                            <p class="text-sm text-gray-600">@ {{ item.unit_price }} LKR each</p>
                                        </div>
                                    </div>
                                    <button
                                        @click="removeReturnItem(item)"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <i class="ri-close-circle-line text-2xl"></i>
                                    </button>
                                </div>

                                <!-- Quantity Controls -->
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-gray-600">Return Qty:</span>
                                        <button
                                            @click="decrementQuantity(item)"
                                            class="w-8 h-8 bg-red-600 text-white rounded flex items-center justify-center hover:bg-red-700"
                                        >
                                            <i class="ri-subtract-line"></i>
                                        </button>
                                        <input
                                            type="number"
                                            v-model.number="item.return_quantity"
                                            min="1"
                                            :max="item.remaining_quantity"
                                            class="w-16 h-8 text-center border-2 border-gray-300 rounded"
                                        />
                                        <button
                                            @click="incrementQuantity(item)"
                                            class="w-8 h-8 bg-red-600 text-white rounded flex items-center justify-center hover:bg-red-700"
                                        >
                                            <i class="ri-add-line"></i>
                                        </button>
                                        <span class="text-sm text-gray-500">(max: {{ item.remaining_quantity }})</span>
                                    </div>
                                    <p class="font-bold text-red-600 text-xl">
                                        -{{ (item.return_quantity * item.unit_price).toFixed(2) }} LKR
                                    </p>
                                </div>

                                <!-- Return Reason (Required) -->
                                <div class="mt-3">
                                    <label class="text-sm font-semibold text-gray-700">
                                        Return Reason <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="item.reason"
                                        type="text"
                                        placeholder="Enter reason for return (required)"
                                        class="w-full mt-1 px-3 py-2 border-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500"
                                        :class="item.reason ? 'border-green-400' : 'border-red-300'"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Empty Cart Message -->
                        <div v-else class="text-center py-12">
                            <i class="ri-shopping-cart-2-line text-6xl text-gray-300"></i>
                            <p class="text-xl text-gray-500 mt-4">No items selected for return</p>
                            <p class="text-gray-400">Search for an order and add items to process a return</p>
                        </div>

                        <!-- Billing Summary -->
                        <div v-if="returnItems.length > 0" class="space-y-3 pt-4 border-t-2 border-gray-200">
                            <!-- Original Order Info -->
                            <div v-if="selectedSale" class="flex justify-between text-lg px-4 py-2 bg-blue-50 rounded-lg">
                                <span class="text-blue-800">Original Order Total:</span>
                                <span class="font-bold text-blue-800">{{ selectedSale.total_amount }} LKR</span>
                            </div>

                            <!-- Return Amount -->
                            <div class="flex justify-between text-xl px-4 py-2 bg-red-100 rounded-lg">
                                <span class="text-red-700 font-semibold">Total Return Amount:</span>
                                <span class="font-bold text-red-700">-{{ returnTotal.toFixed(2) }} LKR</span>
                            </div>

                            <!-- Custom Discount -->
                            <div class="flex items-center justify-between px-4">
                                <span class="text-lg">Return Discount:</span>
                                <div class="flex items-center space-x-2">
                                    <input
                                        v-model.number="customDiscount"
                                        type="number"
                                        min="0"
                                        class="w-24 h-10 px-3 text-center border-2 border-gray-300 rounded-lg"
                                    />
                                    <select
                                        v-model="customDiscountType"
                                        class="h-10 px-3 border-2 border-gray-300 rounded-lg"
                                    >
                                        <option value="percent">%</option>
                                        <option value="fixed">LKR</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Discount Amount Display -->
                            <div v-if="discountAmount > 0" class="flex justify-between text-lg px-4 text-orange-600">
                                <span>Discount Applied:</span>
                                <span class="font-semibold">-{{ discountAmount.toFixed(2) }} LKR</span>
                            </div>

                            <!-- Cash Return Input -->
                            <div class="flex items-center justify-between px-4 py-3 bg-green-50 rounded-lg">
                                <span class="text-lg font-semibold text-green-800">Cash Return Amount:</span>
                                <div class="flex items-center space-x-2">
                                    <input
                                        v-model.number="cashReturnAmount"
                                        type="number"
                                        min="0"
                                        class="w-32 h-12 px-3 text-lg text-center border-2 border-green-400 rounded-lg font-bold"
                                    />
                                    <span class="text-lg font-bold text-green-800">LKR</span>
                                </div>
                            </div>

                            <!-- Final Refund Amount -->
                            <div class="flex justify-between text-2xl px-4 py-3 bg-yellow-100 rounded-lg border-2 border-yellow-400">
                                <span class="font-bold text-yellow-800">Final Refund:</span>
                                <span class="font-bold text-yellow-800">{{ finalRefundAmount.toFixed(2) }} LKR</span>
                            </div>

                            <!-- Balance (if cash given is different) -->
                            <div v-if="cashReturnAmount > 0 && Math.abs(cashReturnAmount - finalRefundAmount) > 0.01"
                                class="flex justify-between text-lg px-4 py-2"
                                :class="balance >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            >
                                <span>{{ balance >= 0 ? 'Change Given:' : 'Amount Short:' }}</span>
                                <span class="font-bold">{{ Math.abs(balance).toFixed(2) }} LKR</span>
                            </div>
                        </div>

                        <!-- Process Return Button -->
                        <button
                            v-if="returnItems.length > 0"
                            @click="processReturn"
                            :disabled="isProcessing || !canProcess"
                            class="w-full py-4 text-xl font-bold text-white rounded-xl transition-colors"
                            :class="canProcess && !isProcessing ? 'bg-red-600 hover:bg-red-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'"
                        >
                            <i v-if="isProcessing" class="ri-loader-4-line animate-spin mr-2"></i>
                            <i v-else class="ri-exchange-funds-line mr-2"></i>
                            {{ isProcessing ? 'Processing...' : 'Process Cash Return' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <ReturnSuccessModal
        :open="isSuccessModalOpen"
        @update:open="handleModalClose"
        @printed="handlePrinted"
        @close="handleClose"
        :returnData="returnSuccessData"
        :companyInfo="companyInfo"
    />

    <Footer />
</template>

<script setup>
import Header from "@/Components/custom/Header.vue";
import Footer from "@/Components/custom/Footer.vue";
import Banner from "@/Components/Banner.vue";
import ReturnSuccessModal from "@/Components/custom/ReturnSuccessModal.vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import axios from "axios";

const page = usePage();
const companyInfo = computed(() => page.props.companyInfo);

const props = defineProps({
    sales: {
        type: Array,
        default: () => []
    },
    loggedInUser: Object,
});

// State
const orderIdSearch = ref('');
const isSearching = ref(false);
const isProcessing = ref(false);
const errorMessage = ref('');
const selectedSale = ref(null);
const selectedSaleEmployee = ref(null);
const loadedItems = ref([]);
const returnItems = ref([]);
const customDiscount = ref(0);
const customDiscountType = ref('percent');
const cashReturnAmount = ref(0);
const isSuccessModalOpen = ref(false);
const returnSuccessData = ref(null);

// Computed
const recentSales = computed(() => {
    return props.sales.slice(0, 10);
});

const returnTotal = computed(() => {
    return returnItems.value.reduce((sum, item) => {
        return sum + (item.return_quantity * item.unit_price);
    }, 0);
});

const discountAmount = computed(() => {
    if (customDiscountType.value === 'percent') {
        return (returnTotal.value * customDiscount.value) / 100;
    }
    return customDiscount.value;
});

const finalRefundAmount = computed(() => {
    return returnTotal.value - discountAmount.value;
});

const balance = computed(() => {
    return cashReturnAmount.value - finalRefundAmount.value;
});

const canProcess = computed(() => {
    // All return items must have a reason
    const allHaveReasons = returnItems.value.every(item => item.reason && item.reason.trim());
    // All return quantities must be valid
    const allValidQuantities = returnItems.value.every(item =>
        item.return_quantity > 0 && item.return_quantity <= item.remaining_quantity
    );
    return returnItems.value.length > 0 && allHaveReasons && allValidQuantities;
});

// Methods
const searchOrder = async () => {
    if (!orderIdSearch.value.trim()) {
        errorMessage.value = 'Please enter an Order ID';
        return;
    }

    isSearching.value = true;
    errorMessage.value = '';

    try {
        // Find the sale by order_id
        const sale = props.sales.find(s =>
            s.order_id.toLowerCase() === orderIdSearch.value.trim().toLowerCase()
        );

        if (sale) {
            await loadOrder(sale.id);
        } else {
            errorMessage.value = `Order "${orderIdSearch.value}" not found. Please check the Order ID.`;
        }
    } catch (error) {
        console.error('Error searching order:', error);
        errorMessage.value = 'Failed to search for order. Please try again.';
    } finally {
        isSearching.value = false;
    }
};

const loadOrder = async (saleId) => {
    isSearching.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.post('/api/sale/items', { sale_id: saleId });

        const sale = props.sales.find(s => s.id === saleId);
        selectedSale.value = sale;
        selectedSaleEmployee.value = response.data.employee;

        // Clear existing items
        returnItems.value = [];
        loadedItems.value = [];

        // Load items with remaining quantities
        response.data.saleItems.forEach(item => {
            if (item.remaining_quantity > 0) {
                loadedItems.value.push({
                    ...item,
                    is_selected: false,
                });
            }
        });

        if (loadedItems.value.length === 0) {
            errorMessage.value = 'No items available for return in this order.';
        }

    } catch (error) {
        console.error('Error loading order:', error);
        errorMessage.value = 'Failed to load order details. Please try again.';
    } finally {
        isSearching.value = false;
    }
};

const clearSelection = () => {
    selectedSale.value = null;
    selectedSaleEmployee.value = null;
    loadedItems.value = [];
    returnItems.value = [];
    customDiscount.value = 0;
    cashReturnAmount.value = 0;
    errorMessage.value = '';
    orderIdSearch.value = '';
};

const addItemToReturn = (item) => {
    const loadedItem = loadedItems.value.find(i => i.id === item.id);
    if (loadedItem) {
        loadedItem.is_selected = true;
    }

    returnItems.value.push({
        id: item.id,
        sale_item_id: item.id,
        product_id: item.product_id,
        product: item.product,
        unit_price: item.unit_price,
        return_quantity: item.remaining_quantity,
        remaining_quantity: item.remaining_quantity,
        reason: '',
        return_date: new Date().toISOString().split('T')[0],
        sale_id: selectedSale.value.id,
        cost_price: item.cost_price || item.product?.cost_price || 0,
    });

    // Auto-calculate cash return
    cashReturnAmount.value = finalRefundAmount.value;
};

const removeItemFromReturn = (itemId) => {
    returnItems.value = returnItems.value.filter(r => r.sale_item_id !== itemId);

    const loadedItem = loadedItems.value.find(i => i.id === itemId);
    if (loadedItem) {
        loadedItem.is_selected = false;
    }

    // Update cash return
    cashReturnAmount.value = finalRefundAmount.value;
};

const removeReturnItem = (item) => {
    removeItemFromReturn(item.sale_item_id);
};

const incrementQuantity = (item) => {
    if (item.return_quantity < item.remaining_quantity) {
        item.return_quantity++;
        cashReturnAmount.value = finalRefundAmount.value;
    }
};

const decrementQuantity = (item) => {
    if (item.return_quantity > 1) {
        item.return_quantity--;
        cashReturnAmount.value = finalRefundAmount.value;
    }
};

const processReturn = async () => {
    // Validate
    const missingReasons = returnItems.value.filter(item => !item.reason || !item.reason.trim());
    if (missingReasons.length > 0) {
        errorMessage.value = 'Please provide a return reason for all items.';
        return;
    }

    isProcessing.value = true;
    errorMessage.value = '';

    try {
        const returnItemsData = returnItems.value.map(item => ({
            sale_id: selectedSale.value.id,
            sale_item_id: item.sale_item_id,
            product_id: item.product_id,
            quantity: item.return_quantity,
            reason: item.reason,
            unit_price: item.unit_price,
            return_date: item.return_date,
            return_type: 'cash', // Cash returns only
        }));

        const response = await axios.post('/returns/process', {
            return_items: returnItemsData,
            custom_discount: customDiscount.value,
            custom_discount_type: customDiscountType.value,
            cash_return_amount: cashReturnAmount.value,
        });

        if (response.data.success) {
            // Prepare success data for modal
            returnSuccessData.value = {
                order_id: selectedSale.value.order_id,
                return_items: returnItems.value.map(item => ({
                    name: item.product?.name,
                    quantity: item.return_quantity,
                    unit_price: item.unit_price,
                    total: item.return_quantity * item.unit_price,
                    reason: item.reason,
                })),
                return_total: returnTotal.value,
                discount: discountAmount.value,
                discount_type: customDiscountType.value,
                discount_value: customDiscount.value,
                final_refund: finalRefundAmount.value,
                cash_returned: cashReturnAmount.value,
                customer: selectedSale.value.customer,
                employee: selectedSaleEmployee.value,
                cashier: props.loggedInUser,
                date: new Date().toLocaleDateString(),
                time: new Date().toLocaleTimeString(),
            };

            isSuccessModalOpen.value = true;
        }
    } catch (error) {
        console.error('Error processing return:', error);
        errorMessage.value = error.response?.data?.error || error.response?.data?.message || 'Failed to process return. Please try again.';
    } finally {
        isProcessing.value = false;
    }
};

const handleModalClose = (value) => {
    isSuccessModalOpen.value = value;
    if (!value) {
        // Redirect to POS after closing success modal
        router.visit('/pos', {
            preserveScroll: false,
            preserveState: false,
        });
    }
};

const handlePrinted = () => {
    // Redirect to POS after printing
    router.visit('/pos', {
        preserveScroll: false,
        preserveState: false,
    });
};

const handleClose = () => {
    // Redirect to POS without printing
    router.visit('/pos', {
        preserveScroll: false,
        preserveState: false,
    });
};
</script>
