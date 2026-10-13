<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Select from 'primevue/select';
import ConfirmPopup from 'primevue/confirmpopup';
import { useConfirm } from 'primevue/useconfirm';
import draggable from "vuedraggable";
import { usePosStore } from '@/stores/pos';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import Message from 'primevue/message';

const toast = useToast();

// --- PROPS RECIBIDAS DESDE LARAVEL/INERTIA ---
const props = defineProps({
    categories: Array,
    taxes: Array,
    products: {
        type: Array,
        default: () => []
    },
    customers: {
        type: Array,
        default: () => []
    },
    activeRegister: {
        type: Object,
        default: null
    },
    paymentMethods: Array,
});

const confirm = useConfirm();
const lastSaleTicket = ref(null);
const productsList = ref([...props.products]);
// --- ESTADOS REACTIVOS ---
const selectedCategory = ref(null); // null significa "All Items"
// --- ESTADOS BÁSCULA USB ---
const scaleConnected = ref(false);
const scalePort = ref(null);
const currentScaleWeight = ref(0.000);
const scaleError = ref(null);
// const cart = ref([]);
const barcodeBuffer = ref('');
const lastKeyTime = ref(Date.now());
const isCreating = ref(false);
const showQuickCreateModal = ref(false);
const pendingBarcode = ref(null);
const searchText = ref('');
const paid = ref(null);
const selectedCustomer = ref(1);
const nextTransactionNumber = ref(2);
const pos = usePosStore();
// const creditPayment = ref(0);
const showPaymentDialog = ref(false);

const defaultPaymentMethod = computed(() => {
    return props.paymentMethods.find(
        method => method.name === 'Efectivo'
    )?.id ?? null;
});

const paymentMethod = ref(defaultPaymentMethod.value);
const showUnitSelector = ref(false);
const selectedProduct = ref(null);
const selectedPackageUnit = ref(null);

// --- LÓGICA DE BÁSCULA USB (WEB SERIAL API) ---
const connectScale = async () => {
    scaleError.value = null;

    if (!('serial' in navigator)) {
        toast.add({
            severity: 'error',
            summary: 'Navegador No Compatible',
            detail: 'Web Serial no está soportado en este navegador. Usa Chrome o Edge.',
            life: 4000
        });
        return;
    }

    try {
        // Solicita al usuario seleccionar el puerto USB asignado a la báscula
        scalePort.value = await navigator.serial.requestPort();

        // Abre el puerto serie (ajusta baudRate según tu báscula, habitualmente 9600)
        await scalePort.value.open({ baudRate: 9600 });
        scaleConnected.value = true;

        toast.add({
            severity: 'success',
            summary: 'Báscula Conectada',
            detail: 'Lectura USB establecida correctamente.',
            life: 3000
        });

        listenToScale();
    } catch (err) {
        console.error('Error báscula:', err);
        scaleConnected.value = false;
        toast.add({
            severity: 'warn',
            summary: 'Conexión cancelada',
            detail: 'No se pudo abrir el puerto serie de la báscula.',
            life: 3000
        });
    }
};

const formatStock = (product) => {
    const rawStock = Number(product.stock) || 0;

    // Si NO es a granel / medible, se muestra como unidades enteras
    if (!product.is_weighted) {
        return `${Math.floor(rawStock)} unidades`;
    }

    // Si ES a granel / medible (is_weighted == 1)
    // Caso 1: Menor a 1 (ejemplo: 0.85 -> 850 g/m, 0.19 -> 190 g/m)
    if (rawStock < 1 && rawStock > 0) {
        // Convertimos los decimales a gramos/centímetros (multiplicando por 1000)
        const subUnits = Math.round(rawStock * 1000);
        return `${subUnits} g`; // O 'mts/cm' según la unidad base de tu negocio
    }

    // Caso 2: Mayor o igual a 1 (ejemplo: 3.00 -> 3 kg/mts)
    // Number() remueve ceros innecesarios a la derecha (ej: 3.50 -> 3.5)
    return `${Number(rawStock.toFixed(3))} kg`;
};

const listenToScale = async () => {
    const textDecoder = new TextDecoderStream();
    const readableStreamClosed = scalePort.value.readable.pipeTo(textDecoder.writable);
    const reader = textDecoder.readable.getReader();

    let buffer = '';

    try {
        while (true) {
            const { value, done } = await reader.read();
            if (done) {
                reader.releaseLock();
                break;
            }

            if (value) {
                buffer += value;

                // Las básculas terminan su trama con salto de línea (\n o \r)
                if (buffer.includes('\n') || buffer.includes('\r')) {
                    parseScaleData(buffer);
                    buffer = ''; // Limpiar buffer para la siguiente lectura
                }
            }
        }
    } catch (err) {
        console.error('Error leyendo flujo de báscula:', err);
        scaleConnected.value = false;
    }
};

const parseScaleData = (rawFrame) => {
    // Expresión regular para extraer números decimales de la trama enviada (ej. "ST,GS,+001.450kg")
    const match = rawFrame.match(/[-+]?\d*\.?\d+/);
    if (match) {
        const parsedWeight = parseFloat(match[0]);
        if (!isNaN(parsedWeight)) {
            currentScaleWeight.value = parsedWeight;
        }
    }
};

