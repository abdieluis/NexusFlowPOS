<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { FilterMatchMode } from '@primevue/core/api';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    customers: {
        type: Array,
        default: () => []
    }
});

// --- ESTADO DEL MODAL Y FORMULARIO ---
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const toast = useToast();

// Modal para confirmar eliminación
const showDeleteModal = ref(false);
const customerToDelete = ref(null);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
    credit_limit: 0
});

const deleteForm = useForm({});

// --- FUNCIONES DEL FORMULARIO ---
const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (customer) => {
    isEditing.value = true;
    editingId.value = customer.id;
    form.clearErrors();

    form.name = customer.name;
    form.phone = customer.phone ?? '';
    form.email = customer.email ?? '';
    form.address = customer.address ?? '';
    form.credit_limit = Number(customer.credit_limit ?? 0);

    showModal.value = true;
};

const saveCustomer = () => {

    if (isEditing.value) {

        form.put(
            route('customers.update', editingId.value),
            {
                onSuccess: () => {

                    showModal.value = false;

                    form.reset();

                    toast.add({
                        severity:'success',
                        summary:'Cliente actualizado',
                        detail:'El cliente se actualizó correctamente.',
                        life:3000
                    });

                },

                onError: () => {

                    toast.add({
                        severity:'error',
                        summary:'Error',
                        detail:'No se pudo actualizar el cliente.',
                        life:3000
                    });

                }
            }
        );


    } else {


        form.post(
            route('customers.store'),
            {

                onSuccess:()=>{

                    showModal.value=false;

                    form.reset();


                    toast.add({
                        severity:'success',
                        summary:'Cliente creado',
                        detail:'El cliente se registró correctamente.',
                        life:3000
                    });


                },


                onError:()=>{


                    toast.add({
                        severity:'error',
                        summary:'Error',
                        detail:'No se pudo crear el cliente.',
                        life:3000
                    });


                }

            }
        );


    }

};

// --- ELIMINAR CLIENTE ---
const confirmDelete = (customer) => {
    customerToDelete.value = customer;
    showDeleteModal.value = true;
};

const deleteCustomer = () => {

    if (!customerToDelete.value) return;


    deleteForm.delete(
        route(
            'customers.destroy',
            customerToDelete.value.id
        ),
        {

            onSuccess:()=>{


                showDeleteModal.value=false;

                toast.add({

                    severity:'success',

                    summary:'Cliente eliminado',

                    detail:'El cliente fue eliminado correctamente.',

                    life:3000

                });


                customerToDelete.value=null;


            },


            onError:()=>{


                toast.add({

                    severity:'error',

                    summary:'Error',

                    detail:'No se pudo eliminar el cliente.',

                    life:3000

                });


            }

        }
    );


};

// --- FILTROS ---
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    phone: { value: null, matchMode: FilterMatchMode.CONTAINS },
    email: { value: null, matchMode: FilterMatchMode.CONTAINS },
    credit_limit: { value: null, matchMode: FilterMatchMode.EQUALS },
    current_balance: { value: null, matchMode: FilterMatchMode.EQUALS }
});

const clearFilter = () => {
    filters.value.global.value = null;
    filters.value.name.value = null;
    filters.value.phone.value = null;
    filters.value.email.value = null;
    filters.value.credit_limit.value = null;
    filters.value.current_balance.value = null;
};

// --- HELPERS ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value ?? 0);
};

const getCreditSeverity = (customer) => {
    if (Number(customer.current_balance) >= Number(customer.credit_limit) && Number(customer.credit_limit) > 0) {
        return 'danger';
    }
    if (Number(customer.current_balance) > 0) {
        return 'warn';
    }
    return 'success';
};
</script>

