<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import ToastManager from "@/Components/ToastManager.vue";
import FavoriteButton from "@/Pages/Favorites/FavoriteButton.vue";

defineProps({ product: { type: Array, required: true } });

const loading = ref(false);
const toastRef = ref(null);

const updateQuantity = async (product, quantity) => {
    loading.value = true;

    try {
        if (product.quantity > product.stock) {
            toastRef.value.showToast("The quantity cannot be higher than stock.", "error");
            loading.value = false;
            return;
        }

        await router.visit(route("cart.update", product), {
            method: "PUT",
            data: { quantity: quantity },
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toastRef.value.showToast("Quantity successfully updated!", "success");
                localProduct.inCart = true;
            },
            onError: () => {
                toastRef.value.showToast("Error adding product to cart.", "error");
            },
            onFinish: () => {
                loading.value = false;
            },
        });
    } catch (error) {
        toastRef.value.showToast("Unexpected error", "error");
        console.log(error);
        loading.value = false;
    }
};

const incrementQuantity = (product) => {
    product.quantity++;
    updateQuantity(product.id, product.quantity);
};

const decrementQuantity = (product) => {
    if (product.quantity > 1) {
        product.quantity--;
        updateQuantity(product.id, product.quantity);
    }
};

const removeFromCart = async (product) => {
    loading.value = true;
    try {
        await router.visit(route("cart.destroy", { product: product }), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toastRef.value.showToast("Product removed from cart!", "info");
                product.inCart = false;
                product.quantity = 1;
            },
            onError: () => {
                toastRef.value.showToast("Error removing product from cart.", "error");
            },
            onFinish: () => {
                loading.value = false;
            },
        });
    } catch (error) {
        toastRef.value.showToast("Unexpected error", "error");
        console.log(error);
        loading.value = false;
    }
};
</script>

<template>
    <div class="space-y-4 md:flex md:items-center md:justify-between md:gap-6 md:space-y-0">
        <a href="#" class="shrink-0 md:order-1">
            <img
                class="h-20 w-20 dark:hidden"
                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front.svg"
                alt="imac image"
            />
            <img
                class="hidden h-20 w-20 dark:block"
                src="https://flowbite.s3.amazonaws.com/blocks/e-commerce/imac-front-dark.svg"
                alt="imac image"
            />
        </a>

        <label for="counter-input" class="sr-only">Choose quantity:</label>
        <div class="flex items-center justify-between md:order-3 md:justify-end">
            <div class="flex items-center">
                <button
                    type="button"
                    @click="decrementQuantity(product)"
                    id="decrement-button"
                    data-input-counter-decrement="counter-input"
                    class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
                >
                    <svg
                        class="h-2.5 w-2.5 text-gray-900 dark:text-white"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 18 2"
                    >
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M1 1h16"
                        />
                    </svg>
                </button>
                <input
                    type="text"
                    id="quantity"
                    data-input-counter
                    class="w-10 shrink-0 border-0 bg-transparent text-center text-sm font-medium text-gray-900 focus:outline-none focus:ring-0 dark:text-white"
                    v-model="product.quantity"
                    required
                />
                <button
                    type="button"
                    @click="incrementQuantity(product)"
                    id="increment-button"
                    data-input-counter-increment="counter-input"
                    class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700"
                >
                    <svg
                        class="h-2.5 w-2.5 text-gray-900 dark:text-white"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 18 18"
                    >
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 1v16M1 9h16"
                        />
                    </svg>
                </button>
            </div>
            <div class="text-end md:order-4 md:w-32">
                <p class="text-base font-bold text-gray-900 dark:text-white">
                    ${{ product.price }}
                </p>
            </div>
        </div>

        <div class="w-full min-w-0 flex-1 space-y-4 md:order-2 md:max-w-md">
            <Link
                :href="route('products.show', product.id)"
                class="text-base font-medium text-gray-900 hover:underline dark:text-white"
            >
                {{ product.name }}
            </Link>

            <div class="flex items-center gap-4">
                <FavoriteButton :product="product" />

                <button
                    @click="removeFromCart(product)"
                    class="inline-flex items-center text-sm font-medium text-red-600 hover:underline dark:text-red-500"
                >
                    <svg
                        class="me-1.5 h-5 w-5"
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
                            d="M6 18 17.94 6M18 18 6.06 6"
                        />
                    </svg>
                    Remove
                </button>
            </div>
        </div>
    </div>
    <ToastManager ref="toastRef" />
</template>
