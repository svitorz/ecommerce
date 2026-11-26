<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SearchInput from "./Components/SearchInput.vue";
import ProductCard from "./Components/ProductCard.vue";
import SortDropdown from "./Components/SortDropdown.vue";
import { ref } from "vue";
import Pagination from "@/Components/Pagination.vue";
const props = defineProps({
    products: Array,
    isAdmin: Boolean,
});
const products = ref([...props.products.data]);
const handleSortedProducts = (value) => {
    products.value = value;
};
</script>

<template>
    <AppLayout title="Product">
        <template #header>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    All Products
                    <PrimaryButton :type="button" class="float-end" v-if="isAdmin">
                        <a href="/products/create"> Create Product </a>
                    </PrimaryButton>
                </h2>
            </div>
            <div class="mt-5">
                <SearchInput />
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-4 flex justify-end px-4 sm:px-0">
                    <SortDropdown :products="products" @sortedProducts="handleSortedProducts" />
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div v-if="products.length > 0">
                        <div
                            class="mb-4 grid gap-4 sm:grid-cols-2 md:mb-8 lg:grid-cols-3 xl:grid-cols-4"
                        >
                            <div
                                v-for="product in products"
                                class="m-2 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                            >
                                <ProductCard :product="product" :isAdmin="isAdmin" />
                            </div>
                        </div>
                        <div class="flex justify-center items-center">
                            <Pagination :links="props.products.links" />
                        </div>
                    </div>
                    <div v-else>
                        <h1 class="text-white text-center">No products found.</h1>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
