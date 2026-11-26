<script setup>
import { formatPrice } from "@/Utils/formatPrice";
import CartButton from "@/Pages/Cart/Components/CartButton.vue";
import FavoriteButton from "@/Pages/Favorites/FavoriteButton.vue";
import StarRating from "@/Components/StarRating.vue";
import { Link } from "@inertiajs/vue3";
const props = defineProps({
    product: Array,
});
</script>
<template>
    <div class="h-56 w-full">
        <a href="#">
            <img
                class="mx-auto h-full dark:hidden"
                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front.svg"
                alt=""
            />
            <img
                class="mx-auto hidden h-full dark:block"
                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front-dark.svg"
                alt=""
            />
        </a>
    </div>
    <div class="pt-6">
        <div class="mb-4 flex items-center justify-between gap-4">
            <span
                class="me-2 dark:text-white rounded bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300"
            >
                {{ product?.category?.name }}
            </span>

            <div class="flex items-center justify-end gap-1">
                <a
                    :href="route('products.show', product.id)"
                    data-tooltip-target="tooltip-quick-look"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                >
                    <span class="sr-only"> Quick look </span>
                    <svg
                        class="h-5 w-5"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke="currentColor"
                            stroke-width="2"
                            d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"
                        />
                        <path
                            stroke="currentColor"
                            stroke-width="2"
                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                        />
                    </svg>
                </a>
                <div
                    id="tooltip-quick-look"
                    role="tooltip"
                    class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-sm transition-opacity duration-300 dark:bg-gray-700"
                    data-popper-placement="top"
                >
                    Quick look
                    <div class="tooltip-arrow"></div>
                </div>
                <FavoriteButton :product="product" />
            </div>
        </div>

        <Link
            :href="route('products.show', product.id)"
            class="text-lg font-semibold leading-tight text-gray-900 hover:underline dark:text-white"
            >{{ product.name }}</Link
        >

        <div class="mt-2 flex items-center gap-2">
            <template v-if="product.ratings_count > 0">
                <StarRating :rating="Number(product.average_rating)" />
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ product.ratings_count }} reviews
                </span>
            </template>
            <span v-else class="text-sm text-gray-500 dark:text-gray-400">No reviews yet</span>
        </div>

        <div class="mt-4 flex items-center justify-between gap-4">
            <p class="text-2xl font-extrabold leading-tight text-gray-900 dark:text-white">
                {{ formatPrice(product.price) }}
            </p>
            <!--  -->
            <CartButton :product="product" />
        </div>
    </div>
</template>
