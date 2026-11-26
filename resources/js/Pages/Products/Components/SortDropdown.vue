<script setup>
import Dropdown from "@/Components/Dropdown.vue";
import { onMounted, ref, computed, defineEmits, watch } from "vue";
const props = defineProps({
    products: Array,
});
const emit = defineEmits(["sortedProducts"]);

const orderBy = ref("recent");
// Computar os produtos ordenados dinamicamente
const sortedProducts = computed(() => {
    // Fazer uma cópia dos produtos para ordenação
    if (!props.products) return [];
    const productsCopy = [...props.products];

    return productsCopy.sort((a, b) => {
        if (orderBy.value === "recent") {
            // Ordenar por mais recente (created_at decrescente)
            return new Date(b.created_at) - new Date(a.created_at);
        } else if (orderBy.value === "higher_price") {
            // Ordenar por maior preço (price decrescente)
            return b.price - a.price;
        } else if (orderBy.value === "lower_price") {
            // Ordenar por menor preço (price crescente)
            return a.price - b.price;
        } else {
            // Sem ordenação ou critério inválido
            return 0;
        }
    });
});

// Função para alterar o critério de ordenação
function setOrderBy(criteria) {
    orderBy.value = criteria;
}
onMounted(() => {
    emit("sortedProducts", sortedProducts.value);
});
// Observa as alterações na const orderBy
watch(orderBy, () => {
    emit("sortedProducts", sortedProducts.value);
});
</script>
<template>
    <Dropdown align="right" width="48">
        <template #trigger>
            <button
                id="sortButton"
                type="button"
                class="m-4 float-end flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
            >
                <svg
                    class="-ms-0.5 me-2 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 4v16M7 4l3 3M7 4 4 7m9-3h6l-6 6h6m-6.5 10 3.5-7 3.5 7M14 18h4"
                    />
                </svg>
                Sort
                <svg
                    class="-me-0.5 ms-2 h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="m19 9-7 7-7-7"
                    />
                </svg>
            </button>
        </template>
        <template #content>
            <ul class="p-2 text-left text-sm font-medium text-gray-500 dark:text-gray-400">
                <li>
                    <button
                        @click="setOrderBy('recent')"
                        class="group inline-flex w-full items-center rounded-md px-3 py-2 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                        Newest
                    </button>
                </li>
                <li>
                    <button
                        @click="setOrderBy('higher_price')"
                        class="group inline-flex w-full items-center rounded-md px-3 py-2 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                        Increasing price
                    </button>
                </li>
                <li>
                    <button
                        @click="setOrderBy('lower_price')"
                        class="group inline-flex w-full items-center rounded-md px-3 py-2 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                        Decreasing price
                    </button>
                </li>
            </ul>
        </template>
    </Dropdown>
</template>
