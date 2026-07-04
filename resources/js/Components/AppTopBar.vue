<script setup>
import { ref, onMounted } from 'vue';

defineEmits([
    'toggle-sidebar'
]);

/* =======================================================
 DARK MODE
======================================================= */

const darkMode = ref(false);

onMounted(() => {

    darkMode.value =
        localStorage.getItem('darkMode') === 'true';

    updateTheme();
});

const toggleDarkMode = () => {

    darkMode.value = !darkMode.value;

    localStorage.setItem(
        'darkMode',
        darkMode.value
    );

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
                class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 dark:text-slate-300 hover:text-slate-800 dark:hover:text-white transition-colors"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="w-5 h-5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3.75 6.75h16.5m-16.5 5.25h16.5m-16.5 5.25h16.5"
                    />

                </svg>

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

                <span
                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        class="w-4 h-4"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 1 5.196 5.196a7.5 7.5 0 0 1 10.607 10.607Z"
                        />
                    </svg>

                </span>

                <input
                    type="text"
                    placeholder="Buscar artículo o SKU..."
                    class="w-full bg-slate-100 dark:bg-slate-800 border border-transparent dark:border-slate-700 rounded-xl pl-9 pr-4 py-2 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:bg-white dark:focus:bg-slate-900 focus:border-emerald-500 transition-colors placeholder-slate-400"
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

                <svg
                    v-if="darkMode"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="w-5 h-5 text-yellow-400"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 3v2.25m0 13.5V21m8.25-9H21M3 12H5.25m12.364 6.364-1.591-1.591M6.227 6.227 4.636 4.636m12.728 0-1.591 1.591M6.227 17.773l-1.591 1.591M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                    />
                </svg>


                <!-- MOON -->

                <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="w-5 h-5 text-slate-500 dark:text-slate-300"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21.752 15.002A9.718 9.718 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 1 0 21.752 15.002Z"
                    />
                </svg>

            </button>


            <!-- NOTIFICATIONS -->

            <button
                class="text-slate-400 dark:text-slate-300 hover:text-slate-600 dark:hover:text-white relative p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            >

                <span
                    class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"
                ></span>

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="w-5 h-5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"
                    />
                </svg>

            </button>


            <!-- PROFILE -->

            <button
                class="text-slate-400 dark:text-slate-300 hover:text-slate-600 dark:hover:text-white p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    class="w-6 h-6"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                    />
                </svg>

            </button>

        </div>

    </header>

</template>