// Asignar el peso actual al producto seleccionado en el carrito o al agregarlo
const applyWeightToCartItem = (itemId) => {
    if(unit.sale_type_id === 2){
        item.quantity=currentScaleWeight.value;
    }
    if (currentScaleWeight.value <= 0) return;

    const item = cart.value.find(i => i.id === itemId);
    if (item) {
        item.quantity = Number(currentScaleWeight.value.toFixed(3));
    }
};

const addSelectedUnit = (unit)=>{
    if(!currentTransaction.value){
        return;
    }
    const product = selectedProduct.value;
    currentTransaction.value.cart.push({
        id: crypto.randomUUID(),
        product_id: product.id,
        product_variant_id: unit.product_variant_id ?? null,
        product_unit_id: unit.id,
        sale_type_id: unit.sale_type_id,
        sale_type: unit.sale_type,
        conversion_factor: unit.conversion_factor,
        name: product.name,
        quantity:1,
        price:Number(unit.price),
        tax:product.tax
    });
    showUnitSelector.value=false;
};
// Variable que nos dirá si la caja está abierta (true / false)
const isRegisterOpen = computed(() => !!props.activeRegister);

const currentTransaction = computed(() => {
    return pos.transactions.find(
        t => t.id === pos.currentTransactionId
    ) ?? null;
});

const cart = computed(() => {
    return currentTransaction.value?.cart ?? [];
});

// const customerDebt = computed(()=>{
//     return Number(
//         selectedCustomerObj.value?.current_balance ?? 0
//     );
// });

// const remainingCreditPayment = computed(()=>{
//     return Math.min(
//         Number(creditPayment.value),
//         customerDebt.value
//     );
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

const isCashPayment = computed(() => {
    return paymentMethod.value === defaultPaymentMethod.value;
});

const openPaymentDialog = () => {

    if (!isRegisterOpen.value) {
        toast.add({
            severity: 'error',
            summary: 'Caja Cerrada',
            detail: 'Abre un turno para realizar ventas.',
            life: 3000
        });

        return;
    }

    if (cart.value.length === 0) {
        toast.add({
            severity: 'warn',
            summary: 'Carrito vacío',
            detail: 'Agrega al menos un producto antes de cobrar.',
            life: 3000
        });

        return;
    }

    // Si es efectivo comenzamos sin monto recibido.
    // Si es otro método, se considera pagado el total.
    if (isCashPayment.value) {
        paid.value = null;
    } else {
        paid.value = Number(amountDue.value);
    }

    showPaymentDialog.value = true;
};

watch(paymentMethod, (method) => {

    if (method !== defaultPaymentMethod.value) {
        paid.value = amountDue.value;
    } else {
        paid.value = null;
    }

});

// --- FILTRADO DE PRODUCTOS ---
const filteredProducts = computed(() => {
    let products = productsList.value ?? [];
    // Categoría
    if (selectedCategory.value) {
        products = products.filter(
            p => p.category_id === selectedCategory.value
        );
    }

    // Texto
    if (pos.search.trim()) {

        const search = pos.search.toLowerCase();

        products = products.filter(product =>
            product.name?.toLowerCase().includes(search) ||
            product.sku?.toLowerCase().includes(search) ||
            product.barcode?.toLowerCase().includes(search) ||
            product.brand?.toLowerCase().includes(search)
        );
    }
    return products;

});

// --- CÁLCULOS DEL CARRITO (SUBTOTAL, IVA, TOTAL) ---
const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => {
        return sum + (
            Number(item.price) * item.quantity
        );
    }, 0);
});

const totalTax = computed(() => {

    return cart.value.reduce((sum, item) => {
        const taxPercentage = item.tax?.percentage
            ? Number(item.tax.percentage)
            : 8.5;
        return sum + (
            Number(item.price) *
            item.quantity *
            (taxPercentage / 100)
        );
    }, 0);

});

const amountDue = computed(() => {
    return subtotal.value + totalTax.value;
});

const totalItemsInCart = computed(() => {
    return cart.value.reduce((sum, item) => sum + item.quantity, 0);
});

const selectProduct = (product)=>{

    // Si tiene unidades configuradas
    if(product.product_units &&
       product.product_units.length > 0)
    {

        // una sola unidad
        if(product.product_units.length === 1){

            addSelectedUnit(
                product.product_units[0]
            );

            return;
        }


        // varias unidades
        selectedProduct.value = product;
        showUnitSelector.value = true;

        return;
    }


    // Producto antiguo / sin configuración
    addToCart(product);

};

// const selectProduct = (product) => {
//     // const packageUnit = product.product_units?.find(
//     //     unit => unit.sale_type?.name?.toLowerCase() === 'paquete'
//     // );
//     const packageUnit = product.product_units?.find(
//         unit => unit.sale_type_id === 4
//     );
//     // Si no existe unidad tipo paquete seguimos igual
//     if (!packageUnit) {
//         addToCart(product);
//         return;
//     }
//     selectedProduct.value = product;
//     selectedPackageUnit.value = packageUnit;
//     showUnitSelector.value = true;
// };

