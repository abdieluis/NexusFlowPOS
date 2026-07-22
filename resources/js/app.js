import '../css/app.css';
import './bootstrap';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import 'primeicons/primeicons.css';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import { createPinia } from 'pinia';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputNumber from 'primevue/inputnumber';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {

        return createApp({
            render: () => h(App, props)
        })

            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.dark'
                    }
                },
                locale: {
                    startsWith: 'Empieza con',
                    contains: 'Contiene',
                    notContains: 'No contiene',
                    endsWith: 'Termina con',
                    equals: 'Igual a',
                    notEquals: 'No igual a',
                    noFilter: 'Sin filtro',
                    lt: 'Menor que',
                    lte: 'Menor o igual que',
                    gt: 'Mayor que',
                    gte: 'Mayor o igual que',
                    dateIs: 'Fecha es',
                    dateIsNot: 'Fecha no es',
                    dateBefore: 'Fecha antes de',
                    dateAfter: 'Fecha después de',

                    clear: 'Limpiar',
                    apply: 'Aplicar',
                    matchAll: 'Coincidir todos',
                    matchAny: 'Coincidir cualquiera',

                    addRule: 'Agregar regla',
                    removeRule: 'Eliminar regla',

                    accept: 'Sí',
                    reject: 'No',

                    choose: 'Seleccionar',
                    upload: 'Subir',
                    cancel: 'Cancelar',

                    dayNames: [
                        'Domingo',
                        'Lunes',
                        'Martes',
                        'Miércoles',
                        'Jueves',
                        'Viernes',
                        'Sábado'
                    ],

                    dayNamesShort: [
                        'Dom',
                        'Lun',
                        'Mar',
                        'Mié',
                        'Jue',
                        'Vie',
                        'Sáb'
                    ],

                    dayNamesMin: [
                        'D',
                        'L',
                        'M',
                        'X',
                        'J',
                        'V',
                        'S'
                    ],

                    monthNames: [
                        'Enero',
                        'Febrero',
                        'Marzo',
                        'Abril',
                        'Mayo',
                        'Junio',
                        'Julio',
                        'Agosto',
                        'Septiembre',
                        'Octubre',
                        'Noviembre',
                        'Diciembre'
                    ],

                    monthNamesShort: [
                        'Ene',
                        'Feb',
                        'Mar',
                        'Abr',
                        'May',
                        'Jun',
                        'Jul',
                        'Ago',
                        'Sep',
                        'Oct',
                        'Nov',
                        'Dic'
                    ],

                    today: 'Hoy',
                    weekHeader: 'Sem'
                }
            })
            .use(ToastService)
            .use(ConfirmationService)
            .component('DataTable', DataTable)
            .component('Column', Column)
            .component('Tag', Tag)
            .component('Button', Button)
            .component('InputText', InputText)
            .component('IconField', IconField)
            .component('InputIcon', InputIcon)
            .component('InputNumber', InputNumber)
            .mount(el);
    },

    progress: {
        color: '#4B5563',
    },
});
