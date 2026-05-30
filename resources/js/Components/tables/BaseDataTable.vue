<script setup>
import { ref } from "vue";
import { FilterMatchMode } from "@primevue/core/api";

import Toolbar from "primevue/toolbar";
import Button from "primevue/button";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import InputText from "primevue/inputtext";
import Dialog from "primevue/dialog";
import Checkbox from "primevue/checkbox";
import Tag from "primevue/tag";
import Skeleton from "primevue/skeleton";
import Popover from "primevue/popover";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";

const props = defineProps({
    title: {
        type: String,
        default: "Tabla",
    },

    rows: {
        type: Array,
        default: () => [],
    },

    columns: {
        type: Array,
        default: () => [],
    },

    loading: {
        type: Boolean,
        default: false,
    },

    exportFilename: {
        type: String,
        default: "export",
    },
});

const dataTable = ref();

const selectedRows = ref([]);

const filters = ref({
    global: {
        value: null,
        matchMode: FilterMatchMode.CONTAINS,
    },
});

const visibleColumns = ref(
    props.columns.reduce((acc, column) => {
        acc[column.field] = true;
        return acc;
    }, {})
);

const exportDialog = ref(false);

const columnsPopover = ref();

const clearFilters = () => {
    filters.value.global.value = null;
};

const exportCSV = () => {
    exportDialog.value = false;

    dataTable.value.exportCSV();
};

const toggleColumns = (event) => {
    columnsPopover.value.toggle(event);
};
</script>

<template>

    <div
        class="
            rounded-3xl
            overflow-hidden

            border
            border-slate-200
            dark:border-slate-800

            bg-white
            dark:bg-slate-950

            shadow-sm
            transition-colors
        "
    >

        <!-- ================================================= -->
        <!-- TOOLBAR -->
        <!-- ================================================= -->

        <Toolbar
            class="
                border-0
                border-b

                border-slate-200
                dark:border-slate-800

                bg-white
                dark:bg-slate-950

                px-6
                py-4
            "
        >

            <!-- LEFT -->

            <template #start>

                <div class="flex items-center gap-2">

                    <Button
                        label="Exportar"
                        icon="pi pi-upload"
                        severity="secondary"
                        outlined
                        @click="exportDialog = true"
                    />

                </div>

            </template>


            <!-- RIGHT -->

            <template #end>

                <div class="flex items-center gap-2">

                    <!-- SEARCH -->

                    <IconField iconPosition="left">

                        <InputIcon>
                            <i class="pi pi-search" />
                        </InputIcon>

                        <InputText
                            v-model="filters.global.value"
                            placeholder="Buscar..."
                            class="w-72"
                        />

                    </IconField>


                    <!-- CLEAR -->

                    <Button
                        icon="pi pi-filter-slash"
                        rounded
                        outlined
                        severity="secondary"
                        @click="clearFilters"
                    />


                    <!-- COLUMNS -->

                    <Button
                        icon="pi pi-sliders-v"
                        rounded
                        outlined
                        severity="secondary"
                        @click="toggleColumns($event)"
                    />

                </div>

            </template>

        </Toolbar>


        <!-- ================================================= -->
        <!-- TABLE -->
        <!-- ================================================= -->

        <DataTable
            ref="dataTable"
            v-model:selection="selectedRows"
            v-model:filters="filters"
            :value="rows"
            :loading="loading"
            dataKey="id"

            paginator
            :rows="10"

            stripedRows
            removableSort
            scrollable

            scrollHeight="600px"

            :rowsPerPageOptions="[10, 25, 50, 100]"

            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"

            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords}"

            responsiveLayout="scroll"

            filterDisplay="menu"

            :exportFilename="exportFilename"

            :globalFilterFields="columns.map(c => c.field)"

            tableStyle="min-width: 90rem"

            class="text-sm"
        >

            <!-- ================================================= -->
            <!-- HEADER -->
            <!-- ================================================= -->

            <template #header>

                <div
                    class="
                        flex
                        items-center
                        justify-between
                    "
                >

                    <div>

                        <h2
                            class="
                                text-xl
                                font-bold

                                text-slate-800
                                dark:text-slate-100
                            "
                        >
                            {{ title }}
                        </h2>

                        <p
                            class="
                                text-sm
                                mt-1

                                text-slate-500
                                dark:text-slate-400
                            "
                        >
                            Administración de registros
                        </p>

                    </div>

                    <Tag
                        severity="info"
                        :value="`${rows.length} registros`"
                    />

                </div>

            </template>


            <!-- ================================================= -->
            <!-- SELECTION -->
            <!-- ================================================= -->

            <Column
                selectionMode="multiple"
                style="width: 4rem"
                :exportable="false"
            />


            <!-- ================================================= -->
            <!-- DYNAMIC COLUMNS -->
            <!-- ================================================= -->

            <Column
                v-for="column in columns"
                :key="column.field"
                :field="column.field"
                :header="column.header"
                sortable
                :style="{
                    display: visibleColumns[column.field]
                        ? ''
                        : 'none'
                }"
                headerClass="font-bold"
            >

                <template #body="{ data }">

                    <Skeleton
                        v-if="loading"
                        height="1rem"
                    />

                    <span
                        v-else
                        class="
                            text-slate-700
                            dark:text-slate-200
                        "
                    >
                        {{ data[column.field] }}
                    </span>

                </template>

            </Column>


            <!-- ================================================= -->
            <!-- ACTIONS -->
            <!-- ================================================= -->

            <Column
                header="Acciones"
                :exportable="false"
                style="min-width: 12rem"
            >

                <template #body>

                    <div class="flex items-center gap-2">

                        <Button
                            icon="pi pi-eye"
                            rounded
                            outlined
                            severity="contrast"
                        />

                        <Button
                            icon="pi pi-pencil"
                            rounded
                            outlined
                            severity="warn"
                        />

                        <Button
                            icon="pi pi-trash"
                            rounded
                            outlined
                            severity="danger"
                        />

                    </div>

                </template>

            </Column>

        </DataTable>


        <!-- ================================================= -->
        <!-- EXPORT DIALOG -->
        <!-- ================================================= -->

        <Dialog
            v-model:visible="exportDialog"
            header="Exportar columnas"
            :style="{ width: '450px' }"
            modal
        >

            <div class="flex flex-col gap-4">

                <div
                    v-for="column in columns"
                    :key="column.field"
                    class="flex items-center gap-3"
                >

                    <Checkbox
                        v-model="visibleColumns[column.field]"
                        :binary="true"
                        :inputId="column.field"
                    />

                    <label
                        :for="column.field"
                        class="
                            text-slate-700
                            dark:text-slate-200
                        "
                    >
                        {{ column.header }}
                    </label>

                </div>

            </div>

            <template #footer>

                <Button
                    label="Cancelar"
                    severity="secondary"
                    text
                    @click="exportDialog = false"
                />

                <Button
                    label="Exportar"
                    severity="success"
                    @click="exportCSV"
                />

            </template>

        </Dialog>


        <!-- ================================================= -->
        <!-- POPOVER -->
        <!-- ================================================= -->

        <Popover ref="columnsPopover">

            <div class="flex flex-col gap-4 min-w-52">

                <div
                    v-for="column in columns"
                    :key="column.field"
                    class="flex items-center gap-3"
                >

                    <Checkbox
                        v-model="visibleColumns[column.field]"
                        :binary="true"
                        :inputId="column.field"
                    />

                    <label
                        :for="column.field"
                        class="
                            text-slate-700
                            dark:text-slate-200
                        "
                    >
                        {{ column.header }}
                    </label>

                </div>

            </div>

        </Popover>

    </div>

</template>
