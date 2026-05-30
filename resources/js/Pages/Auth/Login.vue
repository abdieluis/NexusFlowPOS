<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';

// Configuración del formulario con Inertia
const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const passwordFieldType = ref('password');

const togglePasswordVisibility = () => {
    passwordFieldType.value = passwordFieldType.value === 'password' ? 'text' : 'password';
};

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Terminal Login - NexusFlow POS" />

    <div class="min-h-screen flex flex-col md:flex-row font-sans text-gray-800 bg-white">

        <div class="relative w-full md:w-7/12 bg-emerald-900 flex flex-col justify-between p-8 md:p-16 text-white overflow-hidden min-h-[40vh] md:min-h-screen">
            <div
                class="absolute inset-0 bg-cover bg-center opacity-30 mix-blend-overlay"
                style="background-image: url('https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=1000');"
            ></div>
            <div class="absolute inset-0 bg-gradient-to-tr from-emerald-950 via-emerald-800/90 to-transparent"></div>

            <div class="relative z-10 my-auto max-w-xl">
                <div class="flex items-center gap-3 mb-6">
                    <div class="grid grid-cols-2 gap-0.5 w-7 h-7 border-2 border-white p-0.5 rounded">
                        <div class="bg-white"></div>
                        <div class="bg-white"></div>
                        <div class="bg-white"></div>
                        <div class="bg-white"></div>
                    </div>
                    <span class="text-3xl font-bold tracking-tight">NexusFlow POS</span>
                </div>

                <p class="text-emerald-100 text-base md:text-lg leading-relaxed mb-12">
                    Optimizamos la experiencia de compra de comestibles con un sistema inteligente de seguimiento de inventario y un proceso de pago ultrarrápido. Seguro, fiable y diseñado para entornos de alta velocidad.
                </p>

                <div class="flex gap-12 text-sm">
                    <div>
                        <p class="text-emerald-300 font-medium uppercase tracking-wider text-xs mb-1">Versión Actual.</p>
                        <p class="font-mono text-emerald-50">v4.2.0-estable</p>
                    </div>
                    <div>
                        <p class="text-emerald-300 font-medium uppercase tracking-wider text-xs mb-1">Estatus</p>
                        <p class="flex items-center gap-2 text-emerald-50">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-400 inline-block animate-pulse"></span>
                            Conectado
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full md:w-5/12 flex flex-col justify-between p-8 md:p-16 bg-slate-50/50">

            <div></div>

            <div class="w-full max-w-md mx-auto">
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 mb-1">Iniciar Sesión</h2>
                    <p class="text-sm text-gray-500">Introduzca sus credenciales para acceder al sistema de punto de venta.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="username" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">
                            ID de empleado o nombre de usuario
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </span>
                            <input
                                id="username"
                                type="text"
                                v-model="form.username"
                                placeholder="Usuario"
                                class="w-full pl-11 pr-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors text-sm shadow-sm placeholder-gray-400"
                                required
                            />
                        </div>
                        <span v-if="form.errors.username" class="text-xs text-red-600 mt-1 block">{{ form.errors.username }}</span>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Contraseña
                            </label>
                            <a href="#" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 transition-colors">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>
                            <input
                                id="password"
                                :type="passwordFieldType"
                                v-model="form.password"
                                placeholder="••••••••"
                                class="w-full pl-11 pr-11 py-2.5 bg-white border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors text-sm shadow-sm placeholder-gray-400"
                                required
                            />
                            <button
                                type="button"
                                @click="togglePasswordVisibility"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </button>
                        </div>
                        <span v-if="form.errors.password" class="text-xs text-red-600 mt-1 block">{{ form.errors.password }}</span>
                    </div>

                    <div class="flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" v-model="form.remember" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                            <span class="ms-3 text-sm font-medium text-gray-600">Recuerdame</span>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full flex items-center justify-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-medium py-2.5 px-4 rounded-lg shadow transition-colors duration-200 disabled:opacity-50 text-sm"
                    >
                        <span>Iniciar sesión</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </form>

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                    <div class="relative flex justify-center text-xs uppercase"></div>
                </div>

                <button
                    type="button"
                    class="w-full flex items-center justify-center gap-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 font-medium py-2.5 px-4 rounded-lg shadow-sm transition-colors duration-200 text-sm"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-gray-500">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.875 12h.75m0 0h.75m-1.5 0v.75m0-.75v-.75M13.5 13.5h.75v.75h-.75v-.75Zm.75 2.25h.75v.75h-.75v-.75Zm-3 0h.75v.75h-.75v-.75Zm1.5 1.5h.75v.75h-.75v-.75Zm-.75 1.5h.75v.75h-.75v-.75Zm-1.5-3h.75v.75h-.75v-.75Zm3-1.5h.75v.75h-.75v-.75Zm1.5 1.5h.75v.75h-.75v-.75Z" />
                    </svg>
                    <span>Inicio de sesión rápido con insignia</span>
                </button>

                <p class="text-center text-xs text-gray-400 mt-6 max-w-xs mx-auto leading-normal">
                    Protegido mediante el cifrado de punto final NexusFlow Secure™. El acceso no autorizado está estrictamente prohibido.
                </p>
            </div>

            <div class="text-center text-xs text-gray-400 mt-8">
                NexusFlow Retail Solutions © 2024
            </div>
        </div>

    </div>
</template>
