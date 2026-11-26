<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import ProductCard from "@/Pages/Products/Components/ProductCard.vue";

defineProps({
    categories: Array,
    products: Array,
    isAdmin: Boolean,
});
</script>

<template>
    <AppLayout title="Home">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Welcome to the store
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <section>
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        Shop by category
                    </h3>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="category in categories"
                            :key="category.id"
                            :href="route('categories.products', category.id)"
                            class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm hover:shadow dark:border-gray-700 dark:bg-gray-800"
                        >
                            <span class="font-semibold text-gray-900 dark:text-white">
                                {{ category.name }}
                            </span>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ category.description }}
                            </p>
                        </Link>
                    </div>
                </section>

                <section>
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                        Latest products
                    </h3>
                    <div
                        v-if="products.length > 0"
                        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div
                            v-for="product in products"
                            :key="product.id"
                            class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                        >
                            <ProductCard :product="product" :isAdmin="isAdmin" />
                        </div>
                    </div>
                    <p v-else class="text-gray-500 dark:text-gray-400">No products yet.</p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
