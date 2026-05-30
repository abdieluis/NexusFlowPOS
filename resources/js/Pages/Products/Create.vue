<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Checkbox from 'primevue/checkbox';
import FloatLabel from 'primevue/floatlabel';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Select from 'primevue/select';

const props = defineProps({
    categories: Array,
    taxes: Array
});

const submitted = ref(false);

const productForm = useForm({
    business_id: 1,
    category_id: null,
    tax_id: null,

    sku: '',
    barcode: '',

    name: '',
    slug: '',

    description: '',
    image: '',

    cost: 0,
    price: 0,
    wholesale_price: 0,

    stock_alert: 0,

    track_stock: true,
    has_variants: false,
    status: true
});

const saveProduct = () => {

    productForm.post(route('product.store'), {

        preserveScroll: true,

        onSuccess: () => {

            productForm.reset();

            console.log('Producto guardado');
        },

        onError: (errors) => {
            console.log(errors);
        }
    });
};

const goToIndex = () => {
    router.get(route('products.index'));
};
</script>

<template>
    <AppLayout>

        <div class="flex flex-col min-h-screen p-6">

            <!-- HEADER -->

            <div class="mb-8">

                <h1
                    class="
                        text-3xl
                        font-bold
                        text-slate-800
                        dark:text-slate-100
                    "
                >
                    Crear Producto
                </h1>

                <p
                    class="
                        text-slate-500
                        dark:text-slate-400
                        mt-2
                    "
                >
                    Completa la información del producto.
                </p>

            </div>


            <!-- CARD -->

            <div
                class="
                    bg-white
                    dark:bg-slate-950

                    border
                    border-slate-200
                    dark:border-slate-800

                    rounded-2xl
                    shadow-sm

                    p-8
                "
            >

                <form @submit.prevent="saveProduct">
                    <div
                        class="
                            grid
                            grid-cols-1
                            md:grid-cols-2
                            xl:grid-cols-3
                            gap-x-8
                            gap-y-6
                        "
                    >

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

                            <small
                                v-if="productForm.errors.name"
                                class="text-red-500 block mt-1"
                            >
                                El nombre es obliatorio
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

                            <small
                                v-if="productForm.errors.sku"
                                class="text-red-500 block mt-1"
                            >
                                El SKU es obliatorio
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

                            <small
                                v-if="productForm.errors.barcode"
                                class="text-red-500 block mt-1"
                            >
                                El Código de barras es obliatorio
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

                            <small
                                v-if="productForm.errors.slug"
                                class="text-red-500 block mt-1"
                            >
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

                            <small
                                v-if="productForm.errors.category_id"
                                class="text-red-500 block mt-1"
                            >
                                La Categoría es obliatoria
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

                            <small
                                v-if="productForm.errors.tax_id"
                                class="text-red-500 block mt-1"
                            >
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

                            <small
                                v-if="productForm.errors.cost"
                                class="text-red-500 block mt-1"
                            >
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

                            <small
                                v-if="productForm.errors.price"
                                class="text-red-500 block mt-1"
                            >
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

                            <small
                                v-if="productForm.errors.wholesale_price"
                                class="text-red-500 block mt-1"
                            >
                                {{ productForm.errors.wholesale_price }}
                            </small>
                        </div>

                        <!-- STOCK ALERTA -->
                        <div>
                            <FloatLabel variant="on">
                                <InputNumber
                                    id="stock_alert"
                                    v-model="productForm.stock_alert"
                                    class="w-full"
                                />
                                <label for="stock_alert">Stock alerta</label>
                            </FloatLabel>

                            <small
                                v-if="productForm.errors.stock_alert"
                                class="text-red-500 block mt-1"
                            >
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

                            <small
                                v-if="productForm.errors.image"
                                class="text-red-500 block mt-1"
                            >
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

                        <small
                            v-if="productForm.errors.description"
                            class="text-red-500 block mt-1"
                        >
                            {{ productForm.errors.description }}
                        </small>
                    </div>

                    <!-- CHECKBOXES -->

                    <div
                        class="
                            mt-10
                            flex
                            flex-wrap
                            gap-8
                        "
                    >

                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.track_stock"
                                :binary="true"
                                inputId="track_stock"
                            />
                            <label for="track_stock">
                                Controlar inventario
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.has_variants"
                                :binary="true"
                                inputId="has_variants"
                            />
                            <label for="has_variants">
                                Tiene variantes
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <Checkbox
                                v-model="productForm.status"
                                :binary="true"
                                inputId="status"
                            />
                            <label for="status">
                                Activo
                            </label>
                        </div>

                    </div>


                    <!-- BOTONES -->

                    <div
                        class="
                            mt-12
                            flex
                            justify-end
                            gap-4
                        "
                    >

                        <Button
                            label="Cancelar"
                            icon="pi pi-arrow-left"
                            severity="secondary"
                            outlined
                            @click="goToIndex"
                            class="rounded-xl"
                        />

                        <Button
                            type="submit"
                            label="Guardar producto"
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