<template>
    <Head title="Clientes" />

    <AppLayout>
        <Toast />
        <div class="p-6">
            <!-- ENCABEZADO -->
            <div class="flex justify-between items-center mb-5">
                <h1 class="text-2xl font-bold text-slate-800 dark:text-white">
                    Clientes
                </h1>
                <Button
                    label="Nuevo cliente"
                    icon="pi pi-user-plus"
                    severity="success"
                    @click="openCreateModal"
                />
            </div>

            <!-- TABLA DE CLIENTES -->
            <div class="card p-6 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800">
                <DataTable
                    v-model:filters="filters"
                    :value="props.customers"
                    paginator
                    :rows="10"
                    dataKey="id"
                    showGridlines
                    filterDisplay="menu"
                    :globalFilterFields="['name', 'phone', 'email']"
                >
                    <template #header>
                        <div class="flex justify-between items-center">
                            <Button icon="pi pi-filter-slash" label="Limpiar" outlined @click="clearFilter" />
                            <IconField iconPosition="left">
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText v-model="filters.global.value" placeholder="Buscar cliente..." />
                            </IconField>
                        </div>
                    </template>

                    <template #empty>
                        No hay clientes registrados.
                    </template>

                    <!-- ACCIONES -->
                    <Column header="Acciones" style="min-width:8rem">
                        <template #body="{ data }">
                            <Button
                                icon="pi pi-pencil"
                                severity="info"
                                outlined
                                size="small"
                                class="mr-2"
                                @click="openEditModal(data)"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                size="small"
                                @click="confirmDelete(data)"
                            />
                        </template>
                    </Column>

                    <!-- NOMBRE -->
                    <Column field="name" header="Nombre" filterField="name" style="min-width:14rem">
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" placeholder="Buscar nombre" />
                        </template>
                    </Column>

                    <!-- TELEFONO -->
                    <Column field="phone" header="Teléfono" filterField="phone" style="min-width:10rem">
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" placeholder="Buscar teléfono" />
                        </template>
                    </Column>

                    <!-- EMAIL -->
                    <Column field="email" header="Correo" filterField="email" style="min-width:14rem">
                        <template #filter="{ filterModel }">
                            <InputText v-model="filterModel.value" placeholder="Buscar correo" />
                        </template>
                    </Column>

                    <!-- LIMITE CREDITO -->
                    <Column field="credit_limit" header="Límite crédito" filterField="credit_limit" style="min-width:10rem">
                        <template #body="{ data }">
                            {{ formatCurrency(data.credit_limit) }}
                        </template>
                        <template #filter="{ filterModel }">
                            <InputNumber v-model="filterModel.value" mode="currency" currency="USD" />
                        </template>
                    </Column>

                    <!-- SALDO PENDIENTE -->
                    <Column field="current_balance" header="Saldo pendiente" filterField="current_balance" style="min-width:11rem">
                        <template #body="{ data }">
                            <Tag :value="formatCurrency(data.current_balance)" :severity="getCreditSeverity(data)" />
                        </template>
                        <template #filter="{ filterModel }">
                            <InputNumber v-model="filterModel.value" mode="currency" currency="USD" />
                        </template>
                    </Column>

                    <!-- ESTADO -->
                    <Column header="Estado crédito" style="min-width:10rem">
                        <template #body="{ data }">
                            <Tag :value="Number(data.current_balance) >= Number(data.credit_limit) && Number(data.credit_limit) > 0 ? 'Crédito agotado' : 'Disponible'" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- MODAL DE CREAR / EDITAR CLIENTE -->
        <Dialog
            v-model:visible="showModal"
            modal
            :header="isEditing ? 'Editar Cliente' : 'Nuevo Cliente'"
            :style="{ width: '500px' }"
            class="p-fluid"
        >
            <form @submit.prevent="saveCustomer" class="flex flex-col gap-4 pt-2">
                <div class="flex flex-col gap-1">
                    <label for="name" class="font-semibold text-sm">Nombre completo <span class="text-red-500">*</span></label>
                    <InputText id="name" v-model="form.name" placeholder="Ej. Juan Pérez" :invalid="!!form.errors.name" autofocus />
                    <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1">
                        <label for="phone" class="font-semibold text-sm">Teléfono</label>
                        <InputText id="phone" v-model="form.phone" placeholder="Ej. 4431234567" :invalid="!!form.errors.phone" />
                        <small v-if="form.errors.phone" class="text-red-500">{{ form.errors.phone }}</small>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="email" class="font-semibold text-sm">Correo Electrónico</label>
                        <InputText id="email" v-model="form.email" type="email" placeholder="cliente@correo.com" :invalid="!!form.errors.email" />
                        <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="credit_limit" class="font-semibold text-sm">Límite de Crédito</label>
                    <InputNumber
                        id="credit_limit"
                        v-model="form.credit_limit"
                        mode="currency"
                        currency="USD"
                        locale="en-US"
                        :min="0"
                        :invalid="!!form.errors.credit_limit"
                    />
                    <small v-if="form.errors.credit_limit" class="text-red-500">{{ form.errors.credit_limit }}</small>
                </div>

                <div class="flex flex-col gap-1">
                    <label for="address" class="font-semibold text-sm">Dirección</label>
                    <Textarea id="address" v-model="form.address" rows="3" autoResize placeholder="Calle, Número, Colonia..." :invalid="!!form.errors.address" />
                    <small v-if="form.errors.address" class="text-red-500">{{ form.errors.address }}</small>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <Button label="Cancelar" icon="pi pi-times" severity="secondary" text @click="showModal = false" />
                    <Button
                        type="submit"
                        :label="isEditing ? 'Actualizar' : 'Guardar'"
                        icon="pi pi-check"
                        severity="success"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </Dialog>

        <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN -->
        <Dialog
            v-model:visible="showDeleteModal"
            header="Confirmar Eliminación"
            modal
            :style="{ width: '400px' }"
        >
            <div class="flex items-center gap-3 my-2">
                <i class="pi pi-exclamation-triangle text-amber-500 text-3xl" />
                <p class="text-sm">
                    ¿Estás seguro de que deseas eliminar al cliente <strong>{{ customerToDelete?.name }}</strong>? Esta acción no se puede deshacer.
                </p>
            </div>
            <template #footer>
                <div class="flex justify-end gap-2 pt-2">
                    <Button label="Cancelar" severity="secondary" text @click="showDeleteModal = false" />
                    <Button
                        label="Eliminar"
                        icon="pi pi-trash"
                        severity="danger"
                        :loading="deleteForm.processing"
                        @click="deleteCustomer"
                    />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>
