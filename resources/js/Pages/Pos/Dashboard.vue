<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Select from 'primevue/select';
import ConfirmPopup from 'primevue/confirmpopup';
import { useConfirm } from 'primevue/useconfirm';
import draggable from "vuedraggable";
import { usePosStore } from '@/stores/pos';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const toast = useToast();

// --- PROPS RECIBIDAS DESDE LARAVEL/INERTIA ---
const props = defineProps({
    categories: Array,
    taxes: Array,
    products: {
        type: Array,
        default: () => []
    }
});

const confirm = useConfirm();

const productsList = ref([...props.products]);

// --- ESTADOS REACTIVOS ---
const selectedCategory = ref(null); // null significa "All Items"
// const cart = ref([]);
const barcodeBuffer = ref('');
const lastKeyTime = ref(Date.now());
const isCreating = ref(false);
const showQuickCreateModal = ref(false);
const pendingBarcode = ref(null);

// const transactions = ref([
//     {
//         id: 1,
//         number: 1,
//         cart: []
//     }
// ]);

const nextTransactionNumber = ref(2);
// const currentTransactionId = ref(1);
const pos = usePosStore();

// const currentTransaction = computed(() => {
//     return pos.transactions.find(
//         t => t.id === currentTransactionId.value
//     );
// });

const currentTransaction = computed(() => {
    return pos.transactions.find(
        t => t.id === pos.currentTransactionId
    ) ?? null;
});

const cart = computed(() => {
    return currentTransaction.value?.cart ?? [];
});

// const quickForm = ref({
//     barcode: '',
//     name: '',
//     price: 0,
//     category_id: null
// });

const quickForm = ref({
    barcode: '',
    sku: '',
    name: '',
    brand: '',
    description: '',
    image: '',
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
        currentTransaction.value.cart.push({
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
        currentTransaction.value.cart =
            currentTransaction.value.cart.filter(
                i => i.id !== productId
            );
    }
};

const clearCart = () => {
    currentTransaction.value.cart = [];
};

// const createTransaction = () => {

//     const id = Date.now();

//     transactions.value.push({
//         id,
//         number: nextTransactionNumber.value++,
//         cart: []
//     });

//     currentTransactionId.value = id;
// };

const removeTransaction = (event, id) => {

    const transaction = pos.transactions.find(t => t.id === id);

    if (!transaction) return;

    if (transaction.cart.length > 0) {

        confirm.require({
            target: event.currentTarget,
            message: 'Esta venta tiene productos. ¿Deseas eliminarla?',
            header: 'Eliminar venta',
            icon: 'pi pi-exclamation-triangle',
            rejectLabel: 'Cancelar',
            acceptLabel: 'Eliminar',
            acceptClass: 'p-button-danger',

            accept: () => {
                deleteTransaction(id);
            }
        });

        return;
    }

    deleteTransaction(id);
}

// const deleteTransaction = (id) => {

//     if (pos.transactions.length === 1) return;

//     pos.transactions = pos.transactions.filter(t => t.id !== id);

//     if (currentTransactionId.value === id) {
//         currentTransactionId.value = pos.transactions.value[0].id;
//     }
// };

const deleteTransaction = (id) => {
    pos.deleteTransaction(id);
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
            }
            else {

                if (isCreating.value) return;

                isCreating.value = true;

                try {

                    const barcode = barcodeBuffer.value;


                    // Primero dejamos el código
                    quickForm.value = {
                        barcode: barcode,
                        sku: barcode,
                        name: '',
                        brand: '',
                        description: '',
                        image: '',
                        price: 0,
                        category_id: null
                    };


                    // Consultar OpenFoodFacts
                    const { data } = await axios.get(
                        route('products.barcode.search'),
                        {
                            params: {
                                barcode: barcode
                            }
                        }
                    );


                    console.log(
                        "Producto encontrado API:",
                        data
                    );


                    // Si encontró información llenamos el formulario
                    if(data && data.name){

                        quickForm.value.name =
                            data.name ?? '';

                        quickForm.value.brand =
                            data.brand ?? '';

                        quickForm.value.description =
                            data.description ?? '';

                        quickForm.value.image =
                            data.image ?? '';

                        quickForm.value.category_id =
                            data.category_id ?? null;

                    }

                    // Abrir modal
                    showQuickCreateModal.value = true;


                } catch(error){

                    console.error(
                        "Error buscando producto:",
                        error
                    );


                    // Aunque falle la API mostramos formulario
                    showQuickCreateModal.value = true;


                } finally {

                    isCreating.value = false;

                }
            }
            // else {
            //     if (isCreating.value) return;

            //     isCreating.value = true;

            //     try {
            //         quickForm.value.barcode = barcodeBuffer.value;

            //         showQuickCreateModal.value = true;

            //     } finally {
            //         isCreating.value = false;
            //     }
            // }

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

        const response = await axios.post(
            route('product.store'),
            quickForm.value
        );

        const data = response.data;

        // Mensaje de éxito
        toast.add({
            severity: 'success',
            summary: 'Producto creado',
            detail: data.message,
            life: 3000
        });

        const product = data.product;

        const exists = productsList.value.some(
            p => p.id === product.id
        );

        if (!exists) {
            productsList.value.unshift(product);
        }

        addToCart(product);

        showQuickCreateModal.value = false;

        quickForm.value = {
            barcode: '',
            sku: '',
            name: '',
            brand: '',
            description: '',
            image: '',
            price: 0,
            category_id: null
        };

    } catch (error) {

        // Errores de validación
        if (error.response?.status === 422) {

            const errors = Object.values(error.response.data.errors)
                .flat()
                .join('\n');

            toast.add({
                severity: 'warn',
                summary: 'Datos inválidos',
                detail: errors,
                life: 5000
            });

            return;
        }

        // Error del servidor
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message ?? 'Ocurrió un error inesperado.',
            life: 5000
        });

        console.error(error);

    }

};

