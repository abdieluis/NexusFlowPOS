<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseDataTable from "@/Components/tables/BaseDataTable.vue";
import { ref, computed } from 'vue';
import Button from 'primevue/button';
import { router } from '@inertiajs/vue3'

const props = defineProps({
    products: Array
});

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
</script>

<template>

    <AppLayout>

        <div class="flex min-h-screen">

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

                    <Button
                        label="Agregar nuevo producto"
                        icon="pi pi-plus-circle"
                        class="!bg-emerald-700 hover:!bg-emerald-800 !border-none"
                        @click="openNewProduct"
                    />

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
                        class="
                            bg-white
                            dark:bg-slate-950

                            border
                            border-slate-200
                            dark:border-slate-800

                            rounded-2xl
                            overflow-hidden

                            shadow-sm
                            hover:shadow-md

                            transition-all
                        "
                    >

                        <div class="relative aspect-[4/3]">

                            <img
                                :src="product.image || '/images/no-image.png'"
                                :alt="product.name"
                                class="w-full h-full object-cover"
                            />

                            <span
                                class="
                                    absolute
                                    top-3
                                    right-3

                                    bg-white/90
                                    dark:bg-slate-900/90

                                    backdrop-blur-sm

                                    text-slate-800
                                    dark:text-slate-100

                                    text-xs
                                    font-bold

                                    px-2.5
                                    py-1

                                    rounded-md
                                "
                            >
                                ${{ Number(product.price).toFixed(2) }}
                            </span>

                        </div>

                        <div class="p-4">

                            <span
                                class="
                                    text-xs
                                    font-semibold
                                    uppercase

                                    text-slate-400
                                    dark:text-slate-500
                                "
                            >
                                {{ product.category?.name }}
                            </span>

                            <h3
                                class="
                                    font-bold
                                    mt-1

                                    text-slate-800
                                    dark:text-slate-100
                                "
                            >
                                {{ product.name }}
                            </h3>

                        </div>

                    </div>

                </div>

            </main>

        </div>

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
