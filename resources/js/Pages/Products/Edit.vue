<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import FloatLabel from 'primevue/floatlabel';
import Button from 'primevue/button';
import Select from 'primevue/select';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const toast = useToast();
const page = usePage();

const props = defineProps({
    categories: Array,
    taxes: Array,
    product: Object,
    inventories: Array,
    saleTypes: Array,
});

// STOCK TOTAL
const totalStock = computed(() => {
    return props.product.inventories?.reduce((sum, item) => {
        return sum + Number(item.stock || 0)
    }, 0) ?? 0;
});

// UNIDADES DE VENTA INICIALES
const productUnits = props.product.product_units?.map(unit => ({
    id: unit.id,
    sale_type_id: unit.sale_type_id,
    conversion_factor: Number(unit.conversion_factor ?? 1),
    price: Number(unit.price ?? 0),
    is_default: Boolean(unit.is_default),
})) ?? [];

// FORMULARIO
const productForm = useForm({
    category_id: props.product.category_id ?? null,
    tax_id: props.product.tax_id ?? null,
    sku: props.product.sku ?? '',
    barcode: props.product.barcode ?? '',
    name: props.product.name ?? '',
    slug: props.product.slug ?? '',
    description: props.product.description ?? '',
    image: props.product.image ?? '',
    cost: props.product.cost ?? 0,
    price: props.product.price ?? 0,
    wholesale_price: props.product.wholesale_price ?? 0,
    stock: totalStock.value,
    add_stock: 0,

    stock_alert: props.product.stock_alert
        ? parseInt(props.product.stock_alert,10)
        : 0,

    track_stock: props.product.track_stock ?? true,
    has_variants: props.product.has_variants ?? false,
    status: props.product.status ?? true,
    product_units: productUnits,
    deleted_units:[]
});

// ============================================================
// CONFIGURACIÓN DE UNIDADES DE VENTA
// ============================================================

// Obtener información del tipo de venta
const getSaleType = (saleTypeId) => {
    return props.saleTypes.find(type => type.id === saleTypeId);
};

// Texto explicativo según el tipo de venta
const getConversionLabel = (saleTypeId) => {

    switch (saleTypeId) {

        // PIEZA
        case 1:
            return '1 unidad física del producto';

        // KILOGRAMO
        case 2:
            return 'Cantidad en kilogramos';

        // METRO
        case 3:
            return 'Cantidad en metros';

        // PAQUETE
        case 4:
            return 'Cantidad de piezas que contiene el paquete';

        default:
            return '';
    }
};


// ============================================================
// TIPOS DE VENTA DISPONIBLES
// ============================================================
const availableSaleTypes = computed(() => {
    return props.saleTypes.filter(type => {
        return !productForm.product_units.some(
            unit => unit.sale_type_id === type.id
        );
    });
});


// ============================================================
// ¿TIENE PIEZA?
// ============================================================
const hasPieceUnit = computed(() => {
    return productForm.product_units.some(
        unit => unit.sale_type_id === 1
    );
});


// ============================================================
// ¿TIENE PAQUETE?
// ============================================================
const hasPackageUnit = computed(() => {
    return productForm.product_units.some(
        unit => unit.sale_type_id === 4
    );
});

// ============================================================
// CAMBIO DE TIPO DE VENTA
// ============================================================

const saleTypeChanged = (unit) => {

    switch (unit.sale_type_id) {

        // PIEZA
        case 1:
            unit.conversion_factor = 1;

            // Si existe paquete, Pieza siempre es la unidad base
            if (hasPackageUnit.value) {
                setAsBasePiece(unit);
            }

            break;

        // KILOGRAMO
        case 2:
            unit.conversion_factor = 1;
            break;

        // METRO
        case 3:
            unit.conversion_factor = 1;
            break;

        // PAQUETE
        case 4:
            if (!unit.conversion_factor || unit.conversion_factor < 2) {
                unit.conversion_factor = null;
            }
            break;
    }
};


// ============================================================
// ESTABLECER PIEZA COMO UNIDAD BASE
// ============================================================
const setAsBasePiece = (pieceUnit) => {
    productForm.product_units.forEach(unit => {
        if (unit.sale_type_id === 1) {
            unit.is_default = true;
        } else {
            unit.is_default = false;
        }
    });
    productForm.price = pieceUnit.price;
};

