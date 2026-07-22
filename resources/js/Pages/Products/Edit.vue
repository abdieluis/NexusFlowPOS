<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import FloatLabel from 'primevue/floatlabel';
import Button from 'primevue/button';
import Select from 'primevue/select';
import { useToast } from 'primevue/usetoast';
import { useForm, usePage, router } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { computed } from 'vue';

const toast = useToast();
const page = usePage();

const props = defineProps({
    categories: Array,
    taxes: Array,
    product: Object,
    inventories: Array,
});

const totalStock = computed(() => {
    return props.product.inventories?.reduce((sum, item) => {
        return sum + Number(item.stock || 0)
    }, 0) ?? 0;
});

// FORM PRELLENADO CON PRODUCTO
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

    stock: totalStock ?? 0,
    add_stock: 0,

    stock_alert: props.product.stock_alert ? parseInt(props.product.stock_alert, 10) : 0,

    track_stock: props.product.track_stock ?? true,
    has_variants: props.product.has_variants ?? false,
    status: props.product.status ?? true,
});

const saveProduct = () => {
    productForm.put(route('products.update', props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            productForm.add_stock = 0;
            productForm.stock = totalStock.value;
            const successMessage = page.props.flash?.success || 'Producto e inventario actualizados correctamente.';

            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: successMessage,
                life: 3000
            });
        },
        onError: (errors) => {
            console.log(errors);

            toast.add({
                severity: 'error',
                summary: 'Error de validación',
                detail: 'Por favor, revisa los campos marcados en rojo.',
                life: 4000
            });
        }
    });
};

const goToIndex = () => {
    router.get(route('products.index'));
};
</script>