// const addAsPieceDirect = (product)=>{
//     const unit = product.product_units?.find(
//         u => u.sale_type_id === 1
//     );

//     if(!unit){
//         toast.add({
//             severity:'warn',
//             summary:'Producto sin unidad',
//             detail:'No tiene unidad de venta configurada',
//             life:3000
//         });
//         return;
//     }

//     currentTransaction.value.cart.push({
//         id: crypto.randomUUID(),
//         product_id:product.id,
//         product_unit_id:unit.id,
//         sale_type_id:unit.sale_type_id,
//         name:product.name,
//         quantity:1,
//         price:Number(unit.price),
//         tax:product.tax
//     });
// };

// const addAsPiece = () => {
//     showUnitSelector.value = false;
//     addToCart(selectedProduct.value);
// };

const addUnitToCart = (product, unit)=>{
    currentTransaction.value.cart.push({
        id: crypto.randomUUID(),
        product_id: product.id,
        product_variant_id: unit.product_variant_id ?? null,
        product_unit_id: unit.id,
        sale_type_id: unit.sale_type_id,
        sale_type: unit.sale_type,
        conversion_factor: unit.conversion_factor,
        name: product.name,
        quantity:1,
        price:Number(unit.price),
        tax:product.tax
    });
};

// const addAsPiece = ()=>{
//     const product = selectedProduct.value;

//     const unit = product.product_units.find(
//         u=>u.sale_type_id === 1
//     );

//     if(!unit){
//         toast.add({
//             severity:'warn',
//             summary:'Sin pieza',
//             detail:'Este producto no se vende por pieza',
//             life:3000
//         });
//         return;
//     }

//     addUnitToCart(product,unit);
//     showUnitSelector.value=false;
// };

// const addAsPackage = () => {
//     showUnitSelector.value = false;
//     addToCart(selectedProduct.value);
// };

// const addAsPackage = () => {
//     const product = selectedProduct.value;
//     const unit = product.product_units.find(
//         u => u.sale_type_id === 4
//     );

//     if(!unit){
//         toast.add({
//             severity:'warn',
//             summary:'Sin paquete',
//             detail:'Este producto no tiene presentación por paquete',
//             life:3000
//         });
//         return;
//     }

//     currentTransaction.value.cart.push({
//         id: crypto.randomUUID(),
//         product_id: product.id,
//         product_unit_id: unit.id,
//         sale_type_id: unit.sale_type_id,
//         name: product.name,
//         quantity:1,
//         price:Number(unit.price),
//         tax: product.tax
//     });

//     showUnitSelector.value=false;
// };

// --- ACCIONES DEL CARRITO ---
const addToCart = (product)=>{
    const defaultUnit = product.product_units?.find(
        u => Number(u.is_default) === 1
    );

    currentTransaction.value.cart.push({
        id: crypto.randomUUID(),
        product_id: product.id,
        product_variant_id:
            product.product_variant_id ?? null,
        product_unit_id:
            defaultUnit?.id ?? null,
        sale_type_id:
            defaultUnit?.sale_type_id ?? null,
        conversion_factor:
            defaultUnit?.conversion_factor ?? 1,
        name:
            product.name,
        quantity:1,
        price:
            Number(
                defaultUnit?.price ??
                product.price
            ),
        tax:
            product.tax
    });
};
// const addToCart = (product, unit = null) => {

//     const existingItem = cart.value.find(
//         item => item.id === product.id
//     );

//     if (existingItem) {

//         existingItem.quantity += 1;

//     } else {

//         currentTransaction.value.cart.push({
//             ...product,
//             price: Number(product.price),
//             quantity: 1
//         });

//     }
// };

const updateQuantity = (productId, change) => {
    const item = cart.value.find(i => i.id === productId);
    if (!item) return;

    item.quantity += change;

    if (item.quantity <= 0) {
        currentTransaction.value.cart =
            currentTransaction.value.cart.filter(
                i => i.id !== productId
            );
    }
};

const clearCart = () => {
    if (currentTransaction.value) {
        currentTransaction.value.cart = [];
    }
    paid.value = null;
};

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

watch(showQuickCreateModal, async (open) => {
    if (open) {
        await nextTick();
        document.querySelector('input')?.focus();
    }
});

// --- CICLO DE VIDA ---
onMounted(() => {
    window.addEventListener('keydown', handleBarcodeScanner);
    // Seleccionar por defecto "Cliente público general"
    const defaultCustomer = props.customers.find(c => c.id === 1);

    if (defaultCustomer) {
        selectedCustomer.value = defaultCustomer.id;
    }

    paymentMethod.value = defaultPaymentMethod.value;
});

onUnmounted(async () => {
    window.removeEventListener('keydown', handleBarcodeScanner);

    if (scalePort.value) {
        try {
            await scalePort.value.close();
        } catch (e) {
            console.error('Error al cerrar puerto de báscula:', e);
        }
    }
});

// --- FORMATEADOR MONETARIO ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

