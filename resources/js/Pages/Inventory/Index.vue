<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';

const props = defineProps({
    inventories: Array
});

// Modal de Ajuste de Stock
const showModal = ref(false);
const selectedInventory = ref(null);

const form = useForm({
    inventory_id: null,
    type: 'in',
    quantity: 1,
    reason: 'Compra / Recepción'
});

const openAdjustModal = (inventory) => {
    selectedInventory.value = inventory;
    form.inventory_id = inventory.id;
    form.type = 'in';
    form.quantity = 1;
    form.reason = 'Compra / Recepción';
    showModal.value = true;
};

const submitAdjustment = () => {
    form.post(route('inventories.adjust'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        }
    });
};

// Filtros de la Tabla (Manteniendo tus filtros por columna intactos)
const filters = ref({
    global: {
        value: null,
        matchMode: FilterMatchMode.CONTAINS
    },
    'branch.name': {
        value: null,
        matchMode: FilterMatchMode.CONTAINS
    },
    'product.name': {
        value: null,
        matchMode: FilterMatchMode.CONTAINS
    },
    'variant.name': {
        value: null,
        matchMode: FilterMatchMode.CONTAINS
    },
    stock: {
        value: null,
        matchMode: FilterMatchMode.EQUALS
    },
    status: {
        value: null,
        matchMode: FilterMatchMode.EQUALS
    }
});

const clearFilter = () => {
    filters.value.global.value = null;
    filters.value['branch.name'].value = null;
    filters.value['product.name'].value = null;
    filters.value['variant.name'].value = null;
    filters.value.stock.value = null;
    filters.value.status.value = null;
};