const addProductUnit = () => {

    // ========================================================
    // 1. SI NO EXISTE PIEZA, AGREGAMOS PIEZA PRIMERO
    // ========================================================

    if (!hasPieceUnit.value) {

        const pieceType = props.saleTypes.find(
            type => type.id === 1
        );

        if (!pieceType) {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'El tipo de venta Pieza no está configurado.',
                life: 4000
            });

            return;
        }

        const newUnit = {
            id: null,
            sale_type_id: 1,
            conversion_factor: 1,
            price: 0,
            is_default: true
        };

        productForm.product_units.push(newUnit);

        productForm.price = newUnit.price;

        return;
    }


    // ========================================================
    // 2. SI EXISTE PIEZA PERO NO PAQUETE,
    //    AGREGAMOS PAQUETE
    // ========================================================

    if (!hasPackageUnit.value) {

        const packageType = props.saleTypes.find(
            type => type.id === 4
        );

        if (packageType) {

            const newUnit = {
                id: null,
                sale_type_id: 1,

                // Valor inicial.
                // El usuario puede cambiarlo.
                conversion_factor: 1,

                price: 0,

                is_default: false
            };

            productForm.product_units.push(newUnit);

            return;
        }
    }


    // ========================================================
    // 3. SI YA EXISTEN PIEZA Y PAQUETE,
    //    BUSCAMOS OTRA UNIDAD DISPONIBLE
    // ========================================================

    if (!availableSaleTypes.value.length) {

        toast.add({
            severity: 'warn',
            summary: 'Aviso',
            detail: 'Ya agregaste todos los tipos de venta disponibles.',
            life: 3000
        });

        return;
    }


    const nextType = availableSaleTypes.value[0];


    // ========================================================
    // 4. CREAR NUEVA UNIDAD
    // ========================================================

    const newUnit = {

        id: null,

        sale_type_id: nextType.id,

        conversion_factor: 1,

        price: 0,

        is_default: false

    };


    productForm.product_units.push(newUnit);

};

const removeProductUnit = (index) => {

    const unit = productForm.product_units[index];

    // ========================================================
    // NO PERMITIR ELIMINAR PIEZA SI EXISTE PAQUETE
    // ========================================================

    if (
        unit.sale_type_id === 1 &&
        hasPackageUnit.value
    ) {

        toast.add({
            severity: 'warn',
            summary: 'No se puede eliminar',
            detail: 'No puedes eliminar la unidad Pieza mientras exista una unidad Paquete. El paquete depende de la pieza como unidad base.',
            life: 4000
        });

        return;
    }


    // ========================================================
    // GUARDAR ID PARA ELIMINAR DESDE LARAVEL
    // ========================================================

    if (unit.id) {
        productForm.deleted_units.push(unit.id);
    }


    // ========================================================
    // ELIMINAR UNIDAD
    // ========================================================

    productForm.product_units.splice(index, 1);


    // ========================================================
    // ASEGURAR QUE EXISTA UNA UNIDAD BASE
    // ========================================================

    if (
        !productForm.product_units.some(
            unit => unit.is_default
        )
        &&
        productForm.product_units.length > 0
    ) {

        productForm.product_units[0].is_default = true;

        productForm.price =
            productForm.product_units[0].price;
    }

};

const getAvailableOptionsForUnit = (currentUnit) => {

    return props.saleTypes.filter(type => {

        // No permitir duplicados
        const alreadyUsed = productForm.product_units.some(unit => {

            return unit !== currentUnit &&
                   unit.sale_type_id === type.id;

        });

        if (alreadyUsed) {
            return false;
        }


        // ------------------------------------------------
        // PAQUETE
        // ------------------------------------------------
        //
        // Un paquete en este sistema representa
        // un conjunto de piezas.
        //
        // Por eso necesita existir Pieza.
        //
        if (type.id === 4) {

            const hasPiece = productForm.product_units.some(unit => {

                return unit !== currentUnit &&
                       unit.sale_type_id === 1;

            });

            // Si estamos editando una unidad que actualmente
            // es Pieza, también debemos permitir que conserve
            // su configuración.
            if (
                currentUnit.sale_type_id === 1 &&
                !hasPiece
            ) {
                return false;
            }

            if (!hasPiece) {
                return false;
            }
        }


        return true;

    });

};

