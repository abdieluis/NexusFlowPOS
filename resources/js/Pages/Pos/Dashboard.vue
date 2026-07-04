<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Select from 'primevue/select';

// --- PROPS RECIBIDAS DESDE LARAVEL/INERTIA ---
const props = defineProps({
    categories: Array,
    taxes: Array,
    products: {
        type: Array,
        default: () => []
    }
});

const productsList = ref([...props.products]);

// --- ESTADOS REACTIVOS ---
const selectedCategory = ref(null); // null significa "All Items"
const cart = ref([]);
const barcodeBuffer = ref('');
const lastKeyTime = ref(Date.now());
const isCreating = ref(false);
const showQuickCreateModal = ref(false);
const pendingBarcode = ref(null);

const quickForm = ref({
    barcode: '',
    name: '',
    price: 0,
    category_id: null
});

// --- FILTRADO DE PRODUCTOS ---
const filteredProducts = computed(() => {
    const products = productsList.value ?? [];

    if (!selectedCategory.value) return products;

    return products.filter(p => p.category_id === selectedCategory.value);
});

// --- CÁLCULOS DEL CARRITO (SUBTOTAL, IVA, TOTAL) ---
const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (Number(item.price) * item.quantity), 0);
});

const totalTax = computed(() => {
    // Calcula el impuesto basado en la relación con el producto o una tasa general
    // Modifica según cómo manejes la relación de tax_id
    return cart.value.reduce((sum, item) => {
        const taxPercentage = item.tax?.percentage ? Number(item.tax.percentage) : 8.5; // 8.5% por defecto si no viene mapeado
        return sum + (Number(item.price) * item.quantity * (taxPercentage / 100));
    }, 0);
});

const amountDue = computed(() => subtotal.value + totalTax.value);

const totalItemsInCart = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

// --- ACCIONES DEL CARRITO ---
const addToCart = (product) => {
    // Validar si el producto cuenta con stock trackeable si es necesario
    const existingItem = cart.value.find(item => item.id === product.id);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.value.push({
            ...product,
            quantity: 1
        });
    }
};

const updateQuantity = (productId, change) => {
    const item = cart.value.find(i => i.id === productId);
    if (!item) return;

    item.quantity += change;

    // Si la cantidad llega a 0 o menos, lo removemos del carrito
    if (item.quantity <= 0) {
        cart.value = cart.value.filter(i => i.id !== productId);
    }
};

const clearCart = () => {
    cart.value = [];
};

const productMap = computed(() => {
    const map = new Map();
    (props.products ?? []).forEach(p => {
        if (p.barcode) map.set(p.barcode, p);
        if (p.sku) map.set(p.sku, p);
    });
    return map;
});

// --- LISTENER DEL LECTOR DE CÓDIGO DE BARRAS ---
const handleBarcodeScanner = async (e) => {
    const currentTime = Date.now();

    if (currentTime - lastKeyTime.value > 50) {
        barcodeBuffer.value = '';
    }

    lastKeyTime.value = currentTime;

    if (e.key === 'Enter') {
        if (barcodeBuffer.value.length > 2) {

            const scannedProduct =
                productMap.value.get(barcodeBuffer.value);

            if (scannedProduct) {
                addToCart(scannedProduct);
            } else {
                if (isCreating.value) return;

                isCreating.value = true;

                try {
                    quickForm.value.barcode = barcodeBuffer.value;

                    showQuickCreateModal.value = true;

                } finally {
                    isCreating.value = false;
                }
            }

            barcodeBuffer.value = '';
        }
    } else {
        if (e.key.length === 1) {
            barcodeBuffer.value += e.key;
        }
    }
};

const createProductFromBarcode = async () => {
    try {
        const { data } = await axios.post(route('products.quickCreate'), quickForm.value);

        const exists = productsList.value.some(p => p.id === data.id);

        if (!exists) {
            productsList.value.unshift(data);
        }

        addToCart(data);

        showQuickCreateModal.value = false;

        quickForm.value = {
            barcode: '',
            name: '',
            price: 0,
            category_id: null
        };

    } catch (error) {
        console.error('Error creando producto rápido:', error);
    }
};


