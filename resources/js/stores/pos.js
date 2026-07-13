import { defineStore } from 'pinia';
import { ref } from 'vue';

export const usePosStore = defineStore('pos', () => {

    const transactions = ref([
        {
            id: 1,
            number: 1,
            cart: []
        }
    ]);

    const currentTransactionId = ref(1);
    const nextTransactionNumber = ref(2);

    const createTransaction = () => {

        const id = Date.now();

        transactions.value.push({
            id,
            number: nextTransactionNumber.value++,
            cart: []
        });

        currentTransactionId.value = id;
    };

    const deleteTransaction = (id) => {

        if (transactions.value.length === 1) {
            return;
        }

        const index = transactions.value.findIndex(
            t => t.id === id
        );

        if (index === -1) {
            return;
        }

        // Si se elimina la pestaña activa,
        // seleccionamos primero la siguiente o la anterior.
        if (currentTransactionId.value === id) {

            const nextTransaction =
                transactions.value[index + 1] ??
                transactions.value[index - 1];

            currentTransactionId.value = nextTransaction.id;
        }

        transactions.value.splice(index, 1);
    };

    return {
        transactions,
        currentTransactionId,
        createTransaction,
        deleteTransaction
    };

});
