<script setup>
import { ref, onMounted } from 'vue';
import { usePosStore } from '@/stores/pos';
import SpeedDial from 'primevue/speeddial';
import { router } from '@inertiajs/vue3';

const visible = ref(false);

const items = ref([
    {
        label: 'Cerrar sesión',
        icon: 'pi pi-sign-out',
        command: () => {
            router.post(route('logout'));
        }
    }
]);

const toggle = () => {
    visible.value = !visible.value;
};


const pos = usePosStore();

// 1. Aquí ya están declarados correctamente tus dos eventos
const emit = defineEmits([
    'toggle-sidebar',
    'search'
]);

// const search = ref('');

// const onSearch = () => {
//     emit('search', search.value);
// };

/* =======================================================
  DARK MODE
======================================================= */

const darkMode = ref(false);

onMounted(() => {
    darkMode.value = localStorage.getItem('darkMode') === 'true';
    updateTheme();
});

const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    localStorage.setItem('darkMode', darkMode.value);
    updateTheme();
};

const updateTheme = () => {
    if (darkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};
</script>
<template>

    <header
        class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-6 shrink-0 z-20 transition-colors"
    >

        <!-- ================================================= -->
        <!-- LEFT -->
        <!-- ================================================= -->

        <div class="flex items-center gap-4">

            <!-- TOGGLE SIDEBAR -->

            <button
                @click="$emit('toggle-sidebar')"
                class="
                    w-10
                    h-10
                    flex
                    items-center
                    justify-center
                    rounded-xl
                    hover:bg-slate-100
                    dark:hover:bg-slate-800
                    text-slate-500
                    dark:text-slate-300
                    hover:text-slate-800
                    dark:hover:text-white
                    transition-colors
                "
            >

                <i class="pi pi-bars text-lg"></i>

            </button>


            <!-- LOGO -->

            <div class="flex items-center gap-2">

                <span
                    class="text-xl font-black tracking-tight text-emerald-500"
                >
                    NexusFlow
                </span>

                <span
                    class="text-xl font-black tracking-tight text-slate-700 dark:text-slate-200"
                >
                    POS
                </span>

            </div>

        </div>


        <!-- ================================================= -->
        <!-- SEARCH -->
        <!-- ================================================= -->

        <div class="w-full max-w-md mx-4">

            <div class="relative">

                <i
                    class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                ></i>


                <input
                    v-model="pos.search"
                    type="text"
                    placeholder="Buscar artículo o SKU..."
                    class="w-full bg-slate-100 dark:bg-slate-800 border border-transparent dark:border-slate-700 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:bg-white dark:focus:bg-slate-900 focus:border-emerald-500 transition-colors"
                />

            </div>

        </div>


        <!-- ================================================= -->
        <!-- RIGHT -->
        <!-- ================================================= -->

        <div class="flex items-center gap-3">

            <!-- STORE INFO -->

            <div class="text-right hidden sm:block">

                <p
                    class="text-[10px] font-bold text-amber-500 uppercase tracking-wider"
                >
                    Tienda León Gto.
                </p>

                <p
                    class="text-xs text-slate-500 dark:text-slate-400 font-medium"
                >
                    Usuario:
                </p>

            </div>


            <!-- DARK MODE -->

            <button
                @click="toggleDarkMode"
                class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            >

                <!-- SUN -->

                <i
                    v-if="darkMode"
                    class="pi pi-sun text-yellow-400 text-lg"
                ></i>


                <!-- MOON -->

                <i
                    v-else
                    class="pi pi-moon text-slate-500 dark:text-slate-300 text-lg"
                ></i>

            </button>


            <!-- NOTIFICATIONS -->

            <button
                class="text-slate-400 dark:text-slate-300 hover:text-slate-600 dark:hover:text-white relative p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            >

                <span
                    class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"
                ></span>

                <i class="pi pi-bell text-lg"></i>

            </button>


            <!-- PROFILE -->

            <button
                class="text-slate-400 dark:text-slate-300 hover:text-slate-600 dark:hover:text-white p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            >

                <i class="pi pi-user text-xl"></i>

            </button>

        </div>

    </header>

</template>
