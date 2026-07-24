<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select'; // O Dropdown según tu versión de PrimeVue (v4/v3)

const props = defineProps({
    registers: Array,
});

const page = usePage();

// Dialogs state
const showOpenModal = ref(false);
const showMovementModal = ref(false);
const showCloseModal = ref(false);
const selectedRegister = ref(null);

// Opciones tipo de movimiento
const movementOptions = [
    { label: 'Ingreso (Entrada extra)', value: 'income' },
    { label: 'Egreso (Retiro / Salida)', value: 'expense' }
];

// Form para crear caja
const createRegisterForm = useForm({});

// Forms de operación
const openForm = useForm({
    opening_amount: 0,
});

const movementForm = useForm({
    type: 'expense',
    amount: 0,
    description: '',
});

// MODIFICADO: Agregamos email_to_send y notes al form
const closeForm = useForm({
    real_cash: 0,
    notes: '',
    email_to_send: '',
});

// Action: Crear Nueva Caja
const handleCreateRegister = () => {
    createRegisterForm.post('/cash-registers', {
        preserveScroll: true,
    });
};

// Actions de turno
const prepareOpen = (register) => {
    selectedRegister.value = register;
    openForm.reset();
    showOpenModal.value = true;
};

const handleOpenShift = () => {
    openForm.post(`/cash-registers/${selectedRegister.value.id}/open`, {
        onSuccess: () => {
            showOpenModal.value = false;
        }
    });
};

const prepareMovement = (register) => {
    selectedRegister.value = register;
    movementForm.reset();
    showMovementModal.value = true;
};

const handleStoreMovement = () => {
    movementForm.post(`/cash-registers/${selectedRegister.value.id}/movements`, {
        onSuccess: () => {
            showMovementModal.value = false;
        }
    });
};

// MODIFICADO: Pre-llenamos el correo con el del usuario autenticado si existe
const prepareClose = (register) => {
    selectedRegister.value = register;
    closeForm.reset();
    closeForm.email_to_send = page.props.auth?.user?.email || '';
    showCloseModal.value = true;
};

const handleCloseShift = () => {
    closeForm.post(`/cash-registers/${selectedRegister.value.id}/close`, {
        onSuccess: () => {
            showCloseModal.value = false;
        }
    });
};
</script>

