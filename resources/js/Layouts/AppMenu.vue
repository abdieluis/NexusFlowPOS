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
        icon: 'pi pi-shopping-cart'
    },
    {
        label: 'Caja y Turnos',
        href: '/cash-registers',
        active: currentPath.startsWith('/cash-registers') || currentPath.startsWith('/cash-cuts'),
        icon: 'pi pi-wallet'
    },
    {
        label: 'Productos',
        href: '/products',
        active: currentPath.startsWith('/products') || currentPath.startsWith('/categories'),
        icon: 'pi pi-tags'
    },
    {
        label: 'Inventario',
        href: '/inventories',
        active: currentPath.startsWith('/inventories'),
        icon: 'pi pi-box'
    },
    {
        label: 'Compras',
        href: '/purchases',
        active: currentPath.startsWith('/purchases'),
        icon: 'pi pi-truck'
    },
    {
        label: 'Proveedores',
        href: '/suppliers',
        active: currentPath.startsWith('/suppliers'),
        icon: 'pi pi-building'
    },
    {
        label: 'Clientes',
        href: '/customers',
        active: currentPath === '/customers',
        icon: 'pi pi-users'
    },
    {
        label: 'Devoluciones',
        href: '/returns',
        active: currentPath.startsWith('/returns'),
        icon: 'pi pi-refresh'
    },
    {
        label: 'Analíticas',
        href: '/pos/analytics',
        active: currentPath === '/pos/analytics',
        icon: 'pi pi-chart-bar'
    },
    {
        label: 'Usuarios y Permisos',
        href: '/users',
        active: currentPath.startsWith('/users') || currentPath.startsWith('/roles'),
        icon: 'pi pi-user-plus'
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
                <i
                    :class="[
                        item.icon,
                        'text-lg'
                    ]"
                ></i>
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
