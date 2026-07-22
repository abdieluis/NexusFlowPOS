<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseDataTable from "@/Components/tables/BaseDataTable.vue";
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    products: Array
});

const toast = useToast();

const file = ref(null)
const loading = ref(false)
const progress = ref(0)
const preview = ref([])
const errors = ref([])
const showImport = ref(false)

const activeCategory = ref('Todos');

const openNewProduct = () => {
    router.visit(route('product.create'))
}

const categories = computed(() => {

    const uniqueCategories = [
        ...new Set(
            props.products
                .map(p => p.category?.name)
                .filter(Boolean)
        )
    ];

    return [
        {
            name: 'Todos',
            icon: '🛒',
            count: props.products.length
        },

        ...uniqueCategories.map(category => ({
            name: category,
            icon: '📦',
            count: props.products.filter(
                p => p.category?.name === category
            ).length
        }))
    ];
});

const filteredProducts = computed(() => {

    if (activeCategory.value === 'Todos') {
        return props.products;
    }

    return props.products.filter(
        p => p.category?.name === activeCategory.value
    );
});

const editProduct = (product) => {
    router.visit(route('products.edit', product.id))
}

const deleteProduct = (id) => {
    if (confirm('¿Eliminar este producto?')) {
        router.delete(route('products.destroy', id))
    }
}

const resetImport = () => {
    file.value = null
    progress.value = 0
    errors.value = []
    loading.value = false
}

const handleFile = (e) => {
    file.value = e.target.files[0]
}

