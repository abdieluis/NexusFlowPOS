<script setup>
defineProps({
    title: {
        type: String,
        default: "Formulario",
    },

    fields: {
        type: Array,
        default: () => [],
    },

    form: {
        type: Object,
        required: true,
    },

    loading: {
        type: Boolean,
        default: false,
    },

    submitLabel: {
        type: String,
        default: "Guardar",
    },
});

const emit = defineEmits([
    "submit",
]);
</script>

<template>
    <div class="card">

        <!-- ============================================== -->
        <!-- HEADER -->
        <!-- ============================================== -->

        <div class="mb-6">

            <h2 class="text-2xl font-bold text-slate-800">
                {{ title }}
            </h2>

        </div>


        <!-- ============================================== -->
        <!-- FORM -->
        <!-- ============================================== -->

        <form
            class="grid grid-cols-1 md:grid-cols-2 gap-5"
            @submit.prevent="$emit('submit')"
        >

            <!-- CAMPOS -->

            <div
                v-for="field in fields"
                :key="field.name"
                class="flex flex-col gap-2"
                :class="field.full ? 'md:col-span-2' : ''"
            >

                <!-- LABEL -->

                <label
                    :for="field.name"
                    class="font-medium text-sm text-slate-700"
                >
                    {{ field.label }}
                </label>


                <!-- INPUT TEXT -->

                <InputText
                    v-if="field.type === 'text'"
                    v-model="form[field.name]"
                    :id="field.name"
                    :placeholder="field.placeholder"
                />


                <!-- INPUT NUMBER -->

                <InputNumber
                    v-else-if="field.type === 'number'"
                    v-model="form[field.name]"
                    :id="field.name"
                    fluid
                />


                <!-- TEXTAREA -->

                <Textarea
                    v-else-if="field.type === 'textarea'"
                    v-model="form[field.name]"
                    :id="field.name"
                    rows="5"
                />


                <!-- SELECT -->

                <Select
                    v-else-if="field.type === 'select'"
                    v-model="form[field.name]"
                    :options="field.options"
                    optionLabel="label"
                    optionValue="value"
                    :placeholder="field.placeholder"
                />


                <!-- SWITCH -->

                <ToggleSwitch
                    v-else-if="field.type === 'switch'"
                    v-model="form[field.name]"
                />


                <!-- ERROR -->

                <small
                    v-if="form.errors?.[field.name]"
                    class="text-red-500"
                >
                    {{ form.errors[field.name] }}
                </small>

            </div>


            <!-- ============================================== -->
            <!-- FOOTER -->
            <!-- ============================================== -->

            <div class="md:col-span-2 flex justify-end gap-3 mt-4">

                <Button
                    label="Cancelar"
                    severity="secondary"
                    type="button"
                />

                <Button
                    :label="submitLabel"
                    icon="pi pi-save"
                    severity="success"
                    type="submit"
                    :loading="loading"
                />

            </div>

        </form>

    </div>
</template>
