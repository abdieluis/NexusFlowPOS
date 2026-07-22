<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import logoBg from '@/assets/images/nexusflow_pos_logo.png';
</script>

<template>
    <AppLayout>
        <div class="relative min-h-screen bg-cover bg-center transition-colors duration-300"
            :style="{ backgroundImage: `url(${logoBg})` }">
            <!-- Overlay dinámico: blanco semi-transparente en modo claro, oscuro en modo dark -->
            <div class="absolute inset-0 bg-white/60 dark:bg-black/60 transition-colors duration-300"></div>

            <!-- Contenido -->
            <div class="relative z-10 flex h-full items-center justify-center text-slate-900 dark:text-white">
                <!-- Tu texto/componentes aquí -->
            </div>
        </div>
    </AppLayout>
</template>