const getSeverity = (stock) => {
    return Number(stock) > 0
        ? 'success'
        : 'danger';
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <h1 class="text-2xl font-bold mb-5">
                Inventario
            </h1>
            <!-- <pre>
                {{ props.inventories }}
            </pre> -->
            <div class="card p-6">

                <DataTable
                    v-model:filters="filters"
                    :value="props.inventories"
                    paginator
                    :rows="10"
                    dataKey="id"
                    showGridlines
                    filterDisplay="menu"
                    :globalFilterFields="[
                        'branch.name',
                        'product.name',
                        'variant.name',
                        'stock'
                    ]"
                >

                    <template #header>
                        <div class="flex justify-between">
                            <Button
                                icon="pi pi-filter-slash"
                                label="Limpiar"
                                outlined
                                @click="clearFilter"
                            />

                            <IconField iconPosition="left">
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>

                                <InputText
                                    v-model="filters.global.value"
                                    placeholder="Buscar..."
                                />
                            </IconField>
                        </div>
                    </template>

                    <template #empty>
                        No hay inventarios.
                    </template>

                    <!-- ACCIONES DE AJUSTE -->
                    <Column
                        header="Acciones"
                        style="min-width:10rem"
                    >
                        <template #body="{data}">
                            <Button
                                icon="pi pi-sync"
                                label="Ajustar"
                                severity="info"
                                size="small"
                                outlined
                                @click="openAdjustModal(data)"
                            />
                        </template>
                    </Column>

                    <!-- SUCURSAL -->
                    <Column
                        header="Sucursal"
                        filterField="branch.name"
                        :showFilterMenu="true"
                        style="min-width:12rem"
                    >
                        <template #body="{data}">
                            {{ data.branch?.name }}
                        </template>

                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                placeholder="Buscar sucursal"
                            />
                        </template>
                    </Column>

                    <!-- PRODUCTO -->
                    <Column
                        header="Producto"
                        filterField="product.name"
                        style="min-width:15rem"
                    >
                        <template #body="{data}">
                            {{data.product?.name}}
                        </template>

                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                placeholder="Buscar producto"
                            />
                        </template>
                    </Column>

                    <!-- VARIANTE -->
                    <Column
                        header="Variante"
                        filterField="variant.name"
                        style="min-width:12rem"
                    >
                        <template #body="{data}">
                            {{ data.variant?.name ?? 'Sin variante' }}
                        </template>

                        <template #filter="{ filterModel }">
                            <InputText
                                v-model="filterModel.value"
                                placeholder="Buscar variante"
                            />
                        </template>
                    </Column>

                    <!-- STOCK -->
                    <Column
                        field="stock"
                        header="Stock"
                        filterField="stock"
                        style="min-width:8rem"
                    >
                        <template #body="{data}">
                            <span class="font-bold">
                                {{ Number(data.stock).toLocaleString() }}
                            </span>
                        </template>

                        <template #filter="{ filterModel }">
                            <InputNumber
                                v-model="filterModel.value"
                                placeholder="Stock"
                            />
                        </template>
                    </Column>

                    <!-- ESTADO -->
                    <Column
                        header="Estado"
                        filterField="status"
                        style="min-width:10rem"
                    >
                        <template #body="{data}">
                            <Tag
                                :value="Number(data.stock) > 0 ? 'Disponible' : 'Agotado'"
                                :severity="getSeverity(data.stock)"
                            />
                        </template>

                        <template #filter="{ filterModel }">
                            <Dropdown
                                v-model="filterModel.value"
                                :options="[
                                    'Disponible',
                                    'Agotado'
                                ]"
                                placeholder="Seleccionar"
                                showClear
                            />
                        </template>
                    </Column>

                </DataTable>

            </div>
        </div>

        <!-- MODAL PARA REGISTRAR ENTRADA/SALIDA -->
        <Dialog
            v-model:visible="showModal"
            header="Ajustar Inventario"
            :modal="true"
            class="w-full max-w-md"
        >
            <form @submit.prevent="submitAdjustment" class="flex flex-col gap-4 mt-2">
                <div>
                    <p class="text-sm text-gray-500">Producto:</p>
                    <p class="font-semibold">{{ selectedInventory?.product?.name }}</p>
                    <p class="text-xs text-gray-400">
                        Sucursal: {{ selectedInventory?.branch?.name }} | Stock actual: {{ selectedInventory?.stock }}
                    </p>
                </div>

                <!-- TIPO DE MOVIMIENTO -->
                <div class="flex flex-col gap-2">
                    <label class="font-medium">Tipo de Operación</label>
                    <SelectButton
                        v-model="form.type"
                        :options="[
                            { label: 'Entrada (+)', value: 'in' },
                            { label: 'Salida (-)', value: 'out' }
                        ]"
                        optionLabel="label"
                        optionValue="value"
                    />
                </div>

                <!-- CANTIDAD -->
                <div class="flex flex-col gap-2">
                    <label class="font-medium">Cantidad</label>
                    <InputNumber
                        v-model="form.quantity"
                        :min="1"
                        showButtons
                        class="w-full"
                    />
                    <small v-if="form.errors.quantity" class="text-red-500">
                        {{ form.errors.quantity }}
                    </small>
                </div>

                <!-- MOTIVO -->
                <div class="flex flex-col gap-2">
                    <label class="font-medium">Motivo / Concepto</label>
                    <Dropdown
                        v-model="form.reason"
                        :options="form.type === 'in'
                            ? ['Compra / Recepción', 'Ajuste Positivo', 'Devolución de Cliente', 'Traspaso Entrante']
                            : ['Ajuste Negativo', 'Merma / Daño', 'Pérdida / Robo', 'Devolución a Proveedor']"
                        placeholder="Selecciona un motivo"
                        class="w-full"
                    />
                    <small v-if="form.errors.reason" class="text-red-500">
                        {{ form.errors.reason }}
                    </small>
                </div>

                <!-- BOTONES -->
                <div class="flex justify-end gap-2 mt-4">
                    <Button
                        label="Cancelar"
                        severity="secondary"
                        text
                        @click="showModal = false"
                    />
                    <Button
                        type="submit"
                        label="Guardar"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </Dialog>

    </AppLayout>
</template>