<template>
        <AppLayout>
        <Toast/>
        <div class="flex flex-col min-h-screen p-6">
            <!-- <pre>
                {{ props }}
            </pre> -->

            <!-- HEADER -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-slate-800 dark:text-slate-100">
                    Editar Producto
                </h1>
                <p class="text-slate-500 dark:text-slate-400 mt-2">
                    Actualiza la información del producto.
                </p>
            </div>

            <!-- CARD -->
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
                                <label for="name">Nombre</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.name" class="text-red-500 block mt-1">
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
                                <label for="sku">SKU</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.sku" class="text-red-500 block mt-1">
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
                                    autofocus
                                />
                                <label for="barcode">Código de barras</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.barcode" class="text-red-500 block mt-1">
                                {{ productForm.errors.barcode }}
                            </small>
                        </div>

                        <!-- SLUG -->
                        <div>
                            <FloatLabel variant="on">
                                <InputText
                                    id="slug"
                                    v-model="productForm.slug"
                                    class="w-full"
                                    placeholder="Ej. coca-cola-600ml"
                                />
                                <label for="slug">Slug</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.slug" class="text-red-500 block mt-1">
                                {{ productForm.errors.slug }}
                            </small>
                        </div>

                        <!-- CATEGORIA -->
                        <div>
                            <FloatLabel variant="on">
                                <Select
                                    id="category"
                                    v-model="productForm.category_id"
                                    :options="props.categories"
                                    optionLabel="name"
                                    optionValue="id"
                                    filter
                                    fluid
                                >
                                    <template #option="slotProps">
                                        <div class="flex items-center gap-2">
                                            <i class="pi pi-tag"></i>
                                            <span>{{ slotProps.option.name }}</span>
                                        </div>
                                    </template>
                                </Select>

                                <label for="category">Categoría</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.category_id" class="text-red-500 block mt-1">
                                {{ productForm.errors.category_id }}
                            </small>
                        </div>

                        <!-- IMPUESTO -->
                        <div>
                            <FloatLabel variant="on">
                                <Select
                                    id="tax"
                                    v-model="productForm.tax_id"
                                    :options="props.taxes"
                                    optionLabel="name"
                                    optionValue="id"
                                    filter
                                    fluid
                                >
                                    <template #option="slotProps">
                                        <div class="flex justify-between items-center w-full">
                                            <span>{{ slotProps.option.name }}</span>
                                            <span class="text-sm text-slate-500">
                                                {{ slotProps.option.rate }}%
                                            </span>
                                        </div>
                                    </template>
                                </Select>

                                <label for="tax">Impuesto</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.tax_id" class="text-red-500 block mt-1">
                                {{ productForm.errors.tax_id }}
                            </small>
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
                                <label for="cost">Costo</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.cost" class="text-red-500 block mt-1">
                                {{ productForm.errors.cost }}
                            </small>
                        </div>

                        <!-- PRECIO -->
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
                                <label for="price">Precio</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.price" class="text-red-500 block mt-1">
                                {{ productForm.errors.price }}
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
                                <label for="wholesale">Precio mayoreo</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.wholesale_price" class="text-red-500 block mt-1">
                                {{ productForm.errors.wholesale_price }}
                            </small>
                        </div>

                        <!-- STOCK ACTUAL (DESHABILITADO) -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="stock"
                                    v-model="productForm.stock"
                                    class="w-full"
                                    disabled
                                />
                                <label for="stock">Stock Actual</label>
                            </FloatLabel>
                        </div>

                        <!-- AGREGAR STOCK (AJUSTE) -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="add_stock"
                                    v-model="productForm.add_stock"
                                    class="w-full"
                                    :min="0"
                                    showButtons
                                />
                                <label for="add_stock">Ingresar Cantidad (Ajuste)</label>
                            </FloatLabel>
                            <small class="text-slate-400 block mt-1 text-xs">
                                Ingrese una cantidad para sumar al stock actual.
                            </small>
                            <small v-if="productForm.errors.add_stock" class="text-red-500 block mt-1">
                                {{ productForm.errors.add_stock }}
                            </small>
                        </div>

                        <!-- STOCK ALERTA -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="stock_alert"
                                    v-model="productForm.stock_alert"
                                    class="w-full"
                                    :min="1"
                                    :useGrouping="false"
                                />
                                <label for="stock_alert">Stock alerta</label>
                            </FloatLabel>
                            <small v-if="productForm.errors.stock_alert" class="text-red-500 block mt-1">
                                {{ productForm.errors.stock_alert }}
                            </small>
                        </div>

                        <!-- IMAGEN -->
                        <div>
                            <FloatLabel variant="on">
                                <InputText
                                    id="image"
                                    v-model="productForm.image"
                                    class="w-full"
                                />
                                <label for="image">URL Imagen</label>
                            </FloatLabel>

                            <small v-if="productForm.errors.image" class="text-red-500 block mt-1">
                                {{ productForm.errors.image }}
                            </small>
                        </div>

                    </div>

                    <!-- DESCRIPCIÓN -->
                    <div class="mt-10">
                        <FloatLabel variant="on">
                            <Textarea
                                id="description"
                                v-model="productForm.description"
                                rows="5"
                                class="w-full"
                            />
                            <label for="description">Descripción</label>
                        </FloatLabel>

                        <small v-if="productForm.errors.description" class="text-red-500 block mt-1">
                            {{ productForm.errors.description }}
                        </small>
                    </div>

                    <!-- CHECKBOXES -->
                    <div class="mt-10 flex flex-wrap gap-8">
                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.track_stock"
                                :binary="true"
                                inputId="track_stock"
                            />
                            <label for="track_stock">Controlar inventario</label>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.has_variants"
                                :binary="true"
                                inputId="has_variants"
                            />
                            <label for="has_variants">Tiene variantes</label>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.status"
                                :binary="true"
                                inputId="status"
                            />
                            <label for="status">Activo</label>
                        </div>
                    </div>

                    <!-- BOTONES -->
                    <div class="mt-12 flex justify-end gap-4">
                        <Button
                            type="button"
                            label="Cancelar"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            outlined
                            @click="goToIndex"
                            class="rounded-xl"
                        />

                        <Button
                            type="submit"
                            label="Guardar cambios"
                            icon="pi pi-save"
                            :loading="productForm.processing"
                            :disabled="productForm.processing"
                            class="!bg-emerald-700 hover:!bg-emerald-800 !border-none"
                        />
                    </div>
                </form>
            </div>

        </div>

    </AppLayout>
</template>