const submitImport = async () => {
    if (!file.value) {
        toast.add({
            severity: 'warn',
            summary: 'Atención',
            detail: 'Por favor, selecciona un archivo antes de importar.',
            life: 4000
        });
        return;
    }

    loading.value = true
    progress.value = 0
    errors.value = []

    const formData = new FormData()
    formData.append('file', file.value)

    const interval = setInterval(() => {
        if (progress.value < 90) progress.value += 10
    }, 200)

    try {
        const { data } = await axios.post(route('products.import'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })

        clearInterval(interval)

        if (data.success) {
            progress.value = 100

            // Lanzar Toast de Éxito
            toast.add({
                severity: 'success',
                summary: '¡Éxito!',
                detail: data.message || 'Productos importados correctamente.',
                life: 4000
            });

            // Recargar la tabla con los nuevos datos usando Inertia de manera limpia
            router.reload({ only: ['products'] });

            setTimeout(() => {
                showImport.value = false
                resetImport()
            }, 600)

        } else {
            // El backend respondió pero hubo errores controlados (ej: duplicados)
            progress.value = 0
            errors.value = data.errors || []

            toast.add({
                severity: 'error',
                summary: 'Error en filas',
                detail: 'El archivo contiene errores que debes corregir.',
                life: 5000
            });
        }

    } catch (error) {
        clearInterval(interval)
        console.error(error)

        // Capturar errores del servidor (ej: 422, 500)
        const serverErrors = error.response?.data?.errors;
        if (serverErrors && Array.isArray(serverErrors)) {
            errors.value = serverErrors;
        } else {
            errors.value = ['Error de conexión o formato de archivo inválido.'];
        }

        toast.add({
            severity: 'error',
            summary: 'Error Crítico',
            detail: 'No se pudo procesar la importación.',
            life: 5000
        });
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <AppLayout>

        <div class="flex min-h-screen">
            <Toast />

            <!-- SIDEBAR INTERNO DE CATEGORÍAS -->

            <aside
                class="
                    w-64
                    bg-white
                    dark:bg-slate-950

                    border-r
                    border-slate-200
                    dark:border-slate-800

                    p-6
                    flex
                    flex-col
                    justify-between

                    hidden
                    md:flex
                "
            >

                <div>

                    <div class="flex items-center justify-between mb-6">

                        <h2
                            class="
                                text-xl
                                font-bold

                                text-slate-900
                                dark:text-slate-100
                            "
                        >
                            Categorias
                        </h2>

                        <i
                            class="
                                pi pi-sliders-h

                                text-slate-500
                                dark:text-slate-400

                                cursor-pointer
                            "
                        ></i>

                    </div>

                    <ul class="space-y-1">

                        <li
                            v-for="category in categories"
                            :key="category.name"
                        >

                            <button
                                @click="activeCategory = category.name"
                                :class="[

                                    `
                                        w-full
                                        flex
                                        items-center
                                        justify-between

                                        px-4
                                        py-2.5

                                        rounded-lg
                                        text-sm
                                        font-medium
                                        transition-colors
                                    `,

                                    activeCategory === category.name

                                        ? `
                                            bg-emerald-100
                                            dark:bg-emerald-900/40

                                            text-emerald-700
                                            dark:text-emerald-300
                                        `

                                        : `
                                            text-slate-600
                                            dark:text-slate-300

                                            hover:bg-slate-100
                                            dark:hover:bg-slate-900
                                        `
                                ]"
                            >

                                <div class="flex items-center gap-2">
                                    <span class="text-base">
                                        {{ category.icon }}
                                    </span>
                                    <span>
                                        {{ category.name }}
                                    </span>
                                </div>

                                <span
                                    :class="[

                                        `
                                            text-xs
                                            font-semibold

                                            px-2
                                            py-0.5
                                            rounded-full
                                        `,

                                        activeCategory === category.name

                                            ? `
                                                bg-emerald-200
                                                dark:bg-emerald-800

                                                text-emerald-800
                                                dark:text-emerald-100
                                            `

                                            : `
                                                bg-slate-100
                                                dark:bg-slate-800

                                                text-slate-600
                                                dark:text-slate-300
                                            `
                                    ]"
                                >

                                    {{ category.count }}

                                </span>

                            </button>

                        </li>

                    </ul>

                </div>

            </aside>


            <!-- CONTENIDO -->

            <main class="flex-1 p-8">

                <header
                    class="
                        flex
                        flex-col
                        sm:flex-row
                        sm:items-center
                        sm:justify-between
                        gap-4
                        mb-8
                    "
                >
                    <div>
                        <h1
                            class="
                                text-2xl
                                font-bold
                                text-slate-900
                                dark:text-slate-100
                            "
                        >
                            Catalogo de Productos
                        </h1>

                        <p
                            class="
                                text-sm
                                text-slate-500
                                dark:text-slate-400
                                mt-1
                            "
                        >
                            Gestiona y organiza tu inventario.
                        </p>
                    </div>

                    <!-- Grupo de botones -->
                    <div class="flex items-center gap-2">
                        <Button
                            label="Importar Productos"
                            icon="pi pi-file-excel"
                            severity="help"
                            variant="text"
                            @click="showImport = true"
                            raised
                        />

                        <Button
                            label="Agregar nuevo producto"
                            icon="pi pi-plus-circle"
                            severity="info"
                            variant="text"
                            @click="openNewProduct"
                            raised
                        />
                    </div>
                </header>


                <!-- GRID PRODUCTOS -->

                <div
                    class="
                        grid
                        grid-cols-1
                        sm:grid-cols-2
                        md:grid-cols-3
                        lg:grid-cols-4
                        gap-6
                    "
                >

                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="group relative bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all"
                    >

                        <div class="relative aspect-[4/3]">

                            <img
                                :src="product.image || '/images/no-image.png'"
                                :alt="product.name"
                                class="w-full h-full object-cover"
                            />

                            <span
                                class="absolute top-3 right-3 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm text-slate-800 dark:text-slate-100 text-xs font-bold px-2.5 py-1 rounded-md"
                            >
                                ${{ Number(product.price).toFixed(2) }}
                            </span>

                            <!-- BOTONES OVERLAY -->
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-end justify-end p-3 opacity-0 group-hover:opacity-100">

                                <div class="flex gap-2">

                                    <Button
                                        icon="pi pi-pencil"
                                        class="p-button-sm p-button-rounded p-button-warning"
                                        @click.stop="editProduct(product)"
                                    />

                                    <Button
                                        icon="pi pi-trash"
                                        class="p-button-sm p-button-rounded p-button-danger"
                                        @click.stop="deleteProduct(product.id)"
                                    />

                                </div>

                            </div>

                        </div>

                        <div class="p-4">

                            <span class="text-xs font-semibold uppercase text-slate-400 dark:text-slate-500">
                                {{ product.category?.name }}
                            </span>

                            <h3 class="font-bold mt-1 text-slate-800 dark:text-slate-100">
                                {{ product.name }}
                            </h3>

                        </div>

                    </div>

                </div>

            </main>

        </div>
        <Dialog v-model:visible="showImport" modal header="Importar productos" :style="{ width: '420px' }" @hide="resetImport">
            <div class="space-y-4 relative">

                <div v-if="loading" class="absolute inset-0 bg-white/70 dark:bg-slate-950/70 z-10 flex flex-col items-center justify-center gap-2 rounded">
                    <i class="pi pi-spin pi-spinner text-3xl text-blue-600"></i>
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Procesando registros...</span>
                </div>

                <div>
                    <label class="text-sm font-medium">Archivo Excel / CSV</label>
                    <input type="file" @change="handleFile" :disabled="loading" accept=".xlsx,.xls,.csv" class="w-full mt-2 border rounded p-2 disabled:opacity-50" />
                </div>

                <div v-if="loading || progress > 0" class="space-y-2">
                    <div class="w-full bg-gray-200 dark:bg-slate-800 h-2 rounded overflow-hidden">
                        <div class="bg-emerald-500 h-2 transition-all duration-300" :style="{ width: progress + '%' }" />
                    </div>
                    <p class="text-xs text-slate-500">Subiendo... {{ progress }}%</p>
                </div>

                <div v-if="errors.length" class="max-h-40 overflow-auto bg-red-50 dark:bg-red-950/20 p-3 rounded border border-red-200 dark:border-red-900/50">
                    <p class="text-sm font-semibold text-red-500 mb-2">Errores encontrados:</p>
                    <ul class="text-xs text-red-600 dark:text-red-400 space-y-1">
                        <li v-for="(e, i) in errors" :key="i">• {{ e }}</li>
                    </ul>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancelar" severity="secondary" :disabled="loading" @click="showImport = false" />
                    <Button label="Importar" icon="pi pi-upload" :loading="loading" @click="submitImport" class="!bg-blue-600 hover:!bg-blue-700 !border-none" />
                </div>
            </div>
        </Dialog>

    </AppLayout>

</template>
<style scoped>
/* Asegura el truncado de texto en navegadores antiguos si no soporta line-clamp nativo de tailwind */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