// const createProductFromBarcode = async () => {
//     try {
//         const { data } = await axios.post(route('products.store'), quickForm.value);

//         const exists = productsList.value.some(p => p.id === data.id);

//         if (!exists) {
//             productsList.value.unshift(data);
//         }

//         addToCart(data);

//         showQuickCreateModal.value = false;

//         quickForm.value = {
//             barcode: barcode,
//             sku: barcode,
//             name: '',
//             brand: '',
//             description: '',
//             image: '',
//             price: 0,
//             category_id: null
//         };
//     }
//     catch(error){

//     console.error(
//         "Error creando producto:",
//         error.response?.data
//     );

// }
    // catch (error) {
    //     console.error('Error creando producto rápido:', error);
    // }
// };


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
        <Toast />
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
                                {{ formatCurrency(product.price ?? 0) }}
                            </span>

                            <div class="aspect-square w-full bg-slate-200 dark:bg-slate-800 overflow-hidden relative">
                                <img
                                    v-if="product.image"
                                    :src="product.image"
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center text-slate-400 font-bold text-xl uppercase"
                                >
                                    {{ product.name?.substring(0, 2) ?? 'PD' }}
                                </div>
                            </div>

                            <div class="p-3.5 flex flex-col flex-1 justify-between">
                                <div>
                                    <h4 class="font-bold text-slate-800 dark:text-slate-200 text-sm line-clamp-2 mb-1">
                                        {{ product.name }}
                                    </h4>
                                    <p class="text-xs text-slate-400 mb-2">
                                        SKU: {{ product.sku ?? product.barcode }}
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

            <div
                class="w-full lg:w-[420px] flex flex-col flex-shrink-0 min-h-0"
            >

                <!-- ===================== -->
                <!-- PESTAÑAS -->
                <!-- ===================== -->

                <div
                    class="bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-t-3xl px-3 pt-3"
                >
                    <div class="flex items-end gap-1 overflow-x-auto scrollbar-none">
                        <draggable
                            v-model="pos.transactions"
                            item-key="id"
                            tag="div"
                            class="flex items-end gap-1"
                            :animation="250"
                            ghost-class="ghost-tab"
                        >
                            <template #item="{ element: transaction }">

                                <div class="shrink-0">

                                    <button
                                        @click="pos.currentTransactionId = transaction.id"
                                        class="group flex items-center gap-2 h-11 px-4 pr-2 rounded-t-xl border border-b-0 text-xs font-semibold transition-all relative"
                                        :class="
                                            pos.currentTransactionId === transaction.id
                                                ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-800 dark:text-white top-px'
                                                : 'bg-slate-200 dark:bg-slate-800 border-transparent text-slate-500 hover:bg-slate-300 dark:hover:bg-slate-700'
                                        "
                                    >
                                        <i class="pi pi-shopping-cart text-xs"></i>

                                        <span class="whitespace-nowrap">
                                            Venta {{ transaction.number }}
                                        </span>

                                        <span
                                            v-if="transaction.cart.length"
                                            class="bg-emerald-500 text-white min-w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold"
                                        >
                                            {{ transaction.cart.length }}
                                        </span>

                                        <!-- Botón cerrar -->
                                        <span
                                            @click.stop="removeTransaction($event, transaction.id)"
                                            class="ml-1 w-5 h-5 rounded-full opacity-0 group-hover:opacity-100 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all cursor-pointer"
                                        >
                                            <i class="pi pi-times text-[10px]"></i>
                                        </span>

                                        <!-- Línea verde -->
                                        <span
                                            v-if="currentTransactionId === transaction.id"
                                            class="absolute bottom-0 left-0 w-full h-[3px] bg-emerald-500 rounded-full"
                                        ></span>

                                    </button>

                                </div>

                            </template>
                        </draggable>

                        <TransitionGroup
                            name="tabs"
                            tag="div"
                            class="flex items-end gap-1"
                        >
                            <button
                                @click="pos.createTransaction"
                                class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 mb-1 ml-2 bg-slate-200 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white transition"
                            >
                                +
                            </button>
                        </TransitionGroup>

                    </div>
                </div>

                <!-- ===================== -->
                <!-- PANEL -->
                <!-- ===================== -->

                <div
                    class="flex-1 flex flex-col min-h-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 border-t-0 rounded-b-3xl shadow-sm overflow-hidden"
                >

                    <!-- HEADER -->

                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800"
                    >
                        <div>

                            <h3 class="font-bold text-lg">
                                Orden actual
                            </h3>

                            <p class="text-xs text-slate-500 mt-1">
                                <i class="pi pi-shopping-cart text-xs"></i> {{ totalItemsInCart }} artículos en el carrito
                            </p>

                        </div>

                        <button
                            v-if="cart.length"
                            @click="clearCart"
                            class="text-xs text-red-500 hover:text-red-600 font-semibold"
                        >
                            Vaciar
                        </button>

                    </div>

                    <Transition
                        name="fade-slide"
                        mode="out-in"
                    >
                        <div :key="currentTransactionId">

                            <!-- carrito -->
                            <div class="flex-1 overflow-y-auto p-5 space-y-3">

                                <div
                                    v-if="cart.length === 0"
                                    class="h-full flex flex-col items-center justify-center text-slate-400"
                                >

                                    <span class="text-6xl mb-4">
                                        <i class="pi pi-shopping-cart text-xs"></i>
                                    </span>

                                    <p class="font-semibold">
                                        El carrito está vacío.
                                    </p>

                                    <p class="text-xs mt-2 text-center">
                                        Selecciona productos o utiliza el lector.
                                    </p>

                                </div>

                                <div
                                    v-for="item in cart"
                                    :key="item.id"
                                    class="flex items-center gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-3"
                                >

                                    <div class="flex-1 min-w-0">

                                        <h4 class="font-semibold truncate">
                                            {{ item.name }}
                                        </h4>

                                        <p class="text-xs text-slate-400">
                                            {{ formatCurrency(item.price) }}
                                            ×
                                            {{ item.quantity }}
                                        </p>

                                    </div>

                                    <div class="font-bold">
                                        {{ formatCurrency(item.price * item.quantity) }}
                                    </div>

                                    <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg p-1">

                                        <button
                                            @click="updateQuantity(item.id,-1)"
                                            class="w-7 h-7"
                                        >
                                            −
                                        </button>

                                        <span class="w-8 text-center">
                                            {{ item.quantity }}
                                        </span>

                                        <button
                                            @click="updateQuantity(item.id,1)"
                                            class="w-7 h-7"
                                        >
                                            +
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>
                    </Transition>

                    <!-- FOOTER -->

                    <div
                        class="border-t border-slate-200 dark:border-slate-800 p-5 space-y-4 bg-white dark:bg-slate-900"
                    >

                        <div class="space-y-2 text-sm">

                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>{{ formatCurrency(subtotal) }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span>IVA</span>
                                <span>{{ formatCurrency(totalTax) }}</span>
                            </div>

                            <div class="flex justify-between border-t pt-3">

                                <span class="font-bold text-lg">
                                    Total
                                </span>

                                <span class="font-black text-4xl text-emerald-600">
                                    {{ formatCurrency(amountDue) }}
                                </span>

                            </div>

                        </div>

                        <div class="grid grid-cols-2 gap-3">

                            <button class="rounded-xl border py-3 font-semibold hover:bg-slate-50">
                                <i class="pi pi-print"></i>
                                <span> Imprimir</span>
                            </button>

                            <button class="rounded-xl border py-3 font-semibold hover:bg-slate-50">
                                <i class="pi pi-wallet"></i>
                                <span> Abrir cajón</span>
                            </button>

                        </div>

                        <button
                            class="w-full bg-emerald-600 hover:bg-emerald-700
                            text-white font-bold py-4 rounded-xl
                            flex items-center justify-center gap-3
                            transition-all"
                        >
                            <i class="pi pi-check-circle"></i>
                            <span>Finalizar venta</span>
                        </button>

                    </div>

                </div>

            </div>

        </div>
        <Dialog
            v-model:visible="showQuickCreateModal"
            modal
            header="Nuevo producto"
            :style="{ width: '450px' }"
        >
            <div class="flex flex-col gap-4">


                <!-- Vista previa OpenFoodFacts -->
                <div
                    v-if="quickForm.image"
                    class="flex gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800"
                >

                    <img
                        :src="quickForm.image"
                        class="w-20 h-20 rounded-lg object-cover border"
                    />

                    <div class="flex flex-col justify-center">

                        <span class="font-bold text-sm">
                            {{ quickForm.name }}
                        </span>

                        <span
                            v-if="quickForm.brand"
                            class="text-xs text-slate-500"
                        >
                            {{ quickForm.brand }}
                        </span>

                        <span
                            v-if="quickForm.description"
                            class="text-xs text-slate-400 line-clamp-2 mt-1"
                        >
                            {{ quickForm.description }}
                        </span>

                    </div>

                </div>


                <!-- Código de barras -->
                <div>

                    <label class="text-sm font-medium">
                        Código de barras
                    </label>

                    <InputText
                        v-model="quickForm.barcode"
                        disabled
                        class="w-full"
                    />

                </div>



                <!-- Nombre -->
                <div>

                    <label class="text-sm font-medium">
                        Nombre
                    </label>

                    <InputText
                        v-model="quickForm.name"
                        class="w-full"
                        placeholder="Nombre del producto"
                    />

                </div>



                <!-- Marca -->
                <div>

                    <label class="text-sm font-medium">
                        Marca
                    </label>

                    <InputText
                        v-model="quickForm.brand"
                        class="w-full"
                        placeholder="Marca"
                    />

                </div>



                <!-- Precio -->
                <div>

                    <label class="text-sm font-medium">
                        Precio venta
                    </label>

                    <InputText
                        v-model="quickForm.price"
                        type="number"
                        class="w-full"
                        placeholder="0.00"
                    />

                </div>



                <!-- Categoría -->
                <div>

                    <label class="text-sm font-medium">
                        Categoría
                    </label>


                    <Select
                        v-model="quickForm.category_id"
                        :options="categories"
                        optionLabel="name"
                        optionValue="id"
                        filter
                        placeholder="Seleccionar categoría"
                        class="w-full"
                    >


                        <template #value="slotProps">

                            <span v-if="slotProps.value">

                                {{
                                    categories.find(
                                        c => c.id === slotProps.value
                                    )?.name
                                }}

                            </span>


                            <span v-else>
                                Seleccionar categoría
                            </span>


                        </template>



                        <template #option="slotProps">

                            <div class="flex justify-between w-full">

                                <span>
                                    {{ slotProps.option.name }}
                                </span>


                                <span class="text-xs text-slate-400">
                                    #{{ slotProps.option.id }}
                                </span>

                            </div>

                        </template>


                    </Select>


                </div>



                <!-- Descripción -->
                <div>

                    <label class="text-sm font-medium">
                        Descripción
                    </label>

                    <textarea
                        v-model="quickForm.description"
                        rows="3"
                        class="
                            w-full rounded-lg
                            border border-slate-300
                            dark:bg-slate-900
                            dark:border-slate-700
                            p-2 text-sm
                        "
                        placeholder="Descripción"
                    ></textarea>

                </div>



                <!-- Botones -->
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
        <ConfirmPopup />
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
.tabs-enter-active{
    transition:all .25s ease;
}

.tabs-leave-active{
    transition:all .2s ease;
}

.tabs-enter-from{
    opacity:0;
    transform:translateY(-15px) scale(.8);
}

.tabs-enter-to{
    opacity:1;
    transform:translateY(0) scale(1);
}

.tabs-leave-to{
    opacity:0;
    transform:scale(.8);
}

.tabs-move{
    transition:transform .25s ease;
}
</style>