const roundPrice = (price) => {

    const value = Number(price);

    if (isNaN(value)) return 0;

    const integer = Math.floor(value);
    const decimal = Number((value - integer).toFixed(2));

    if (decimal === 0) {
        return integer;
    }

    if (decimal <= 0.50) {
        return integer + 0.50;
    }

    return integer + 1;
};

const suggestedTotal = computed(() => {
    return roundPrice(amountDue.value);
});

const hasSuggestedRounding = computed(() => {
    return Number(suggestedTotal.value) !== Number(amountDue.value);
});

// --- FINALIZAR Y REGISTRAR VENTA ---
const finishSale = async () => {

    if (!isRegisterOpen.value) {
        toast.add({
            severity: 'error',
            summary: 'Caja Cerrada',
            detail: 'Abre un turno para realizar ventas.',
            life: 3000
        });
        return;
    }

    if (!canProcessSale.value) {
        toast.add({
            severity: 'warn',
            summary: 'Pago inválido',
            detail: isGeneralPublic.value
                ? 'El cliente Público General debe liquidar el total de la compra.'
                : 'El cliente no cuenta con el crédito disponible para cubrir la diferencia.',
            life: 4000
        });
        return;
    }

    try {
        // 1. Guardar la copia de los datos actuales de la venta antes de vaciar
        const saleData = {
            items: cart.value.map(item => ({
                name:item.name,
                product_id: item.product_id,
                product_unit_id: item.product_unit_id,
                sale_type_id: item.sale_type_id,
                quantity: item.quantity,
                price: item.price,
                subtotal:item.price * item.quantity
            })),
            subtotal: subtotal.value,
            tax: totalTax.value,
            total: amountDue.value,
            paid: Number(paid.value),
            change: changeAmount.value,
            paymentMethod: paymentMethod.value
        };

        console.log(cart.value)

        await axios.post(route('pos.store'), {
            items: saleData.items,
            subtotal: saleData.subtotal,
            tax: saleData.tax,
            total: saleData.total,
            paid: saleData.paid,
            change: saleData.change,
            customer_id:selectedCustomer.value,
            payment_method_id:saleData.paymentMethod,
            credit_payment: creditUsed.value
        });

        const customer = props.customers.find(c=>c.id===selectedCustomer.value);

        if(customer){
            customer.current_balance =
                Number(customer.current_balance)
                +
                Number(missingAmount.value);
        }

        toast.add({
            severity: 'success',
            summary: 'Venta Registrada',
            detail: 'La venta se ha procesado con éxito.',
            life: 3000
        });

        // 2. Mandar a imprimir pasando la copia de los datos
        // Guardar ticket para poder reimprimir después
        lastSaleTicket.value = saleData;

        // Imprimir automáticamente después de liquidar
        printTicket(saleData);

        // 3. Limpiar la interfaz
        clearCart();
        paid.value = null;
        paymentMethod.value = defaultPaymentMethod.value;
        selectedCustomer.value = props.customers.find(c => c.id === 1)?.id ?? null;
        showPaymentDialog.value = false;

    } catch (error) {
        console.error("Error al registrar venta:", error);
        toast.add({
            severity: 'error',
            summary: 'Error en Venta',
            detail: error.response?.data?.message ?? 'No se pudo completar el registro de la venta.',
            life: 5000
        });
    }
};

const reprintTicket = () => {
    if (!lastSaleTicket.value) {
        return;
    }
    printTicket(lastSaleTicket.value);
};

// --- CÁLCULOS Y VALIDACIONES DE PAGO ---

// 1. Calcular el Cambio a entregar
const changeAmount = computed(() => {
    if (!paid.value || Number(paid.value) <= 0) return 0;
    const diff = Number(paid.value) - Number(amountDue.value);
    return diff > 0 ? diff : 0;
});

// 2. Calcular Faltante por pagar
const missingAmount = computed(() => {
    const payment = Number(paid.value ?? 0);
    const difference =
        Number(amountDue.value) - payment;
    return difference > 0
        ? difference
        : 0;
});

const excessPayment = computed(()=>{

    if(isGeneralPublic.value){
        return 0;
    }

    const payment = Number(paid.value ?? 0);

    const difference =
        payment - Number(amountDue.value);

    return difference > 0
        ? difference
        : 0;
});

// const excessPayment = computed(()=>{
//     const payment = Number(paid.value ?? 0);
//     const difference =
//         payment - Number(amountDue.value);
//     return difference > 0
//         ? difference
//         : 0;
// });

// 3. Obtener el objeto del cliente seleccionado
const selectedCustomerObj = computed(() => {
    return props.customers.find(c => c.id === selectedCustomer.value) ?? null;
});

const availableCredit = computed(()=>{
    if(!selectedCustomerObj.value)
        return 0;
    return Number(
        selectedCustomerObj.value.credit_limit
    )
    -
    Number(
        selectedCustomerObj.value.current_balance
    );
});

// 4. Validar si es Cliente Público General (asumiendo ID 1)
const isGeneralPublic = computed(() => {
    // return selectedCustomer.value === 1 || !selectedCustomerObj.value;
    return Number(selectedCustomerObj.value?.is_default) === 1;
    // return selectedCustomerObj.value?.is_default === true;
});