const setDefaultUnit = (index) => {

    const selectedUnit = productForm.product_units[index];

    // ========================================================
    // SI EXISTE PIEZA, PIEZA SIEMPRE ES LA UNIDAD BASE
    // ========================================================

    if (
        hasPieceUnit.value &&
        selectedUnit.sale_type_id !== 1
    ) {

        const pieceIndex = productForm.product_units.findIndex(
            unit => unit.sale_type_id === 1
        );

        productForm.product_units.forEach((unit, i) => {
            unit.is_default = i === pieceIndex;
        });

        const pieceUnit = productForm.product_units[pieceIndex];

        if (pieceUnit) {
            productForm.price = pieceUnit.price;
        }

        toast.add({
            severity: 'info',
            summary: 'Unidad base',
            detail: 'Cuando el producto se vende por pieza y paquete, la pieza es la unidad base.',
            life: 3500
        });

        return;
    }


    // ========================================================
    // COMPORTAMIENTO NORMAL
    // ========================================================

    productForm.product_units.forEach((unit, i) => {
        unit.is_default = i === index;
    });


    const defaultUnit = productForm.product_units[index];

    if (defaultUnit) {

        productForm.price = defaultUnit.price;

    }

};

// CAMBIO DE PRECIO DE UNIDAD PRINCIPAL
const updateUnitPrice = (index)=>{
    const unit = productForm.product_units[index];
    if(unit.is_default){
        productForm.price = unit.price;
    }
};

// GUARDAR
const saveProduct = () => {
    // ========================================================
    // VALIDAR PAQUETES
    // ========================================================

    const invalidPackage = productForm.product_units.some(unit =>
        unit.sale_type_id === 4 &&
        (
            unit.conversion_factor === null ||
            unit.conversion_factor === undefined ||
            Number(unit.conversion_factor) < 2
        )
    );

    if (invalidPackage) {

        toast.add({
            severity: 'warn',
            summary: 'Paquete incompleto',
            detail: 'Debes indicar cuántas piezas contiene cada paquete.',
            life: 4000
        });

        return;
    }


    // ========================================================
    // VALIDAR PIEZA + PAQUETE
    // ========================================================

    if (
        hasPackageUnit.value &&
        !hasPieceUnit.value
    ) {

        toast.add({
            severity: 'error',
            summary: 'Configuración inválida',
            detail: 'Una unidad Paquete requiere una unidad Pieza como unidad base.',
            life: 4000
        });

        return;
    }
    
    productForm.put(route('products.update', props.product.id), {
        preserveScroll:true,
        onSuccess:()=>{
            productForm.add_stock = 0;
            productForm.stock = totalStock.value;
            const successMessage =
                page.props.flash?.success ??
                'Producto actualizado correctamente.';

            toast.add({
                severity:'success',
                summary:'Éxito',
                detail:successMessage,
                life:3000
            });
        },
        onError:(errors)=>{
            console.log(errors);
            toast.add({
                severity:'error',
                summary:'Error de validación',
                detail:'Revisa los campos marcados.',
                life:4000
            });
        }
    });
};

// REGRESAR
const goToIndex = ()=>{
    router.get(route('products.index'));
};
</script>