watch(showQuickCreateModal, async (open) => {
    if (open) {
        await nextTick();
        document.querySelector('input')?.focus();
    }
});

// --- CICLO DE VIDA ---
onMounted(() => {
    window.addEventListener('keydown', handleBarcodeScanner);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleBarcodeScanner);
});

// --- FORMATEADOR MONETARIO ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};
</script>

<template>
    <Head title="Punto de Venta" />

    <AppLayout>
        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-120px)] overflow-hidden">

            <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

                <div class="flex items-center gap-2 pb-4 overflow-x-auto scrollbar-none border-b border-slate-100 dark:border-slate-800 mb-6 flex-shrink-0">
                    <button
                        @click="selectedCategory = null"
                        class="px-5 py-2.5 rounded-xl font-medium transition-all text-sm whitespace-nowrap"
                        :class="!selectedCategory
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100'"
                    >
                        📂 Todos los artículos
                    </button>

                    <button
                        v-for="category in categories"
                        :key="category.id"
                        @click="selectedCategory = category.id"
                        class="px-5 py-2.5 rounded-xl font-medium transition-all text-sm whitespace-nowrap"
                        :class="selectedCategory === category.id
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100'"
                    >
                        {{ category.name }}
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto pr-1 pb-4">
                    <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center h-full text-slate-400 py-12">
                        <span class="text-4xl mb-2">📦</span>
                        <p>No se encontraron productos en esta categoría.</p>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            @click="addToCart(product)"
                            class="group relative flex flex-col bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden cursor-pointer hover:shadow-md transition-all duration-200"
                        >
                            <span class="absolute top-3 right-3 z-10 bg-black/70 backdrop-blur-md text-white px-2.5 py-1 rounded-lg text-xs font-bold">
                                {{ formatCurrency(product.price) }}
                            </span>

                            <div class="aspect-square w-full bg-slate-200 dark:bg-slate-800 overflow-hidden relative">
                                <img
                                    v-if="product.image"
                                    :src="product.image"
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400 font-bold text-xl uppercase">
                                    {{ product.name.substring(0, 2) }}
                                </div>
                            </div>

                            <div class="p-3.5 flex flex-col flex-1 justify-between">
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm line-clamp-2 mb-1">
                                        {{ product.name }}
                                    </h4>
                                    <p class="text-xs text-slate-400 mb-2">
                                        SKU: {{ product.sku }}
                                    </p>
                                </div>
                                <div class="flex items-center justify-between mt-1 text-xs">
                                    <span class="text-slate-500">Existencias:</span>
                                    <span :class="Number(product.stock_alert) >= 10 ? 'text-red-500 font-bold' : 'text-slate-600 dark:text-slate-400'">
                                        100 unidades</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-[420px] bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-3xl flex flex-col overflow-hidden shadow-sm flex-shrink-0">

                <div class="p-5 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 dark:text-slate-100">Orden actual</h3>
                        <p class="text-xs text-slate-500 mt-0.5">🛒 {{ totalItemsInCart }} artículos en el carrito</p>
                    </div>
                    <button
                        @click="clearCart"
                        v-if="cart.length > 0"
                        class="text-xs font-semibold text-red-500 hover:text-red-600 flex items-center gap-1 transition-colors"
                    >
                        🗑️ Clear
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-5 space-y-3">
                    <div v-if="cart.length === 0" class="h-full flex flex-col items-center justify-center text-slate-400 dark:text-slate-600 py-12">
                        <span class="text-5xl mb-3">🛒</span>
                        <p class="text-sm font-medium">El carrito está vacío.</p>
                        <p class="text-xs text-slate-400 mt-1 text-center max-w-[200px]">Selecciona productos o pasa el lector de barras.</p>
                    </div>

                    <div
                        v-for="item in cart"
                        :key="item.id"
                        class="flex items-center gap-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-3 rounded-xl shadow-2xs"
                    >
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-sm text-slate-800 dark:text-slate-200 truncate">{{ item.name }}</h4>
                            <p class="text-xs text-slate-400 mt-0.5">{{ formatCurrency(item.price) }} × {{ item.quantity }}</p>
                        </div>

                        <div class="font-bold text-sm text-slate-700 dark:text-slate-300 px-1">
                            {{ formatCurrency(item.price * item.quantity) }}
                        </div>

                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg p-1 overflow-hidden">
                            <button
                                @click="updateQuantity(item.id, -1)"
                                class="w-7 h-7 flex items-center justify-center text-slate-600 dark:text-slate-300 font-bold hover:bg-white dark:hover:bg-slate-700 rounded-md transition-all text-sm"
                            >
                                −
                            </button>
                            <span class="w-8 text-center text-xs font-bold text-slate-800 dark:text-slate-200">
                                {{ item.quantity }}
                            </span>
                            <button
                                @click="updateQuantity(item.id, 1)"
                                class="w-7 h-7 flex items-center justify-center text-slate-600 dark:text-slate-300 font-bold hover:bg-white dark:hover:bg-slate-700 rounded-md transition-all text-sm"
                            >
                                +
                            </button>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 space-y-4 flex-shrink-0">
                    <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ formatCurrency(subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tax (8.5%)</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ formatCurrency(totalTax) }}</span>
                        </div>
                        <div class="flex justify-between items-baseline pt-2 border-t border-slate-100 dark:border-slate-800">
                            <span class="font-bold text-slate-800 dark:text-slate-200 text-base">Amount Due</span>
                            <span class="font-black text-3xl text-emerald-600 dark:text-emerald-500">
                                {{ formatCurrency(amountDue) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <button class="flex items-center justify-center gap-2 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 font-bold text-sm text-slate-700 dark:text-slate-300 py-3 rounded-xl transition-all">
                            🖨️ Print
                        </button>
                        <button class="flex items-center justify-center gap-2 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 font-bold text-sm text-slate-700 dark:text-slate-300 py-3 rounded-xl transition-all">
                            🔓 Open Drawer
                        </button>
                    </div>

                    <button
                        :disabled="cart.length === 0"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-200 dark:disabled:bg-slate-800 disabled:text-slate-400 text-white font-bold py-4 rounded-xl flex items-center justify-center gap-2 shadow-xs transition-all text-base"
                    >
                        💵 Finish Sale
                    </button>
                </div>

            </div>

        </div>
        <Dialog
            v-model:visible="showQuickCreateModal"
            modal
            header="Nuevo producto"
            :style="{ width: '400px' }"
        >
            <div class="flex flex-col gap-3">

                <!-- Barcode (solo lectura) -->
                <div>
                    <label class="text-sm font-medium">Código de barras</label>
                    <InputText v-model="quickForm.barcode" disabled class="w-full" />
                </div>

                <!-- Nombre -->
                <div>
                    <label class="text-sm font-medium">Nombre</label>
                    <InputText v-model="quickForm.name" class="w-full" placeholder="Nombre del producto" />
                </div>

                <!-- Precio -->
                <div>
                    <label class="text-sm font-medium">Precio</label>
                    <InputText v-model="quickForm.price" type="number" class="w-full" placeholder="0.00" />
                </div>

                <!-- Categoría -->
                <div>
                    <Select
                        v-model="quickForm.category_id"
                        :options="categories"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        placeholder="Seleccionar categoría"
                        class="w-full"
                    >
                        <!-- Valor seleccionado -->
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">
                                        {{ categories.find(c => c.id === slotProps.value)?.name }}
                                    </span>
                                </div>
                            </div>
                            <span v-else>
                                Seleccionar categoría
                            </span>
                        </template>

                        <!-- Opciones -->
                        <template #option="slotProps">
                            <div class="flex items-center justify-between w-full">
                                <span class="font-medium">
                                    {{ slotProps.option.name }}
                                </span>

                                <span class="text-xs text-slate-400">
                                    ID: {{ slotProps.option.id }}
                                </span>
                            </div>
                        </template>
                    </Select>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 pt-2">
                    <Button
                        label="Cancelar"
                        severity="secondary"
                        @click="showQuickCreateModal = false"
                    />

                    <Button
                        label="Guardar"
                        icon="pi pi-check"
                        severity="success"
                        @click="createProductFromBarcode"
                    />
                </div>

            </div>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
/* Elimina la barra de scroll visual en categorías manteniendo su funcionamiento */
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