<template>
    <AppLayout>
        <div class="p-6 space-y-6">
            <!-- Header con título y botón de creación -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Caja y Turnos</h1>
                    <p class="text-xs text-slate-500">Gestión de terminales y cortes de caja</p>
                </div>

                <Button
                    label="Nueva Caja"
                    icon="pi pi-plus"
                    :loading="createRegisterForm.processing"
                    @click="handleCreateRegister"
                    class="p-button-primary"
                />
            </div>

            <!-- Si NO hay cajas creadas -->
            <div
                v-if="!registers || registers.length === 0"
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center space-y-4"
            >
                <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto text-slate-400">
                    <i class="pi pi-wallet text-xl"></i>
                </div>
                <div class="space-y-1">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-white">No hay cajas registradas</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                        Aún no existe ninguna terminal configurada para esta sucursal.
                    </p>
                </div>
                <Button
                    label="Crear Primera Caja"
                    icon="pi pi-plus"
                    :loading="createRegisterForm.processing"
                    @click="handleCreateRegister"
                />
            </div>

            <!-- Listado de Tarjetas de Cajas -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="register in registers"
                    :key="register.id"
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 space-y-4 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-lg text-slate-800 dark:text-white">
                            Caja / Terminal #{{ register.id }}
                        </span>
                        <span
                            :class="[
                                'px-3 py-1 text-xs font-semibold rounded-full',
                                register.status === 'open'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400'
                            ]"
                        >
                            {{ register.status === 'open' ? 'Abierta' : 'Cerrada' }}
                        </span>
                    </div>

                    <!-- Si la caja está ABIERTA -->
                    <div v-if="register.status === 'open'" class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div class="text-sm text-slate-500">
                            Atendido por: <strong class="text-slate-700 dark:text-slate-200">{{ register.opened_by?.name || 'Usuario activo' }}</strong>
                        </div>
                        <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl">
                            <span class="text-xs text-slate-500">Fondo Inicial:</span>
                            <span class="font-bold text-slate-800 dark:text-white">${{ register.opening_amount || '0.00' }}</span>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <Button
                                label="Movimiento"
                                icon="pi pi-dollar"
                                class="p-button-secondary w-full"
                                @click="prepareMovement(register)"
                            />
                            <Button
                                label="Cerrar Caja"
                                icon="pi pi-lock"
                                class="p-button-danger w-full"
                                @click="prepareClose(register)"
                            />
                        </div>
                    </div>

                    <!-- Si la caja está CERRADA -->
                    <div v-else class="space-y-3 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <div v-if="register.closing_amount" class="flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 p-3 rounded-xl">
                            <span class="text-xs text-slate-500">Último Cierre:</span>
                            <span class="font-bold text-slate-800 dark:text-white">${{ register.closing_amount }}</span>
                        </div>
                        <Button
                            label="Abrir Turno"
                            icon="pi pi-key"
                            class="p-button-success w-full"
                            @click="prepareOpen(register)"
                        />
                    </div>
                </div>
            </div>

            <!-- DIALOG: ABRIR CAJA -->
            <Dialog
                v-model:visible="showOpenModal"
                :header="`Abrir Turno de Caja #${selectedRegister?.id}`"
                :modal="true"
                class="p-fluid w-full max-w-md"
            >
                <form @submit.prevent="handleOpenShift" class="space-y-4 pt-2">
                    <div class="flex flex-col gap-2">
                        <label for="opening_amount" class="text-sm font-medium">Monto de Apertura (Fondo Inicial)</label>
                        <InputNumber
                            id="opening_amount"
                            v-model="openForm.opening_amount"
                            mode="currency"
                            currency="MXN"
                            locale="es-MX"
                            :minFractionDigits="2"
                            class="w-full"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button label="Cancelar" icon="pi pi-times" text @click="showOpenModal = false" />
                        <Button label="Iniciar Turno" icon="pi pi-check" type="submit" severity="success" :loading="openForm.processing" />
                    </div>
                </form>
            </Dialog>

            <!-- DIALOG: MOVIMIENTO -->
            <Dialog
                v-model:visible="showMovementModal"
                header="Ingreso / Retiro de Efectivo"
                :modal="true"
                class="p-fluid w-full max-w-md"
            >
                <form @submit.prevent="handleStoreMovement" class="space-y-4 pt-2">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Tipo de Movimiento</label>
                        <Select
                            v-model="movementForm.type"
                            :options="movementOptions"
                            optionLabel="label"
                            optionValue="value"
                            class="w-full"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Monto</label>
                        <InputNumber
                            v-model="movementForm.amount"
                            mode="currency"
                            currency="MXN"
                            locale="es-MX"
                            :minFractionDigits="2"
                            class="w-full"
                        />
                    </div>

                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Concepto / Motivo</label>
                        <InputText
                            v-model="movementForm.description"
                            placeholder="Ej. Depósito a banco"
                            required
                            class="w-full"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button label="Cancelar" icon="pi pi-times" text @click="showMovementModal = false" />
                        <Button label="Guardar" icon="pi pi-save" type="submit" severity="success" :loading="movementForm.processing" />
                    </div>
                </form>
            </Dialog>

            <!-- DIALOG: CERRAR CAJA -->
            <Dialog
                v-model:visible="showCloseModal"
                header="Cierre de Caja (Corte Z)"
                :modal="true"
                class="p-fluid w-full max-w-md"
            >
                <form @submit.prevent="handleCloseShift" class="space-y-4 pt-2">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Efectivo Real en Caja (Contado)</label>
                        <InputNumber
                            v-model="closeForm.real_cash"
                            mode="currency"
                            currency="MXN"
                            locale="es-MX"
                            :minFractionDigits="2"
                            class="w-full"
                        />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Enviar reporte de corte a:</label>
                        <InputText
                            v-model="closeForm.email_to_send"
                            type="email"
                            placeholder="ejemplo@correo.com (Opcional)"
                            class="w-full"
                        />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Notas / Observaciones</label>
                        <InputText
                            v-model="closeForm.notes"
                            placeholder="Ej. Faltaron $50 por diferencia en cambio"
                            class="w-full"
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <Button label="Cancelar" icon="pi pi-times" text @click="showCloseModal = false" />
                        <Button label="Realizar Corte y Cerrar" icon="pi pi-lock" type="submit" severity="danger" :loading="closeForm.processing" />
                    </div>
                </form>

            </Dialog>
        </div>
    </AppLayout>
</template>
