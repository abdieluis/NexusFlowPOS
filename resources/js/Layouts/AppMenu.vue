<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    collapsed: Boolean
});

const currentPath = window.location.pathname;

const menuItems = computed(() => [
    {
        label: 'Punto de Venta',
        href: '/pos',
        active: currentPath === '/pos',
        icon: 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.116 60.116 0 0 0-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z'
    },

    {
        label: 'Inventario',
        href: '/pos/create',
        active: currentPath === '/pos/create',
        icon: 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'
    },

    {
        label: 'Analíticas',
        href: '/pos/analytics',
        active: currentPath === '/pos/analytics',
        icon: 'M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6ZM13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z'
    },

    {
        label: 'Catálogo',
        href: '/pos/catalog',
        active: currentPath === '/pos/catalog',
        icon: 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z'
    },

    {
        label: 'Productos',
        href: '/product',
        active: currentPath.startsWith('/product'),
        icon: 'M7.5 4.5v15m9-15v15M3.75 7.5h16.5m-16.5 9h16.5'
    }
]);
</script>

<template>

    <nav class="space-y-2">

        <Link
            v-for="item in menuItems"
            :key="item.label"
            :href="item.href"
            :title="collapsed ? item.label : ''"
            :class="[
                `
                    group
                    relative
                    w-full
                    flex
                    items-center
                    rounded-2xl
                    transition-all
                    duration-200
                    overflow-hidden
                `,

                collapsed
                    ? 'justify-center h-12'
                    : 'gap-3 px-4 py-3',

                item.active
                    ? `
                        bg-emerald-500
                        dark:bg-emerald-600
                        text-white
                        shadow-lg
                        shadow-emerald-500/20
                    `
                    : `
                        text-slate-600
                        dark:text-slate-300
                        hover:bg-slate-100
                        dark:hover:bg-slate-800
                        hover:text-slate-900
                        dark:hover:text-white
                    `
            ]"
        >

            <!-- ACTIVE INDICATOR -->

            <div
                v-if="item.active && !collapsed"
                class="absolute left-0 top-2 bottom-2 w-1 rounded-r-full bg-white"
            ></div>


            <!-- ICON -->

            <div
                :class="[
                    `
                        flex
                        items-center
                        justify-center
                        shrink-0
                        transition-all
                        duration-200
                    `,

                    collapsed
                        ? 'w-12 h-12'
                        : 'w-5 h-5'
                ]"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    :class="[
                        collapsed
                            ? 'w-5 h-5'
                            : 'w-5 h-5'
                    ]"
                >

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        :d="item.icon"
                    />

                </svg>

            </div>


            <!-- LABEL -->

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-x-2"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 -translate-x-2"
            >

                <span
                    v-if="!collapsed"
                    class="whitespace-nowrap font-semibold tracking-tight"
                >
                    {{ item.label }}
                </span>

            </Transition>

        </Link>

    </nav>

</template>
