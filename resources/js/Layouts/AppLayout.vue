<script setup>
import { ref } from 'vue';

import AppSideBar from '@/Components/AppSideBar.vue';
import AppTopBar from '@/Components/AppTopBar.vue';

const sidebarCollapsed = ref(false);

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
};

const searchProducts = (value) => {
    searchText.value = value;
};
</script>

<template>

    <div
        class="
            h-screen
            flex
            overflow-hidden

            bg-slate-100
            dark:bg-slate-900

            transition-colors
            duration-300
        "
    >

        <!-- SIDEBAR -->

        <AppSideBar
            :collapsed="sidebarCollapsed"
        />


        <!-- CONTENT -->

        <div
            class="
                flex-1
                flex
                flex-col
                overflow-hidden
            "
        >

            <!-- TOPBAR -->

            <AppTopBar
                @toggle-sidebar="toggleSidebar"
                @search="searchProducts"
            />


            <!-- MAIN -->

            <main
                class="
                    flex-1
                    overflow-y-auto
                    p-6

                    bg-slate-100
                    dark:bg-slate-900

                    transition-colors
                    duration-300
                "
            >
                <slot />
            </main>

        </div>

    </div>

</template>