<template>
    <AppLayout>
        <Toast/>
        <!-- <pre>
            {{ props.product }}
        </pre> -->
        <div class="flex flex-col min-h-screen p-6">
            <!-- HEADER -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100">
                    Editar Producto
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Actualiza la información del producto.
                </p>
            </div>

            <div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm p-8">
                <form @submit.prevent="saveProduct">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-8 gap-y-6">
                        <!-- NOMBRE -->
                        <div>
                            <FloatLabel variant="on">
                                <InputText
                                    id="name"
                                    v-model="productForm.name"
                                    class="w-full"
                                />
                                <label for="name">
                                    Nombre
                                </label>
                            </FloatLabel>
                            <small v-if="productForm.errors.name"
                            class="text-red-500 block mt-1">
                                {{ productForm.errors.name }}
                            </small>
                        </div>

                        <!-- SKU -->
                        <div>
                            <FloatLabel variant="on">
                                <InputText
                                    id="sku"
                                    v-model="productForm.sku"
                                    class="w-full"
                                />
                                <label for="sku">
                                SKU
                                </label>
                            </FloatLabel>
                            <small v-if="productForm.errors.sku"
                            class="text-red-500 block mt-1">
                                {{ productForm.errors.sku }}
                            </small>
                        </div>

                        <!-- BARCODE -->
                        <div>
                            <FloatLabel variant="on">
                                <InputText
                                    id="barcode"
                                    v-model="productForm.barcode"
                                    class="w-full"
                                />
                                <label for="barcode">
                                Código de barras
                                </label>
                            </FloatLabel>
                        </div>

                        <!-- SLUG -->
                        <div>
                            <FloatLabel variant="on">
                                <InputText
                                    id="slug"
                                    v-model="productForm.slug"
                                    class="w-full"
                                />
                                <label for="slug">
                                Slug
                                </label>
                            </FloatLabel>
                            <small v-if="productForm.errors.slug"
                            class="text-red-500 block mt-1">
                                {{ productForm.errors.slug }}
                            </small>
                        </div>

                        <!-- CATEGORIA -->

                        <div>
                            <FloatLabel variant="on">
                                <Select
                                    id="category"
                                    v-model="productForm.category_id"
                                    :options="categories"
                                    optionLabel="name"
                                    optionValue="id"
                                    filter
                                    fluid
                                />
                                <label for="category">
                                Categoría
                                </label>
                            </FloatLabel>
                        </div>

                        <!-- IMPUESTO -->
                        <div>
                            <FloatLabel variant="on">
                                <Select
                                    id="tax"
                                    v-model="productForm.tax_id"
                                    :options="taxes"
                                    optionLabel="name"
                                    optionValue="id"
                                    filter
                                    fluid
                                >
                                    <template #option="slotProps">
                                        <div class="flex justify-between w-full">
                                            <span>
                                            {{ slotProps.option.name }}
                                            </span>
                                            <span class="text-sm text-slate-500">
                                            {{ slotProps.option.rate }}%
                                            </span>
                                        </div>

                                    </template>
                                </Select>
                                <label for="tax">
                                    Impuesto
                                </label>
                            </FloatLabel>
                        </div>

                        <!-- COSTO -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="cost"
                                    v-model="productForm.cost"
                                    mode="currency"
                                    currency="MXN"
                                    locale="es-MX"
                                    class="w-full"
                                />
                                <label for="cost">
                                Costo
                                </label>
                            </FloatLabel>
                        </div>

                        <!-- PRECIO BASE -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="price"
                                    v-model="productForm.price"
                                    mode="currency"
                                    currency="MXN"
                                    locale="es-MX"
                                    class="w-full"
                                />
                                <label for="price">
                                    Precio base
                                </label>
                            </FloatLabel>
                            <small class="text-slate-400">
                                Se sincroniza con la unidad predeterminada.
                            </small>
                        </div>

                        <!-- PRECIO MAYOREO -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="wholesale"
                                    v-model="productForm.wholesale_price"
                                    mode="currency"
                                    currency="MXN"
                                    locale="es-MX"
                                    class="w-full"
                                />
                                <label for="wholesale">
                                    Precio mayoreo
                                </label>
                            </FloatLabel>
                        </div>

                        <!-- STOCK -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="stock"
                                    v-model="productForm.stock"
                                    disabled
                                    class="w-full"
                                />
                                <label for="stock">
                                    Stock actual
                                </label>
                            </FloatLabel>
                        </div>

                        <!-- AJUSTE STOCK -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="add_stock"
                                    v-model="productForm.add_stock"
                                    :min="0"
                                    showButtons
                                    class="w-full"
                                />
                                <label for="add_stock">
                                    Ingresar cantidad
                                </label>
                            </FloatLabel>
                        </div>

                        <!-- STOCK ALERTA -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="stock_alert"
                                    v-model="productForm.stock_alert"
                                    :min="1"
                                    class="w-full"
                                />
                                <label for="stock_alert">
                                    Stock alerta
                                </label>
                            </FloatLabel>
                        </div>

                        <!-- IMAGEN -->
                        <div>
                            <FloatLabel variant="on">
                                <InputText
                                    id="image"
                                    v-model="productForm.image"
                                    class="w-full"
                                />
                                <label for="image">
                                    URL Imagen
                                </label>
                            </FloatLabel>
                        </div>
                    </div>

                    <!-- ============================= -->
                    <!-- CONFIGURACION DE VENTA -->
                    <!-- ============================= -->

                    <div class="mt-12">
                        <div class="flex justify-between items-center mb-5">
                            <div>
                                <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">
                                    Configuración de venta
                                </h2>
                                <p class="text-sm text-slate-500 mt-1">
                                    Configura las diferentes formas en las que este producto puede venderse.
                                </p>
                            </div>

                            <Button
                                type="button"
                                label="Agregar unidad"
                                icon="pi pi-plus"
                                size="small"
                                @click="addProductUnit"
                            />
                        </div>

                        <div
                            v-if="productForm.product_units.length"
                            class="border rounded-xl overflow-hidden"
                        >
                            <table class="w-full">

                                <thead class="bg-slate-100 dark:bg-slate-800">
                                    <tr>
                                        <th class="p-3 text-left w-72">
                                            Tipo de venta
                                        </th>
                                        <th class="p-3 text-center">
                                            Equivalencia
                                        </th>
                                        <th class="p-3 text-center">
                                            Precio de venta
                                        </th>
                                        <th class="p-3 text-center">
                                            Unidad base
                                        </th>
                                        <th class="p-3 w-16"></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(unit,index) in productForm.product_units"
                                        :key="index"
                                        class="border-t"
                                    >

                                        <!-- Tipo -->
                                        <td class="p-3 w-72">
                                            <Select
                                                v-model="unit.sale_type_id"
                                                :options="getAvailableOptionsForUnit(unit)"
                                                optionLabel="name"
                                                optionValue="id"
                                                fluid
                                                @change="saleTypeChanged(unit)"
                                            />
                                        </td>

                                        <!-- Cantidad -->
                                        <td class="p-3">
                                            <InputNumber
                                                v-model="unit.conversion_factor"
                                                class="w-full"
                                                :min="unit.sale_type_id === 4 ? 2 : 1"
                                                :useGrouping="false"
                                            />
                                            <small class="block mt-2 text-slate-500 dark:text-slate-400">
                                                {{ getConversionLabel(unit.sale_type_id) }}
                                            </small>
                                        </td>

                                        <!-- Precio -->
                                        <td class="p-3">
                                            <InputNumber
                                                v-model="unit.price"
                                                mode="currency"
                                                currency="MXN"
                                                locale="es-MX"
                                                class="w-full"
                                                @input="updateUnitPrice(index)"
                                            />
                                        </td>

                                        <!-- Principal -->
                                        <td class="text-center">
                                            <Checkbox
                                                :binary="true"
                                                :modelValue="unit.is_default"
                                                @update:modelValue="setDefaultUnit(index)"
                                            />
                                        </td>

                                        <!-- Eliminar -->
                                        <td class="text-center">
                                            <Button
                                                type="button"
                                                icon="pi pi-trash"
                                                severity="danger"
                                                text
                                                @click="removeProductUnit(index)"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-else
                            class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center text-slate-500"
                        >
                            Este producto todavía no tiene unidades de venta configuradas.
                        </div>
                    </div>

                    <!-- DESCRIPCION -->
                    <div class="mt-10">
                        <FloatLabel variant="on">
                            <Textarea
                                id="description"
                                v-model="productForm.description"
                                rows="5"
                                class="w-full"
                            />
                            <label for="description">
                                Descripción
                            </label>
                        </FloatLabel>
                    </div>

                    <!-- PAQUETES ABIERTOS -->
                    <div
                        v-if="product.package_openings?.length"
                        class="mt-12"
                    >
                        <h2 class="text-xl font-bold mb-4">
                            Paquetes abiertos
                        </h2>
                        <table class="w-full border rounded-xl">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="p-3 text-left">
                                        Fecha
                                    </th>
                                    <th class="p-3">
                                        Paquetes
                                    </th>
                                    <th class="p-3">
                                        Piezas iniciales
                                    </th>
                                    <th class="p-3">
                                        Restantes
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="opening in product.package_openings"
                                    :key="opening.id"
                                    class="border-t"
                                >
                                    <td class="p-3">
                                        {{ new Date(opening.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="text-center">
                                        {{ opening.package_quantity }}
                                    </td>
                                    <td class="text-center">
                                        {{ opening.pieces_per_package }}
                                    </td>
                                    <td class="text-center">
                                        {{ opening.remaining_pieces }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- CHECKBOXES -->
                    <div class="mt-10 flex flex-wrap gap-8">
                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.track_stock"
                                binary
                            />
                                <label>
                                    Controlar inventario
                                </label>
                        </div>
                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.has_variants"
                                binary
                            />
                            <label>
                            Tiene variantes
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.status"
                                binary
                            />
                            <label>
                                Activo
                            </label>
                        </div>
                    </div>

                    <!-- BOTONES -->
                    <div class="mt-12 flex justify-end gap-4">
                        <Button
                            type="button"
                            label="Cancelar"
                            severity="secondary"
                            outlined
                            icon="pi pi-arrow-left"
                            @click="goToIndex"
                        />

                        <Button
                            type="submit"
                            label="Guardar cambios"
                            icon="pi pi-save"
                            :loading="productForm.processing"
                            :disabled="productForm.processing"
                        />
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
