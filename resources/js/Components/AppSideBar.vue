<script setup>
import AppMenu from '@/Layouts/AppMenu.vue';
import { router } from '@inertiajs/vue3';

defineProps({
    collapsed: Boolean
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>

    <aside
        :class="[
            `
                border-r
                flex
                flex-col
                justify-between
                p-4
                shrink-0
                h-screen
                sticky
                top-16
                transition-all
                duration-300

                bg-slate-50
                dark:bg-slate-950

                border-slate-200
                dark:border-slate-800
            `,

            collapsed
                ? 'w-20'
                : 'w-60'
        ]"
    >

        <!-- TOP -->

        <div class="space-y-6">

            <!-- LOGO -->

            <div
                :class="[
                    `
                        flex
                        items-center
                        gap-3
                        p-2
                        rounded-xl
                        transition-colors

                        bg-slate-100/80
                        dark:bg-slate-900
                    `,

                    collapsed
                        ? 'justify-center'
                        : ''
                ]"
            >

                <!-- ICON -->

                <div
                    class="
                        w-10
                        h-10
                        bg-emerald-700
                        text-white
                        flex
                        items-center
                        justify-center
                        rounded-xl
                        shadow-sm
                        font-black
                        text-lg
                    "
                >
                    N
                </div>

                <!-- TEXT -->

                <div v-if="!collapsed">

                    <h4
                        class="
                            text-xs
                            font-bold
                            leading-tight

                            text-slate-800
                            dark:text-slate-100
                        "
                    >
                        NexusFlow
                    </h4>

                    <p
                        class="
                            text-[10px]
                            font-medium

                            text-slate-400
                            dark:text-slate-500
                        "
                    >
                        Terminal 01
                    </p>

                </div>

            </div>


            <!-- MENU -->

            <AppMenu :collapsed="collapsed" />

        </div>


        <!-- BOTTOM -->

        <div
            class="
                space-y-4
                pt-4

                border-t
                border-slate-200
                dark:border-slate-800
            "
        >

            <!-- NEW TRANSACTION -->

            <button
                class="
                    w-full
                    flex
                    items-center
                    justify-center
                    gap-2

                    bg-emerald-800
                    hover:bg-emerald-900

                    text-white
                    font-bold

                    py-2
                    px-3

                    rounded-xl
                    text-xs
                    shadow-xs

                    transition-colors
                "
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                    class="w-4 h-4"
                >

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4.5v15m7.5-7.5h-15"
                    />

                </svg>

                <span v-if="!collapsed">
                    Nueva Transacción
                </span>

            </button>


            <!-- LINKS -->

            <div class="space-y-1">

                <!-- SETTINGS -->

                <a
                    href="#"
                    :class="[
                        `
                            flex
                            items-center
                            gap-3
                            px-3
                            py-2
                            text-xs
                            font-semibold
                            rounded-lg
                            transition-colors

                            text-slate-500
                            dark:text-slate-400

                            hover:text-slate-800
                            dark:hover:text-white

                            hover:bg-slate-100
                            dark:hover:bg-slate-900
                        `,

                        collapsed
                            ? 'justify-center'
                            : ''
                    ]"
                >

                    <span>⚙️</span>

                    <span v-if="!collapsed">
                        Configuración
                    </span>

                </a>


                <!-- LOGOUT -->

                <button
                    @click="logout"
                    :class="[
                        `
                            w-full
                            flex
                            items-center
                            gap-3

                            px-3
                            py-2

                            text-xs
                            font-semibold
                            rounded-lg

                            transition-colors

                            text-slate-500
                            dark:text-slate-400

                            hover:text-red-600

                            hover:bg-red-50
                            dark:hover:bg-red-950/40
                        `,

                        collapsed
                            ? 'justify-center'
                            : ''
                    ]"
                >

                    <span>🚪</span>

                    <span v-if="!collapsed">
                        Cerrar Sesión
                    </span>

                </button>

            </div>

        </div>

    </aside>

</template>