// 5. Validar si el cliente tiene crédito suficiente para cubrir el faltante
const hasEnoughCredit = computed(() => {

    if (isGeneralPublic.value) return false;

    const customer = selectedCustomerObj.value;

    if (!customer) return false;


    const availableCredit =
        Number(customer.credit_limit ?? 0)
        -
        Number(customer.current_balance ?? 0);


    return availableCredit >= missingAmount.value;

});

const creditUsed = computed(() => {
    if(
        !isGeneralPublic.value &&
        missingAmount.value > 0
    ){
        return Number(missingAmount.value);
    }
    return 0;
});

const creditExtraPayment = computed(()=>{
    if(
        !isGeneralPublic.value &&
        excessPayment.value > 0
    ){
        return Number(excessPayment.value);
    }
    return 0;
});

// 6. Determinar si se puede procesar la venta
const canProcessSale = computed(() => {
    if(cart.value.length === 0)
        return false;
    if(isGeneralPublic.value){
        return Number(paid.value ?? 0) >= Number(amountDue.value);
    }
    const payment = Number(paid.value ?? 0);
    // Cliente con crédito
    if(!isGeneralPublic.value){
        return true;
    }
    return payment >= amountDue.value;
});

const printTicket = (saleData) => {
    const { items, subtotal, tax, total, paid, change, paymentMethod, customer_name } = saleData;

    // Etiqueta legible del método de pago
    const methodLabel = props.paymentMethods.find(
        method => method.id === paymentMethod
    )?.name ?? 'Sin método';

    const html = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>Ticket de Venta</title>
        <style>
            @page { margin: 0; }
            body {
                font-family: 'Courier New', Courier, monospace;
                width: 78mm;
                margin: 0;
                padding: 8px;
                font-size: 12px;
                color: #000;
            }
            .center { text-align: center; }
            .right { text-align: right; }
            .bold { font-weight: bold; }
            h2 { margin: 0; font-size: 16px; }
            table { width: 100%; border-collapse: collapse; margin-top: 5px; }
            td { padding: 2px 0; vertical-align: top; }
            hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
            .total-row { font-size: 14px; font-weight: bold; }
        </style>
    </head>
    <body>
        <h2>NEXUSFLOW POS</h2>
        <div class="center">
            Ticket de Venta<br>
            ${new Date().toLocaleString()}
        </div>
        <hr>
        <table>
            <thead>
                <tr>
                    <td class="bold">Cant x Prod</td>
                    <td class="right bold">Total</td>
                </tr>
            </thead>
            <tbody>
                ${items.map(item => `
                    <tr>
                        <td colspan="2">${item.name}</td>
                    </tr>
                    <tr>
                        <td>${item.quantity} x ${formatCurrency(item.price)}</td>
                        <td class="right">${formatCurrency(item.price * item.quantity)}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
        <hr>
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right">${formatCurrency(subtotal)}</td>
            </tr>
            <tr>
                <td>IVA</td>
                <td class="right">${formatCurrency(tax)}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="right">${formatCurrency(total)}</td>
            </tr>
        </table>
        <hr>
        <table>
            <tr>
                <td>Método de Pago:</td>
                <td class="right">${methodLabel}</td>
            </tr>
            <tr>
                <td>Monto Recibido:</td>
                <td class="right">${formatCurrency(paid)}</td>
            </tr>
            <tr>
                <td class="bold">Cambio:</td>
                <td class="right bold">${formatCurrency(change)}</td>
            </tr>
        </table>
        <hr>
        <div class="center">
            ¡Gracias por su compra!
        </div>
    </body>
    </html>
    `;

    // Crear un iframe invisible para mandar a imprimir sin abrir pestañas nuevas
    let iframe = document.getElementById('print-iframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'print-iframe';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
    }

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();

    // Invocar la impresión de forma limpia
    setTimeout(() => {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 250);
};
</script>

<template>
    <Head title="Punto de Venta" />
    <AppLayout>
        <Toast />
        <!-- <pre>
        {{ props.customers }}
        </pre> -->
        <div
            v-if="!isRegisterOpen"
            class="mb-4 p-4 bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-between shadow-sm"
        >
            <div class="flex items-center gap-3 font-semibold text-sm">
                <i class="pi pi-exclamation-triangle text-xl text-amber-500"></i>
                <span>Atención: No hay una caja abierta actualmente. Debes abrir un turno para poder cobra y realizar ventas.</span>
            </div>
            <a
                href="/cash-registers"
                class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition-all shadow"
            >
                Abrir Turno
            </a>
        </div>
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
                            @click="selectProduct(product)"
                            class="group relative flex flex-col bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl overflow-hidden cursor-pointer hover:shadow-md transition-all duration-200"
                        >
                            <span class="absolute top-3 right-3 z-10 bg-black/70 backdrop-blur-md text-white px-2.5 py-1 rounded-lg text-xs font-bold">
                                {{ formatCurrency(roundPrice(product.price ?? 0)) }}
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
                                    <span :class="parseFloat(product.stock) <= parseFloat(product.stock_alert)
                                        ? 'text-red-500 font-bold'
                                        : 'text-slate-600 dark:text-slate-400'">

                                        {{ formatStock(product) }}
                                    </span>
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
                                            v-if="pos.currentTransactionId === transaction.id"
                                            class="absolute bottom-0 left-0 w-full h-[3px] bg-emerald-500 rounded-full"
                                        ></span>

                                    </button>

                                </div>

                            </template>
                        </draggable>

                        <!-- <TransitionGroup
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
                        </TransitionGroup> -->
                        <div
                            class="flex items-end gap-1"
                        >
                            <button
                                @click="pos.createTransaction"
                                class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 mb-1 ml-2 bg-slate-200 dark:bg-slate-800 hover:bg-emerald-600 hover:text-white transition"
                            >
                                +
                            </button>
                        </div>

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
                        <div class="flex items-center gap-2">
                            <!-- BOTÓN CONEXIÓN BÁSCULA -->
                            <button
                                type="button"
                                @click="connectScale"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm"
                                :class="scaleConnected
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300'
                                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200'"
                            >
                                <i class="pi pi-compass" :class="scaleConnected ? 'animate-spin' : ''"></i>
                                <span>{{ scaleConnected ? `${currentScaleWeight} kg` : 'Conectar Báscula' }}</span>
                            </button>

                            <button
                                v-if="cart.length"
                                @click="clearCart"
                                class="text-xs text-red-500 hover:text-red-600 font-semibold ml-2"
                            >
                                Vaciar
                            </button>
                        </div>

                        <!-- <button
                            v-if="cart.length"
                            @click="clearCart"
                            class="text-xs text-red-500 hover:text-red-600 font-semibold"
                        >
                            Vaciar
                        </button> -->

                    </div>

                    <Transition
                        name="fade-slide"
                        mode="out-in"
                    >
                        <div
                            :key="pos.currentTransactionId"
                            class="flex-1 flex flex-col min-h-0"
                        >

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
                                            {{ item.sale_type_id
                                                ? item.sale_type?.name
                                                : 'Unidad'
                                            }}
                                            |
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
                        class="border-t border-slate-200 dark:border-slate-800 p-5 space-y-4 bg-white dark:bg-slate-900 overflow-y-auto max-h-[35vh]"
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

                            <div
                                v-if="hasSuggestedRounding"
                                class="mt-3 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-700"
                            >
                                <div class="flex items-center gap-2 font-bold text-sm">
                                    <i class="pi pi-lightbulb"></i>
                                    Precio a cobrar sugerido
                                </div>
                                <div class="text-xl font-black mt-1">
                                    {{ formatCurrency(suggestedTotal) }}
                                </div>
                                <p class="text-xs mt-1">
                                    Es una recomendación para facilitar el cobro.
                                    La venta se registrará con el importe real.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">

                            <button
                                v-if="lastSaleTicket"
                                @click="reprintTicket"
                                class="rounded-xl border py-3 font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center justify-center gap-2 transition-all"
                            >
                                <i class="pi pi-print"></i>
                                <span>Reimprimir ticket</span>
                            </button>
                            <button class="rounded-xl border py-3 font-semibold hover:bg-slate-50">
                                <i class="pi pi-wallet"></i>
                                <span> Abrir cajón</span>
                            </button>
                        </div>
                    </div>
                    <div
                        class="shrink-0 p-5 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900"
                    >
                        <!-- <button
                            @click="finishSale"
                            :disabled="!canProcessSale"
                            class="w-full font-bold py-4 rounded-xl flex items-center justify-center gap-3 transition-all"
                            :class="canProcessSale
                                ? 'bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer shadow-md'
                                : 'bg-slate-300 dark:bg-slate-800 text-slate-500 cursor-not-allowed border border-slate-200 dark:border-slate-700'"
                        >
                            <i :class="canProcessSale ? 'pi pi-check-circle' : 'pi pi-lock'"></i>
                            <span>{{ canProcessSale ? 'Finalizar venta' : 'Completar datos de pago' }}</span>
                        </button> -->
                        <button
                            @click="openPaymentDialog"
                            :disabled="cart.length === 0 || !isRegisterOpen"
                            class="w-full font-bold py-4 rounded-xl flex items-center justify-center gap-3 transition-all"
                            :class="cart.length > 0 && isRegisterOpen
                                ? 'bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer shadow-md'
                                : 'bg-slate-300 dark:bg-slate-800 text-slate-500 cursor-not-allowed border border-slate-200 dark:border-slate-700'"
                        >
                            <i
                                :class="cart.length > 0 && isRegisterOpen
                                    ? 'pi pi-dollar'
                                    : 'pi pi-lock'"
                            ></i>

                            <span>
                                Finalizar venta
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- ===================================================== -->
        <!-- DIALOG DE COBRO -->
        <!-- ===================================================== -->

        <Dialog
            v-model:visible="showPaymentDialog"
            modal
            header="Cobrar venta"
            :style="{ width: '500px' }"
            :breakpoints="{
                '960px': '70vw',
                '640px': '90vw'
            }"
            :draggable="false"
        >
            <div class="space-y-5">

                <!-- ===================================== -->
                <!-- RESUMEN DE VENTA -->
                <!-- ===================================== -->

                <div
                    class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
                >
                    <div class="flex justify-between items-center">

                        <div>
                            <p class="text-xs text-slate-500">
                                Total de la venta
                            </p>

                            <p class="text-3xl font-black text-emerald-600">
                                {{ formatCurrency(amountDue) }}
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="text-xs text-slate-500">
                                Artículos
                            </p>

                            <p class="font-bold">
                                {{ totalItemsInCart }}
                            </p>
                        </div>

                    </div>
                </div>


                <!-- ===================================== -->
                <!-- CLIENTE -->
                <!-- ===================================== -->

                <div>
                    <label class="text-sm font-semibold block mb-2">
                        Cliente
                    </label>

                    <Select
                        v-model="selectedCustomer"
                        :options="props.customers"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Cliente público general"
                        class="w-full"
                        showClear
                    />
                </div>


                <!-- ===================================== -->
                <!-- CRÉDITO DISPONIBLE -->
                <!-- ===================================== -->

                <div
                    v-if="!isGeneralPublic"
                    class="p-3 rounded-xl"
                >
                    <Message
                        severity="secondary"
                        :closable="false"
                    >
                        Crédito disponible:

                        <strong>
                            {{ formatCurrency(availableCredit) }}
                        </strong>
                    </Message>
                </div>


                <!-- ===================================== -->
                <!-- MÉTODO DE PAGO -->
                <!-- ===================================== -->

                <div>
                    <label class="text-sm font-semibold block mb-2">
                        Método de pago
                    </label>

                    <Select
                        v-model="paymentMethod"
                        :options="props.paymentMethods"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Seleccionar método de pago"
                        class="w-full"
                    />
                </div>


                <!-- ===================================== -->
                <!-- PAGO RECIBIDO -->
                <!-- ===================================== -->

                <div>
                    <label class="text-sm font-semibold block mb-2">
                        Pago recibido
                    </label>

                    <InputNumber
                        v-model="paid"
                        mode="decimal"
                        :min="0"
                        :minFractionDigits="2"
                        :maxFractionDigits="2"
                        :updateOnInput="true"
                        class="w-full"
                        placeholder="0.00"
                        :disabled="!isCashPayment"
                    />

                    <small
                        v-if="!isCashPayment"
                        class="text-slate-500"
                    >
                        Para este método el pago se considera por el total de la venta.
                    </small>
                </div>


                <!-- ===================================== -->
                <!-- DESGLOSE DEL PAGO -->
                <!-- ===================================== -->

                <div
                    v-if="paid !== null && paid !== '' && Number(paid) > 0"
                    class="p-4 rounded-2xl border transition-all"
                    :class="{
                        'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800':
                            Number(paid) >= Number(amountDue),

                        'bg-amber-50 border-amber-200 dark:bg-amber-950/30 dark:border-amber-800':
                            Number(paid) < Number(amountDue) && isGeneralPublic,

                        'bg-blue-50 border-blue-200 dark:bg-blue-950/30 dark:border-blue-800':
                            Number(paid) < Number(amountDue) &&
                            !isGeneralPublic &&
                            hasEnoughCredit,

                        'bg-red-50 border-red-200 dark:bg-red-950/30 dark:border-red-800':
                            Number(paid) < Number(amountDue) &&
                            !isGeneralPublic &&
                            !hasEnoughCredit
                    }"
                >

                    <!-- ================================= -->
                    <!-- PAGO COMPLETO -->
                    <!-- ================================= -->

                    <div
                        v-if="Number(paid) >= Number(amountDue)"
                        class="space-y-2 text-sm"
                    >

                        <div class="flex justify-between">
                            <span>Total:</span>

                            <strong>
                                {{ formatCurrency(amountDue) }}
                            </strong>
                        </div>

                        <div class="flex justify-between">
                            <span>Recibido:</span>

                            <strong>
                                {{ formatCurrency(paid) }}
                            </strong>
                        </div>


                        <!-- PÚBLICO GENERAL -->

                        <template v-if="isGeneralPublic">

                            <div
                                class="flex justify-between border-t pt-2"
                            >
                                <span class="font-bold">
                                    Cambio:
                                </span>

                                <strong class="text-emerald-600 text-lg">
                                    {{ formatCurrency(changeAmount) }}
                                </strong>
                            </div>

                        </template>


                        <!-- CLIENTE REGISTRADO -->

                        <template v-else>

                            <div
                                v-if="excessPayment > 0"
                                class="p-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700"
                            >
                                <i class="pi pi-info-circle mr-1"></i>

                                El cliente entregó
                                <strong>
                                    {{ formatCurrency(excessPayment) }}
                                </strong>
                                adicionales.

                                <div class="text-xs mt-1">
                                    Este importe actualmente se considera
                                    como abono a crédito.
                                </div>
                            </div>

                            <div
                                class="flex justify-between border-t pt-2"
                            >
                                <span class="font-bold">
                                    Cambio:
                                </span>

                                <strong class="text-emerald-600 text-lg">
                                    {{ formatCurrency(changeAmount) }}
                                </strong>
                            </div>

                        </template>

                    </div>


                    <!-- ================================= -->
                    <!-- PÚBLICO GENERAL - PAGO INCOMPLETO -->
                    <!-- ================================= -->

                    <div
                        v-else-if="isGeneralPublic"
                        class="text-sm text-amber-700 dark:text-amber-400 space-y-2"
                    >

                        <div class="flex items-center gap-2 font-bold">
                            <i class="pi pi-exclamation-triangle"></i>

                            <span>
                                Pago incompleto
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span>Total:</span>

                            <strong>
                                {{ formatCurrency(amountDue) }}
                            </strong>
                        </div>

                        <div class="flex justify-between">
                            <span>Recibido:</span>

                            <strong>
                                {{ formatCurrency(paid) }}
                            </strong>
                        </div>

                        <div class="flex justify-between">
                            <span>Faltante:</span>

                            <strong class="text-red-600">
                                {{ formatCurrency(missingAmount) }}
                            </strong>
                        </div>

                        <p class="text-xs">
                            El cliente Público General debe liquidar
                            el total de la compra.
                        </p>

                    </div>


                    <!-- ================================= -->
                    <!-- CLIENTE CON CRÉDITO -->
                    <!-- ================================= -->

                    <div
                        v-else-if="hasEnoughCredit"
                        class="text-sm text-blue-700 dark:text-blue-400 space-y-2"
                    >

                        <div class="flex items-center gap-2 font-bold">
                            <i class="pi pi-credit-card"></i>

                            <span>
                                Venta a crédito
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span>Total:</span>

                            <strong>
                                {{ formatCurrency(amountDue) }}
                            </strong>
                        </div>

                        <div class="flex justify-between">
                            <span>Recibido:</span>

                            <strong>
                                {{ formatCurrency(paid) }}
                            </strong>
                        </div>

                        <div class="flex justify-between border-t pt-2">
                            <span>
                                Se cargará a crédito:
                            </span>

                            <strong class="text-blue-700">
                                {{ formatCurrency(missingAmount) }}
                            </strong>
                        </div>

                    </div>


                    <!-- ================================= -->
                    <!-- CRÉDITO INSUFICIENTE -->
                    <!-- ================================= -->

                    <div
                        v-else
                        class="text-sm text-red-700 dark:text-red-400 space-y-2"
                    >

                        <div class="flex items-center gap-2 font-bold">
                            <i class="pi pi-times-circle"></i>

                            <span>
                                Crédito insuficiente
                            </span>
                        </div>

                        <div class="flex justify-between">
                            <span>Total:</span>

                            <strong>
                                {{ formatCurrency(amountDue) }}
                            </strong>
                        </div>

                        <div class="flex justify-between">
                            <span>Recibido:</span>

                            <strong>
                                {{ formatCurrency(paid) }}
                            </strong>
                        </div>

                        <div class="flex justify-between">
                            <span>Faltante:</span>

                            <strong class="text-red-600">
                                {{ formatCurrency(missingAmount) }}
                            </strong>
                        </div>

                        <p class="text-xs">
                            El cliente no tiene suficiente crédito
                            disponible para cubrir la diferencia.
                        </p>

                    </div>

                </div>

            </div>


            <!-- ===================================== -->
            <!-- FOOTER -->
            <!-- ===================================== -->

            <template #footer>

                <div class="flex justify-between gap-3 w-full">

                    <Button
                        label="Cancelar"
                        severity="secondary"
                        outlined
                        @click="showPaymentDialog = false"
                    />

                    <Button
                        :label="canProcessSale
                            ? 'Confirmar venta'
                            : 'Completar pago'"
                        :icon="canProcessSale
                            ? 'pi pi-check'
                            : 'pi pi-lock'"
                        :disabled="!canProcessSale"
                        :severity="canProcessSale
                            ? 'success'
                            : 'secondary'"
                        @click="finishSale"
                    />

                </div>

            </template>

        </Dialog>
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
        <Dialog
            v-model:visible="showUnitSelector"
            modal
            header="Seleccionar tipo de venta"
            :style="{ width: '420px' }"
        >
            <div
                v-if="selectedProduct"
                class="space-y-5"
            >

                <div class="text-center">
                    <h2 class="text-xl font-bold">
                        {{ selectedProduct.name }}
                    </h2>
                    <p class="text-slate-500 text-sm mt-2">
                        Selecciona la presentación de venta
                    </p>
                </div>

                <div class="grid gap-3">
                    <Button
                        v-for="unit in selectedProduct.product_units"
                        :key="unit.id"
                        class="w-full flex justify-between items-center"
                        outlined
                        @click="addSelectedUnit(unit)"
                    >
                        <span class="flex items-center gap-2">
                            <i
                                :class="{
                                    'pi pi-box':
                                        unit.sale_type_id === 1,
                                    'pi pi-weight':
                                        unit.sale_type_id === 2,
                                    'pi pi-arrows-h':
                                        unit.sale_type_id === 3,
                                    'pi pi-shopping-bag':
                                        unit.sale_type_id === 4
                                }"
                            ></i>

                            {{ unit.sale_type?.name }}
                        </span>
                        <span class="font-bold">
                            {{ formatCurrency(unit.price) }}
                        </span>
                    </Button>
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
